<?php

namespace Model\Base;

final class Column implements \Consts\ColumnConst, \Consts\ErrnoConst {

    /**
     * The true name of this column.
     *
     * @var string
     */
    public $name;

    /**
     * The type of this column: STRING, INTEGER, ...
     *
     * @var integer
     */
    public $type;

    /**
     * True if this column is shardId.
     *
     * @var bool
     */
    public $shard;

    /**
     * The constraint of maximum length of this column.
     *
     * @var int
     */
    public $length;

    /**
     * True if this column allows null.
     *
     * @var boolean
     */
    public $nullable;

    /**
     * True if this column is a primary key.
     *
     * For shard tables, shard column cannot be declared as pk!
     *
     * @var boolean
     */
    public $pk;

    /**
     * The default value of the column.
     *
     * @var mixed
     */
    public $default = NULL;

    /**
     * The valid values of this column.
     *
     * Useful when fill in the dropdown option list.
     *
     * e,g. column 'sex'. Its choices will be array('unknown', 'male', 'female').
     *
     * @var array
     */
    public $choices;

    /**
     * If the update is like update col=col+delta
     *
     * @var boolean
     */
    public $delta = FALSE;

    /**
     * Usually json blob, only faed can write it to avoid concurrent update.
     *
     * @var bool
     */
    public $critical = FALSE;

    public function __construct(array $attributes) {
        foreach ($attributes as $attr => $value) {
            $this->$attr = $value;
        }

        if (!isset($this->name)) {
            throw new \ExpectedErrorException('Column name must be declared', self::ERRNO_SYS_INVALID_ARGUMENT);
        }
        if (!isset($this->type)) {
            throw new \ExpectedErrorException('Column type must be declared', self::ERRNO_SYS_INVALID_ARGUMENT);
        }

        if (NULL === $this->default) {
            // by name
            if ($this->name == self::CTIME || $this->name == self::MTIME) {
                $this->default = \System\RequestHandler::getInstance()->currentOpTime();
            } else {
                // by type
                switch ($this->type) {
                    case self::JSON:
                        $this->default = array();
                        break;

                    case self::VER:
                    case self::INTEGER:
                    case self::DECIMAL:
                    case self::UINT:
                        $this->default = 0;
                        break;

                    case self::STRING:
                        $this->default = '';
                        break;
                    case self::DATETIME:
                        $this->default = 1414368000; // default [2014-10-27 00:00:00] because mysql 5.6 can not insert 1970-01-01 00:00:00
                        break;
                }
            }
        }
    }

    /**
     * 数据类型转换和基于数据类型的基本校验.
     *
     * @param mixed $value
     * @return \DateTime|float|int|null|string
     * @throws \Exception
     */
    public function cast($value) {
        if ($value === NULL) {
            return NULL;
        }

        switch ($this->type) {
            case self::STRING:
                if (!empty($this->choices) && !in_array($value, $this->choices)) {
                    throw new \ExpectedErrorException("$value not within choices", self::ERRNO_SYS_INVALID_ARGUMENT);
                }

                return (string)$value;

            case self::VER:
            case self::INTEGER:
            case self::UINT:
                if (!is_numeric($value)) {
                    throw new \ExpectedErrorException("Int expected, got $value", self::ERRNO_SYS_INVALID_ARGUMENT);
                }
                return (int)$value;

            case self::DECIMAL:
                if (!is_numeric($value)) {
                    throw new \ExpectedErrorException("Double expected, got $value", self::ERRNO_SYS_INVALID_ARGUMENT);
                }
                return round((double)$value, 2);

            case self::JSON:
                return (array)$value;

            case self::DATETIME:
            case self::DATE: // FIXME
                if (!$value) {
                    return null;
                }

                if ($value instanceof \DateTime) {
                    return $value;
                }

                return (int)$value;

            default:
                throw new \Exception('Unkown column type: ' . $this->type);
        }
    }

}
