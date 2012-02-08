<?php

/**
 * A response object class for the IntraLibrary REST service
 * expecting data in JSON format
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryRESTResponse
{
	private $data = FALSE;
	private $error;
	private $unauthorised;
	
	/**
	 * Load the response data
	 * 
	 * @param string $responseData the response data
	 * @return void
	 */
	public function load($responseData)
	{
		$this->error = $this->data = $this->unauthorised = NULL;
		
		$response = json_decode($responseData, TRUE);
		if (isset($response['intralibrary-ws']['response']))
		{
			$this->data = $response['intralibrary-ws']['response'];
			
			if (isset($this->data['exception']))
			{
				$this->error = $this->data['exception']['message'];
			}
			
			// Hackish, but haven't figure out a way to do this properly yet..
			// we shouldn't be checking on 'message' but on a status code
			if ($this->error == 'Cannot access to this action because :You need to have admin access[false] => FAILED')
			{
				$this->unauthorised = TRUE;
			}
		}
		else
		{
			$this->error = TRUE;
			throw new IntraLibraryException('IntraLibraryRESTResponse responseData invalid');
		}
	}
	
	/**
	 * Get the error from the response
	 * 
	 * @return mixed The error message or TRUE if no error message could be read, or NULL on no error
	 */
	public function getError()
	{
		return $this->error;
	}
	
	/**
	 * Get the response data
	 * 
	 * @return array
	 */
	public function getData()
	{
		return $this->data;
	}
	
	/**
	 * Determine if this response was for an unauthorised request
	 * 
	 * @return boolean
	 */
	public function isUnauthorised()
	{
		return $this->unauthorised;
	}
}
