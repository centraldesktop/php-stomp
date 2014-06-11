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
use CentralDesktop\Stomp\Connection;


/**
 * Stomp test case.
 * @package Stomp
 * @author  Val Minyaylo <vminyaylo@centraldesktop.com>
 */
class ConnectionReadTest extends \PHPUnit_Framework_TestCase {


    public function testConnectionRad() {


        $connection = new Connection(new Stomp\ConnectionFactory\NullFactory());

        $this->assertFalse($connection->_bufferContainsMessage(), 'Do not expect any messages in connection');

        $frame = new Stomp\Message\Bytes("lorem ipsum \n\n \x00 dolore sitamet", array('foo' => 'meta'));
        $connection->_appendToBuffer("\n\n");
        $connection->_appendToBuffer($frame->__toString());
        $connection->_appendToBuffer("\n\n\n");

        $frame2 = new Stomp\Message\Bytes("lorem forem \n\n, none \x00", array('beta' => 'treu', 'mod' => false));
        $connection->_appendToBuffer($frame2->__toString());

        $this->assertTrue($connection->_bufferContainsMessage(), 'Expect to have frame to read.');
        $result = $connection->readFrame();

        $this->assertInstanceOf('CentralDesktop\Stomp\Frame', $result);
        $this->assertEquals($frame->body, $result->body);

        $this->assertTrue($connection->_bufferContainsMessage(), "We should have some message in buffer");
        $connection->readFrame();

        $this->assertFalse($connection->_bufferContainsMessage(), 'Now buffer should be empty');

    }
}