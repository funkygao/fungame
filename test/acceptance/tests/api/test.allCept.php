<?php 
$I = new ApiTester($scenario);

$myId = random_string(20);
$I->wantTo("Make sure the whole game backend works fine!");

list($uid, $city_id, $user_data) = login_with_deviceid($I, $myId);

// mobileId, uid, city_id, user_data will be passed into each suite

require_once 'suite/building.php';
require_once 'suite/item.php';
require_once 'suite/alliance.php';
require_once 'suite/mail.php';
require_once 'suite/hero.php';
require_once 'suite/quest.php';
require_once 'suite/gift.php';
require_once 'suite/pve.php';
require_once 'suite/pvp.php';
require_once 'suite/map.php';
require_once 'suite/tearDown.php';
