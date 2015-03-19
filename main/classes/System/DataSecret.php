<?php

namespace System;

/**
 * DES encrypt/decrypt JSON content.
 */
final class DataSecret {

    const DES_KEY = '_fUnplUs+';
    const METHOD = 'aes128';

    const PRIV_KEY = '-----BEGIN RSA PRIVATE KEY-----
MIICXQIBAAKBgQDjvkGrLeUh+BR5ZKgpuFbDJ7dPlzFtFKkJUKz9GVerETOL1bwx
vdDJIPpcrNfcyKtiuvMuS3LBrgsbEs+g7OeKTPvoCd82Vhnc+v8aRnary0mlq9Ku
iAx3y+zXLbJNgwhNAQhh1vJFIMnFcdIDhFJ4HgE64TZc8iixgHIPss3lBQIDAQAB
AoGBANFpkqy6qOTRXtI48kBalr0gefifh/1LcBt2qXqZoPlV+dXtFP2QErm+rzgk
XuFPS/ie+xlomv7o8KFWbMEG1eejzywnvcF3XFEj4E8Slf+4N1Xj2AKvQ6C8jBWv
HqAROXOvAS+i4Gq52wP7gLiKnetST5HMFkipJFlfpUld1GpJAkEA8GA+c6FEGRoo
bTrqW/XX56a3ekG1lpM93yBCM0pFy+YTmpzHTKBcuRwPvi5WB8on6DBaKDA1cd9b
7VTJTW5JTwJBAPKLzwJHy/dskuqXDRg74SR1TiL2Mb5gOMtUOSvWDSL4TMla8sX/
TPcaxKLJ0SxnDQM1ZXPODO1mOCKfFapUb2sCQGVYPpxjvqFSvP6om0ygTyIU0UCT
WasdczWSlTaottqrq1JBjWmKJu3Inf6R7KWmHsMvN6PAB5h2EnzyhVjHCdMCQHVu
8byl7ICQhhWlKwbnvxt63GkbEHyfdAAmeEPcMKIU3IDFUzRAZxBhWoGC+47nGirK
iKNoSWQMEPPaWJAOr58CQQCaBbaORjjUG9JGaPPCw7RczCnfkkson3KCtWmBouWl
c78PP1TV/tYQaC/ws4v28/CEfNXtS4Sidg5unNd7iLpp
-----END RSA PRIVATE KEY-----';

    public static function aesEncrypt($str, $iv, $key) {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CBC, $iv);
    }

    public static function aesDecrypt($str, $iv, $key) {
        // If the size of the data is not n * blocksize, the data will be padded with '\0'
        return trim(mcrypt_decrypt(MCRYPT_RIJNDAEL_128, $key, $str, MCRYPT_MODE_CBC, $iv));
    }

    public static function rsaPrivDecrypt($str) {
        return rsa_decrypt('openssl_private_decrypt', openssl_pkey_get_private(self::PRIV_KEY), $str);
    }

    public static function digest($str) {
        return md5($str);
    }

    public static function verifyDigest($data, $sign) {
        if (self::digest($data) != $sign) {
            throw new \CheatingException("Integrity check fails");
        }
    }

    public static function base64Encrypt($string, $key='%key&') {
        $result = '';
        for($i=0; $i<strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $ordChar = ord($char);
            $ordKeychar = ord($keychar);
            $sum = $ordChar + $ordKeychar;
            $char = chr($sum);
            $result.=$char;
        }
        return base64_encode($result);
    }

    public static function base64Decrypt($string, $key='%key&') {
        $result = '';
        $string = base64_decode($string);
        for($i=0; $i<strlen($string); $i++) {
            $char = substr($string, $i, 1);
            $keychar = substr($key, ($i % strlen($key))-1, 1);
            $ordChar = ord($char);
            $ordKeychar = ord($keychar);
            $sum = $ordChar - $ordKeychar;
            $char = chr($sum);
            $result.=$char;
        }
        return $result;
    }

    public static function encrypt($rawStr, $key) {
        return mcrypt_encrypt(MCRYPT_RIJNDAEL_256, $key, $rawStr, MCRYPT_MODE_ECB);
    }

    public static function decrypt($encryptedStr, $key) {
        return mcrypt_decrypt(MCRYPT_RIJNDAEL_256, $key, $encryptedStr, MCRYPT_MODE_ECB);
    }

    public static function sslEncrypt($rawStr, $key = self::DES_KEY, $method = self::METHOD) {
        return openssl_encrypt($rawStr, $method, $key);
    }

    public static function sslDecrypt($encryptedStr, $key = self::DES_KEY, $method = self::METHOD) {
        return openssl_decrypt($encryptedStr, $method, $key);
    }

    /**
     * openssl genrsa -out private.pem 1024
     * openssl rsa -in private.pem -out public.pem -outform PEM -pubout
     *
     * $public_key = file_get_contents('/PATH/TO/public.pem');
     * $private_key = file_get_contents('/PATH/TO/private.pem');
     */
    public static function rsaEncrypt($rawStr, $publicKey) {
        openssl_public_encrypt($rawStr, $encrypted, $publicKey);
        return $encrypted;
    }

    public static function rsaDecrypt($encryptedStr, $privateKey) {
        openssl_private_decrypt($encryptedStr, $rawStr, $privateKey);
        return $rawStr;
    }

}
