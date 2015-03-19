<?php
/**
 * The front controller.
 *
 * Main entry point of the game backend.
 */
require_once realpath(dirname(__FILE__)) . '/../../main/init.php';

\System\Application::getInstance(\System\RequestHandler::getInstance(),
    \System\ResponseHandler::getInstance())
    ->init()
    ->execute();
