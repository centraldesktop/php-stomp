<?php
/**
 *
 * Copyright (C) 2009 Progress Software, Inc. All rights reserved.
 * http://fusesource.com
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

// include a library
require_once("Stomp.php");
// make a connection
$con = new Stomp("tcp://localhost:61613");
// connect
$con->connect();
$con->setReadTimeout(1);

// subscribe to the queue
$con->subscribe("/queue/transactions", array('ack' => 'client','activemq.prefetchSize' => 1 ));

// send some messages to the queue
for ($i = 1; $i < 4; $i++) {
	$con->send("/queue/transactions", $i);
}

$con->begin("tx1");
echo "Beginning transaction 'tx1'\n";
// receive some messages from the queue
for ($i = 1; $i < 3; $i++) {
	$msg = $con->readFrame();
	echo "Received message with body '$msg->body'\n";
    $con->ack($msg, "tx1");
}
echo "Aborting transaction 'tx1'\n";
$con->abort("tx1");

//try again
$con->begin("tx2");
echo "Beginning transaction 'tx2'\n";
for ($i = 1; $i < 4; $i++) {
	$msg = $con->readFrame();
	echo "Received message with body '$msg->body'\n";
    $con->ack($msg, "tx2");
}
$con->commit("tx2");
echo "Committing transaction 'tx2'\n";

//ensure there are no more messages in the queue
$frame = $con->readFrame();

if ($frame === false) {
	echo "No more messages in the queue\n";
} else {
	echo "Warning: some messages still in the queue: $frame\n";
}

// disconnect
$con->disconnect();
?>