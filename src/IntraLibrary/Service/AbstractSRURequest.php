<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * An abstract SRU request class
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
abstract class AbstractSRURequest extends Request
{
    private $responseObject;

    /**
     * Create an XSearchRequest object
     *
     * @param SRWResponse $responseObject the object used to handle the response
     */
    public function __construct(SRWResponse $responseObject)
    {
        parent::__construct($this->getEndpoint());

        $this->responseObject = $responseObject;
    }

    /**
     * Get the API endpoint for the request
     *
     * @return string
     */
    abstract protected function getEndpoint();

    /**
     * Update the request parameters
     *
     * @param array $requestParams The request parameres.
     * @return array modified request parameters
     */
    abstract protected function updateRequestParams($requestParams);

    /**
     * Execute an XSearch query
     *
     * @param array $params An array of options. Required: 'query'. Optional: 'limit', 'username', 'showUnpublished'
     * @return SRWResponse
     */
    public function query($params)
    {
        // query parameter is required
        if (empty($params['query'])) {
            throw new IntraLibraryException('Missing query parameter');
        }

        $requestParams = array(
            'version' => '1.1',
            'operation' => 'searchRetrieve',
            'recordSchema' => $this->responseObject->getRecordSchema(),
            'query' => $params['query']
        );

        if (!empty($params['limit']) && ((int) $params['limit']) != 0) {
            $requestParams['maximumRecords'] = (int) $params['limit'];
        }

        if (!empty($params['startRecord']) && ((int) $params['startRecord']) != 0) {
            $requestParams['startRecord'] = (int) $params['startRecord'];
        }

        if (!empty($params['showUnpublished'])) {
            $requestParams['showUnpublished'] = 'true';
        }

        $requestParams = $this->updateRequestParams($requestParams);

        return $this->get('', $requestParams);
    }

    /**
     * Prepare the response
     *
     * @see Request::prepareResponse()
     *
     * @param string $responseData the response data
     * @return SRWResponse
     */
    protected function prepareResponse($responseData)
    {
        $httpCode = $this->getLastResponseCode();
        if ($httpCode < 200 || $httpCode > 399) {
            // non-OK http codes for XSearch requests don't return a normal XML response
            $responseData = null;
        }

        $this->responseObject->load($responseData);

        $numRecords 	= count($this->responseObject->getRecords());
        $totalRecords 	= $this->responseObject->getTotalRecords();

        if ($numRecords != $totalRecords) {
            $requestInfo = $this->getLastRequestInfo();
            $messageFormat = "IntraLibrary-PHP: total records (%s) do not match response count (%s) for %s";
            error_log(sprintf($messageFormat, $totalRecords, $numRecords, $requestInfo));
        }

        return $this->responseObject;
    }
}

