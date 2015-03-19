<?php

namespace Driver;

// use SQLite for storage, publisher will push sqlite dbs to each php-fpm instance.
// each sqlite db is readonly, only for faster accessing expecially for bulk data filter
// TODO http://www.sqlite.org/whentouse.html
final class GameDataFactory {

    /**
     * @var \PDO
     */
    private $_dbh;

    /**
     * @param string $dbfile
     * @return GameDataFactory
     */
    public static function instance($dbfile) {
        static $instances = array();
        if (!isset($instances[$dbfile])) {
            $instances[$dbfile] = new self();
            $handle = new \PDO("sqlite:$dbfile");
            $handle->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
            $handle->exec('PRAGMA synchronous = OFF');
            $handle->exec('PRAGMA journal_mode = MEMORY');
            $handle->exec('PRAGMA read_uncommitted = true');
            $instances[$dbfile]->_dbh = $handle;
        }

        return $instances[$dbfile];
    }

    /**
     * @param string $sql
     * @param array $args
     * @return array
     */
    public function query($sql, $args = array()) {
        $stmt = $this->_dbh->prepare($sql); // TODO cache the statement
        $stmt->execute($args);
        $data = $stmt->fetchAll(\PDO::FETCH_ASSOC);
        return $data;
    }

}
