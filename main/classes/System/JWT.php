<?php

namespace System;

/**
 * JSON Web Token
 *
 * JWT doesn’t use sessions, has no problems with mobile, it doesn’t
 * need CSRF and it works like a charm with CORS
 *
 * https://developers.google.com/wallet/instant-buy/about-jwts
 * http://tools.ietf.org/html/draft-ietf-oauth-json-web-token-06
 *
 * A token xxx.yyy.zzz is divided into 3 parts:
 * <ul>
 * <li>A header</li>
 * <li>A payload</li>
 * <li>A signature</li>
 * </ul>
 * </pre>
 */
final class JWT {

    const DEFAULT_ALG = 'HS256';

    private static $methods = array(
        'HS256' => array('hash_hmac', 'SHA256'),
        'HS512' => array('hash_hmac', 'SHA512'),
        'HS384' => array('hash_hmac', 'SHA384'),
        'RS256' => array('openssl', 'SHA256'),
    );

    /**
     * Decode a JWT token into payload array.
     *
     * @param string $token Full JWT token
     * @param string $secret Secret key
     * @param bool $verify
     * @return array Payload array
     * @throws \UnexpectedValueException
     * @throws \DomainException
     */
    public static function decode($token, $secret = NULL, $verify = TRUE) {
        $segments = explode('.', $token);
        if (count($segments) != 3) {
            throw new \UnexpectedValueException('Token wrong number of segments');
        }

        list($headb64, $bodyb64, $cryptob64) = $segments;
        if (!($header = self::_jsonDecode(self::_urlsafeB64Decode($headb64)))) {
            throw new \UnexpectedValueException('Invalid header encoding');
        }
        if (!($payload = self::_jsonDecode(self::_urlsafeB64Decode($bodyb64)))) {
            throw new \UnexpectedValueException('Invalid payload encoding');
        }

        if ($verify) {
            if (empty($header['alg'])) {
                throw new \DomainException('Empty algorithm');
            }

            $sig = self::_urlsafeB64Decode($cryptob64);
            if (!self::_verify("$headb64.$bodyb64", $sig, $secret, $header['alg'])) {
                throw new \UnexpectedValueException('Signature verification failed');
            }

            // Check token expiry time if defined.
            if (isset($payload['exp']) && time() >= $payload['exp']) {
                throw new \UnexpectedValueException('Expired Token: ' . $payload['exp']);
            }
        }

        return $payload;
    }

    /**
     * @param array $payload
     * @param string $secret
     * @param string $alg
     * @return string
     */
    public static function encode(array $payload, $secret, $alg = self::DEFAULT_ALG) {
        $segments = array();
        $header = array('alg' => $alg);
        $segments[] = self::_urlsafeB64Encode(self::_jsonEncode($header));
        $segments[] = self::_urlsafeB64Encode(self::_jsonEncode($payload));
        $signing_input = implode('.', $segments);
        $signature = self::_sign($signing_input, $secret, $alg);
        $segments[] = self::_urlsafeB64Encode($signature);
        return implode('.', $segments);
    }

    /**
     * Sign a string with a given key and algorithm.
     *
     * @param string $msg The message to sign
     * @param string $secret The secret key
     * @param string $method The signing algorithm. Supported algorithms
     * are 'HS256', 'HS384', 'HS512' and 'RS256'
     *
     * @return string An encrypted message
     * @throws \DomainException Unsupported algorithm was specified
     */
    private static function _sign($msg, $secret, $method = self::DEFAULT_ALG) {
        if (empty(self::$methods[$method])) {
            throw new \DomainException('Algorithm not supported');
        }

        list($function, $alg) = self::$methods[$method];
        switch ($function) {
            case 'hash_hmac':
                return hash_hmac($alg, $msg, $secret, TRUE);

            case 'openssl':
                $signature = '';
                $success = openssl_sign($msg, $signature, $secret, $alg);
                if (!$success) {
                    throw new \DomainException("OpenSSL unable to sign data");
                } else {
                    return $signature;
                }
        }
    }

    private static function _verify($msg, $signature, $key, $method = self::DEFAULT_ALG) {
        if (empty(self::$methods[$method])) {
            throw new \DomainException('Algorithm not supported');
        }

        list($function, $alg) = self::$methods[$method];
        switch ($function) {
            case 'openssl':
                $success = openssl_verify($msg, $signature, $key, $alg);
                if (!$success) {
                    throw new \DomainException("OpenSSL unable to verify data: " . openssl_error_string());
                } else {
                    return $signature;
                }
                break;

            case 'hash_hmac':
            default:
                return $signature === hash_hmac($alg, $msg, $key, TRUE);
        }
    }

    private static function _jsonDecode($input) {
        $ret = json_decode($input, TRUE);
        if ($errno = json_last_error()) {
            //self::_handleJsonError($errno);
        } else if (!$ret && $input !== 'null') {
            throw new \DomainException('Null result with non-null input');
        }

        return $ret;
    }

    private static function _jsonEncode($input) {
        $json = json_encode($input);
        if ($errno = json_last_error()) {
            self::_handleJsonError($errno);
        } else if ($json === 'null' && $input !== NULL) {
            throw new \DomainException('Null result with non-null input');
        }
        return $json;
    }

    /**
     * Decode a string with URL-safe Base64.
     *
     * @param string $input A Base64 encoded string
     *
     * @return string A decoded string
     */
    private static function _urlsafeB64Decode($input) {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $padlen = 4 - $remainder;
            $input .= str_repeat('=', $padlen);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    /**
     * Encode a string with URL-safe Base64.
     *
     * @param string $input The string you want encoded
     *
     * @return string The base64 encode of what you passed in
     */
    private static function _urlsafeB64Encode($input) {
        return str_replace('=', '', strtr(base64_encode($input), '+/', '-_'));
    }

    private static function _handleJsonError($errno) {
        $messages = array(
            JSON_ERROR_DEPTH => 'Maximum stack depth exceeded',
            JSON_ERROR_CTRL_CHAR => 'Unexpected control character found',
            JSON_ERROR_SYNTAX => 'Syntax error, malformed JSON'
        );
        throw new \DomainException(
            isset($messages[$errno])
                ? $messages[$errno]
                : 'Unknown JSON error: ' . $errno
        );
    }

}
