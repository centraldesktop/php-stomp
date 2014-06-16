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

    /**
     * simple read test from buffer
     * put couple of messages in buffer and try to read them
     */
    public function testConnectionRad() {


        $connection = new Connection(new Stomp\ConnectionFactory\NullFactory());

        $this->assertFalse($connection->_bufferContainsMessage(), 'Do not expect any messages in connection');

        $frame1 = new Stomp\Message\Bytes("lorem ipsum \n\n \x00 dolore sitamet", array('foo' => 'meta'));
        $connection->_appendToBuffer("\n\n");
        $connection->_appendToBuffer($frame1->__toString());
        $connection->_appendToBuffer("\n\n\n");

        $frame2 = new Stomp\Message\Bytes("lorem forem \n\n, none \x00", array('beta' => 'treu', 'mod' => false));
        $connection->_appendToBuffer($frame2->__toString());

        $this->assertTrue($connection->_bufferContainsMessage(), 'Expect to have frame to read.');
        $result = $connection->readFrame();

        $this->assertInstanceOf('CentralDesktop\Stomp\Frame', $result);
        $this->assertEquals($frame1->body, $result->body);

        $this->assertTrue($connection->_bufferContainsMessage(), "We should have some message in buffer");
        $connection->readFrame();

        $this->assertFalse($connection->_bufferContainsMessage(), 'Now buffer should be empty');

    }

    /**
     * append messages to a buffer in chunks
     * and make sure we properly determine if there is a message in a buffer
     * and properly read messages
     */
    public function testConnectionReadChunks() {

        $connection = new Connection(new Stomp\ConnectionFactory\NullFactory());
        $frame1 = new Stomp\Message\Map(array("title" => "lorem ipsum \n\n test \x00 end of line", "descripiton" => "test1\x00\n\n"));
        $frame2 = new Stomp\Message\Map(array("title" => "lorem ipsum \n\n test \x00 end of line", "descripiton" => "test2\x00\n\n"));

        $this->assertFalse($connection->_bufferContainsMessage(), 'Do not expect any messages in connection');

        $connection->_appendToBuffer($frame1->__toString());

        // brake second message apart so we can insert chunks of it in to buffer
        $frame2_msg = $frame2->__toString();
        $frame2_part1 = substr($frame2_msg, 0, 50);
        $frame2_part2 = substr($frame2_msg, 50);

        // append just a chunk of a second message
        $connection->_appendToBuffer("\n\n");
        $connection->_appendToBuffer($frame2_part1);

        $this->assertTrue($connection->_bufferContainsMessage(), 'Expect a message and a half');

        //remove first message from buffer
        $message = $connection->readFrame();
        $this->assertNotNull($message, "Expected a message");

        $this->assertFalse($connection->_bufferContainsMessage(), "There is only half of message in a buffere");

        // put second chank in to a buffer
        $connection->_appendToBuffer($frame2_part2);
        $this->assertTrue($connection->_bufferContainsMessage(), "We should have got a message in a buffer");

        $message2 = $connection->readFrame();
        $this->assertNotNull($message2, "Expected second frame here");
    }
}

































