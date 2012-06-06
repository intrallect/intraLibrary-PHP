<?php

/**
 * IntraLibraryCURLFileHandler post a file via curl requests
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryCURLFilePostHandler implements IntraLibraryCURLHandler
{
	private $filepath;
	private $fileParameter;
	
	/**
	 * 
	 * @param string $filepath
	 * @param string $fileParameter
	 */
	public function __construct($filepath, $fileParameter)
	{
		$this->filepath = $filepath;
		$this->fileParameter = $fileParameter;
	}
	
	/**
	 * @param resource $curlHandle
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
	 * @param resourec $curlHandle
	 * @param string   $response
	 */
	public function postCurl($curlHandle, $response)
	{
		// Nothing to do here
	}
}