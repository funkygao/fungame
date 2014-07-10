<?php

namespace System\Appender;

interface Appender {

    /**
     * @param string $category
     * @param string $msg
     */
    public function append($category, $msg);

}
