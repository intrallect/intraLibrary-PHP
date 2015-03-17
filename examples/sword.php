<?php

use IntraLibrary\Service\SWORDService;

require 'init.php';

$host = ''; // intralibrary host ie. 'http://demonstrator.intralibrary.com'
$username = ''; // intralibrary username
$password = ''; // intralibrary password
$filepath = ''; // file to upload

IntraLibrary\Configuration::set('hostname', $host);

$swordService = new SWORDService($username, $password);
$details = $swordService->getDepositDetails();

foreach ($details as $workspace => $workspaceUrls) {
    foreach ($workspaceUrls as $collection => $url) {

        echo "Depositing $filepath to '$workspace'/'$collection' at $url\n";

        $resp = $swordService->deposit($url, $filepath);

        var_dump($resp);
        exit;
    }
}

echo "Did not find any deposit points..\n";

