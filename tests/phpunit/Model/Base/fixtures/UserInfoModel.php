<?php

final class UserInfoModel extends \Model\Base\ActiveRecord implements \Consts\ColumnConst {
    public static $table = 'UserInfo';
    public static $pool = 'user';

    public static $columns = array(
    
        array(
            self::NAME     => 'uid',
            self::TYPE     => self::INTEGER,
            self::PK       => true,
            self::SHARD    => true,
        ),
        
        array(
            self::NAME     => 'alliance_id',
            self::TYPE     => self::INTEGER,
        ),
        
        array(
            self::NAME     => 'power',
            self::TYPE     => self::INTEGER,
        ),
        
        array(
            self::NAME     => 'name',
            self::TYPE     => self::STRING,
        ),
        
        array(
            self::NAME     => 'gold',
            self::TYPE     => self::INTEGER,
        ),
        
        array(
            self::NAME     => 'inventory_slots',
            self::TYPE     => self::INTEGER,
        ),
        
        array(
            self::NAME     => 'ctime',
            self::TYPE     => self::DATETIME,
        ),
        
        array(
            self::NAME     => 'mtime',
            self::TYPE     => self::DATETIME,
        ),
        
    );

}

