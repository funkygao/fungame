<?php

namespace Driver\Ticket;

class Fae implements Driver {

    public function nextId($tag) {
        return \FaeEngine::client()->id_next(
            \FaeEngine::ctx(),
            0
        );
    }

}
