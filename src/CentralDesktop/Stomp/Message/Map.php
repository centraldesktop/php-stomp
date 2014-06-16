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

namespace CentralDesktop\Stomp\Message;
use CentralDesktop\Stomp;

/**
 * Message that contains a set of name-value pairs
 *
 * @package Stomp
 */
class Map extends Stomp\Message\Bytes {
    public $map;

    /**
     * Constructor
     *
     * @param Frame|string $msg
     * @param array        $headers
     */
    function __construct($msg, $headers = null) {
        if ($msg instanceof Stomp\Frame) {
            $this->_init($msg->command, $msg->headers, $msg->body);

            if ($msg->headers['transformation'] == 'jms-map-xml') {
                $this->map = self::decode_xml($msg->body);
            }
            elseif ($msg->headers['transformation'] == 'jms-map-json') {
                $this->map = self::decode_json($msg->body);
            }

            if (count($this->map) == 0) {
                error_log("Json error: " . json_last_error() . " on: " . $msg->body);
                error_log("Body length is: " . strlen($msg->body));
            }

        }
        else {

            if(!is_array($headers)) {
            	$headers = array();
            }

            $headers = array_merge($headers, array('transformation' => 'jms-map-json'));

            parent::__construct(json_encode($msg), $headers);
        }
    }


    static
    function decode_json($body) {
        return json_decode($body, true);
    }

    static
    function decode_xml($body) {

        $parser = new Stomp\ParseXMLMap();
        $parser->XML($body);
        $map = $parser->parse();

        return $map;

    }
}

