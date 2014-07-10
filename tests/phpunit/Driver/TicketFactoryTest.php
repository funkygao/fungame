<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class TicketFactoryTest extends FunTestCaseBase {

    public function testGenerateTicket() {
        $t1 = \Driver\TicketFactory::instance()->nextId('User');
        $this->assertTrue(is_numeric($t1));
        $t2 = \Driver\TicketFactory::instance()->nextId('User');
        $this->assertEquals(1, $t2 - $t1);
    }

}

