<?php

namespace Consts;

interface GameDataConst {

    // TODO kill all magic strings related to these const
    // interim columns for game data that was generated by parser
    // besides these, all columns can be found in google doc
    // TODO add underscore for all tools generated keys to distinguish from googledoc
    const
        COLUMN_INTERNAL_ID = 'Internal_ID',
        COLUMN_REWARDS = 'Rewards',
        COLUMN_UNITS = 'Units',
        COLUMN_BENEFITS = 'Benefits',
        COLUMN_COSTS = 'Costs',
        COLUMN_REQUIREMENTS = 'Requirements',
        COLUMN_BUFF_ID = 'Buff_ID',
        COLUMN_VALUE = 'Value',
        COLUMN_UPKEEP = 'Upkeep',
        COLUMN_MAX_EXP = 'max_exp';

    // all game data files
    const
        FILE_SECRET_GIFT = 'Secret_Gift',
        FILE_BUILDING = 'Building',
        FILE_GIFT_LEVEL = 'Gift_Level',
        FILE_GIFT_TYPE = 'Gift_Type',
        FILE_ALLIANCE_GIFT = 'Alliance_Gift',
        FILE_GAMECONFIG = 'GameConfig',
        FILE_HERO_SKILLS = 'Hero_Skills',
        FILE_HERO_EQUIP = 'Hero_Equipment',
        FILE_UNIT_STATS = 'Unit_Statistics',
        FILE_PVE_ZONE = 'PvE_ZoneData',
        FILE_KINGDOM_CONFIG = 'KingdomConfig',
        FILE_PVE_ZONE_NEW = 'PVE_ZoneData_New',
        FILE_PVE_ENCOUNTER = 'PVE_EncounterData',
        FILE_RESEARCH = 'Research',
        FILE_SHOP = 'Shop',
        FILE_COMBAT_EFFECTIVENESS = 'Combat_Effectiveness',
        FILE_QUEST = 'Quest',
        FILE_QUEST_CHANCE = 'Quest_Chance',
        FILE_BUFFS = 'Buffs',
        FILE_TILE_STAT = 'Tile_Statistics';
}
