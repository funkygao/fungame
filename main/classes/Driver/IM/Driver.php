<?php

namespace Driver\IM;

interface Driver {

    public function init();

    /**
     * @param string $channel Channel name
     * @param string $cmd
     * @param array $params
     * @return bool
     */
    public function publish($channel, $cmd, array $params);

}
