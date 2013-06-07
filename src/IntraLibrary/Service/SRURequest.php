<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * An XSearch request, pointed at the /IntraLibrary-XSearch endpoint.
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class SRURequest extends AbstractSRURequest
{
    const TOKEN_TAG = 'x-info-2-auth1.0-authenticationToken';

    protected $token = null;

    /**
     *
     * @param SRWResponse $responseObject
     */
    public function __construct(SRWResponse $responseObject) {
        parent::__construct($responseObject);
        $this->setLogin(null, null);
    }

    /**
     * Get the API endpoint for the request
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return 'IntraLibrary-SRU';
    }

    /**
     * Set the Collection token for this request
     *
     * @param string $token the collection authentication token
     * @return void
     */
    public function setToken($token)
    {
        $this->token = $token;
    }

    /**
     * Update the request parameters
     *
     * @param array $requestParams The request parameres.
     * @return array modified request parameters
     */
    protected function updateRequestParams($requestParams)
    {
        if ($this->token !== null)
        {
            $requestParams[self::TOKEN_TAG] = $this->token;
        }

        return $requestParams;
    }
}

