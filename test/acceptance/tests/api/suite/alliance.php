<?php

// Create alliance
$allianceInfo = call_commit($I, 'Alliance', 'createAlliance', array(
    'uid' => $uid,
    'name' => random_string(3),
    'acronym' => random_string(3),
));
$I->assertGreaterThenOrEqual(1, $allianceInfo['payload']['allianceId']);
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$allianceId = $user_data['payload']['user']['info']['alliance_id'];
$tag = $user_data['payload']['alliance']['info']['acronym'];
$name = $user_data['payload']['alliance']['info']['name'];
$I->assertEquals($allianceInfo['payload']['allianceId'], $allianceId);

// modify alliance
$ret = call_commit($I, 'Alliance', 'modifyAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'name' => $name,
    'tag' => $tag,
));
$I->assertTrue($ret['payload']['data']);

// check name
login_with_deviceid($I, $myId);
$ret = call_commit($I, 'Alliance', 'checkAllianceNameAvailable', array(
    'name' => $name,
));
$I->assertFalse($ret['payload']['data']);

// check tag
$ret = call_commit($I, 'Alliance', 'checkAllianceAcronymAvailable', array(
    'name' => $tag,
));
$I->assertFalse($ret['payload']['data']);

// Search alliance by user
$users = call_commit($I, 'Alliance', 'searchAllianceByUser', array(
    'context' => 'player',
));
$I->assertGreaterThenOrEqual(0, count($users['payload']));

// change another player
$hisId = random_string(20);
list($his_uid, $his_city_id, $his_user_data) = login_with_deviceid($I, $hisId);

// join alliance
$ret = call_commit($I, 'Alliance', 'joinAlliance', array(
    'uid' => $his_uid,
    'alliance_id' => $allianceId,
));
$I->assertTrue($ret['payload']['ret']);

// leave alliance
login_with_deviceid($I, $hisId);
$ret = call_commit($I, 'Alliance', 'leaveAlliance', array(
    'uid' => $his_uid,
    'alliance_id' => $allianceId,
));
$I->assertTrue($ret['payload']['ret']);

// setup alliance
login_with_deviceid($I, $myId);
$ret = call_commit($I, 'Alliance', 'setupAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'is_private' => 'private',
));
$I->assertTrue($ret['payload']['ret']);

// apply alliance
login_with_deviceid($I, $hisId);
$ret = call_commit($I, 'Alliance', 'applyAlliance', array(
    'uid' => $his_uid,
    'alliance_id' => $allianceId,
));
$I->assertTrue($ret['payload']['ret']);

// revoke apply
$ret = call_commit($I, 'Alliance', 'revokeApplyByUser', array(
    'alliance_id' => $allianceId,
    'apply_uid' => $his_uid,
));
$I->assertTrue($ret['payload']['data']);

// apply again
login_with_deviceid($I, $hisId);
$ret = call_commit($I, 'Alliance', 'applyAlliance', array(
    'uid' => $his_uid,
    'alliance_id' => $allianceId,
));
$I->assertTrue($ret['payload']['ret']);

// get apply by user
$ret = call_commit($I, 'Alliance', 'getApplyListByUser', array(
    'apply_uid' => $uid,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['apply']);

// get apply by alliance
login_with_deviceid($I, $myId);
$ret = call_commit($I, 'Alliance', 'getApplyListByAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['apply']);

// deny apply
$ret = call_commit($I, 'Alliance', 'AcceptOrDenyApplyByAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'apply_uid' => $his_uid,
    'type' => 'deny',
));
$I->assertTrue($ret['payload']['data']);

// invite user
$ret = call_commit($I, 'Alliance', 'allianceSendInvite', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'invite_uid' => $his_uid,
));
$I->assertTrue($ret['payload']['data']);

// revoke invite
$ret = call_commit($I, 'Alliance', 'revokeInviteByAlliance', array(
    'alliance_id' => $allianceId,
    'invite_uid' => $his_uid,
));
$I->assertTrue($ret['payload']['data']);

// invite again
$ret = call_commit($I, 'Alliance', 'allianceSendInvite', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'invite_uid' => $his_uid,
));
$I->assertTrue($ret['payload']['data']);

// get sent invite by alliance
$ret = call_commit($I, 'Alliance', 'getAllianceSentInvitations', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['allianceList']);

// get invite by user
login_with_deviceid($I, $hisId);
$ret = call_commit($I, 'Alliance', 'getInviteByUser', array(
    'uid' => $his_uid,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['allianceList']);

// accept invite
$ret = call_commit($I, 'Alliance', 'denyOrAcceptInviteByUser', array(
    'uid' => $his_uid,
    'alliance_id' => $allianceId,
    'type' => 'accept'
));
$I->assertTrue($ret['payload']['data']);

// kick user
login_with_deviceid($I, $myId);
$ret = call_commit($I, 'Alliance', 'kickUserFromAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'kick_uid' => $his_uid
));
$I->assertTrue($ret['payload']['ret']);

// ban user
$ret = call_commit($I, 'Alliance', 'banByAdmin', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
    'banned_uid' => $his_uid,
    'operate' => 'ban',
));
$I->assertTrue($ret['payload']['ret']);

// get banned list
$ret = call_commit($I, 'Alliance', 'getBannedListByAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
));
$I->assertGreaterThenOrEqual(1, $ret['payload']['list']);

// disband alliance
$ret = call_commit($I, 'Alliance', 'disbandAlliance', array(
    'uid' => $uid,
    'alliance_id' => $allianceId,
));
$I->assertTrue($ret['payload']['ret']);
