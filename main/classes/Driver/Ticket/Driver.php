<?php

namespace Driver\Ticket;

interface Driver
{
    /**
     * @param string $tag
     * @return string|int
     */
    public function nextId($tag);
}