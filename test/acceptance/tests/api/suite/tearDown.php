<?php

// kill me 
call_commit($I, 'Player', 'resetAccountByUUID', array(
    'uuid' => $myId,
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));

// kill him
call_commit($I, 'Player', 'resetAccountByUUID', array(
    'uuid' => $hisId,
));
$I->seeResponseContainsJson(array('payload' => array('ret' => TRUE)));
