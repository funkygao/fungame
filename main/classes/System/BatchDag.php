<?php

namespace System;

/**
 * DAG(directed acyclic graph) that manages cmds within a batch request.
 *
 * Currently, we plan to merge ActiveRecord get() and getAll to reduce
 * uneccessary extra DB query.
 */
final class BatchDag {

    /**
     * @var array[]
     */
    private $_commands = NULL;

    public static function getInstance() {
        static $instance = NULL;
        if (NULL === $instance) {
            $instance = new self();
        }

        return $instance;
    }

    private function __construct() { }

    public function feedCommands(array $commands) {
        if ($this->_commands !== NULL) {
            throw new \InvalidArgumentException("Cannot be called twice");
        }

        $this->_commands = $commands;
    }

    /**
     * @param array $cmd1 e,g. {'op': 'Player:loadPlayerInfo'}
     * @param array $cmd2
     * @return bool
     */
    public function hasPathTo(array $cmd1, array $cmd2) {

    }

}
