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
 * @package Stomp
 * @author  Michael Caplan <mcaplan@labnet.net>
 * @author  Dejan Bosanac <dejan@nighttale.net>
 * @version $Revision: 40 $
 */
class ConnectionTest extends PHPUnit_Framework_TestCase {
    /**
     * @var Connection
     */
    private $Stomp;
    private $broker = 'tcp://localhost:61613';
    private $queue = '/queue/test';
    private $topic = '/topic/test';

    /**
     * Prepares the environment before running a test.
     */
    protected
    function setUp() {
        parent::setUp();

        $this->Stomp       = new Connection($this->broker);
        $this->Stomp->sync = false;
    }

    /**
     * Cleans up the environment after running a test.
     */
    protected
    function tearDown() {
        $this->Stomp = null;
        parent::tearDown();
    }


    // Test message buffering and parsing, before we test actually talking to a Stomp server
    public
    function testReceivePacket() {
        $stomp = $this->Stomp;

        // Make sure the buffer is empty to start
        $this->assertEquals('', $stomp->_getBuffer());

        $stomp->_appendToBuffer("MESSAGE\n\n");
        $this->assertEquals("MESSAGE\n\n", $stomp->_getBuffer());

        $stomp->_appendToBuffer("Body\n");
        $this->assertEquals("MESSAGE\n\nBody\n", $stomp->_getBuffer());

        $stomp->_appendToBuffer("\x00MESSAGE2\x00");
        $this->assertEquals("MESSAGE\n\nBody\n\x00MESSAGE2\x00", $stomp->_getBuffer());
    }

