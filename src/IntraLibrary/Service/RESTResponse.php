<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * A response object class for the IntraLibrary REST service
 * expecting data in JSON format
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class RESTResponse
{
    private $data = false;
    private $error;
    private $unauthorised;

    /**
     * Load the response data
     *
     * @param string $responseData the response data
     * @return void
     */
    public function load($responseData)
    {
        $this->error = $this->data = $this->unauthorised = null;

        $response = json_decode($responseData, true);
        if (isset($response['intralibrary-ws']['response'])) {
            $this->data = $response['intralibrary-ws']['response'];

            if (isset($this->data['exception'])) {
                $exception = $this->data['exception'];
                $this->error = $exception['message'];

                if (isset($exception['_attributes']['class'])
                        && $exception['_attributes']['class'] == 'AccessDeniedException') {
                    $this->unauthorised = true;
                }
            }
        } else {
            $this->error = true;
            $message = 'RESTResponse responseData invalid';

            if ($error = json_last_error()) {
                $message .= " (json decode error $error)";
            }

            throw new IntraLibraryException($message);
        }
    }

    /**
     * Get the error from the response
     *
     * @return mixed The error message or true if no error message could be read, or null on no error
     */
    public function getError()
    {
        return $this->error;
    }

    /**
     * Get the response data
     *
     * @return array
     */
    public function getData()
    {
        return $this->data;
    }

    /**
     * Determine if this response was for an unauthorised request
     *
     * @return boolean
     */
    public function isUnauthorised()
    {
        return $this->unauthorised;
    }
}

