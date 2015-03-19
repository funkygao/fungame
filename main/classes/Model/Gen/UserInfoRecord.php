<?php

namespace Model\Gen;

abstract class UserInfoRecord extends \Model\Base\ActiveRecord {
    public static $table = 'UserInfo';
    public static $tname = 'user_info';
    public static $pool = 'user';
    public static $cacheable = TRUE;

    public static $columns = array(
    
        array(
            self::NAME     => 'uid',
            self::TYPE     => self::UINT,
            self::PK       => TRUE,
            self::SHARD    => TRUE,
        ),
        
        array(
            self::NAME     => 'alliance_id',
            self::TYPE     => self::UINT,
        ),
        
        array(
            self::NAME     => 'power',
            self::TYPE     => self::UINT,
        ),
        
        array(
            self::NAME     => 'gold',
            self::TYPE     => self::UINT,
        ),

        array(
            self::NAME     => 'honor',
            self::TYPE     => self::UINT,
        ),
        
        array(
            self::NAME     => 'essence',
            self::TYPE     => self::UINT,
        ),

        array(
            self::NAME     => 'chat_channel',
            self::TYPE     => self::UINT,
        ),
        
        array(
            self::NAME     => 'inventory_slots',
            self::TYPE     => self::UINT,
        ),

        array(
            self::NAME     => 'gift_mode',
            self::TYPE     => self::UINT,
        ),

        array(
            self::NAME     => 'daily_streak',
            self::TYPE     => self::JSON,
        ),

        array(
            self::NAME     => 'portrait',
            self::TYPE     => self::STRING,
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

