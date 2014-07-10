<?php
require_once(__DIR__ . '/../../../../main/init.php');

function login_with_deviceid($I, $deviceId) {
    call_dragonwar($I, 'call', 'init', array(
        'mobile_id' => $deviceId,
    ));
    $uid = $I->grabDataFromJsonResponse('payload.user.info.uid');
    $city_id = $I->grabDataFromJsonResponse('payload.city.info.city_id');
    $I->assertGreaterThen(2, $uid);
    $I->assertGreaterThen(2, $city_id);
    $I->assertEquals(1, $I->grabDataFromJsonResponse('ok'));
    return array($uid, $city_id, json_decode($I->grabResponse(), TRUE));
}

function call_dragonwar($I, $class, $method, array $params) {
    $I->sendPOST('index.php', array(
        'class' => $class,
        'method' => $method,
        'params' => json_encode($params),
    ));

    $response = json_decode($I->grabResponse(), TRUE);
    $msg = isset($response['msg']) ? $response['msg'] : '';
    $I->assertEquals('', $msg);
    $I->seeResponseCodeIs(200);
    $I->seeResponseIsJson();
}

function call_commit($I, $class, $method, array $params) {
    static $seq = 1;
    $uid = isset($params['uid']) ? $params['uid'] : 1;
    $I->sendPOST('index.php', array(
        'class' => 'call',
        'method' => 'commit',
        'seq' => $seq,
        'params' => json_encode(array(
            'ct' => time(), // commit time
            'uid' => $uid,
            'cmds' => array(
                array(
                    'op' => $class . ':' . $method,
                    'at' => time(),
                    'args' => $params,
                )
            )
        )),
    ));
    $seq ++;

    $response = json_decode($I->grabResponse(), TRUE);
    $msg = isset($response['msg']) ? $response['msg'] : '';
    $I->assertEquals('', $msg);
    $I->seeResponseCodeIs(200);
    $I->seeResponseIsJson();
    $I->seeResponseContainsJson(array('ok' => 1));
    return $response;
}
