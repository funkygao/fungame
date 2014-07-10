<?php

// world map
$map = call_commit($I, 'Map', 'loadKingdom', array(
    'kingdom_id' => 1,
));
$I->assertGreaterThenOrEqual(1, count($map['payload']));
$I->assertGreaterThenOrEqual(1, $map['payload'][0]['city_id']);

// world map block "x1":0,"y1":0,"x2":100,"y2":100
$map = call_commit($I, 'Map', 'loadKingdomBlock', array(
    'kingdom_id' => 1,
    'x1' => 0,
    'y1' => 0,
    'x2' => 101,
    'y2' => 102,
));
$I->assertEquals(1, $map['payload'][0]['world_id']);
$I->assertGreaterThenOrEqual(0, $map['payload'][0]['map_x']);
$I->assertGreaterThenOrEqual($map['payload'][0]['map_x'], 101);

// load kingdom tile
$map = call_commit($I, 'Map', 'kingdomTile', array(
    'k' => 1,
    'x' => 0,
    'y' => 0,
));
$I->assertGreaterThenOrEqual(0, $map['payload']['power']);