<?php

// Mail count
$mailInfo = call_commit($I, 'Mail', 'getCount', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_NEW,
));
$I->assertGreaterThenOrEqual(0, $mailInfo['payload']['count']);

// Mail getList
$mailInfo = call_commit($I, 'Mail', 'getList', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_NORMAL,
    'last_mail_id' => 0,
    'limit' => 100,
));
$I->assertGreaterThenOrEqual(0, $mailInfo['payload']['total']);
//
// compose Mail
$mailInfo = call_commit($I, 'Mail', 'composeMail', array(
    'uid' => 1,
    'to_uid' => $uid,
    'subject' => 'Re: test',
    'body' => 'hello, world!',
));
$I->assertEquals(TRUE, $mailInfo['payload']['ret']);

// Mail getList
$mailInfo = call_commit($I, 'Mail', 'getList', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_NEW | \Consts\MailConst::STATUS_NORMAL,
    'last_mail_id' => 0,
    'limit' => 1,
));
$I->assertGreaterThenOrEqual(0, $mailInfo['payload']['total']);

$mailId = $mailInfo['payload']['list'][0]['mail_id'];

// Mail getList
$mailInfo = call_commit($I, 'Mail', 'getList', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_NEW | \Consts\MailConst::STATUS_NORMAL,
    'last_mail_id' => 0,
    'limit' => 1,
));
$I->assertEquals(0, $mailInfo['payload']['total']);

// tag read
$mailInfo = call_commit($I, 'Mail', 'tagRead', array(
    'uid' => $uid,
    'mail_id' => $mailId,
));
$I->assertEquals(TRUE, $mailInfo['payload']['ret']);

// tag mark
$mailInfo = call_commit($I, 'Mail', 'tagMark', array(
    'uid' => $uid,
    'mail_id' => $mailId,
    'flag' => 1,
));
$I->assertEquals(TRUE, $mailInfo['payload']['ret']);

// get mail
$mailInfo = call_commit($I, 'Mail', 'getMail', array(
    'uid' => $uid,
    'mail_id' => $mailId,
));
$I->assertNotEmpty($mailInfo['payload']);
$I->assertNotEmpty($mailInfo['payload'][0]);
$I->assertEquals($uid, $mailInfo['payload'][0]['to_uid']);

// Mail getList
$mailInfo = call_commit($I, 'Mail', 'getList', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_MARK | \Consts\MailConst::STATUS_NORMAL,
    'last_mail_id' => 0,
    'limit' => 1,
));
$I->assertNotEmpty($mailInfo['payload']['list']);
$I->assertNotEmpty($mailInfo['payload']['list'][0]);
$I->assertGreaterThen(0, $mailInfo['payload']['total']);
$I->assertEquals(0, $mailInfo['payload']['list'][0]['status'] & \Consts\MailConst::STATUS_NEW);
$I->assertGreaterThen(0, $mailInfo['payload']['list'][0]['status'] & \Consts\MailConst::STATUS_MARK);

// delete
$mailInfo = call_commit($I, 'Mail', 'deleteMail', array(
    'uid' => $uid,
    'mail_id' => $mailId,
));
$I->assertEquals(TRUE, $mailInfo['payload']['ret']);

// checking deleting
$mailInfo = call_commit($I, 'Mail', 'getList', array(
    'uid' => $uid,
    'filter' => \Consts\MailConst::STATUS_MARK | \Consts\MailConst::STATUS_NORMAL,
    'last_mail_id' => 0,
    'limit' => 1,
));
$I->assertEmpty($mailInfo['payload']['list']);

