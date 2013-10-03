<?php

namespace CentralDesktop\Stomp\ConnectionFactory;


interface FactoryI {


    /**
     * Gets the next URL to connect to
     *
     * @return
     */
    public function getHostIterator();
}