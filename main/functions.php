<?php

function json_pretty($str) {
    $json = json_decode($str);
    return json_encode($json, JSON_PRETTY_PRINT);
}

function is_assoc(array $array) {
    return !isset($array[0]);
}

function shuffle_assoc(array &$assoc) {
    $keys = array_keys($assoc);
    shuffle($keys);
    $random = array();
    foreach ($keys as $key) {
        $random[$key] = $assoc[$key];
    }
    $assoc = $random;
}

/**
 * Weighted rand.
 *
 * http://gamedev.stackexchange.com/questions/60299/data-structure-for-random-outcome-from-a-hit-probability-table
 *
 * @param array $weightedValues e,g array('Ore'=> 20, 'Food'=> 40, 'Silver'=> 10, 'Wood'=> 20)
 * @return string
 */
function wrand(array $weightedValues) {
    $rand = mt_rand(1, (int)array_sum($weightedValues));

    foreach ($weightedValues as $key => $value) {
        $rand -= $value;
        if ($rand <= 0) {
            return $key;
        }
    }
}

function random_string($length) {
    return substr(str_shuffle("0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ"),
        0, $length);
}

function rand_percent($percent) {
    return rand(1, 100) <= $percent;
}

function xor_encrypt($message, $key) {
    $messageLen = strlen($message);
    $keyLen = strlen($key);
    $newmsg = '';

    for ($i = 0; $i < $messageLen; $i++) {
        $newmsg = $newmsg . ($message[$i] ^ $key[$i % $keyLen]);
    }

    return base64_encode($newmsg);
}

function xor_decrypt($encrypted_message, $key) {
    $msg = base64_decode($encrypted_message);
    $messageLen = strlen($msg);
    $keyLen = strlen($key);
    $newmsg = '';

    for ($i = 0; $i < $messageLen; $i++){
        $newmsg = $newmsg . ($msg[$i] ^ $key[$i % $keyLen]);
    }

    return $newmsg;
}

/**
 * Convert unix timestamp(int) to mysql timestamp(string).
 *
 * @param int $unix_timestamp
 * @return string
 */
function ts_unix2mysql($unix_timestamp) {
    return gmdate('Y-m-d H:i:s', $unix_timestamp); // because mysql uses UTC timezone, timestamp col has no zone info
}

/**
 * Convert mysql timestamp(string) to unix timestamp(int).
 *
 * @param string $mysql_timestamp e,g. '2014-07-28 10:36:19'
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

/**
 * FIXME 考虑更好的解决方案防止空数据产生
 * 过滤掉数组里所有的NULL 和 ''
 * @param array $arr
 * @return array
 */
function array_deep_filter($arr) {
    if (is_array($arr)) { // recursive, so it's required
        $arr = array_filter($arr, function ($var) {
            return ($var !== NULL && $var !== '');
        });
        foreach ($arr as $k => $v) {
            $arr[$k] = array_deep_filter($v);
        }
    }
    return $arr;
}

function rest_call($url, $timeout = 4) {
    static $handle = NULL; // reuse connections to the same server
    if ($handle === NULL) {
        $handle = curl_init();
    }

    curl_setopt($handle, CURLOPT_URL, $url);
    curl_setopt($handle, CURLOPT_HTTPHEADER, array(
        'Accept: application/json',
        'Content-Type: application/json',
    ));
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
    curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($handle, CURLOPT_TIMEOUT, $timeout); // in sec
    $ret = curl_exec($handle);
    $errno = curl_errno($handle);
    if ($errno > 0) {
        // e,g timeout
        $ret = array();
        $ret['_err'] = $errno;
    } else {
        $ret = json_decode($ret, TRUE);
    }

    //curl_close($handle);
    return $ret;
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

function get_domain() {
    $scheme = $_SERVER['HTTPS'] ? 'https://' : 'http://';
    $port = '';
    if ($_SERVER["SERVER_PORT"] != '80') {
        $port = ':'.$_SERVER["SERVER_PORT"];
    }
    return $scheme.$_SERVER['SERVER_NAME'].$port.'/';
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
    $request_ctx['elapsed'] = round(microtime(TRUE) - $_SERVER['REQUEST_TIME_FLOAT'], 5);

    if (isset($request_ctx['callee'])) {
        return $request_ctx;
    }

    // the following keeps the same within a http request
    $request_ctx['host'] = empty($_SERVER['SERVER_ADDR']) ? 'localhost' : $_SERVER['SERVER_ADDR']; // current web server ip
    $request_ctx['ip'] = get_client_ip();
    // FIXME can't trust client generated sid, TODO will generate on server side, on call.init
    $request_ctx['sid'] = empty($_REQUEST['sid']) ? 0 : $_REQUEST['sid']; // client(e,g. flash) passed in session id
    $uri = empty($_SERVER['REQUEST_URI']) ? get_included_files()[0] : $_SERVER['REQUEST_URI'];
    $uri = parse_url($uri, PHP_URL_PATH); // normalize
    $method = empty($_SERVER['REQUEST_METHOD']) ? 'CLI' : $_SERVER['REQUEST_METHOD'];
    $requestId = dechex(mt_rand()); // keep unchaged within a request
    //$requestId = hexdec(uniqid()); // TODO
    $request_ctx['rid'] = $requestId;
    $request_ctx['callee'] = $method . '+' . $uri . '+' . $requestId;

    return $request_ctx;
}

/**
 * @param $timeStamp
 * @param $timeStamp2 The date time to compare to
 * @return mixed
 */
function days_diff($timeStamp, $timeStamp2) {
    return date_diff(
        date_create(gmdate("y-m-d", $timeStamp)),
        date_create(gmdate("y-m-d", $timeStamp2))
    )->days;
}

function rsa_encrypt($method, $key, $data, $rsa_bit = 1024) {
    $inputLen = strlen($data);
    $offSet = 0;
    $i = 0;
 
    $maxDecryptBlock = $rsa_bit / 8 - 11;
 
    $en = '';
 
    // 对数据分段加密
    while ($inputLen - $offSet > 0) {
        //echo $i . "\n";

        $cache = '';
 
        if ($inputLen - $offSet > $maxDecryptBlock) {
            //echo "a\n";
            $method(substr($data, $offSet, $maxDecryptBlock), $cache, $key);
        } else {
            //echo "b\n";
            $method(substr($data, $offSet, $inputLen - $offSet), $cache, $key);
        }
 
        $en = $en . $cache;
 
        $i++;
        $offSet = $i * $maxDecryptBlock;
    }
    return $en;
}
 
function rsa_decrypt($method, $key, $data, $rsa_bit = 1024) {
    $inputLen = strlen($data);
    $offSet = 0;
    $i = 0;
 
    $maxDecryptBlock = $rsa_bit / 8;
 
    $de = '';
    $cache = '';
 
    // 对数据分段解密
    while ($inputLen - $offSet > 0) {

        $cache = '';
 
        if ($inputLen - $offSet > $maxDecryptBlock) {
            $method(substr($data, $offSet, $maxDecryptBlock), $cache, $key);
        } else {
            $method(substr($data, $offSet, $inputLen - $offSet), $cache, $key);
        }
 
        $de = $de . $cache;
 
        $i = $i + 1;
        $offSet = $i * $maxDecryptBlock;
    }
    return $de;
}
