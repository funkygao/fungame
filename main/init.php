<?php

// Set the full path to the docroot
defined('DOC_ROOT') or define('DOC_ROOT', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../') . DIRECTORY_SEPARATOR);

// Define the application path to the docroot
defined('APP_PATH') or define('APP_PATH', DOC_ROOT . 'application' . DIRECTORY_SEPARATOR);

// Define the system path to the docroot
defined('SYS_PATH') or define('SYS_PATH', DOC_ROOT . 'libraries' . DIRECTORY_SEPARATOR);

// Define the modules path to the docroot
defined('MOD_PATH') or define('MOD_PATH', DOC_ROOT . 'modules' . DIRECTORY_SEPARATOR);

// Define the system path to the docroot
defined('DATA_PATH') or define('DATA_PATH', DOC_ROOT . 'data' . DIRECTORY_SEPARATOR);

defined('CONFIG_PATH') or define('CONFIG_PATH', DATA_PATH . 'game' . DIRECTORY_SEPARATOR);

defined('TEMPLATE_PATH') or define('TEMPLATE_PATH', DOC_ROOT . 'template' . DIRECTORY_SEPARATOR);

defined('WEB_ROOT') or define('WEB_ROOT', DOC_ROOT . 'docroot' . DIRECTORY_SEPARATOR);

defined('ASSETS_PATH') or define('ASSETS_PATH', WEB_ROOT . 'assets' . DIRECTORY_SEPARATOR);

// the current path
defined('V2_PATH') or define('V2_PATH', DOC_ROOT . 'v2' . DIRECTORY_SEPARATOR);

// the classes under v2
defined('V2CLASSES_PATH') or define('V2CLASSES_PATH', DOC_ROOT . 'v2' . DIRECTORY_SEPARATOR . 'classes' . DIRECTORY_SEPARATOR);

// the libraries under v2
defined('V2LIB_PATH') or define('V2LIB_PATH', V2_PATH . 'libraries' . DIRECTORY_SEPARATOR);
set_include_path(get_include_path() . PATH_SEPARATOR . V2LIB_PATH);

// macros for time related
defined('REQUEST_TIME') or define('REQUEST_TIME', $_SERVER['REQUEST_TIME']);
defined('REQUEST_TIME_FLOAT') or define('REQUEST_TIME_FLOAT', microtime(TRUE));

// set up the new autoloader using namespaces
require_once(V2_PATH . "UniversalClassLoader.php");
require_once(V2_PATH . "functions.php");

$classLoader = new Symfony\Component\ClassLoader\UniversalClassLoader(); // this is the only thing used from Symfony
$classLoader->registerNamespaceFallbacks(array(V2CLASSES_PATH));
$classLoader->registerPrefixFallback(V2LIB_PATH);
$classLoader->register();

