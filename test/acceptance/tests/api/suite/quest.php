<?php
//TODO: when the vip system ok, remove this
function is_vip($uid) {
    if ($uid % 2 == 0) {
        return true;
    }
    return false;
}

function test_alliance_vip_quest($uid, $I) {
    if (is_vip($uid)) {

        // load vip quest
        $questInfo = call_commit($I, 'Quest', 'loadVipQuest', array(
            'uid' => $uid,
        ));
        $I->assertGreaterThen(1, $questInfo['payload']['quests']);

        // start vip quest
        $questId = $questInfo['payload']['quests'][0]['quest_id'];
        $ret = call_commit($I, 'Quest', 'startVipQuest', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertTrue($ret['payload']['ret']);

        // check vip quest
        $ret = call_commit($I, 'Quest', 'checkVipQuest', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertGreaterThen(0, $ret['payload']['status']);

        // collect vip quest
        $ret = call_commit($I, 'Quest', 'collectVipQuestReward', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertFalse($ret['payload']['ret']);

        // refresh vip quest
        $questInfo = call_commit($I, 'Quest', 'refreshVipQuest', array(
            'uid' => $uid,
        ));
        $I->assertGreaterThen(1, $questInfo['payload']['quests']);
    } else {
        // load alliance quest
        $questInfo = call_commit($I, 'Quest', 'loadAllianceQuest', array(
            'uid' => $uid,
        ));
        $I->assertGreaterThen(1, $questInfo['payload']['quests']);

        // start alliance quest
        $questId = $questInfo['payload']['quests'][0]['quest_id'];
        $ret = call_commit($I, 'Quest', 'startAllianceQuest', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertTrue($ret['payload']['ret']);

        // check alliance quest
        $ret = call_commit($I, 'Quest', 'checkAllianceQuest', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertGreaterThen(0, $ret['payload']['status']);

        // collect alliance quest
        $ret = call_commit($I, 'Quest', 'collectAllianceQuestReward', array(
            'uid' => $uid,
            'quest_id' => $questId,
        ));
        $I->assertFalse($ret['payload']['ret']);

        // refresh alliance quest
        $questInfo = call_commit($I, 'Quest', 'refreshAllianceQuest', array(
            'uid' => $uid,
        ));
        $I->assertGreaterThen(1, $questInfo['payload']['quests']);
    }
}

// create alliance and test
$allianceInfo = call_commit($I, 'Alliance', 'createAlliance', array(
    'uid' => $uid,
    'name' => random_string(3),
    'acronym' => random_string(3),
));
test_alliance_vip_quest($uid, $I);

// change anthor player
login_with_deviceid($I, $hisId);

// create alliance and test
$allianceInfo = call_commit($I, 'Alliance', 'createAlliance', array(
    'uid' => $his_uid,
    'name' => random_string(3),
    'acronym' => random_string(3),
));
$I->assertGreaterThenOrEqual(1, $allianceInfo['payload']['allianceId']);
test_alliance_vip_quest($his_uid, $I);
