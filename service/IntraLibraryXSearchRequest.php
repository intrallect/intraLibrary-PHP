<?php

/**
 * An XSearch request, pointed at the /IntraLibrary-XSearch endpoint.
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryXSearchRequest extends IntraLibraryRequest
{
	
	private $responseObject;
	
	/**
	 * Create an IntraLibraryXSearchRequest object
	 * 
	 * @param IntraLibrarySRWResponse $responseObject the object used to handle the response
	 */
	public function __construct(IntraLibrarySRWResponse $responseObject)
	{
		parent::__construct('IntraLibrary-XSearch');
		
		$this->responseObject = $responseObject;
	}
	
	/**
	 * Execute an XSearch query
	 * 
	 * @param array $params An array of options. Required: 'query'. Optional: 'limit', 'username', 'showUnpublished'
	 * @return IntraLibrarySRWResponse
	 */
	public function query($params)
	{
		// query parameter is required
		if (empty($params['query']))
		{
			throw new IntraLibraryException('Missing query parameter');
		}
		
		$queryParams = array(
			'version' => '1.1',
			'operation' => 'searchRetrieve',
			'recordSchema' => $this->responseObject->getRecordSchema(),
			'username' => isset($params['username']) ? $params['username'] : $this->getUsername(),
			'query' => $params['query']
		);
		
		if (!empty($params['limit']) && ((int) $params['limit']) != 0)
		{
			$queryParams['maximumRecords'] = (int) $params['limit'];
		}
		
		if (!empty($params['showUnpublished']))
		{
			$queryParams['showUnpublished'] = 'true';
		}
		
		return $this->get('', $queryParams);
	}
	
	/**
	 * Prepare the response
	 * 
	 * @see IntraLibraryRequest::prepareResponse()
	 * 
	 * @param string $responseData the response data
	 * @return IntraLibrarySRWResponse
	 */
	protected function prepareResponse($responseData)
	{
		$httpCode = $this->getLastResponseCode();
		if ($httpCode < 200 || $httpCode > 399)
		{
			// non-OK http codes for XSearch requests don't return a normal XML response
			$responseData = NULL;
		}
		
		$this->responseObject->load($responseData);
		
		$numRecords 	= count($this->responseObject->getRecords());
		$totalRecords 	= $this->responseObject->getTotalRecords();
		
		if ($numRecords != $totalRecords)
		{
			$requestInfo = $this->getLastRequestInfo();
			error_log("IntraLibrary-PHP: total records ($totalRecords) do not match response count ($numRecords) for $requestInfo");
		}
		
		return $this->responseObject;
	}
}
