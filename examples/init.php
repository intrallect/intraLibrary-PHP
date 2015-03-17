<?php

use IntraLibrary\Loader;
use IntraLibrary\Configuration;
use IntraLibrary\Debug;

error_reporting(E_ALL);

// initialise the class loader
require_once __DIR__ . '/../src/IntraLibrary/Loader.php';
Loader::register(__DIR__ . '/../src');

// Optional:
// register a logging function, which will be called after network requests
// and various other events
Debug::register(
    "log",
    function ($message) {
        echo print_r($message, true) . "\n";
    }
);
