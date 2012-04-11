<?php

/**
 * IntraLibraryRequest is a simple cURL wrapper
 * 
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class IntraLibraryRequest
{
	private $curlHandler;
	private $apiUrl;
	private $username;
	private $password;
	private $requestURL;
	private $responseCode;
	private $responseType;
	
	/**
	 * Construct an IntraLibrary request object
	 * 
	 * @param string $apiEndpoint the api endpoint to be called (appended to the intralibrary hostname)
	 * @throws IntraLibraryException if a hostname has not been configured with the IntraLibraryConfiguration class
	 */
	public function __construct($apiEndpoint)
	{
		$config = IntraLibraryConfiguration::get();
		
		if (empty($config->hostname))
		{
			throw new IntraLibraryException('Configuration Exception: hostname not configured');
		}
		
		if (!preg_match('/^http[s]?:\/\//', $config->hostname))
			$config->hostname = 'http://' . $config->hostname;
		
		$this->apiUrl = $config->hostname . '/' . $apiEndpoint;
		
		$this->setLogin($config->username, $config->password);
	}
	
	/**
	 * Perform an IntraLibrary request
	 * 
	 * @param string $method the request method 
	 * @param array  $params the request method parameters
	 * @return mixed 
	 */
	public function get($method = '', array $params = array())
	{
		$this->requestURL = $this->apiUrl . $method;
		if ($params)
		{
			$queryString = http_build_query($params, NULL, '&');
			// preg_replace helps http_build_query accept arrays as parameters (for duplicate param names)
			$queryString = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $queryString);
			$this->requestURL .= '?' . $queryString;
		}
		
		if ($this->curlHandler === NULL)
		{
			$this->curlHandler = curl_init();
		}
		
		curl_setopt($this->curlHandler, CURLOPT_URL, $this->requestURL);
		curl_setopt($this->curlHandler, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->curlHandler, CURLOPT_HEADER, FALSE);
		curl_setopt($this->curlHandler, CURLINFO_HEADER_OUT, TRUE);
		
		if ($this->username && $this->password)
		{
			curl_setopt($this->curlHandler, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($this->curlHandler, CURLOPT_USERPWD, "$this->username:$this->password");
		}
		
		$responseData 		= curl_exec($this->curlHandler);
		$this->responseCode = curl_getinfo($this->curlHandler, CURLINFO_HTTP_CODE);
		$this->responseType = curl_getinfo($this->curlHandler, CURLINFO_CONTENT_TYPE);
		
		// log this request
		IntraLibraryDebug::log(curl_getinfo($this->curlHandler, CURLINFO_HEADER_OUT));
		
		// reset the curl handler
		curl_close($this->curlHandler);
		$this->curlHandler 	= NULL;
		
		if ($this->responseCode < 200 || $this->responseCode > 399)
		{
			$message  = "IntraLibrary request to <pre style='font-weight: normal;'>{$this->requestURL}</pre> by user {$this->username} received status code {$this->responseCode}";
			$message .= "<pre style='font-weight:normal;'>" . htmlentities(substr($responseData, 0, 1000)) . "</pre>";
			IntraLibraryDebug::screen($message);
		}
		
		return $this->prepareResponse($responseData);
	}
	
	/**
	 * Perform an admin-level authenticated IntraLibrary request 
	 * 
	 * @param string $method the request method
	 * @param array  $params the request params
	 * @return mixed
	 */
	public function adminGet($method = '', $params = array())
	{
		// remember the current setting
		$username = $this->username;
		$password = $this->password;
		
		// authenticate as the admin
		$ilConfig = IntraLibraryConfiguration::get();
		
		if (empty($ilConfig->admin_username) || empty($ilConfig->admin_password))
		{
			throw new IntraLibraryException('IntraLibrary is not configured with admin login credentials');
		}
		
		$this->setLogin($ilConfig->admin_username, $ilConfig->admin_password);
		
		$this->curlHandler = curl_init();
		
		$cookiePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'intralibrary-admin.cookie';
		curl_setopt($this->curlHandler, CURLOPT_COOKIEFILE, $cookiePath);
		curl_setopt($this->curlHandler, CURLOPT_COOKIEJAR, $cookiePath);
		
		$response = $this->get($method, $params);
		
		// restore original settings
		$this->setLogin($username, $password);
		
		return $response;
	}
	
	/**
	 * Set the basic authentication credentials for this request
	 * 
	 * @param string $username the username
	 * @param string $password the password
	 * @return void
	 */
	public function setLogin($username, $password)
	{
		$this->username = $username;
		$this->password = $password;
	}
	
	/**
	 * Prepare the response. Subclasses should override this function
	 * 
	 * @param string $responseData the response data
	 * @return mixed
	 */
	protected function prepareResponse($responseData)
	{
		return $responseData;
	}
	
	/**
	 * Get the username used in this request's basic authentication
	 * 
	 * @return string the username or NULL 
	 */
	protected function getUsername()
	{
		return $this->username;
	}
	
	/**
	 * Get the last request's URL
	 * 
	 * @return string
	 */
	public function getLastRequestURL()
	{
		return $this->requestURL;
	}
	
	/**
	 * Get an information string of the last request in the format
	 * url [username/password]
	 * 
	 * @return string
	 */
	public function getLastRequestInfo()
	{
		return $this->getLastRequestURL() . " [$this->username/$this->password]";
	}
	
	/**
	 * Get the last http response code
	 * 
	 * @return integer
	 */
	public function getLastResponseCode()
	{
		return $this->responseCode;
	}
	
	/**
	 * Get the content type for the last request
	 * 
	 * @return string
	 */
	public function getLastContentType()
	{
		return $this->responseType;
	}
}
