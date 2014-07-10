<?php

namespace System;

final class GameData {

    /**
     * @var array
     */
    private $_data;

    /**
     * @param string $configName
     * @param string $path
     * @return GameData
     * @throws \Exception
     */
    public static function getInstance($configName, $path = NULL) {
        if (!$path) {
            $path = CONFIG_PATH;
        }
        $filepath = $path . $configName . '.php';
        if (!file_exists($filepath)) {
            // TODO -- change to Exception before release
            throw new \System\GameException("Game data not found: $filepath");
        }

        static $instance = array();
        if (!isset($instance[$configName])) {
            $instance[$configName] = new self(require $filepath);
        }
        return $instance[$configName];
    }

    private function __construct(array $config) {
        $this->_data = $config;
    }

    public static function getLevel($id) {
        $level = substr($id, strrpos($id, '_') + 1);
        if (!is_numeric($level)) {
            throw new \Exception("No level of ID: $id");
        }
        return (int) $level;
    }

    public static function internalIdToSheetName($internalId, $path = NULL) {
        static $mapping = array();
        if (!$path) {
            $path = DATA_PATH;
        }

        if (!$mapping) {
            $mapping = require $path . 'ConfigAddress.php';
        }

        $span = $mapping['span'];
        return $mapping['mapping'][(int)($internalId / $span) * $span];
    }

    /**
     * @param int $internalId The primary key
     * @return array|null
     * @throws \Exception
     */
    public function getByPk($internalId) {
        if (!is_numeric($internalId)) {
            throw new \Exception("Game config internal id is int, $internalId given");
        }

        if (isset($this->_data['data'][$internalId])) {
            return $this->_list2map($this->_data['data'][$internalId]);
        }

        return NULL;
    }

    /**
     * Find lines that match the query criteria.
     *
     * @param array $criteria [$columnName => [$operation => $value]] e,g. ('Count' => ['>' => 3]) means get lines whose Count column is larger than 3
     * @return array|null Lines of game data
     */
    public function findByCriteria(array $criteria) {
        $headerFlip = array_flip($this->_data['headers']);
        foreach ($criteria as $key => $value) {
            if (!is_array($value)) {
                if (isset($headerFlip[$key])) {
                    $criteria[$headerFlip[$key]] = $value;
                }
                unset($criteria[$key]);
            }
        }
        if (!count($criteria)) {
            return null;
        }

        $result = array();
        foreach ($this->_data['data'] as $internalId => $row) {
            $match = true;
            foreach ($criteria as $key => $value) {
                if (is_array($value)) {
                    /*
                     * now $value is like array('>', 5)
                     */
                    $operator = key($value);
                    if (!is_array($operator)) {
                        $operator = array($operator);
                    }
                    $val = current($value);
                    foreach ($operator as $op) {
                        eval("\$match = \$row[\$headerFlip[\$key]]$op\$val;"); // FIXME we never use eval
                        if (!$match) {
                            break;
                        }
                    }

                    if (!$match) {
                        break;
                    }
                } else {
                    if (is_array($row[$key])) {
                        if (!isset($row[$key][$value]) && !in_array($value, $row[$key])) {
                            $match = false;
                            break;
                        }
                    } else if ($row[$key] != $value) {
                        $match = false;
                        break;
                    }
                }
            }

            if ($match) {
                $result[$internalId] = $this->_list2map($row);
            }
        }

        return $result;
    }

    /**
     * @param string $id The ID column in the config file, some configs has no such column
     * @return array|null
     */
    public function getById($id) {
        if (isset($this->_data[$id])) {
            return $this->_list2map($this->_data[$id]);
        }

        return NULL;
    }

    public function inquire($path, $default = NULL) {
        $arr = explode('.', $path, 2);
        if(isset($this->_data['data'][$arr[0]])) {
            $row = $this->_list2map($this->_data['data'][$arr[0]]);
        } elseif(isset($this->_data[$arr[0]])) {
            $row = $this->_list2map($this->_data[$arr[0]]);
        } else {
            return $default;
        }

        if(count($arr) < 2) {
            return $row;
        }

        return array_deep_get($row, $arr[1], $default);
    }

    private function _list2map(array $line) {
        return array_combine($this->_data['headers'], $line);
    }

    public function getLevelup(array $line) {
        $id = $line['ID'];
        $level = self::getLevel($id);
        $nextLevel = $level + 1;
        return $this->getById(substr($id, 0, strrpos($id, '_') + 1) . $nextLevel);
    }
    
    public static function sheetName($ref){
        if(strpos($ref, '.') !== false){
            return explode('.', $ref)[0];
        }
        return null;
    }

    public static function columnName($ref){
        if(strpos($ref, '.') !== false){
            return substr($ref, strpos($ref, '.') + 1, strpos($ref, '@') - strpos($ref, '.') - 1);
        }
        return null;
    }

    public static function cellValue($ref){
        if(strpos($ref, '.') !== false && strpos($ref, '@') !== false){
            if(strpos($ref, ':') !== false){
                return substr($ref, strpos($ref, '@') + 1, strpos($ref, ':') - strpos($ref, '@') - 1);
            } else {
                return explode('@', $ref)[1];
            }
        }
        return $ref;
    }
}
