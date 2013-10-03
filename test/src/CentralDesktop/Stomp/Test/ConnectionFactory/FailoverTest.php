<?php


namespace CentralDesktop\Stomp\Test\ConnectionFactory;

use CentralDesktop\Stomp\ConnectionFactory\Failover;

class FailoverTest extends \PHPUnit_Framework_TestCase {

    public
    function testOrdered() {
        $hosts = array("host1", "host2", "host3");

        $failover = new Failover($hosts, false);

        $iter = $failover->getHostIterator();

        $iter->rewind();

        foreach ($hosts as $host) {

            $this->assertSame($host, $iter->current());
            $iter->next();

        }

        // one extra time for good measure to make sure the lists wraps around properly
        $this->assertSame($hosts[0], $iter->current());
    }


    public
    function testRandomize() {
        $hosts = array("host1", "host2", "host3");

        $failover = new Failover($hosts, true);
        $iter = $failover->getHostIterator();

        $count    = 0;
        $previous = null;

        foreach ($iter as $host) {
            $this->assertNotSame($previous, $host);

            if (++$count > 5) {
                break;
            }
        }
    }

}