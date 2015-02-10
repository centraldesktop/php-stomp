<?php




namespace CentralDesktop\Stomp\Test;

use CentralDesktop\Stomp;
use CentralDesktop\Stomp\ConnectionFactory\NullFactory;


class BufferTest extends \PHPUnit_Framework_TestCase {
    /**
     * @var Stomp\Connection
     */
    private $stomp = null;

    function setUp() {
        $this->stomp = new Stomp\Connection(new NullFactory());

    }

    public
    function testProtoHandling() {
        $iters = 5;
        $bytes = [10, 4, 49, 48, 50, 52, 24, 50];
//        $bytes   = [\004, 1, 0, 2, 4, \030, 2];
        $body    = implode(array_map("chr", $bytes));
        $headers = ['ack'            => 'ID\\ctest-35459-1423180748247-4597\\c1',
                    'message-id'     => 'ID\\cCD-test-59238-1423527427959-7\\c1\\c2\\c1\\c1',
                    'destination'    => '/queue/test',
                    'timestamp'      => '1423527680019',
                    'expires'        => '1423527700019',
                    'subsc ription'  => '0',
                    'persistent'     => 'true',
                    'content-length' => '8',
                    'priority'       => '4',
                    'reply-to'       => '/remote-temp-queue/ID\\cCD-test-59238-1423527427959-7\\c1\\c1',
                    'correlation-id' => 'Camel-ID -CD-test-cdlocal-59236-1423527427164-0-7'];

        $frame = new Stomp\Message\Bytes($body,$headers);
        $s_frame = $frame->__toString();

        $stomp = $this->stomp;


        foreach (range(0,$iters) as $i) {
            $stomp->_appendToBuffer($s_frame);
        }


        foreach(range(0,$iters) as $i) {
            $frame = $stomp->readFrame();
            $body = $frame->body;

            $this->assertEquals(count($bytes), mb_strlen($body,'8bit'), "byte count doesn't match on iter $i");
            $this->assertEquals($body, $frame->body, "body doesn't match on iter $i");
        }

    }

