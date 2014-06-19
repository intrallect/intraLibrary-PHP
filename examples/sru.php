<?php

use IntraLibrary\Service\SRURequest;
use IntraLibrary\Service\SRWResponse;
use IntraLibrary\Loader;
use IntraLibrary\Configuration;
use IntraLibrary\Debug;

// initialise the class loader
require_once __DIR__ . '/../src/IntraLibrary/Loader.php';
Loader::register(__DIR__ . '/../src');

// configure the client with intraLibrary's url
Configuration::set(
    array(
        'hostname' => 'http://demonstrator.intralibrary.com'
    )
);

// Optional:
// register a logging function, which will be called after network requests
// and various other events
Debug::register(
    "log",
    function ($message) {
        // print_r($message);
    }
);

// make a request and search for 'Test'
$resp = new SRWResponse();
$req = new SRURequest($resp);
$req->query(array('query' => 'Test'));

echo "Found " . $resp->getTotalRecords() . " records\n";

foreach ($resp->getRecords() as $record) {
    $title = $record->get('title');
    if (is_array($title)) {
        $title = implode('", "', $title);
    }
    $description = $record->get('description');
    if (is_array($description)) {
        $description = implode('", "', $description);
    }

    echo "\n Title: \"$title\"\n";
    echo " Description: \"$description\"\n";
}

