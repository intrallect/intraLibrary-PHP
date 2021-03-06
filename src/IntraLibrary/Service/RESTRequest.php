<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * A REST request, pointed at the /IntraLibrary-REST/ endpoint.
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko <j.lasocki-biczysko@intrallect.com>
 */
class RESTRequest extends Request
{

    /**
     * @var RESTResponse
     */
    private $responseObject;

    /**
     * Create an RESTRequest object
     *
     * @param RESTResponse $responseObject the object to handle the response data
     */
    public function __construct(RESTResponse $responseObject = null)
    {
        parent::__construct('IntraLibrary-REST/');

        if ($responseObject == null) {
            $responseObject = new RESTResponse();
        }

        $this->responseObject = $responseObject;
    }

    /**
     * Requests a response from the REST service, attempts to default the output
     * content type as JSON
     *
     * @see Request::get()
     *
     * @param string    $method    the method to call
     * @param array    $params     the parameters that will be psased to this method (as HTTP GET parameters)
     * @param resource $curlHandle (optional) the curl handle to use
     * @return RESTResponse
     */
    public function get($method = '', array $params = array(), $curlHandle = null)
    {
        // Faster to parse JSON than XML
        return parent::get($method, array_merge(array('output' => 'json'), $params), $curlHandle);
    }

    /**
     * Send an admin-level authenticated request.
     * If the response comes back unauthorised, this will automatically attempt to set an admin
     * authorisation level and try again.
     *
     * @see Request::adminGet()
     *
     * @param string $method The request method
     * @param array  $params the request params
     * @return RESTResponse
     */
    public function adminGet($method = '', $params = array())
    {
        // send a normal 'adminGet'
        $originalResp = parent::adminGet($method, $params);

        // if it isn't authorised, try to authorise it and
        if ($originalResp->isUnauthorised()) {
            // create a new response object for the authorisation call
            $this->responseObject = new RESTResponse();

            // authorise the "adminGet" session (ie. set cURL's cookie)
            $authResponse = parent::adminGet('Test/authentication');

            // no auto-recovery if there's an error
            if ($error = $authResponse->getError()) {
                throw new IntraLibraryException($error, -1);
            }

            // restore the original response object and request the same data again
            $this->responseObject = $originalResp;
            return parent::adminGet($method, $params);
        }

        return $originalResp;
    }

    /**
     * Decode the JSON response
     *
     * @see Request::prepareResponse()
     *
     * @param string $responseData the data returned from the request
     * @return RESTResponse
     */
    protected function prepareResponse($responseData, $curlError)
    {
        try {
            $this->responseObject->load($responseData, $curlError);
        } catch (Exception $ex) {
            error_log('Failed to load REST response from ' . $this->getLastRequestInfo());
            error_log('Exception: ' . $ex->getMessage());

            // Do some nicer logging based on content type
            $contentTypeHeader 	= explode(';', $this->getLastContentType());
            $contentType 		= strtolower(trim($contentTypeHeader[0]));
            switch ($contentType) {
                case 'text/html';
                    $responseData = strip_tags($responseData);
                    error_log("Response (HTML tags stripped, $contentType): $responseData");
                    break;
                default:
                    error_log("Response (RAW, $contentType): $responseData");
                    break;
            }
        }

        return $this->responseObject;
    }
}

