<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * An XSearch request, pointed at the /IntraLibrary-XSearch endpoint.
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class XSearchRequest extends AbstractSRURequest
{
    private $xsearchUsername;

    /**
     * Get the API endpoint for the request
     *
     * @return string
     */
    protected function getEndpoint()
    {
        return 'IntraLibrary-XSearch';
    }

    // @codingStandardsIgnoreStart
    /**
     * Set the XSearch user
     *
     * @param string $username the intralibrary username
     * @return void
     */
    public function setXSearchUsername($username)
    // @codingStandardsIgnoreEnd
    {
        $this->xsearchUsername = $username;
    }

    /**
     * Update the request parameters
     *
     * @param array $requestParams The request parameres.
     * @return array modified request parameters
     */
    protected function updateRequestParams($requestParams)
    {
        $requestParams['username'] = empty($this->xsearchUsername) ? $this->getUsername() : $this->xsearchUsername;

        return $requestParams;
    }
}

