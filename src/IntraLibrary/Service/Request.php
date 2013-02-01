<?php

namespace IntraLibrary\Service;

use \IntraLibrary\IntraLibraryException;
use \IntraLibrary\Configuration;
use \IntraLibrary\Debug;

/**
 * IntraLibrary Request is a simple cURL wrapper
 *
 * @package IntraLibrary_PHP
 * @author  Janek Lasocki-Biczysko, <j.lasocki-biczysko@intrallect.com>
 */
class Request
{
	/**
	 * Determines if a string is a valid url
	 *
	 * @param string $url the url to validate
	 * @throws IntraLibraryException
	 * @return string the url if it's valid
	 */
	public static function validateUrl($url)
	{
		if (empty($url))
		{
			throw new IntraLibraryException('Configuration Exception: empty url');
		}

		if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED))
		{
			throw new IntraLibraryException("Configuration Exception: url must include scheme (supplied: $url)");
		}

		return $url;
	}

	private $headers;
	private $curlHandle;
	private $curlHandler;
	private $apiEndpoint;
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
	 * @throws IntraLibraryException if a hostname has not been configured with the Configuration class
	 */
	public function __construct($apiEndpoint)
	{
		$this->apiEndpoint = $apiEndpoint;

		$this->setHostname(Configuration::get('hostname'));
		$this->setLogin(
			Configuration::get('username'),
			Configuration::get('password')
		);
	}

	/**
	 * Set the curl handler for this request
	 *
	 * @param CURLHandler $curlHandler the curl handler
	 * @return void
	 */
	public function setCurlHandler(CURLHandler $curlHandler)
	{
		$this->curlHandler = $curlHandler;
	}

	/**
	 * Set the hostname for this request
	 *
	 * @param string $hostname the hostname
	 * @return void
	 */
	public function setHostname($hostname)
	{
		self::validateUrl($hostname);
		$this->apiUrl = rtrim($hostname, '/') . '/' . $this->apiEndpoint;
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

		if ($this->curlHandle === NULL)
		{
			$this->curlHandle = curl_init();
		}

		curl_setopt($this->curlHandle, CURLOPT_URL, $this->requestURL);
		curl_setopt($this->curlHandle, CURLOPT_RETURNTRANSFER, TRUE);
		curl_setopt($this->curlHandle, CURLOPT_HEADER, FALSE);
		curl_setopt($this->curlHandle, CURLINFO_HEADER_OUT, TRUE);
		curl_setopt($this->curlHandle, CURLOPT_VERBOSE, 1);
		curl_setopt($this->curlHandle, CURLOPT_HEADERFUNCTION, array($this, '_consumeHeader'));

		if ($this->username && $this->password)
		{
			curl_setopt($this->curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
			curl_setopt($this->curlHandle, CURLOPT_USERPWD, "$this->username:$this->password");
		}

		if ($this->curlHandler)
		{
			$this->curlHandler->preCurl($this->curlHandle);
		}

		// execute
		$this->headers = array();
		$responseData = curl_exec($this->curlHandle);

		// and log this request
		Debug::log(curl_getinfo($this->curlHandle, CURLINFO_HEADER_OUT));
		Debug::log("Response Headers:\n" . implode("", $this->headers));

		if ($this->curlHandler)
		{
			$this->curlHandler->postCurl($this->curlHandle, $responseData);
		}

		$this->responseCode = curl_getinfo($this->curlHandle, CURLINFO_HTTP_CODE);
		$this->responseType = curl_getinfo($this->curlHandle, CURLINFO_CONTENT_TYPE);

		// reset the curl handler
		curl_close($this->curlHandle);
		$this->curlHandle 	= NULL;

		if ($this->responseCode < 200 || $this->responseCode > 399)
		{
			$message  = "IntraLibrary request to <pre style='font-weight: normal;'>{$this->requestURL}</pre> by user {$this->username} received status code {$this->responseCode}";
			$message .= "<pre style='font-weight:normal;'>" . htmlentities(substr($responseData, 0, 1000)) . "</pre>";
			Debug::screen($message);
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
		$ilConfig = Configuration::get();

		if (empty($ilConfig->admin_username) || empty($ilConfig->admin_password))
		{
			throw new IntraLibraryException('IntraLibrary is not configured with admin login credentials');
		}

		$this->setLogin($ilConfig->admin_username, $ilConfig->admin_password);

		$this->curlHandle = curl_init();

		$cookiePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'intralibrary-admin.cookie';
		if (!$this->_isWritable($cookiePath))
		{
			throw new IntraLibraryException("Unable to write to IntraLibrary admin cookie $cookiePath");
		}

		curl_setopt($this->curlHandle, CURLOPT_COOKIEFILE, $cookiePath);
		curl_setopt($this->curlHandle, CURLOPT_COOKIEJAR, $cookiePath);

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

	/**
	 * Determines whether a filepath is writable
	 *
	 * @param string $filename the filename
	 * @return boolean
	 */
	private function _isWritable($filename)
	{
		if (file_exists($filename))
		{
			return is_writable($filename);
		}

		$filename = dirname($filename);
		if (is_dir($filename))
		{
			return is_writable($filename);
		}

		return FALSE;
	}

	/**
	 * Consumer a header entry
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 * @SuppressWarnings(PHPMD.UnusedPrivateMethod)
	 *
	 * @param resource $curlHandle the curl handle
	 * @param string   $header     the header being processed
	 * @return integer
	 */
	private function _consumeHeader($curlHandle, $header)
	{
		$this->headers[] = $header;
		return strlen($header);
	}
}
