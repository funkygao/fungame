<?php

/**
 * HTTP status checker for haproxy.
 */
require_once realpath(dirname(__FILE__)) . '/../main/init.php';

\System\Application::getInstance(\System\RequestHandler::getInstance(),
    \System\ResponseHandler::getInstance())
    ->init();

echo 'OK'; // 'ERROR' shows web server is down

