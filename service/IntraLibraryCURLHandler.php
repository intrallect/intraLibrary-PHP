<?php

/**
 * IntraLibraryCURLHanlder provides an interface to interact with 
 * IntraLibraryRequest curl handles
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
interface IntraLibraryCURLHandler
{
	
	/**
	 * Will fire before a curl request is executed
	 * 
	 * @param resource $curlHandle the curl handle
	 */
	public function preCurl($curlHandle);
	
	/**
	 * Will fire after a curl request is executed
	 * 
	 * @param resource $curlHandle the curl handle
	 * @param string   $response   the response
	 */
	public function postCurl($curlHandle, $response);
}
