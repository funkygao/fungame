<?php

namespace System;

interface Flushable {

    /**
     * @return bool
     */
    public function isDirty();

    /**
     * @param bool $validate
     * @return bool True if succeed
     */
    public function save($validate = TRUE);

}
