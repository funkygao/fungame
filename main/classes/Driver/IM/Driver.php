<?php

namespace Driver\IM;

interface Driver {

    public function init();

    /**
     * @param string $channel Channel name
     * @param string $cmd
     * @param array $payload
     * @param array $data
     * @return bool
     */
    public function publish($channel, $cmd, array $payload, array $data = null, $flag = 0, $sender = 0);

}
