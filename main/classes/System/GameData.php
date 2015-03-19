<?php

namespace System;

final class GameData implements \Consts\MarchConst {

    /**
     * @var array
     */
    private $_data;

    /**
     * @param string $configName
     * @param string $path
     * @return GameData
     * @throws \RestartGameException
     */
    public static function getInstance($configName, $path = NULL) {
        if (!$path) {
            $path = CONFIG_PATH;
        }

        $key = md5($path); // TODO 此处不需要时间换空间，去掉md5
        static $instance = array();
        if (!isset($instance[$key][$configName])) {
            $filepath = $path . self::_getVersion($path) . '/' . $configName . '.php';
            if (!file_exists($filepath)) {
                throw new \RestartGameException("Game data not found: $filepath");
            }
            $instance[$key][$configName] = new self(require $filepath);
        }
        return $instance[$key][$configName];
    }

    private function __construct(array $config) {
        $this->_data = $config;
    }

    /**
     * 服务器当前使用的GameData的版本号.
     *
     * 客户端使用的版本号与服务器使用的版本号可能不同。
     * 例如，上传了一个assets bundle，此时客户端
     * 使用的版本号/getAssetsVersion会变，但服务器的不变
     *
     * @param string $path
     * @return int
     */
    private static function _getVersion($path = NULL) {
        if (!$path) {
            $path = CONFIG_PATH;
        }

        $key = md5($path); // FIXME KILL all md5
        static $version = array();
        if (!isset($version[$key])) {
            $versionFilePath = $path . 'Version.json'; // TODO Version.php
            $version[$key] = (int) json_decode(file_get_contents($versionFilePath),
                TRUE);
        }

        return $version[$key];
    }

    public function getData() {
        return $this->_data;
    }

    /**
     * 客户端当前使用的配置文件版本号.
     *
     * @param null|string $path
     * @return int
     */
    public static function getAssetsVersion($path = NULL) {
        if (!$path) {
            $path = ASSETS_PATH;
        }

        $key = md5($path);
        static $version = array();
        if (!isset($version[$key])) {
            $versionFilePath = $path . 'Version.json';
            $version[$key] = (int) json_decode(file_get_contents($versionFilePath), true);
        }
        return $version[$key];
    }

    public static function getLevel($id) {
        $level = substr($id, strrpos($id, '_') + 1);
        if (!is_numeric($level)) {
            throw new \RestartGameException("No level of ID: $id");
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
     * @throws \RestartGameException
     */
    public function getByPk($internalId) {
        if (!is_numeric($internalId)) {
            throw new \RestartGameException("Game config internal id is int, $internalId given");
        }

        if (isset($this->_data['data'][$internalId])) {
            return $this->_list2map($this->_data['data'][$internalId]);
        }

        return NULL;
    }

    /**
     * Find lines that match the query criteria.
     *
     * @param array $criteria e.g. ["column_1 == aaa", "column_2 >= 1", "column_3 in 2|3|4", "Requirements.internal_id == 96", "Requirements.vip_points <= 300"]
     * @return array|null Lines of game data
     */
    public function findByCriteria(array $criteria) {
        $headerFlip = array_flip($this->_data['headers']);
        $criteriaArray = array();
        foreach ($criteria as $criterion) {
            list($column, $operator, $value) = explode(' ', $criterion);
            if ($operator == 'in') {
                $value = array_flip(explode('|', $value));
            }
            $criteriaArray[] = array(
                'column' => $column,
                'operator' => $operator,
                'value' => $value,
                'isSub' => strpos($column, '.') !== false,
            );
        }

        $result = array();
        foreach ($this->_data['data'] as $internalId => $row) {
            $match = true;
            foreach ($criteriaArray as $criterion) {
                if ($criterion['isSub']) {
                    list($key, $sub) = explode('.', $criterion['column']);
                    if ($sub == 'internal_id' && isset($row[$headerFlip[$key]][$criterion['value']])) {
                        $match = true;
                        continue;
                    }
                    $candidate = $row[$headerFlip[$key]][$sub];
                } else {
                    $candidate = $row[$headerFlip[$criterion['column']]];
                }

                switch ($criterion['operator']) {
                    case '==':
                        $match = $candidate == $criterion['value'];
                        break;
                    case '>':
                        $match = $candidate > $criterion['value'];
                        break;
                    case '<':
                        $match = $candidate < $criterion['value'];
                        break;
                    case '>=':
                        $match = $candidate >= $criterion['value'];
                        break;
                    case '<=':
                        $match = $candidate <= $criterion['value'];
                        break;
                    case '!=':
                        $match = $candidate != $criterion['value'];
                        break;
                    case 'in':
                        $match = isset($criterion['value'][$candidate]);
                        break;
                    default:
                        $match = false;
                        break;
                }

                if (!$match) {
                    break;
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

    public function _list2map(array $line) {
        return array_combine($this->_data['headers'], $line);
    }

    public function getLevelup(array $line) {
        $id = $line['id'];
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

    /*
     * @param string $filter eg.
     *     1) pve_encounter.filter@type=camp
     *     2) pve_encounter.filter@id=encounter_id_camp_01
     *     3) pve_encounter.filter@type=camp&level=5|6
     * @return array|null Lines of game data
     */
    public static function findByFilter($filter, $path = NULL) {
        list($header, $query) = explode('@', $filter);
        list($configname) = explode('.', $header);
        $gameData = self::getInstance($configname, $path);
        parse_str($query, $queryArr);
        $criteria = array();
        foreach ($queryArr as $column => $value) {
            if (str_contains($value, '|')) {
                $criteria[] = "$column in $value";
            } else {
                $criteria[] = "$column == $value";
            }
        }
        $lines = $gameData->findByCriteria($criteria);
        if (!$lines) {
            throw new \RestartGameException("GameData not found filter[$filter]");
        }

        return $lines[array_rand($lines)];
    }

    public static function getResourceId($resourceId, $resourceLevel) {
        $mapping = array(
            self::TILE_RESOURCE_FOOD => 'farm_kingdom_',
            self::TILE_RESOURCE_WOOD => 'lumber_camp_kingdom_',
            self::TILE_RESOURCE_ORE => 'mine_kingdom_',
            self::TILE_RESOURCE_SILVER => 'estate_kingdom_',
        );

        return $mapping[$resourceId] . $resourceLevel;
    }
}
