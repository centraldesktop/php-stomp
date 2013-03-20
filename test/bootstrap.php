<?php

$autoload_path =  __DIR__ . "/../vendor/autoload.php";
require_once($autoload_path);

$loader =   ComposerAutoloaderInit::getLoader();
$loader->add('CentralDesktop\Stomp\Test', __DIR__);