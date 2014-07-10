<?php

function json_pretty($str) {
    $json = json_decode($str);
    return json_encode($json, JSON_PRETTY_PRINT);
}

function is_assoc(array $array) {
    return !isset($array[0]);
}

/**
 * Convert unix timestamp(int) to mysql timestamp(string).
 *
 * @param int $unix_timestamp
 * @return string
 */
function ts_unix2mysql($unix_timestamp) {
    return date('Y-m-d H:i:s', $unix_timestamp); // TODO gmdate
}

/**
 * Convert mysql timestamp(string) to unix timestamp(int).
 *
 * @param $mysql_timestamp
 * @return int
 */
function ts_mysql2unix($mysql_timestamp) {
    return strtotime($mysql_timestamp);
}

function num_zero_fill($num, $pad_len = 2) {
    return str_pad($num, $pad_len, '0', STR_PAD_LEFT);
}

function str_startswith($haystack, $needle) {
    return $needle === "" || strpos($haystack, $needle) === 0;
}

function str_endswith($haystack, $needle) {
    return $needle === "" || substr($haystack, -strlen($needle)) === $needle;
}

function str_contains($haystack, $needle) {
    return strpos($haystack, $needle) !== FALSE;
}

function str_icontains($haystack, $needle) {
    return stripos($haystack, $needle) !== FALSE;
}

function get_class_basename($object) {
    if (!is_object($object)) {
        return NULL;
    }

    $fullQualifiedClassName = explode('\\', get_class($object));
    return $fullQualifiedClassName[count($fullQualifiedClassName) - 1];
}

function array_deep_get(array $arr, $path, $default = NULL) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    foreach ($pieces as $piece) {
        if (!is_array($arr) || !isset($arr[$piece])) {
            // not found
            return $default;
        }
        $arr = $arr[$piece];
    }
    return $arr;
}

function array_deep_set(array &$arr, $path, $val) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    for ($i = 0, $n = count($pieces) - 1; $i < $n; $i++) {
        $arr = &$arr[$pieces[$i]];
    }
    $arr[$pieces[$n]] = $val;
}

function array_deep_del(array &$arr, $path) {
    $path = rtrim($path, '.'); // remove last char if it is '.'
    $pieces = explode('.', $path);
    for ($i = 0, $n = count($pieces) - 1; $i < $n; $i++) {
        $arr = &$arr[$pieces[$i]];
    }
    unset($arr[$pieces[$n]]);
}

function time_before_today($timestamp) {
    static $todayBeginsAt = NULL;
    if ($todayBeginsAt === NULL) {
        $todayBeginsAt = strtotime(gmdate("Y-m-d\T00:00:00\Z")); // FIXME
    }

    if ($timestamp < $todayBeginsAt) {
        return true;
    }
    return false;
}

function in_between($min, $max, $in) {
    if($min <= $in && $max >= $in) {
        return TRUE;
    }

    return FALSE;
}

function random_string($length) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
        0, $length);
}

// TODO not fully tested
function get_caller_info() {
    $trace = debug_backtrace();
    $size = (count($trace) - 1);
    while ($size) {
        $test = (strpos($trace[0]['function'], 'call_user_fun') === FALSE ? 0 : 1);
        $test += (strpos($trace[0]['function'], 'query') === FALSE ? 0 : 1);
        $test += (strpos($trace[0]['class'], 'Shard') === FALSE ? 0 : 1);
        $test += (strpos($trace[0]['function'], 'getCallInfo') === FALSE ? 0 : 1);
        $test += (stripos($trace[0]['class'], 'Cache') === FALSE ? 0 : 1);
        $test += (stripos($trace[0]['class'], 'Database') === FALSE ? 0 : 1);
        $test += (stripos($trace[0]['class'], 'Profiler') === FALSE ? 0 : 1);
        $test += (stripos($trace[0]['class'], 'DataTables') === FALSE ? 0 : 1);

        if (!$test) {
            return $trace[0];
        }

        array_shift($trace);
        $size = (count($trace) - 1);
    }
}

function get_client_ip() {
    if (isset($_SERVER['HTTP_X_REAL_IP'])) {
        // haproxy will pass in this env var
        $ip = $_SERVER['HTTP_X_REAL_IP'];
    } elseif (isset($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } elseif (isset($_SERVER['HTTP_CLIENTIP'])) {
        $ip = $_SERVER['HTTP_CLIENTIP'];
    } elseif (isset($_SERVER['REMOTE_ADDR'])) {
        $ip = $_SERVER['REMOTE_ADDR'];
    } elseif (getenv('HTTP_X_FORWARDED_FOR')) {
        $ip = getenv('HTTP_X_FORWARDED_FOR');
    } elseif (getenv('HTTP_CLIENTIP')) {
        $ip = getenv('HTTP_CLIENTIP');
    } elseif (getenv('REMOTE_ADDR')) {
        $ip = getenv('REMOTE_ADDR');
    } else {
        $ip = '127.0.0.1';
    }

    $pos = strpos($ip, ',');
    if ($pos > 0) {
        $ip = substr($ip, 0, $pos);
    }

    return trim($ip);
}

/**
 * Get current request context.
 *
 * Client(flash/mobile) will pass in session id(sid) so that we can
 * trace each session's(sid) each request(callee).
 *
 * @return array {'callee':s, 'host':s, 'ip':s, 'seq':i, 'sid':s, 'elapsed':f}
 */
function request_ctx() {
    static $request_ctx = array();
    static $seq = 0; // within a single request

    $request_ctx['seq'] = ++$seq;
    $request_ctx['elapsed'] = 1000 * (microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT']); // ms

    if (isset($request_ctx['callee'])) {
        return $request_ctx;
    }

    // the following keeps the same within a http request
    $request_ctx['host'] = empty($_SERVER['SERVER_ADDR']) ? 'localhost' : $_SERVER['SERVER_ADDR']; // current web server ip
    $request_ctx['ip'] = get_client_ip();
    $request_ctx['sid'] = empty($_REQUEST['sid']) ? 0 : $_REQUEST['sid']; // client(e,g. flash) passed in session id
    $uri = empty($_SERVER['REQUEST_URI']) ? get_included_files()[0] : $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri, PHP_URL_PATH); // normalize
    $method = empty($_SERVER['REQUEST_METHOD']) ? 'CLI' : $_SERVER['REQUEST_METHOD'];
    $request_ctx['callee'] = $method . '+' . $uri . '+' . dechex(mt_rand());

    return $request_ctx;
}

