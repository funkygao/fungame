<?php

namespace System\Appender;

class File implements Appender {

    /**
     * @var string
     */
    private $_basedir;

    public function __construct($basedir) {
        $this->_basedir = $basedir;
    }

    public function append($category, $msg) {
        $date = new \DateTime('now', new \DateTimeZone('Asia/Hong_Kong'));
        $header = $date->format('Y-m-d H:i:s');
        $msg = "$header $msg\n";
        file_put_contents($this->_basedir . '/' . $category . '.json',
            $msg, FILE_APPEND | LOCK_EX);
    }

}
