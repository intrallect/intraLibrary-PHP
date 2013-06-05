<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * CURLFileHandler post a file via curl requests
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class CURLFilePostHandler implements CURLHandler
{
    private $filepath;
    private $fileParameter;

    /**
     * Create a CURLFileHandler object
     *
     * @param string $filepath      the path to the file
     * @param string $fileParameter the name of the parameter for this file
     */
    public function __construct($filepath, $fileParameter)
    {
        $this->filepath = $filepath;
        $this->fileParameter = $fileParameter;
    }

    /**
     * Execute pre curl_exec
     *
     * @param resource $curlHandle the curl handle being executed
     * @return void
     */
    public function preCurl($curlHandle)
    {
        $file_to_upload = array(
                $this->fileParameter => '@' . $this->filepath
        );

        curl_setopt($curlHandle, CURLOPT_VERBOSE, 1);
        curl_setopt($curlHandle, CURLOPT_POST, 1);
        curl_setopt($curlHandle, CURLOPT_POSTFIELDS, $file_to_upload);
    }

    /**
     * Execut post curl_exec
     *
     * @param resourec $curlHandle the curl handle being executed
     * @param string   $response   the response from curl_exec
     * @return void
     */
    public function postCurl($curlHandle, $response)
    {
        // Nothing to do here
    }
}

