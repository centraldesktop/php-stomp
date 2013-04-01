<?php




namespace CentralDesktop\Stomp\Test;

use CentralDesktop\Stomp;



class BufferTest {


    public function testReceivePacket() {
        $stomp = new Stomp\Connection( 'tcp://localhost:61612');

            // Make sure the buffer is empty to start
        $this->assertEquals( '', $stomp->_getBuffer());

        $stomp->_appendToBuffer( "MESSAGE\n\n");
        $this->assertEquals( "MESSAGE\n\n", $stomp->_getBuffer());

        $stomp->_appendToBuffer( "Body\n");
        $this->assertEquals( "MESSAGE\n\nBody\n", $stomp->_getBuffer());

        $stomp->_appendToBuffer( "\x00MESSAGE2\x00");
        $this->assertEquals( "MESSAGE\n\nBody\n\x00MESSAGE2\x00", $stomp->_getBuffer());
    }

    public function testBufferContainsMessage() {
        $stomp = new Stomp\Connection( 'tcp://localhost:61612');

            // Make sure the buffer is empty to start
        $this->assertEquals( '', $stomp->_getBuffer());

        $this->assertFalse( $stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer( "MESSAGE\n\n");
        $this->assertFalse( $stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer( "Body\n");
        $this->assertFalse( $stomp->_bufferContainsMessage());

        $stomp->_appendToBuffer( "\x00MESSAGE2\x00");
        $this->assertTrue( $stomp->_bufferContainsMessage());
    }

    public function testExtractNextMessage() {
        $stomp = new Stomp\Connection( 'tcp://localhost:61612');

            // Make sure the buffer is empty to start
        $this->assertEquals( '', $stomp->_getBuffer());

        $this->assertFalse( $stomp->_bufferContainsMessage());

            // Fill the buffer up with 3 messages
        $stomp->_appendToBuffer( "MESSAGE1\n\nBODY1\n\x00");
        $stomp->_appendToBuffer( "MESSAGE2\n\nBODY2\n\x00");
        $stomp->_appendToBuffer( "MESSAGE3\n\nBODY3\n\x00");
        $this->assertEquals( "MESSAGE1\n\nBODY1\n\x00MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());
        $this->assertTrue( $stomp->_bufferContainsMessage());

            // Extract all 3 sequentially
        $this->assertEquals( "MESSAGE1\n\nBODY1\n", $stomp->_extractNextMessage());
        $this->assertEquals( "MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());

        $this->assertEquals( "MESSAGE2\n\nBODY2\n", $stomp->_extractNextMessage());
        $this->assertEquals( "MESSAGE3\n\nBODY3\n\x00", $stomp->_getBuffer());

        $this->assertEquals( "MESSAGE3\n\nBODY3\n", $stomp->_extractNextMessage());
        $this->assertEquals( "", $stomp->_getBuffer());

            // Now that the buffer is empty, add another message
        $stomp->_appendToBuffer( "MESSAGE4\n\nBODY4\n\x00");
        $this->assertEquals( "MESSAGE4\n\nBODY4\n\x00", $stomp->_getBuffer());

            // Extract that message
        $this->assertEquals( "MESSAGE4\n\nBODY4\n", $stomp->_extractNextMessage());
        $this->assertEquals( "", $stomp->_getBuffer());

            // verify that trying to extract from an empty buffer returns empty string
        $stomp->_appendToBuffer( "");
        $this->assertEquals( "", $stomp->_getBuffer());

            // Now that the buffer is empty, add another message
            // This is to verify that extracting a message from an empty buffer doesn't break something
        $stomp->_appendToBuffer( "MESSAGE5\n\nBODY5\n\x00");
        $this->assertEquals( "MESSAGE5\n\nBODY5\n\x00", $stomp->_getBuffer());

            // Extract that message
        $this->assertEquals( "MESSAGE5\n\nBODY5\n", $stomp->_extractNextMessage());
        $this->assertEquals( "", $stomp->_getBuffer());

            // verify that trying to extract from an empty buffer returns empty string
        $stomp->_appendToBuffer( "");
        $this->assertEquals( "", $stomp->_getBuffer());
    }
}

