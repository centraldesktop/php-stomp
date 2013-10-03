<?php

namespace CentralDesktop\Stomp\ConnectionFactory;


class Failover implements FactoryI {
    private $hosts = array();

    public
    function __construct(array $hosts, $randomize = false) {
        if ($randomize){
            // this is mutating operation, it doesn't return, it modifies the list
            shuffle($hosts);
        }
        $this->hosts = $hosts;
    }

    /**
     * Gets the next URL to connect to
     *
     * @return
     */
    public
    function getHostIterator() {
        return new \InfiniteIterator(new \ArrayIterator($this->hosts));
    }
}