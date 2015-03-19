<?php

namespace Driver\Ticket;

interface Driver {

    /**
     * @param string $tag
     * @return string|int
     */
    public function nextId($tag);

    /**
     * @param string $tag
     * @param int $count
     * @return array (startId, endId)
     */
    public function nextIds($tag, $count);

    /**
     * @param string $tag
     * @return int
     */
    public function nextIdWithTag($tag);

    /**
     * @param int $id
     * @return array [ts, tag, wid, seq]
     */
    public function decodeId($id);

}
