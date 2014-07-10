<?php

namespace Consts;

interface DbConst {

    const
        POOL_USER = 'user', // shard by uid
        POOL_LOOKUP = 'lookup', // non-shard
        POOL_GLOBAL = self::POOL_LOOKUP, // non-shard
        POOL_ALLIANCE = 'alliance', // shard by alliance_id
        POOL_WORLD = 'world', // shard by world_id
        POOL_TICKETS = 'tickets', // non-shard
        POOL_TOOLS = 'tools', // non-shard
        POOL_CHAT = 'chat', // shard by room_id or group_id
        POOL_MAIN = 'main'; // TODO

    const
        COLUMN_CTIME = 'ctime', // TODO kill this, use ColumnConst
        COLUMN_MTIME = 'mtime';

    /**
     * basis on how we shard
     */
    const ENTITIES_PER_SHARD = 300000;

}
