<?php
/**
 *
 * Copyright 2005-2006 The Apache Software Foundation
 *
 * Licensed under the Apache License, Version 2.0 (the "License");
 * you may not use this file except in compliance with the License.
 * You may obtain a copy of the License at
 *
 * http://www.apache.org/licenses/LICENSE-2.0
 *
 * Unless required by applicable law or agreed to in writing, software
 * distributed under the License is distributed on an "AS IS" BASIS,
 * WITHOUT WARRANTIES OR CONDITIONS OF ANY KIND, either express or implied.
 * See the License for the specific language governing permissions and
 * limitations under the License.
 */

namespace CentralDesktop\Stomp\Test;

use CentralDesktop\Stomp;

/**
 * Stomp test case.
 *
 * @package Stomp
 * @author  Michael Caplan <mcaplan@labnet.net>
 * @version $Revision: 35 $
 */
class FailoverTest extends PHPUnit_Framework_TestCase {
    /**
     * @var Connection
     */
    private $Stomp;

    /**
     * Prepares the environment before running a test.
     */
    protected
    function setUp() {
        parent::setUp();

        $stomp_path = realpath('../../main/php5/');
        set_include_path(get_include_path() . PATH_SEPARATOR . $stomp_path);

        $this->Stomp = new Connection('failover://(tcp://localhost:61614,tcp://localhost:61613)?randomize=false');
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected
    function tearDown() {
        $this->Stomp = null;
        parent::tearDown();
    }

    /**
     * Tests Stomp->connect()
     */
    public
    function testFailoverConnect() {
        $this->assertTrue($this->Stomp->connect());
    }
}

