<?php

namespace IntraLibrary\Service;

/**
 * A response object class for the IntraLibrary REST service
 * expecting an IntraLibrary-REST endpoint file download response
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class RESTFileResponse extends RESTResponse
{
    /**
     * Load the response data
     *
     * @param mixed $responseData the response data
     * @return void
     */
    public function load($responseData)
    {
        // successful file download responses via CURL
        // will return true
        if ($responseData !== true) {
            parent::load($responseData);
        }
    }
}