    public
    function testBufferContainsMessage() {
        $stomp = $this->Stomp;

        // Make sure the buffer is empty to start
        $this->assertEquals('', $stomp->_getBuffer());

        $this->assertFalse($stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer("MESSAGE\n\n");
        $this->assertFalse($stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer("Body\n");
        $this->assertFalse($stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer("\x00MESSAGE2\x00");
        $this->assertTrue($stomp->_bufferContainsMessage());
    }

    public
    function testExtractNextMessage() {
        $stomp = $this->Stomp;

        // Make sure the buffer is empty to start
        $this->assertEquals('', $stomp->_getBuffer());

        $this->assertFalse($stomp->_bufferContainsMessage());

        // Fill the buffer up with 3 messages
        $stomp->_appendToBuffer("MESSAGE1\n\nBODY1\n\x00");
        $stomp->_appendToBuffer("MESSAGE2\n\nBODY2\n\x00");
        $stomp->_appendToBuffer("MESSAGE3\n\nBODY3\n\x00");
        $this->assertEquals("MESSAGE1\n\nBODY1\n\x00MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());
        $this->assertTrue($stomp->_bufferContainsMessage());

        // Extract all 3 sequentially
        $this->assertEquals("MESSAGE1\n\nBODY1\n", $stomp->_extractNextMessage());
        $this->assertEquals("MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());

        $this->assertEquals("MESSAGE2\n\nBODY2\n", $stomp->_extractNextMessage());
        $this->assertEquals("MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());

        $this->assertEquals("MESSAGE3\n\nBODY3\n", $stomp->_extractNextMessage());
        $this->assertEquals("", $stomp->_getBuffer());

        // Now that the buffer is empty, add another message
        $stomp->_appendToBuffer("MESSAGE4\n\nBODY4\n\x00");
        $this->assertEquals("MESSAGE4\n\nBODY4\n\x00", $stomp->_getBuffer());

        // Extract that message
        $this->assertEquals("MESSAGE4\n\nBODY4\n", $stomp->_extractNextMessage());
        $this->assertEquals("", $stomp->_getBuffer());

        // verify that trying to extract from an empty buffer returns empty string
        $stomp->_appendToBuffer("");
        $this->assertEquals("", $stomp->_getBuffer());

        // Now that the buffer is empty, add another message
        // This is to verify that extracting a message from an empty buffer doesn't break something
        $stomp->_appendToBuffer("MESSAGE5\n\nBODY5\n\x00");
        $this->assertEquals("MESSAGE5\n\nBODY5\n\x00", $stomp->_getBuffer());

        // Extract that message
        $this->assertEquals("MESSAGE5\n\nBODY5\n", $stomp->_extractNextMessage());
        $this->assertEquals("", $stomp->_getBuffer());

        // verify that trying to extract from an empty buffer returns empty string
        $stomp->_appendToBuffer("");
        $this->assertEquals("", $stomp->_getBuffer());
    }


    /**
     * Tests Stomp->hasFrameToRead()
     *
     */
    public
    function testHasFrameToRead() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }

        $this->Stomp->setReadTimeout(5);

        $this->assertFalse($this->Stomp->hasFrameToRead(), 'Has frame to read when non expected');

        $this->Stomp->send($this->queue, 'testHasFrameToRead');

        $this->Stomp->subscribe($this->queue, array('ack' => 'client', 'activemq.prefetchSize' => 1));

        $this->assertTrue($this->Stomp->hasFrameToRead(), 'Did not have frame to read when expected');

        $frame = $this->Stomp->readFrame();

        $this->assertTrue($frame instanceof StompFrame, 'Frame expected');

        $this->Stomp->ack($frame);

        $this->Stomp->disconnect();

        $this->Stomp->setReadTimeout(60);
    }

    /**
     * Tests Stomp->ack()
     */
    public
    function testAck() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }

        $messages = array();

        for ($x = 0; $x < 100; ++$x) {
            $this->Stomp->send($this->queue, $x);
            $messages[$x] = 'sent';
        }

        $this->Stomp->disconnect();

        for ($y = 0; $y < 100; $y += 10) {

            $this->Stomp->connect();

            $this->Stomp->subscribe($this->queue, array('ack' => 'client', 'activemq.prefetchSize' => 1));

            for ($x = $y; $x < $y + 10; ++$x) {
                $frame = $this->Stomp->readFrame();
                $this->assertTrue($frame instanceof StompFrame);
                $this->assertArrayHasKey($frame->body, $messages, $frame->body . ' is not in the list of messages to ack');
                $this->assertEquals('sent', $messages[$frame->body], $frame->body . ' has been marked acked, but has been received again.');
                $messages[$frame->body] = 'acked';

                $this->assertTrue($this->Stomp->ack($frame), "Unable to ack {$frame->headers['message-id']}");

            }

            $this->Stomp->disconnect();

        }

        $un_acked_messages = array();

        foreach ($messages as $key => $value) {
            if ($value == 'sent') {
                $un_acked_messages[] = $key;
            }
        }

        $this->assertEquals(0, count($un_acked_messages), 'Remaining messages to ack' . var_export($un_acked_messages, true));
    }

    /**
     * Tests Stomp->abort()
     */
    public
    function testAbort() {
        $this->Stomp->setReadTimeout(1);
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->Stomp->begin("tx1");
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend', array("transaction" => "tx1")));
        $this->Stomp->abort("tx1");

        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertFalse($frame);
        $this->Stomp->unsubscribe($this->queue);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->connect()
     */
    public
    function testConnect() {
        $this->assertTrue($this->Stomp->connect());
        $this->assertTrue($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->disconnect()
     */
    public
    function testDisconnect() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->Stomp->isConnected());
        $this->Stomp->disconnect();
        $this->assertFalse($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->getSessionId()
     */
    public
    function testGetSessionId() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertNotNull($this->Stomp->getSessionId());
    }

    /**
     * Tests Stomp->isConnected()
     */
    public
    function testIsConnected() {
        $this->Stomp->connect();
        $this->assertTrue($this->Stomp->isConnected());
        $this->Stomp->disconnect();
        $this->assertFalse($this->Stomp->isConnected());
    }

    /**
     * Tests Stomp->readFrame()
     */
    public
    function testReadFrame() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->Stomp->send($this->queue, 'testReadFrame');
        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof StompFrame);
        $this->assertEquals('testReadFrame', $frame->body, 'Body of test frame does not match sent message');
        $this->Stomp->ack($frame);
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->send()
     */
    public
    function testSend() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->Stomp->send($this->queue, 'testSend'));
        $this->Stomp->subscribe($this->queue);
        $frame = $this->Stomp->readFrame();
        $this->assertTrue($frame instanceof StompFrame);
        $this->assertEquals('testSend', $frame->body, 'Body of test frame does not match sent message');
        $this->Stomp->ack($frame);
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp->subscribe()
     */
    public
    function testSubscribe() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->assertTrue($this->Stomp->subscribe($this->queue));
        $this->Stomp->unsubscribe($this->queue);
    }

    /**
     * Tests Stomp message transformation - json map
     */
    public
    function testJsonMapTransformation() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $body                     = array("city" => "Belgrade", "name" => "Dejan");
        $header                   = array();
        $header['transformation'] = 'jms-map-json';
        $mapMessage               = new MessageMap($body, $header);
        $this->Stomp->send($this->queue, $mapMessage);

        $this->Stomp->subscribe($this->queue, array('transformation' => 'jms-map-json'));
        $msg = $this->Stomp->readFrame();
        $this->assertTrue($msg instanceOf MessageMap);
        $this->assertEquals($msg->map, $body);
        $this->Stomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp byte messages
     */
    public
    function testByteMessages() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $body       = "test";
        $mapMessage = new StompMessageBytes($body);
        $this->Stomp->send($this->queue, $mapMessage);

        $this->Stomp->subscribe($this->queue);
        $msg = $this->Stomp->readFrame();
        $this->assertEquals($msg->body, $body);
        $this->Stomp->ack($msg);
        $this->Stomp->disconnect();
    }

    /**
     * Tests Stomp->unsubscribe()
     */
    public
    function testUnsubscribe() {
        if (!$this->Stomp->isConnected()) {
            $this->Stomp->connect();
        }
        $this->Stomp->subscribe($this->queue);
        $this->assertTrue($this->Stomp->unsubscribe($this->queue));
    }

    public
    function testDurable() {
        $this->subscribe();
        sleep(2);
        $this->produce();
        sleep(2);
        $this->consume();
    }

    protected
    function produce() {
        $producer       = new Connection($this->broker);
        $producer->sync = false;
        $producer->connect("system", "manager");
        $producer->send($this->topic, "test message", array('persistent' => 'true'));
        $producer->disconnect();
    }

    protected
    function subscribe() {
        $consumer           = new Connection($this->broker);
        $consumer->sync     = false;
        $consumer->clientId = "test";
        $consumer->connect("system", "manager");
        $consumer->subscribe($this->topic);
        $consumer->unsubscribe($this->topic);
        $consumer->disconnect();
    }

    protected
    function consume() {
        $consumer2           = new Connection($this->broker);
        $consumer2->sync     = false;
        $consumer2->clientId = "test";
        $consumer2->setReadTimeout(1);
        $consumer2->connect("system", "manager");
        $consumer2->subscribe($this->topic);

        $frame = $consumer2->readFrame();
        $this->assertEquals($frame->body, "test message");
        if ($frame != null) {
            $consumer2->ack($frame);
        }

        $consumer2->disconnect();
    }


}

