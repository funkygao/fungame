<?php

defined('DOC_ROOT') or define('DOC_ROOT', realpath(dirname(__FILE__) . DIRECTORY_SEPARATOR . '../') . DIRECTORY_SEPARATOR);
defined('DATA_PATH') or define('DATA_PATH', DOC_ROOT . 'data' . DIRECTORY_SEPARATOR);
defined('CONFIG_PATH') or define('CONFIG_PATH', DATA_PATH . 'config' . DIRECTORY_SEPARATOR);
defined('FUN_PATH') or define('FUN_PATH', DOC_ROOT . 'main' . DIRECTORY_SEPARATOR);
defined('FUNCLASSES_PATH') or define('FUNCLASSES_PATH', FUN_PATH . 'classes' . DIRECTORY_SEPARATOR);
defined('FUNLIB_PATH') or define('FUNLIB_PATH', FUN_PATH . 'libraries' . DIRECTORY_SEPARATOR);
require_once(FUN_PATH . "functions.php");
set_include_path(get_include_path() . PATH_SEPARATOR . FUNLIB_PATH);

// macros for time related
defined('REQUEST_TIME') or define('REQUEST_TIME', isset($_SERVER['REQUEST_TIME']) ? $_SERVER['REQUEST_TIME'] : time());
defined('REQUEST_TIME_FLOAT') or define('REQUEST_TIME_FLOAT', microtime(TRUE));

// autoloader
final class ClassLoader {

    private $namespaceFallbacks = array();
    private $prefixFallbacks = array();

    public function registerNamespaceFallbacks(array $dirs) {
        $this->namespaceFallbacks = $dirs;
        return $this;
    }

    public function registerPrefixFallback($dir) {
        $this->prefixFallbacks[] = $dir;
        return $this;
    }

    public function register($prepend = false) {
        spl_autoload_register(array($this, 'loadClass'), true, $prepend);
    }

    public function loadClass($class) {
        if ($file = $this->findFile($class)) {
            require $file;
        }
    }

    public function findFile($class) {
        if ('\\' == $class[0]) {
            $class = substr($class, 1);
        }

        if (FALSE !== $pos = strrpos($class, '\\')) {
            // namespaced class name
            $namespace = substr($class, 0, $pos);
            $className = substr($class, $pos + 1);
            $normalizedClass = str_replace('\\', DIRECTORY_SEPARATOR, $namespace)
                . DIRECTORY_SEPARATOR . str_replace('_', DIRECTORY_SEPARATOR, $className)
                . '.php';
            foreach ($this->namespaceFallbacks as $dir) {
                $file = $dir . DIRECTORY_SEPARATOR . $normalizedClass;
                if (is_file($file)) {
                    return $file;
                }
            }
        } else {
            // PEAR-like class name
            $normalizedClass = str_replace('_', DIRECTORY_SEPARATOR, $class) . '.php';
            foreach ($this->prefixFallbacks as $dir) {
                $file = $dir . DIRECTORY_SEPARATOR . $normalizedClass;
                if (is_file($file)) {
                    return $file;
                }
            }
        }
    }

}

$classLoader = new ClassLoader();
$classLoader->registerNamespaceFallbacks(array(FUNCLASSES_PATH))
    ->registerPrefixFallback(FUNLIB_PATH)
    ->register();

// for performance, we place all our custom exceptions here

/**
 * 预料之中的异常，客户端不会restart game.
 *
 * 抛出此类异常时，通常会把额外返回payload信息，以便客户端知道如何处理
 */
class ExpectedErrorException extends \Exception {

    /**
     * @var array
     */
    private $_payload;

    public final function __construct($msg = '', $code = 0,
                                      $data = array(), \Exception $previous = null) {
        $this->_payload = array(
            'errno' => $code,
            'errmsg' => $msg,
            'data' => $data, // client need this key to extract payload map
        );
        parent::__construct(json_encode($this->_payload), $code, $previous);
    }

    public final function getPayload() {
        return $this->_payload;
    }

}
class RestartGameException extends \Exception {}
class CheatingException extends \RestartGameException {}
class MaintainException extends \Exception {}
class NotImplementedException extends \Exception {}
class OptimisticLockException extends \Exception {}
class HttpNotFoundException extends \Exception {}
