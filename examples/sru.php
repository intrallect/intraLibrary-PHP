<?php

use IntraLibrary\Service\SRURequest;
use IntraLibrary\Service\SRWResponse;

require 'init.php';

IntraLibrary\Configuration::set('hostname', 'http://demonstrator.intralibrary.com');

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

