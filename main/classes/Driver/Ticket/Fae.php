<?php

namespace Driver\Ticket;

class Fae implements Driver {

    public function nextId($tag) {
        return \FaeEngine::client()->id_next(\FaeEngine::ctx());
    }

    // TODO
    public function nextIds($tablePrefix, $count) {
        return array(1, 1);
    }

    /**
     * @param string $tag
     * @return int
     */
    public function nextIdWithTag($tag) {
        $fae = \FaeEngine::client();
        return $fae->id_next_with_tag(\FaeEngine::ctx(), $tag);
    }

    /**
     * @param int $id
     * @return array [ts, tag, wid, seq]
     */
    public function decodeId($id) {
        $fae = \FaeEngine::client();
        return $fae->id_decode(\FaeEngine::ctx(), $id);
    }
}
