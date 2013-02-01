<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * CURLFileHandler saves curl requests to files
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class CURLFileSaveHandler implements CURLHandler
{
	private $savepath;
	private $file;

	/**
	 * Create a CURLFileSaveHandler object
	 *
	 * @param string $savepath the path to save the response to
	 */
	public function __construct($savepath)
	{
		$this->savepath = $savepath;
	}

	/**
	 * Get the path of the saved file
	 *
	 * @return string
	 */
	public function getFilepath()
	{
		return $this->savepath;
	}

	/**
	 * Execute pre curl_exec
	 *
	 * @param resource $curlHandle the curl handle being executed
	 * @return void
	 */
	public function preCurl($curlHandle)
	{
		$this->file = fopen($this->savepath, 'w');
		if (!$this->file)
		{
			throw new IntraLibraryException('Unable to create file for saving');
		}

		curl_setopt($curlHandle, CURLOPT_BINARYTRANSFER, 1);
		curl_setopt($curlHandle, CURLOPT_TIMEOUT, 50);
		curl_setopt($curlHandle, CURLOPT_FILE, $this->file);
	}

	/**
	 * Execute post curl_exec
	 *
	 * @param resourec $curlHandle the curl handle that was executed
	 * @param string   $response   the response from curl_exec
	 * @return void
	 */
	public function postCurl($curlHandle, $response)
	{
		if ($this->file)
		{
			fclose($this->file);
		}
	}
}
