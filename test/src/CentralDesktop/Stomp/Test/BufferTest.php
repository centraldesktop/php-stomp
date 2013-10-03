<?php




namespace CentralDesktop\Stomp\Test;

use CentralDesktop\Stomp;


class NullFactory implements Stomp\ConnectionFactory\FactoryI {

    /**
     * Gets the next URL to connect to
     *
     * @return
     */
    public
    function getHostIterator() {
        return new \InfiniteIterator(new \ArrayIterator(array()));
    }
}



class BufferTest extends \PHPUnit_Framework_TestCase {
    private $stomp = null;

    function setUp(){
        $this->stomp = new Stomp\Connection(new NullFactory());

    }

    public function testReceivePacket() {
            // Make sure the buffer is empty to start
        $this->assertEquals( '', $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer( "MESSAGE\n\n");
        $this->assertEquals( "MESSAGE\n\n", $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer( "Body\n");
        $this->assertEquals( "MESSAGE\n\nBody\n", $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer( "\x00MESSAGE2\x00");
        $this->assertEquals( "MESSAGE\n\nBody\n\x00MESSAGE2\x00", $this->stomp->_getBuffer());
    }

    public function testBufferContainsMessage() {
            // Make sure the buffer is empty to start
        $this->assertEquals( '', $this->stomp->_getBuffer());

        $this->assertFalse( $this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer( "MESSAGE\n\n");
        $this->assertFalse( $this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer( "Body\n");
        $this->assertFalse( $this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer( "\x00MESSAGE2\x00");
        $this->assertTrue( $this->stomp->_bufferContainsMessage());
    }

    public function testExtractNextMessage() {
            // Make sure the buffer is empty to start
        $this->assertEquals( '', $this->stomp->_getBuffer());

        $this->assertFalse( $this->stomp->_bufferContainsMessage());

            // Fill the buffer up with 3 messages
        $this->stomp->_appendToBuffer( "MESSAGE1\n\nBODY1\n\x00");
        $this->stomp->_appendToBuffer( "MESSAGE2\n\nBODY2\n\x00");
        $this->stomp->_appendToBuffer( "MESSAGE3\n\nBODY3\n\x00");
        $this->assertEquals( "MESSAGE1\n\nBODY1\n\x00MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $this->stomp->_getBuffer());
        $this->assertTrue( $this->stomp->_bufferContainsMessage());

            // Extract all 3 sequentially
        $this->assertEquals( "MESSAGE1\n\nBODY1\n", $this->stomp->_extractNextMessage());
        $this->assertEquals( "MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $this->stomp->_getBuffer());

        $this->assertEquals( "MESSAGE2\n\nBODY2\n", $this->stomp->_extractNextMessage());
        $this->assertEquals( "MESSAGE3\n\nBODY3\n\x00", $this->stomp->_getBuffer());

        $this->assertEquals( "MESSAGE3\n\nBODY3\n", $this->stomp->_extractNextMessage());
        $this->assertEquals( "", $this->stomp->_getBuffer());

            // Now that the buffer is empty, add another message
        $this->stomp->_appendToBuffer( "MESSAGE4\n\nBODY4\n\x00");
        $this->assertEquals( "MESSAGE4\n\nBODY4\n\x00", $this->stomp->_getBuffer());

            // Extract that message
        $this->assertEquals( "MESSAGE4\n\nBODY4\n", $this->stomp->_extractNextMessage());
        $this->assertEquals( "", $this->stomp->_getBuffer());

            // verify that trying to extract from an empty buffer returns empty string
        $this->stomp->_appendToBuffer( "");
        $this->assertEquals( "", $this->stomp->_getBuffer());

            // Now that the buffer is empty, add another message
            // This is to verify that extracting a message from an empty buffer doesn't break something
        $this->stomp->_appendToBuffer( "MESSAGE5\n\nBODY5\n\x00");
        $this->assertEquals( "MESSAGE5\n\nBODY5\n\x00", $this->stomp->_getBuffer());

            // Extract that message
        $this->assertEquals( "MESSAGE5\n\nBODY5\n", $this->stomp->_extractNextMessage());
        $this->assertEquals( "", $this->stomp->_getBuffer());

            // verify that trying to extract from an empty buffer returns empty string
        $this->stomp->_appendToBuffer( "");
        $this->assertEquals( "", $this->stomp->_getBuffer());
    }
}

