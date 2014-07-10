<?php

login_with_deviceid($I, $myId);

// check secret gift
$giftInfo = call_commit($I, 'Gift', 'checkSecretGift', array(
    'uid' => $uid,
));
$I->assertGreaterThen(1, $giftInfo['payload']['reward_id']);

// accept secret gift
$giftInfo = call_commit($I, 'Gift', 'acceptSecretGift', array(
    'uid' => $uid,
));
$I->assertFalse($giftInfo['payload']['result']);

// send alliance gift
$giftInfo = call_commit($I, 'Gift', 'sendAllianceGift', array(
    'uid' => $uid,
    'type_id' => 2000002,
));
$I->assertTrue($giftInfo['payload']['result']);

// get alliance gift
$giftInfo = call_commit($I, 'Gift', 'getAllianceGift', array(
    'uid' => $uid,
));
$I->assertGreaterThenOrEqual(1, $giftInfo['payload']['list']);
$giftId = $giftInfo['payload']['list'][0]['gift_id'];

// open alliance gift
$giftInfo = call_commit($I, 'Gift', 'openAllianceGift', array(
    'uid' => $uid,
    'gift_id' => $giftId
));
$I->assertGreaterThen(1, $giftInfo['payload']['reward_id']);

// clear alliance gift
$giftInfo = call_commit($I, 'Gift', 'clearAllianceGift', array(
    'uid' => $uid,
    'gift_id' => $giftId
));
$I->assertTrue($giftInfo['payload']['result']);
