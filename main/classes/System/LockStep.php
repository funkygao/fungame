<?php

namespace System;

final class LockStep {

    private static $_lockKeys = array();

    // github.com/funkygao/lockkey
    private static function _keyOfUser($uid) {
        return "lk:u:$uid";
    }

    // github.com/funkygao/lockkey
    private static function _keyOfAttackee($k, $x, $y) {
        return "lk:m:$k,$x,$y";
    }

    public static function lockUser($uid, $reason = '') {
        $key = self::_keyOfUser($uid);
        if (isset(self::$_lockKeys[$key])) {
            // reentrant safe, in case of lock 1 user twice
            return;
        }

        if (!$reason) {
            $reason = \System\RequestHandler::getInstance()->faeReason();
        }

        if (\FaeEngine::client()->gm_lock(\FaeEngine::ctx(), $reason, $key)) {
            // we've accquired the user lock successfully
            self::$_lockKeys[$key] = TRUE;
        } else {
            throw new \LockException($uid);
        }
    }

    public static function releaseAll() {
        static $releasedAlready = FALSE;
        if (empty(self::$_lockKeys) || TRUE === $releasedAlready) {
            return;
        }

        $fae = \FaeEngine::client();
        $ctx = \FaeEngine::ctx();
        $reason = \System\RequestHandler::getInstance()->faeReason();
        foreach (self::$_lockKeys as $key => $_) {
            $fae->gm_unlock($ctx, $reason, $key);
        }

        $releasedAlready = TRUE;
    }

}
