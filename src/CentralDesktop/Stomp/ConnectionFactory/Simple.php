<?php

namespace CentralDesktop\Stomp\ConnectionFactory;


class Simple implements FactoryI {

    private $uri;

    public
    function __construct($uri) {
        $this->uri = $uri;
    }


    /**
     * Gets the next URL to connect to
     *
     * @return
     */
    public
    function getHostIterator() {
        return new \InfiniteIterator(new \ArrayIterator(array($this->uri)));
    }
}