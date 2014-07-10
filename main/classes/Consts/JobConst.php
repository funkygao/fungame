<?php

namespace Consts;

interface JobConst {

    // MODIFY JobModel::getTypeLabel() when you modify this file !!!
    const
        EVENT_TYPE_RESOURCE_FOOD_BOOST = 1,
        EVENT_TYPE_RESOURCE_WOOD_BOOST = 2,
        EVENT_TYPE_RESOURCE_ORE_BOOST = 3,
        EVENT_TYPE_RESOURCE_SILVER_BOOST = 4,
        EVENT_TYPE_ATTACK_BOOST = 5,
        EVENT_TYPE_DEFENSE_BOOST = 6,

        EVENT_TYPE_BUILDING_CONSTRUCTION = 30,
        EVENT_TYPE_BUILDING_DECONSTRUCTION = 31,
        EVENT_TYPE_TRAIN_TROOP = 32,
        EVENT_TYPE_RESEARCH = 33,
        EVENT_TYPE_PVP_MARCH = 34,
        EVENT_TYPE_PVE_MARCH = 35,
        EVENT_TYPE_ENCOUNTER_RESPAWN = 36,
        EVENT_TYPE_UPKEEP_REDUCE = 38;

    // MODIFY JobModel::getTypeLabel() when you modify this file !!!

}
