CentralDesktop STOMP Library
----------------------------

Includes partial 1.2 support (including NACK), circular buffers, XML maps (in addition to JSON) and a whole host of fixes and improvements to the fusesource.org code base from which we worked some years ago.

License:  Apache

To run unit tests:

    composer install (or composer update)
    vendor/bin/phpunit

Fork of: http://stomp.fusesource.org

## Usage

```php
  use CentralDesktop\Stomp\Connection;
  $con = new Connection("failover://(tcp://somehost:61612)");
  
  // connect with some bad credentials and stomp protocol 1.2 (default 1.0 currently)
  $con->connect('username','password',1.2);
  
  // send a message to the "test" queue with body of payload
  // and with the message attribute persistent:true
  $con->send("test", "payload", array("persistent" => 'true'));
```
