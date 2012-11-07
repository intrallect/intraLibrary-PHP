<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;

/**
 * IntraLibraryCURLFileHandler saves curl requests to files
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class CURLFileSaveHandler implements CURLHandler
{
	private $savepath;
	private $file;

	/**
	 *
	 * @param string $savepath
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
	 * @param resource $curlHandle
	 */
	public function preCurl($curlHandle)
	{
		$this->file = fopen($this->savepath, 'w');
		if (!$this->file)
		{
			throw new IntraLibraryException('Unable to create file for saving');
		}

		curl_setopt($curlHandle, CURLOPT_FILE, $this->file);
	}

	/**
	 * @param resourec $curlHandle
	 * @param string   $response
	 */
	public function postCurl($curlHandle, $response)
	{
		if ($this->file)
		{
			fclose($this->file);
		}
	}
}
