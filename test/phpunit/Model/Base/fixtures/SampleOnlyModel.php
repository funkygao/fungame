<?php

final class SampleOnlyModel extends \Model\Base\ActiveRecord
    implements \Consts\ColumnConst {
    public static $table = 'table_sample';
    public static $pool = 'user';

    // transient attribute, if accessible, must be public and start with underscore
    public $city;

    public function equals($that) {
        if (!parent::equals($that)) {
            return FALSE;
        }

        return $this->city == $that->city;
    }

    public static $columns = array(
    
        array(
            self::NAME     => 'uid',
            self::TYPE     => self::INTEGER,
            self::SHARD    => true,
            self::PK       => true,
        ),
        
        array(
            self::NAME     => 'pid',
            self::TYPE     => self::INTEGER,
            self::PK       => true,
        ),
        
        array(
            self::NAME     => 'title',
            self::TYPE     => self::STRING,
            self::DEFAULTS => 'hello world',
        ),
        
        array(
            self::NAME     => 'ctime',
            self::TYPE     => self::DATETIME,
        ),
        
        array(
            self::NAME     => 'mtime',
            self::TYPE     => self::DATETIME,
        ),
        
        array(
            self::NAME     => 'gendar',
            self::TYPE     => self::STRING,
            self::CHOICES  => array('male', 'female', 'unknown', 'I am female'),
        ),

        array(
            self::NAME     => 'json',
            self::TYPE     => self::JSON,
        ),
        
    );

    // This kind of hook is ALWAYS protected instead of public
    public function set_gendar($gendar) {
        // We NEVER assign gendar like $this->gendar = 'foo'; in this hook
        // We JUST return new value or do something else
        // e,g update another shadow table
        return 'I am ' . $gendar;
    }

    // This kind of hook is ALWAYS protected instead of public
    public function get_gendar($gendar) {
        // We did nothing here, so the 'equals' can work as expected
        return $gendar;
    }
}

