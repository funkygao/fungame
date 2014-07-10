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
 * TODO centralized flusher for delete/insert/update
 * TODO layered cache
 * TODO _all cache contains only flag, instead of dup record
 * FIXME maybe mtime shouldn't be set by DB, should use opTime also
 * TODO delete then create the same record, bug that it gets deleted
 */
abstract class ActiveRecord
    implements \Model\Base\Flushable, \Consts\DbConst, \Consts\ColumnConst {

    /**
     * Cache of the all models of all ActiveRecord classes.
     *
     * array[modelClassName][cachePath] = Model instance
     *
     * @var array
     */
    private static $_modelsCache = array();

    private $_row = array();

    private $_dirtyColumns = array();

    private $_flusherRegistered = FALSE;

    /**
     * Flag that determines if a call to save() should issue an insert or an update sql statement
     *
     * @var boolean
     */
    private $_isNewRow;

    private $_deleted = FALSE;

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

    public static final function table() {
        return Table::load(get_called_class());
    }

    public static final function sqlBuilder() {
        return new \Utils\SqlBuilder(self::table()->name);
    }

    protected final function _getLogger() {
        return \System\Logger::getLogger(get_class($this));
    }

    private static function _getCacheKey(Table $table, array $row) {
        $cacheKey = '';
        foreach ($table->pk as $key) {
            if (isset($row[$key])) {
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

        $cacheKey = self::_getCacheKey($table, $row);
        $modelClassWithNamespace = get_called_class();
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
            throw new \InvalidArgumentException("{$table->name} JSON column[$column] must be array, $value given");
        }
        if ($table->columns[$column]->type == self::DATETIME
            && !is_numeric($value)) {
            throw new \InvalidArgumentException("{$table->name} Timestamp column[$column] must be int, $value given");
        }
        if ($table->columns[$column]->type == self::UINT
            && $value < 0) {
            throw new \InvalidArgumentException("{$table->name} int column[$column] must be positive, $value given");
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
            $value = $this->$getter($value);
        }
        return $value;
    }

    // utilized for reading data from inaccessible properties
    public final function __set($column, $value) {
        if ($this->_deleted) {
            $tableName = self::table()->name;
            throw new \InvalidArgumentException("Cannot access deleted record column, $tableName: $column, $value");
        }

        $table = self::table();
        if (in_array($column, $table->pk)) {
            throw new \InvalidArgumentException("Primary key[$column] is readonly");
        }

        if (!array_key_exists($column, $this->_row)) {
            $tableName = self::table()->name;
            throw new \InvalidArgumentException("Unknown column: $tableName: $column");
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

    private function _registerToFlusherOnce() {
        if (!$this->_flusherRegistered) {
            $this->_flusherRegistered = TRUE;
            \System\Flusher::register($this);
        }
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

    public final function pkValues() {
        $table = self::table();
        $pk_values = array();
        foreach ($table->pk as $name) {
            if (array_key_exists($name, $this->_row)) {
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
        $id = (int)\Driver\TicketFactory::instance()->nextId(self::table()->ticket);
        if ($id < 1) {
            throw new \System\ServerException("Bad hintId: $id");
        }
        return $id;
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
            //clear cache
            $this->_clearCache();
            $rs = self::table()->delete($this->hintId(), $this->pkValues());
            return $rs->getAffectedRows() == 1;
        }

        return $this->_isNewRow ? $this->_insert($validate)
            : $this->_update($validate);
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

    private function _insert($validate = TRUE) {
        $table = self::table();
        if ($validate) {
            $table->validateRow($this->_row);
        }

        $rowClone = $this->_row;
        if (isset($rowClone[self::MTIME])) {
            // mtime always CURRENT_TIMESTAMP in DB
            unset($rowClone[self::MTIME]);
        }
        $rs = $table->insert($this->hintId(), $rowClone);
        $ok = $rs->getAffectedRows() == 1;
        if ($ok) {
            $this->_isNewRow = FALSE;
            $this->_dirtyColumns = array();
        }

        return $ok;
    }

    private function _update($validate = TRUE) {
        if (!$this->isDirty()) {
            return FALSE;
        }

        $table = self::table();
        if ($validate) {
            $table->validateRow($this->_row);
        }

        $pkValues = $this->pkValues();
        if (empty($pkValues)) {
            throw new \Exception("Cannot update, no primary key defined");
        }
        $whereClause = '1=1';
        $whereArgs = array();
        foreach ($pkValues as $key => $val) {
            $whereClause .= " AND $key=?";
            $whereArgs[] = $val;
        }

        $rs = $table->update($this->hintId(),
            $this->_dirtyColumns, $whereClause, $whereArgs);
        $this->_dirtyColumns = array();
        return $rs->getAffectedRows() == 1;
    }

    // it's not final, because some Model wants fake delete
    // in that case, they will override delete()
    // FIXME after delete than call AR::count, the deleted record still counts
    public function delete() {
        $this->_deleted = TRUE;

        // leave the cache untouched, because now the cache entry's isDeleted() is TRUE
        // php's class instance is referenced instead of cloned

        $this->_registerToFlusherOnce();
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
     * @return ActiveRecord[] Array of Model
     */
    public final static function findAll($hintId, $where, array $args = array()) {
        $models = array();
        foreach (self::table()->select($hintId, $where, $args) as $row) {
            $models[] = self::instance($row, TRUE);
        }

        return $models;
    }

    public static final function exportAll(/* primary_id1, primary_id2, ... */) {
        $args = func_get_args();
        $rows = array();
        foreach (self::getAll($args) as $model) {
            $rows[] = $model->export(null);
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
        $sb = self::sqlBuilder();
        $sb->select('COUNT(*) AS C')->where($where);
        $result = self::table()->query($hintId, $sb->to_s(), $args)->getResults();
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
    public static function get(/* primary_id1, primary_id2, ... */) {
        $args = func_get_args();
        $table = self::table();
        if (count($args) != count($table->pk)) {
            $n1 = count($args);
            $n2 = count($table->pk);
            throw new \InvalidArgumentException("Table {$table->name} primary key count mismatch: $n1 != $n2");
        }

        // lookup cache
        $recordInCache = array_deep_get(self::$_modelsCache,
            self::_cachePathFromArgs($args), NULL);
        if (NULL !== $recordInCache) {
            if ($recordInCache->isDeleted()) {
                return NULL;
            }

            return $recordInCache;
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

        $models = self::findAll($hintId, $where, $args);
        if (!$models) {
            return NULL;
        }
        if (count($models) > 1) {
            throw new \InvalidArgumentException("Found more than 1 rows");
        }

        return $models[0];
    }

    /**
     * Borrowed from Java Hibernate.
     *
     * If load() can’t find the object in the cache or database, ObjectNotFoundException is thrown.
     *
     * The get() method returns null if the object can't be found.
     */
    public final static function load(/* primary_id1, primary_id2, ... */) {
        $model = call_user_func_array(array(get_called_class(), 'get'), func_get_args());
        if (NULL === $model) {
            throw new ObjectNotFoundException();
        }

        return $model;
    }

    public final static function getOrCreate(array $row /* , primary_id1, primary_id2, ... */) {
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
    public final static function getAll(/* primary_id1, primary_id2, ... */) {
        $args = func_get_args();
        if (count($args) == 0) {
            throw new \InvalidArgumentException("Are you sure to get All in DB?");
        }
        
        if (count($args) != count($args, COUNT_RECURSIVE)) {
            $args = $args[0];
        }
        $table = self::table();
        if (count($args) > count($table->pk)) {
            throw new \InvalidArgumentException('Primary key count mismatch');
        }

        // '_all' will never conflict with cache key of model class name
        // we have ever called getAll, now its safe to get from cache
        // else, will not pull from cache
        $recordsInCaches = array_deep_get(self::$_modelsCache,
            self::_cachePathOfAll($args), NULL);
        if ($recordsInCaches !== NULL) {
            $records = array();
            foreach ($recordsInCaches as $record) {
                if (!$record->isDeleted()) {
                    $records[] = $record;
                }
            }
            return $records;
        }

        $where = '1=1';
        for ($i=0, $count = count($args); $i < $count; $i++) {
            $where .= " AND {$table->pk[$i]}=?";
        }

        $hintId = 0;
        if ($table->shardColumn) {
            $hintId = (int)$args[0];
        }

        $models = self::findAll($hintId, $where, $args);
        foreach ($models as $idx => $model) {
            if ($model->isDeleted()) {
                unset($models[$idx]);
            }
        }
        array_deep_set(self::$_modelsCache, self::_cachePathOfAll($args), $models);
        return $models;
    }

    protected static function _cachePathOfAll($args) {
        $className = get_called_class();
        $argsString = join('-', $args);
        return '_all.' . $className . '.' . $argsString;
    }

    /**
     * Export to raw array for frontend.
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

    public function toString() {
        return json_encode($this->export());
    }

}

class ObjectNotFoundException extends \Exception {}