    public
    function testReceivePacket() {
        // Make sure the buffer is empty to start
        $this->assertEquals('', $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer("MESSAGE\n\n");
        $this->assertEquals("MESSAGE\n\n", $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer("Body\n");
        $this->assertEquals("MESSAGE\n\nBody\n", $this->stomp->_getBuffer());

        $this->stomp->_appendToBuffer("\x00MESSAGE2\x00");
        $this->assertEquals("MESSAGE\n\nBody\n\x00MESSAGE2\x00", $this->stomp->_getBuffer());
    }

    public
    function testBufferContainsMessage() {
        // Make sure the buffer is empty to start
        $this->assertEquals('', $this->stomp->_getBuffer());

        $this->assertFalse($this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer("MESSAGE\n\n");
        $this->assertFalse($this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer("Body\n");
        $this->assertFalse($this->stomp->_bufferContainsMessage());

        $this->stomp->_appendToBuffer("\x00MESSAGE2\x00");
        $this->assertTrue($this->stomp->_bufferContainsMessage());
    }

    public
    function testExtractNextMessage() {
        // Make sure the buffer is empty to start
        $this->assertEquals('', $this->stomp->_getBuffer());

        $this->assertFalse($this->stomp->_bufferContainsMessage());

        // Fill the buffer up with 3 messages
        $this->stomp->_appendToBuffer("MESSAGE1\n\nBODY1\n\x00");
        $this->stomp->_appendToBuffer("MESSAGE2\n\nBODY2\n\x00");
        $this->stomp->_appendToBuffer("MESSAGE3\n\nBODY3\n\x00");
        $this->assertEquals("MESSAGE1\n\nBODY1\n\x00MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00",
                            $this->stomp->_getBuffer());
        $this->assertTrue($this->stomp->_bufferContainsMessage());

        // Extract all 3 sequentially
        $this->assertEquals("MESSAGE1\n\nBODY1\n", $this->stomp->_extractNextMessage());
        $this->assertEquals("MESSAGE2\n\nBODY2\n\x00MESSAGE3\n\nBODY3\n\x00", $this->stomp->_getBuffer());

        $this->assertEquals("MESSAGE2\n\nBODY2\n", $this->stomp->_extractNextMessage());
        $this->assertEquals("MESSAGE3\n\nBODY3\n\x00", $this->stomp->_getBuffer());

        $this->assertEquals("MESSAGE3\n\nBODY3\n", $this->stomp->_extractNextMessage());
        $this->assertEquals("", $this->stomp->_getBuffer());

        // Now that the buffer is empty, add another message
        $this->stomp->_appendToBuffer("MESSAGE4\n\nBODY4\n\x00");
        $this->assertEquals("MESSAGE4\n\nBODY4\n\x00", $this->stomp->_getBuffer());

        // Extract that message
        $this->assertEquals("MESSAGE4\n\nBODY4\n", $this->stomp->_extractNextMessage());
        $this->assertEquals("", $this->stomp->_getBuffer());

        // verify that trying to extract from an empty buffer returns empty string
        $this->stomp->_appendToBuffer("");
        $this->assertEquals("", $this->stomp->_getBuffer());

        // Now that the buffer is empty, add another message
        // This is to verify that extracting a message from an empty buffer doesn't break something
        $this->stomp->_appendToBuffer("MESSAGE5\n\nBODY5\n\x00");
        $this->assertEquals("MESSAGE5\n\nBODY5\n\x00", $this->stomp->_getBuffer());

        // Extract that message
        $this->assertEquals("MESSAGE5\n\nBODY5\n", $this->stomp->_extractNextMessage());
        $this->assertEquals("", $this->stomp->_getBuffer());

        // verify that trying to extract from an empty buffer returns empty string
        $this->stomp->_appendToBuffer("");
        $this->assertEquals("", $this->stomp->_getBuffer());
    }

    /**
     * @dataProvider read_frame_provider
     *
     * @param Stomp\Connection $stomp
     * @param array            $expected_frames
     * @param                  $message
     */
    public
    function testReadFrame(Stomp\Connection $stomp, array $expected_frames, $message) {
        foreach ($expected_frames as $frame) {
            $this->assertEquals($frame, $stomp->readFrame(), $message);
        }
    }

    public
    function read_frame_provider() {
        return [
            $this->read_frame_empty_buffer_empty_socket(),
            $this->read_frame_single_message_buffer_empty_socket(),
            $this->read_frame_multi_message_buffer_empty_socket()
        ];
    }

    protected
    function read_frame_empty_buffer_empty_socket() {
        $stomp = $this->getMockBuilder('\CentralDesktop\Stomp\Connection')
                      ->setConstructorArgs([new NullFactory()])
                      ->setMethods(['_bufferContainsMessage', 'hasFrameToRead'])
                      ->getMock();

        $stomp->expects($this->once())
              ->method('_bufferContainsMessage')
              ->will($this->returnValue(false));

        $stomp->expects($this->once())
              ->method('hasFrameToRead')
              ->will($this->returnValue(false));

        return [
            $stomp,
            [
                false
            ],
            "Expects no frame when buffer and socket are empty."
        ];
    }

    protected
    function read_frame_single_message_buffer_empty_socket() {
        $stomp = $this->getMockBuilder('\CentralDesktop\Stomp\Connection')
                      ->setConstructorArgs([new NullFactory()])
                      ->setMethods(['hasFrameToRead'])
                      ->getMock();

        $stomp->expects($this->once())
              ->method('hasFrameToRead')
              ->will($this->returnValue(false));

        $stomp->_appendToBuffer("MESSAGE1\n\nBODY1\n\x00");

        return [
            $stomp,
            [
                new Stomp\Frame(
                    "MESSAGE1",
                    [],
                    "BODY1\n"
                )
            ],
            "Expected a frame for MESSAGE1."
        ];
    }

    protected
    function read_frame_multi_message_buffer_empty_socket() {
        $stomp = $this->getMockBuilder('\CentralDesktop\Stomp\Connection')
                      ->setConstructorArgs([new NullFactory()])
                      ->setMethods(['hasFrameToRead'])
                      ->getMock();

        $stomp->expects($this->once())
              ->method('hasFrameToRead')
              ->will($this->returnValue(false));

        $stomp->_appendToBuffer("MESSAGE1\n\nBODY1\n\x00");
        $stomp->_appendToBuffer("MESSAGE2\n\nBODY2\n\x00");
        $stomp->_appendToBuffer("MESSAGE3\n\nBODY3\n\x00");

        return [
            $stomp,
            [
                new Stomp\Frame(
                    "MESSAGE1",
                    [],
                    "BODY1\n"
                ),
                new Stomp\Frame(
                    "MESSAGE2",
                    [],
                    "BODY2\n"
                ),
                new Stomp\Frame(
                    "MESSAGE3",
                    [],
                    "BODY3\n"
                )
            ],
            "Expected a frame for MESSAGE1, MESSAGE2 and MESSAGE3."
        ];
    }


}

