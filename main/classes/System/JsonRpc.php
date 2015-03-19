<?php

namespace System;

// 使用它的可能性不大
// TODO
final class JsonRpc {

    const
        TIMEOUT_CONN = 5,  // in seconds
        TIMEOUT_IO = 3000; // in microseconds

    /**
     * @var string
     */
    private $host;

    /**
     * @var string
     */
    private $port;

    /**
     * @var string
     */
    private $path;

    /**
     * @var resource
     */
    private $conn;

    /**
     * @var int
     */
    private $reqId;

    public function __construct($host, $port, $path) {
        $this->host = $host;
        $this->port = $port;
        $this->path = $path;
        $this->conn = NULL;
        $this->reqId = 1;
    }

    private function _dial() {
        $this->conn = @fsockopen($this->host, $this->port, $errno, $errstr, self::TIMEOUT_CONN);
        if (!$this->conn) {
            return "JsonRPC _dial Failed: $errstr ($errno)";
        }

        $err = fwrite($this->conn, "GET " . $this->path . " HTTP/1.1\n\n");
        if ($err === false) {
            return "JsonRPC Init Failed";
        }

        stream_set_timeout($this->conn, 0, self::TIMEOUT_IO);
        $info = stream_get_meta_data($this->conn);
        if ($info['timed_out']) {
            fclose($this->conn);
            return "JsonRPC Init Time Out";
        }

        // check first http head
        $line = fgets($this->conn);
        if ($line != "HTTP/1.1 200 Connected to JSON RPC\n") {
            fclose($this->conn);
            return "JsonRPC Unexpected Result: $line";
        }

        // ignore http head
        for (; ;) {
            $line = fgets($this->conn);
            if ($line == "\n") {
                break;
            }
        }

        return NULL;
    }

    /**
     * <pre>
     * $client = new JsonRpc("127.0.0.1", 12345, "/test/");
     * $r = $client->call("Arith.Multiply", array('A'=>7, 'B'=>8));
     * var_dump($r);
     * </pre>
     *
     * @param string $method
     * @param array $params
     * @return array
     */
    public function call($method, array $params) {
        if ($this->conn == NULL) {
            $dialResult = $this->_dial();
            if ($dialResult !== NULL) {
                return $dialResult;
            }
        }

        $err = fwrite($this->conn, json_encode(array(
                'method' => $method,
                'params' => array($params),
                'id' => $this->reqId++,
            )) . "\n");
        if ($err === false) {
            return "JsonRPC Send Failed";
        }

        stream_set_timeout($this->conn, 0, self::TIMEOUT_IO);
        $info = stream_get_meta_data($this->conn);
        if ($info['timed_out']) {
            fclose($this->conn);
            return "JsonRPC Time Out";
        }

        $line = fgets($this->conn);
        if ($line === false) {
            return NULL;
        }

        return json_decode($line, TRUE);
    }

}
