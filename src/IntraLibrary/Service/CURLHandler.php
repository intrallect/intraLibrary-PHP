<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * CURLHanlder provides an interface to interact with
 * Request curl handles
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
interface CURLHandler
{

	/**
	 * Will fire before a curl request is executed
	 *
	 * @param resource $curlHandle the curl handle
	 * @return void
	 */
	public function preCurl($curlHandle);

	/**
	 * Will fire after a curl request is executed
	 *
	 * @param resource $curlHandle the curl handle
	 * @param string   $response   the response
	 * @return void
	 */
	public function postCurl($curlHandle, $response);
}
