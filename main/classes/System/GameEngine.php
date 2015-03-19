<?php

namespace System;

final class GameEngine
    implements \Consts\ErrnoConst {

    const
        REG_USER = 'u',
        REG_CHAT = 'c',
        REG_KINGDOM = 'k',
        REG_ALLIANCE = 'a';

    const
        RESERVE_USERNAME = 'u',
        RESERVE_ALLIANCE_NAME = 'a',
        RESERVE_ALLIANCE_TAG = 'at';

    /**
     * @var \fun\rpc\FunServantClient
     */
    private $_faeClient;

    /**
     * @var \fun\rpc\Context
     */
    private $_faeCtx;

    /**
     * @return GameEngine
     */
    public static function instance() {
        static $instance = NULL;
        if ($instance == NULL) {
            $instance = new self(\FaeEngine::client(), \FaeEngine::ctx());
        }

        return $instance;
    }

    private function __construct($fae, $ctx) {
        $this->_faeClient = $fae;
        $this->_faeCtx = $ctx;
    }

    /**
     * @return int
     */
    public function regKingdomId() {
        return $this->_faeClient->gm_register($this->_faeCtx, self::REG_KINGDOM);
    }

    /**
     * @return int
     */
    public function regUserShardId() {
        return $this->_faeClient->gm_register($this->_faeCtx, self::REG_USER);
    }

    /**
     * @return int
     */
    public function regAllianceShardId() {
        return $this->_faeClient->gm_register($this->_faeCtx, self::REG_ALLIANCE);
    }

    /**
     * @return int
     */
    public function regChatShardId() {
        return $this->_faeClient->gm_register($this->_faeCtx, self::REG_CHAT);
    }

    /**
     * @return string
     */
    public function recommendAllianceTag() {
        return $this->_faeClient->gm_name3($this->_faeCtx);
    }

    public function reportRequestSummary($payloadSize) {
        $this->_faeClient->gm_latency($this->_faeCtx,
            (int)((microtime(TRUE) - REQUEST_TIME_FLOAT) * 1000),
            $payloadSize
        );
    }

    /**
     * @param string $typ
     * @param string $name
     * @param string $oldName for rename
     * @throws \ExpectedErrorException
     * TODO UserLookupModel  AllianceLookupModel  Consumable/Rename
     */
    public function reserveName($typ, $name, $oldName = '') {
        if (!$this->_faeClient->gm_reserve($this->_faeCtx,
            $typ, $oldName, $name)) {
            throw new \ExpectedErrorException("dup $typ:$name",
                self::ERRNO_SYS_DUPLICATED);
        }
    }

    /**
     * @param array $uids List of uid, e,g. array(15, 88, 9)
     * @return bool[] e,g. array(true, false, true)
     */
    public function onlineStatus(array $uids) {
        return $this->_faeClient->gm_presence(
            $this->_faeCtx, $uids
        );
    }

}
