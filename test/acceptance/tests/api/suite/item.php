<?php

$shopConfig = \System\GameData::getInstance('Shop');
$line = $shopConfig->getById('SHOPITEM_WOOD_SMALL2'); // add wood 2400
// Buy item
call_commit($I, 'Item', 'buyConsumableItem', array(
    'uid' => $uid,
    'amount' => 1,
    'item_id' => $line['Internal_ID'],
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));

// validate item added
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$currentConsumablesN = count($user_data['payload']['consumables']);
$woodN = $user_data['payload']['city']['info']['resource']['resource']['wood'];
$I->assertGreaterThenOrEqual(1, $currentConsumablesN);

// Use item
call_commit($I, 'Item', 'useConsumableItem', array(
    'uid' => $uid,
    'city_id' => $city_id,
    'amount' => 1,
    'item_id' => $line['Internal_ID'],
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));

// validate item consumed
list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);
$currentConsumablesN1 = count($user_data['payload']['consumables']);
$I->assertEquals($currentConsumablesN, $currentConsumablesN1 + 1);

// validate wood added
$I->assertEquals($woodN + 2400,
    $user_data['payload']['city']['info']['resource']['resource']['wood']);
