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
    private $showUnpublished;

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
     * Enable/disable unpublished resources
     *
     * @param boolean $enabled Whether to display unpublished resources
     * @return void
     */
    public function setShowUnpublished($enabled)
    {
        $this->showUnpublished = (boolean) $enabled;
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

        if ($this->showUnpublished) {
            $requestParams['showUnpublished'] = true;
        }

        return $requestParams;
    }
}

