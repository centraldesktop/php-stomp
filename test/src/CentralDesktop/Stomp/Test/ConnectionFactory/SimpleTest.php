<?php


namespace CentralDesktop\Stomp\Test\ConnectionFactory;

use CentralDesktop\Stomp\ConnectionFactory\Simple;

class SimpleTest extends \PHPUnit_Framework_TestCase {

    public
    function testBasic() {
        $uri = "tcp://foo.com:1234";

        $simple = new Simple($uri);


        $count = 0;
        foreach ($simple->getHostIterator() as $host){
            $this->assertSame($uri, $host);

            if ($count++ > 2){
                break;
            }
        }
    }

}