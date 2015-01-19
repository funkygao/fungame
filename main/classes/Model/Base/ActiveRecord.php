<?php

namespace Model\Base;

/**
 * A SINGLE row(record) in a table.
 *
 * automodel will connect to DB and auto generate Model php file for us.
 *
 * Shard column MUST always be the first column!
 *
 * Dirty row management is entirely dependent upon __set(), so NEVER declare
 * class member var that conficts with DB column name!
 *
 * ActiveRecord can have transient attributes: declare a public class member var.
 *
 * TODO _all cache contains only flag, instead of dup record
 * FIXME maybe mtime shouldn't be set by DB, should use opTime also
 * TODO delete then create the same record, bug that it gets deleted
 */
abstract class ActiveRecord
    implements \System\Flushable, \Consts\ColumnConst,
        \Model\Observer\Subject, \Consts\ErrnoConst {

    /**
     * Cache of the all models of all ActiveRecord classes.
     *
     * array[modelClassName][cachePath] = Model instance
     *
     * @var array
     */
    private static $_modelsCache = array();

    private static $_useCache = TRUE;

    /**
     * @var \Model\Observer\Observer[]
     */
    private $_observers = array();

    /**
     * Create will sync db instantly, but then because of validation failure, php may throw
     * exception, then we use this(redolog) to rollback(delete the created models)
     *
     * @var ActiveRecord[]
     */
    private static $_createdModels = array();

    private $_row = array();

    /**
     * @var array {columnName: columnValue}
     */
    private $_dirtyColumns = array();

    // some Model memory only, needn't sync to DB
    // TODO kill this after AR::save/create() is async
    private $_flushEnabled = TRUE;

    private $_flusherRegistered = FALSE;

    /**
     * Flag that determines if a call to save() should issue an insert or an update sql statement
     *
     * @var boolean
     */
    private $_isNewRow;

    private $_deleted = FALSE;

    /*
     * Optimistic Lock Column
     */
    private $_verColumn = NULL;

    /*
     * Used for update where limit (e.g. member_count < member_max)
     */
    private $_whereClause = NULL;

    /**
     * Table columns.
     *
     * Child class MUST override this to specify db columns info, automodel will do that.
     *
     * @var array hash
     */
    public static $columns;

    // specifically leave for child to overwrite
    protected function _init(array $row) { }

    public function attach(\Model\Observer\Observer $observer) {
        $this->_observers[] = $observer;
    }

    public function notify($event, $data = null) {
        foreach ($this->_observers as $observer) {
            $observer->update($event, $data, $this);
        }
    }

    public static final function table() {
        return Table::load(get_called_class());
    }

    protected final function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

    private static function _getCacheKey(Table $table, array $row) {
        $cacheKey = '';
        foreach ($table->pk as $key) {
            if (isset($row[$key])) {
                if (str_contains($row[$key], '.')) {
                    //throw new \InvalidArgumentException("Primary key can't contain '.': {$row[$key]}, row: " . json_encode($row));
                    // FIXME uncomment the exception after Li change game data
                }

                $cacheKey .= $row[$key] . '.';
            } else {
                throw new \InvalidArgumentException('Emtpy pk:' . $key);
            }
        }

        return $cacheKey;
    }

    /**
     * Dedicated for unit test.
     *
     * Never call this method in your non-unit-test code!
     *
     * PHPUnit fears singleton. Without this, PHPUnit will always fail because
     * of singleton problem.
     * Yes, you can use PHPUnit --process-isolation option, but that's terribly SLOW.
     */
    public static final function tearDownForUnitTest() {
        self::$_modelsCache = array();
    }

    /**
     * New a model instance.
     *
     * Model instance can't be new'ed, only through this method is permitted.
     * Just because we need centralized cache management.
     *
     * @param array $row
     * @param bool $fromDb
     * @throws \InvalidArgumentException
     * @return ActiveRecord
     */
    public final static function instance(array $row = array(), $fromDb = FALSE) {
        $table = self::table();
        if ($table->shardColumn && empty($row[$table->shardColumn])) {
            throw new \InvalidArgumentException('Empty hintId in row');
        }

        $modelClassWithNamespace = get_called_class();

        if (!self::$_useCache) {
            return new $modelClassWithNamespace($table, $row, $fromDb);
        }

        $cacheKey = self::_getCacheKey($table, $row);
        $cachePath = $modelClassWithNamespace . '.' . $cacheKey;

        if (NULL === array_deep_get(self::$_modelsCache, $cachePath, NULL)) {
            array_deep_set(self::$_modelsCache, $cachePath,
                new $modelClassWithNamespace($table, $row, $fromDb));
        }

        return array_deep_get(self::$_modelsCache, $cachePath);
    }

    private function __construct(Table $table, array $row, $fromDb) {
        if ($fromDb) {
            $this->_isNewRow = FALSE;
        } else {
            $this->_isNewRow = TRUE;

            // set attributes default values
            foreach ($table->columns as $column) {
                // $column is a Column instance
                $this->_row[$column->name] = $column->default;
                if ($column->type == self::VER) {
                    $this->_verColumn = $column;
                }
            }
        }

        foreach ($row as $col => $value) {
            if (!array_key_exists($col, $table->columns)) {
                // ignore the non-column keys
                continue;
            }

            if ($fromDb) {
                // convert from mysql data type to php data type
                if ($table->columns[$col]->type == self::DATETIME) {
                    $value = ts_mysql2unix($value);
                }

                if ($table->columns[$col]->type == self::JSON) {
                    // if $value is empty str, json_decode will return NULL
                    $value = json_decode($value, TRUE);
                    if (NULL === $value) {
                        $value = array();
                    }
                }

                if ($table->columns[$col]->type == self::VER) {
                    $this->_verColumn = $table->columns[$col];
                }
            } else {
                // we trust data from db, but not that from developer
                $this->_validateColumnValue($table, $col, $value);
            }

            $this->_assignColumnValue($col, $value);
        }

        if ($fromDb) {
            $this->_dirtyColumns = array();
        }

        // _construct is private, child can call _init
        $this->_init($row);
    }

    private function _validateColumnValue(Table $table, $column, $value) {
        if ($table->columns[$column]->type == self::JSON
            && !is_null($value) && !is_array($value)) {
            //throw new \InvalidArgumentException("{$table->name} JSON column[$column] must be array, $value given");
        }
        if ($table->columns[$column]->type == self::DATETIME
            && !is_numeric($value)) {
            //throw new \InvalidArgumentException("{$table->name} Timestamp column[$column] must be int, $value given");
        }
        if ($table->columns[$column]->type == self::UINT
            && $value < 0) {
            //throw new \InvalidArgumentException("{$table->name} int column[$column] must be positive, $value given");
        }
        if ($table->columns[$column]->type == self::VER
            && $value < 0) {
            //throw new \InvalidArgumentException("{$table->name} VER column[$column] must be positive, $value given");
        }
    }

    public final function __clone() {
        $this->_dirtyColumns = array();
        return $this;
    }

    public function equals($that) {
        if (!($that instanceof ActiveRecord)) {
            throw new \InvalidArgumentException("Equals with incompatible record: $that");
        }

        foreach ($this->_row as $name => $value) {
            if ($value !== $that->$name) {
                return FALSE;
            }
        }

        return TRUE;
    }

    public final function isDeleted() {
        return $this->_deleted;
    }

    // run when writing data to inaccessible properties
    public final function __get($column) {
        if ($this->_deleted) {
            $tableName = self::table()->name;
            throw new \InvalidArgumentException("Cannot access deleted record column: $tableName: $column");
        }

        if (!array_key_exists($column, $this->_row)) {
            $tableName = self::table()->name;
            throw new \InvalidArgumentException("Unknown column: $tableName: $column");
        }

        $value = $this->_row[$column];
        $getter = "get_$column";
        if (method_exists($this, $getter)) {
            $value = $this->$getter($value); // requiring $value argument to prevent dead recursion loop
        }
        return $value;
    }

    // utilized for reading data from inaccessible properties
    public final function __set($column, $value) {
        $table = self::table();
        if ($this->_deleted) {
            $tableName = $table->name;
            throw new \InvalidArgumentException("Cannot access deleted record column, $tableName: $column, $value");
        }

        if (in_array($column, $table->pk)) {
            throw new \InvalidArgumentException("Primary key[$column] is readonly");
        }

        if (!array_key_exists($column, $this->_row)) {
            $tableName = $table->name;
            throw new \InvalidArgumentException("Unknown column: $tableName: $column");
        }

        if ($this->_verColumn !== NULL && $column == $this->_verColumn->name) {
            throw new \InvalidArgumentException("Cannot assign ver column: $column");
        }

        if ($table->columns[$column]->delta) {
            throw new \InvalidArgumentException("Cannot assign delta column: $column, use increase()");
        }
        if ($table->columns[$column]->critical) {
            throw new \InvalidArgumentException("Cannot assign critical column: $column, use serialUpdate()");
        }

        $this->_validateColumnValue($table, $column, $value);

        $setter = "set_$column"; // deadloop pitfall
        if (method_exists($this, $setter)) {
            $value = $this->$setter($value);
            if (is_null($value)) {
                $clz = get_called_class();
                throw new \InvalidArgumentException("$clz::$setter forgot to return value");
            }
        }
        return $this->_assignColumnValue($column, $value);
    }

    public final function __isset($name) {
        if ($this->_deleted) {
            throw new \InvalidArgumentException("Cannot access deleted record column");
        }

        return isset($this->_row[$name]);
    }

    /**
     * Function unserialize() uses this magic method.
     */
    public final function __wakeup() {
        // make sure the models Table instance gets initialized when waking up
        self::table();
    }

    /**
     * Let faed convert a json column concurrent update into serial update.
     *
     * Update a single row.
     *
     * rally: {"num":4,"info":{"413":496,"415":497}}
     * alliance help:
     *
     * @param string $columnName
     * @param array $value
     * @return array New value of this blob json column
     * @throws \ExpectedException
     */
    public final function serialUpdate($columnName, array $value) {
        $table = self::table();
        $stickyKey = $table->name . ':' . json_encode($this->pkValues());
        list($shardName, $_, $_) = \Driver\Db\ShardInfo::pool2shard($table->pool);
        $where = array();
        foreach ($table->pk as $col) {
            $where[] = "$col=" . $this->_row[$col];
        }
        $where = join(' AND ', $where);

        // fae will do db update if merge succeeds
        $result = \FaeEngine::client()->my_merge(
            \FaeEngine::ctx(),
            $shardName, $table->name, $this->hintId(), $where,
            $stickyKey, $columnName, json_encode($value));
        if (!$result->ok) {
            throw new \ExpectedException('faed failed during serial update',
                self::ERRNO_SYS_SERIAL_UPDATE);
        }

        $newVal = json_decode($result->newVal, TRUE);
        $this->_row[$columnName] = $newVal;
        $this->_dirtyColumns[$columnName]= $newVal;
        return $newVal;
    }

    /**
     * delta column only use increase, can not use __set.
     *
     * @param string $columnName
     * @param int $delta
     * @param null|string $whereClause
     * @return int new value of this column
     * @throws \InvalidArgumentException
     */
    public final function increase($columnName, $delta, $whereClause = NULL) {
        $table = self::table();
        if ($this->_deleted) {
            $tableName = $table->name;
            throw new \InvalidArgumentException("Cannot access deleted record column, $tableName: $columnName, $delta");
        }

        if (in_array($columnName, $table->pk)) {
            throw new \InvalidArgumentException("Primary key[$columnName] is readonly");
        }

        if (!$table->columns[$columnName]->delta) {
            throw new \InvalidArgumentException("Only delta column permitted: $columnName");
        }

        if (!array_key_exists($columnName, $this->_row)) {
            $tableName = $table->name;
            throw new \InvalidArgumentException("Unknown column: $tableName: $columnName");
        }

        if ($table->columns[$columnName]->type != self::INTEGER
            && $table->columns[$columnName]->type != self::UINT) {
            throw new \InvalidArgumentException("Delta column must be integer type");
        }

        $delta = $table->columns[$columnName]->cast($delta);

        $setter = "inc_$columnName";
        if (method_exists($this, $setter)) {
            $delta = $this->$setter($delta);
            if (is_null($delta)) {
                $clz = get_called_class();
                throw new \InvalidArgumentException("$clz::$setter forgot to return value");
            }
        }

        $this->_row[$columnName] += $delta;
        $this->_dirtyColumns[$columnName] += $delta;

        if ($whereClause) {
            $this->_whereClause = $whereClause;
        }

        if (NULL !== $whereClause) {
            // TODO 这种情况下，就需要立即flush db了，因为可能会失败
        }

        // lazy register to Flusher
        $this->_registerToFlusherOnce();
        return $this->_row[$columnName];
    }

    private function _registerToFlusherOnce() {
        if ($this->_flushEnabled && !$this->_flusherRegistered) {
            $this->_flusherRegistered = TRUE;
            \System\Flusher::register($this);
        }
    }

    public function disableFlush() {
        $this->_flushEnabled = FALSE;
    }

    public static function disableCache() {
        self::$_useCache = FALSE;
    }

    private function _assignColumnValue($column, $value) {
        $table = self::table();
        if (array_key_exists($column, $table->columns)) {
            // db data type casting and validation
            $value = $table->columns[$column]->cast($value);
        }

        if (!isset($this->_row[$column]) || $this->_row[$column] != $value) {
            // only when attribute value change do we set it value and flag dirty
            $this->_row[$column] = $value;
            $this->_dirtyColumns[$column] = $value;

            // lazy register to Flusher
            $this->_registerToFlusherOnce();
        }

        return $value;
    }

    public static final function currentOpTime() {
        return \System\RequestHandler::getInstance()->currentOpTime();
    }

    public final function isDirty() {
        return !empty($this->_dirtyColumns) || $this->_deleted;
    }

    public static final function undoCreates() {
        foreach (self::$_createdModels as $model) {
            if ($model->table()->name == 'UserLookup') {
                // 用户注册失败，那么Application会undoCreates，会把UserLookup删除该记录
                // 但可能造成该用户部分数据有、部分没有，导致其他用户地图浏览到他时抛异常
                continue;
            }

            $model::table()->delete($model->hintId(), $model->pkValues());
        }
    }

    public final function pkValues() {
        $table = self::table();
        $pk_values = array();
        foreach ($table->pk as $name) {
            if (array_key_exists($name, $this->_row)) { // TODO why if?
                $pk_values[$name] = $this->_row[$name];
            }
        }

        return $pk_values;
    }

    /**
     * HintId of current Model.
     *
     * @return int
     */
    public final function hintId() {
        $table = self::table();
        if (!$table->shardColumn) {
            // table is not sharded
            return 0;
        }

        return $this->_row[$table->shardColumn];
    }

    public final static function nextHintId() {
        return (int)\Driver\TicketFactory::instance()
            ->nextId(self::table()->ticket);
    }

    /**
     * Flush the model in memory to DB.
     *
     * Needn't call directly, {\System\Flusher} will automatically call Model::save
     * for each dirty model.
     *
     * @param bool $validate FIXME uneccessary argument
     * @return bool True if succeed
     */
    public final function save($validate = TRUE) {
        if ($this->_deleted) {
            if (self::$_useCache) {
                $this->_clearCache();
            }

            $table = self::table();
            $cacheKey = '';
            if ($table->cacheable) {
                $cacheKey = $table->name
                    . ':'
                    . json_encode(array_values($this->pkValues()));
            }
            $rs = $table->delete($this->hintId(), $this->pkValues(), $cacheKey);
            // for callback
            if (\System\RequestHandler::getInstance()->isAutoCallback()) {
                $this->_addDeleteCallback();
            }
            return $rs->getAffectedRows() == 1;
        }

        return $this->_isNewRow ? $this->_insert($validate)
            : $this->_update($validate);
    }

    private function _addDeleteCallback() {
        if (self::table()->noAutoCallback) {
            return;
        }
        $data = array();
        $tname = self::table()->tname;
        $data['del'][$tname] = $this->pkValues();
        \System\ResponseHandler::getInstance()->appendAutoCallback($data);
    }

    private function _addInsertCallback() {
        if (self::table()->noAutoCallback) {
            return;
        }
        $data = array();
        $tname = self::table()->tname;
        $data['set'][$tname] = $this->_row;
        \System\ResponseHandler::getInstance()->appendAutoCallback($data);
    }

    private function _addUpdateCallback() {
        if (self::table()->noAutoCallback) {
            return;
        }
        $data = array();
        $tname = self::table()->tname;
        $responseHandler = \System\ResponseHandler::getInstance();

        $changedData = array();
        foreach ($this->_dirtyColumns as $column => $_) {
            $changedData[$column] = $this->_row[$column];
        }

        $hasExist = FALSE;
        $autoCallbackData = $responseHandler->getAutoCallback();
        if (isset($autoCallbackData['set'][$tname])) { // 先create再update
            foreach ($autoCallbackData['set'][$tname] as &$row) {
                $diff = array_diff_assoc($this->pkValues(), $row);
                if (empty($diff)) { // 已经存在
                    $row = array_merge($row, $changedData);
                    $hasExist = TRUE;
                }
            }
            $responseHandler->setAutoCallback($autoCallbackData);
        }
        if (!$hasExist) {
            $data['set'][$tname] = array_merge($changedData, $this->pkValues());
            $responseHandler->appendAutoCallback($data);
        }
    }

    private function _clearCache() {
        $cacheKey = self::_getCacheKey(self::table(), $this->_row);
        $modelClassWithNamespace = get_called_class();
        $cachePath = $modelClassWithNamespace . '.' . $cacheKey;
        $cacheModel = array_deep_get(self::$_modelsCache, $cachePath);
        array_deep_del(self::$_modelsCache, $cachePath);

        $className = get_called_class();
        if (!isset(self::$_modelsCache['_all'][$className])) {
            return;
        }
        foreach (self::$_modelsCache['_all'][$className] as $comKey => $models) {
            foreach ($models as $key => $model) {
                if ($model === $cacheModel) {
                    unset(self::$_modelsCache['_all'][$className][$comKey][$key]);
                }
            }
        }
    }

    public function dbCacheKey() {
        $table = self::table();
        $cacheKey = '';
        if ($table->cacheable) {
            $cacheKey = $table->name
                . ':'
                . json_encode(array_values($this->pkValues()));
        }
        return $cacheKey;
    }

    private function _insert($validate = TRUE) {
        $table = self::table();
        $rowClone = $this->_row;
        if (isset($rowClone[self::MTIME])) {
            // mtime always CURRENT_TIMESTAMP in DB
            unset($rowClone[self::MTIME]);
        }

        $rs = $table->insert($this->hintId(), $rowClone, FALSE, array(), $this->dbCacheKey());
        $ok = $rs->getAffectedRows() == 1;
        if ($ok) {
            $this->_isNewRow = FALSE;
            $this->_dirtyColumns = array();

            // update getAll cache, otherwise
            // getAll() -> create() -> getAll() will lose data
            if (self::$_useCache) {
                $pks = array_values(array_slice($rowClone, 0, $table->getAllSliceN));
                $getAllCachePath = self::_cachePathOfAll($pks);
                $models = array_deep_get(self::$_modelsCache, $getAllCachePath, NULL);
                if (NULL !== $models) {
                    // ever called AR:getAll() in this request
                    $models[] = $this;
                    array_deep_set(self::$_modelsCache, $getAllCachePath, $models);
                }
            }

            // for callback
            if (\System\RequestHandler::getInstance()->isAutoCallback()) {
                $this->_addInsertCallback();
            }
        }

        return $ok;
    }

    private function _update($validate = TRUE) {
        if (!$this->isDirty()) {
            return FALSE;
        }

        $table = self::table();
        $pkValues = $this->pkValues();
        if (empty($pkValues)) {
            throw new \Exception("Cannot update, no primary key defined");
        }
        $whereClause = '1=1'; // TODO
        $whereArgs = array();

        // if using optimistic locking, add the clause
        if ($this->_verColumn !== NULL) {
            $columnName = $this->_verColumn->name;
            $whereClause .= " AND {$columnName} = ?";
            $whereArgs[] = $this->$columnName;
            $this->_dirtyColumns[$columnName] = $this->{$columnName} + 1;
        }

        foreach ($pkValues as $key => $val) {
            $whereClause .= " AND $key=?";
            $whereArgs[] = $val;
        }

        if ($this->_whereClause) {
            $whereClause .= " AND {$this->_whereClause}";
        }

        $rs = $table->update($this->hintId(),
            $this->_dirtyColumns, $whereClause, $whereArgs, $this->dbCacheKey());
        // for callback
        if (\System\RequestHandler::getInstance()->isAutoCallback()) {
            $this->_addUpdateCallback();
        }
        $this->_dirtyColumns = array();

        // TODO: currently just throw exception
        if ($rs->getAffectedRows() == 0 && $this->_verColumn !== NULL) {
            throw new \OptimisticLockException("The data is dirty, VER:"
                . $this->{$this->_verColumn->name});
        }

        return $rs->getAffectedRows() == 1;
    }

    // FIXME after delete than call AR::count, the deleted record still counts
    final public function delete() {
        $this->_beforeDelete();
        $this->_deleted = TRUE;
        $this->_registerToFlusherOnce();
    }

    protected function _beforeDelete() {
    }

    /**
     * 立即从数据库里删除.
     *
     * <pre>
     * 例如player tiles pool，当新注册用户时，从该池子里取一个，并删除
     * 如果是{@link ActiveRecord::delete()}，则需要上层代码额外加锁
     * 这时就该使用同步的awaitDelete，就不必加锁了
     * </pre>
     *
     * @return bool
     */
    public function awaitDelete() {
        $this->_deleted = TRUE;

        $table = self::table();
        $modelClass = get_called_class();

        if (self::$_useCache) {
            unset(self::$_modelsCache[$modelClass][self::_getCacheKey($table, $this->pkValues())]);
        }

        $rs = $table->delete($this->hintId(), $this->pkValues(), $this->dbCacheKey());
        $this->_isNewRow = FALSE;
        $this->_dirtyColumns = $this->_row = array();
        return $rs->getAffectedRows() == 1;
    }

    /**
     * Can be called any time any times.
     *
     * Use case, within a batch request, the cmds flows like:
     * <pre>
     * 1. user join chat room (create)
     * 2. user leave (delete)
     * 3. user get banned (update)
     * </pre>
     *
     * So, we need provide 'undelete', so that 3. will:
     * <pre>
     * undelete, then update
     * </pre>
     */
    public final function undelete() {
        $this->_deleted = FALSE;
    }

    /**
     * Create a Model and sync to db instantly.
     *
     * It's caller's responsibility to setup the ticket for row.
     *
     * @param array $row
     * @param bool $validate
     * @return ActiveRecord|NULL
     */
    public final static function create(array $row, $validate = TRUE) {
        $model = self::instance($row, FALSE);
        if ($model->isDeleted()) {
            // found in cache
            $model->undelete();

            // update cache model to the new row
            $pk = array_keys($model->pkValues());
            foreach($row as $key => $value) {
                if (!in_array($key, $pk)) {
                    $model->$key = $value;
                }
            }
        }
        
        $ok = $model->save($validate);
        if (FALSE === $ok) {
            return NULL;
        }

        // remember the redolog for created model
        self::$_createdModels[] = $model;

        return $model;
    }

    /**
     * Avoid call findAll twice for the same Model.
     *
     * It only fill DB rows into cache. Will not hit cache.
     *
     * It's discouraged from using externally.
     *
     * @param int $hintId
     * @param string $where
     * @param array $args
     * @param string $cacheKey
     * @return ActiveRecord[] Array of Model
     */
    public final static function findAll($hintId, $where, array $args = array(),
                                         $cacheKey = '') {
        $models = array();
        foreach (self::table()->select($hintId, $where, $args, '*', $cacheKey) as $row) {
            $models[] = self::instance($row, TRUE);
        }
        return $models;
    }

    public static final function exportAll(/* primary_key1, primary_key2, ... */) {
        $args = func_get_args();
        $rows = array();
        foreach (self::getAll($args) as $model) {
            $rows[] = $model->export(NULL); // FIXME can't control column exclusion
        }
        return $rows;
    }

    /**
     * Count with instant DB query without any cache.
     *
     * @param int $hintId
     * @param string $where
     * @param array $args
     * @return int
     */
    public final static function count($hintId, $where, array $args = array()) {
        $table = self::table();
        $sb = new \Utils\SqlBuilder($table->name);
        $sb->select('COUNT(*) AS C')->where($where);
        $result = $table->query($hintId, $sb->to_s(), $args)->getResults();
        return $result[0]['C'];
    }

    /**
     * Global count.
     *
     * Same as count except that its table is not sharded.
     */
    public final static function g_count($where, array $args = array()) {
        return self::count(0, $where, $args);
    }

    /**
     * Check if entities exists by condition without any cache.
     *
     * @param int $hintId
     * @param string $where
     * @param array $args
     * @return bool
     */
    public final static function exists($hintId, $where, array $args = array()) {
        return self::count($hintId, $where, $args) > 0;
    }

    /**
     * Global exits.
     *
     * Same as count except that its table is not sharded.
     */
    public final static function g_exits($where, array $args = array()) {
        return self::exists(0, $where, $args);
    }

    private static function _cachePathFromArgs(array $args) {
        $cachePath = get_called_class();
        foreach ($args as $arg) {
            if (is_object($arg)) {
                // db column is always primitive type
                throw new \InvalidArgumentException('Invalid args: ' . json_encode($args));
            }

            $cachePath .= '.' . $arg;
        }
        return $cachePath;
    }
    
    /**
     * Get a single model from db by primary key.
     *
     * First argument is ALWAYS hintId if sharded.
     *
     * It's not final, because in some cases, e,g. UserStatsModel
     * when get() returns NULL, it will create a new row instantly.
     *
     * @return NULL|ActiveRecord Null if not found
     * @throws \InvalidArgumentException
     */
    public static function get(/* primary_key1, primary_key2, ... */) {
        $args = func_get_args();
        $table = self::table();
        if (count($args) != count($table->pk)) {
            $n1 = count($args);
            $n2 = count($table->pk);
            throw new \InvalidArgumentException("Table {$table->name} primary key count mismatch: $n1 != $n2");
        }

        // lookup cache
        if (self::$_useCache) {
            $recordInCache = array_deep_get(self::$_modelsCache,
                self::_cachePathFromArgs($args), NULL);
            if (NULL !== $recordInCache) {
                if (!is_object($recordInCache)) {
                    // FIXME kill this block, just for debug
                    throw new \InvalidArgumentException(json_encode($recordInCache));
                }
                if ($recordInCache->isDeleted()) {
                    return NULL;
                }

                return $recordInCache;
            }
        }

        $where = array();
        foreach ($table->pk as $col) {
            $where[] = "$col=?";
        }
        $where = join(' AND ', $where);

        $hintId = 0;
        if ($table->shardColumn) {
            $hintId = (int)$args[0];
        }

        $cacheKey = '';
        if ($table->cacheable) {
            self::_columnCast($args);
            $cacheKey = $table->name
                . ':'
                . json_encode($args);
        }
        $models = self::findAll($hintId, $where, $args, $cacheKey);
        if (!$models) {
            return NULL;
        }
        if (count($models) > 1) {
            throw new \InvalidArgumentException("Found more than 1 rows");
        }

        return $models[0];
    }

    private static function _columnCast(&$args) {
        $table = self::table();
        $pks = $table->pk;
        for ($i = 0; $i < count($args); $i++) {
            $args[$i] = $table->columns[$pks[$i]]->cast($args[$i]);
        }
    }

    /**
     * Borrowed from Java Hibernate.
     *
     * If load() can’t find the object in the cache or database, ObjectNotFoundException is thrown.
     *
     * The get() method returns null if the object can't be found.
     */
    public final static function load(/* primary_key1, primary_key2, ... */) {
        $args = func_get_args();
        $modelClass = get_called_class();
        $model = call_user_func_array(array($modelClass, 'get'), $args);
        if (NULL === $model) {
            throw new ObjectNotFoundException(json_encode(array(
                'model' => $modelClass,
                'args' => $args,
            )));
        }

        return $model;
    }

    // FIXME not atomic between get and create
    public final static function getOrCreate(array $row /* , primary_key1, primary_key2, ... */) {
        $args = func_get_args();
        array_shift($args); // $args now is primary keys values
        $model = call_user_func_array(array(get_called_class(), 'get'), $args);
        if (!$model) {
            $model = self::create($row);
        }

        return $model;
    }

    /**
     * Get all models that match first parts of compound primary keys.
     *
     * First argument is ALWAYS hintId if sharded.
     *
     * @return ActiveRecord[] Array of Model
     * @throws \InvalidArgumentException
     */
    public final static function getAll(/* primary_key1, primary_key2, ... */) {
        $pks = func_get_args();
        if (count($pks) == 0) {
            throw new \InvalidArgumentException("Are you sure to get All in DB?");
        }
        
        if (count($pks) != count($pks, COUNT_RECURSIVE)) {
            $pks = $pks[0];
        }
        $table = self::table();
        if (count($pks) > count($table->pk)) {
            throw new \InvalidArgumentException('Primary key count mismatch');
        }

        // '_all' will never conflict with cache key of model class name
        // we have ever called getAll, now its safe to get from cache
        // else, will not pull from cache
        if (self::$_useCache) {
            $recordsInCaches = array_deep_get(self::$_modelsCache,
                self::_cachePathOfAll($pks), NULL);
            if ($recordsInCaches !== NULL) {
                $records = array();
                foreach ($recordsInCaches as $record) {
                    if (!$record->isDeleted()) {
                        $records[] = $record;
                    }
                }

                return $records;
            }
        }

        $where = '1=1'; // TODO
        for ($i=0, $count = count($pks); $i < $count; $i++) {
            $where .= " AND {$table->pk[$i]}=?";
        }

        $hintId = 0;
        if ($table->shardColumn) {
            $hintId = (int)$pks[0];
        }

        $models = self::findAll($hintId, $where, $pks);
        foreach ($models as $idx => $model) {
            if ($model->isDeleted()) {
                unset($models[$idx]);
            }
        }

        if (self::$_useCache) {
            array_deep_set(self::$_modelsCache, self::_cachePathOfAll($pks), $models);
        }

        return $models;
    }

    protected static function _cachePathOfAll($args) {
        $className = get_called_class();
        $argsString = join('-', $args);
        return '_all.' . $className . '.' . $argsString;
    }

    /**
     * Export to raw array for frontend(contains columns exclusive).
     *
     * @param array $excludedColumns List of column names
     * @return array
     */
    public function export($excludedColumns = array(self::MTIME, self::CTIME)) {
        if (empty($excludedColumns)) {
            return $this->_row;
        }

        return array_diff_key($this->_row, array_flip($excludedColumns));
    }

    /**
     * Export to raw array for frontend(contains columns inclusive).
     *
     * @param array $includedColumns List of column names
     * @return array
     */
    public function exportInclusive($includedColumns = array()) {
        if (empty($includedColumns)) {
            return $this->_row;
        }

        return array_intersect_key($this->_row, array_flip($includedColumns));
    }

    public function toString() {
        return json_encode($this->export());
    }

}

class ObjectNotFoundException extends \Exception {}
