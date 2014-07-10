<?php

require_once realpath(__DIR__ . '/../') . "/FunTestCaseBase.php";

class DbResultTest extends FunTestCaseBase {

    public function testBasic() {
        $result = new \Driver\DbResult();
        $result->setResults(array(
            array(
                'uid' => 1,
                'name' => 'a',
                'gendar' => 'male',
            ),
            array(
                'uid' => 2,
                'name' => 'b',
                'gendar' => 'male',
            ),
        ));

        $this->assertEquals(2, count($result)); // countable
        $this->assertEquals('a', $result[0]['name']); // array access

        // test readonly
        $result[0] = 'xxx';
        $this->assertEquals(array(
            'uid' => 1,
            'name' => 'a',
            'gendar' => 'male',
        ), $result[0]);
        // Indirect modification of overloaded element of Driver\DbResult has no effect
        $result[1]['gendar'] = 'female';
        $this->assertEquals('male', $result[1]['gendar']);

        // test foreach'able
        $num = 0;
        foreach ($result as $row) {
            $this->assertEquals('male', $row['gendar']);
            $num ++;
        }
        $this->assertEquals(2, $num);
    }
}
