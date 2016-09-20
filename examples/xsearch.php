<?php

use IntraLibrary\Service\SRWResponse;
use IntraLibrary\Service\XSearchRequest;

require 'init.php';

$host = getenv('ILHOST') ?: 'http://demonstrator.intralibrary.com';
$user = getenv('ILUSER');
$pass = getenv('ILPASS');
$query = getenv('ILQUERY');

if (!$user || !$pass || !$query) {
    throw new Exception("Missing ILUSER or ILPASS or ILQUERY");
}

IntraLibrary\Configuration::set(array(
    'hostname' => $host,
    'username' => $user,
    'password' => $pass
));

// make a request and search for 'Test'
$resp = new SRWResponse();
$req = new XSearchRequest($resp);

echo "Searching for '$query'...\n";

$req->query(array('query' => '"' . addslashes($query) . '"'));

$error = $resp->getError();
if ($error) {
    echo "Error: $error\n";
    exit;
}

echo "Found " . $resp->getTotalRecords() . " records:\n";

foreach ($resp->getRecords() as $record) {
    $title = $record->get('title');
    if (is_array($title)) {
        $title = implode('", "', $title);
    }
    $description = $record->get('description');
    if (is_array($description)) {
        $description = implode('", "', $description);
    }

    echo "\n Package ID: {$record->getId()}\n";
    echo " Title: \"$title\"\n";
    echo " Description: \"$description\"\n";
}

