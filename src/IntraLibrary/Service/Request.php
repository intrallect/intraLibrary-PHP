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
        if (empty($url)) {
            throw new IntraLibraryException('Configuration Exception: empty url');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL, FILTER_FLAG_SCHEME_REQUIRED)) {
            throw new IntraLibraryException("Configuration Exception: url must include scheme (supplied: $url)");
        }

        return $url;
    }

    /**
     * Set a curl resource to use the admin cookie file
     *
     * @param resource $curlHandle the curl handle to configure with the admin cookie
     * @throws IntraLibraryException
     */
    public static function useAdminCookie($curlHandle)
    {
        $cookiePath = Configuration::get('admin_cookie_path');
        if (empty($cookiePath)) {
            $cookiePath = sys_get_temp_dir() . DIRECTORY_SEPARATOR . 'intralibrary-admin.cookie';
        }

        if (!self::isWritable($cookiePath)) {
            throw new IntraLibraryException("Unable to write to IntraLibrary admin cookie $cookiePath");
        }

        curl_setopt($curlHandle, CURLOPT_COOKIEFILE, $cookiePath);
        curl_setopt($curlHandle, CURLOPT_COOKIEJAR, $cookiePath);
    }

    /**
     * Determines whether a filepath is writable
     *
     * @param string $filename the filename
     * @return boolean
     */
    private static function isWritable($filename)
    {
        if (file_exists($filename)) {
            return is_writable($filename);
        }

        $filename = dirname($filename);
        if (is_dir($filename)) {
            return is_writable($filename);
        }

        return false;
    }

    private $curlHandler;
    private $apiEndpoint;
    private $apiUrl;
    private $username;
    private $password;
    private $requestURL;
    private $responseData;
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
     * @param string   $method     the request method
     * @param array    $params     (optional) the request method parameters
     * @param resource $curlHandle (optional) the curl handle to use
     * @return mixed
     */
    public function get($method = '', array $params = array(), $curlHandle = null)
    {
        // prepare the request URL
        $this->requestURL = $this->apiUrl . $method;
        if ($params) {
            $queryString = http_build_query($params, null, '&');
            // preg_replace helps http_build_query accept arrays as parameters (for duplicate param names)
            $queryString = preg_replace('/%5B(?:[0-9]|[1-9][0-9]+)%5D=/', '=', $queryString);
            $this->requestURL .= '?' . $queryString;
        }

        // initialise and configure the curl handle
        if ($curlHandle === null) {
            $curlHandle = curl_init();
        }
        $responseHeaders = array();
        $this->configureCurlHandle($curlHandle, $responseHeaders);

        // execute the curl handle
        if ($this->curlHandler) {
            $this->curlHandler->preCurl($curlHandle);
        }

        $this->responseData = curl_exec($curlHandle);
        $this->responseCode = curl_getinfo($curlHandle, CURLINFO_HTTP_CODE);
        $this->responseType = curl_getinfo($curlHandle, CURLINFO_CONTENT_TYPE);

        if ($this->curlHandler) {
            $this->curlHandler->postCurl($curlHandle, $this->responseData);
        }

        // check if any errors have occured
        $error = curl_errno($curlHandle);
        if ($error) {
            $errorMsg = curl_error($curlHandle);
            Debug::log("cURL error while requesting $this->requestURL: $errorMsg ($error)");
        } else {
            // otherwise log this request
            Debug::log("Request Headers:\n" . curl_getinfo($curlHandle, CURLINFO_HEADER_OUT));
            Debug::log("Response Headers:\n" . implode("", $responseHeaders));
        }

        curl_close($curlHandle);

        if ($this->responseCode < 200 || $this->responseCode > 399) {
            $responseSnippet = htmlentities(substr($this->responseData, 0, 1000));
            $message  = "IntraLibrary request to <pre style='font-weight: normal;'>{$this->requestURL}</pre>";
            $message .= " by user {$this->username} received status code {$this->responseCode}";
            $message .= "<pre style='font-weight:normal;'>$responseSnippet</pre>";
            Debug::screen($message);
        }

        return $this->prepareResponse($this->responseData);
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

        if (empty($ilConfig->admin_username) || empty($ilConfig->admin_password)) {
            throw new IntraLibraryException('IntraLibrary is not configured with admin login credentials');
        }

        $curlHandle = curl_init();

        // setup admin login credentials
        $this->setLogin($ilConfig->admin_username, $ilConfig->admin_password);

        // configure the admin cookie
        self::useAdminCookie($curlHandle);

        $response = $this->get($method, $params, $curlHandle);

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
     * @return string the username or null
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
    public function getLastRequestUrl()
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
        return $this->getLastRequestUrl() . " [$this->username/$this->password]";
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
     * Get the response data of the last request
     *
     * @return string
     */
    public function getLastResponseData()
    {
        return $this->responseData;
    }



    /**
     * Configure a curl handle
     *
     * @param resource $curlHandle      the curl handle
     * @param array    $responseHeaders the response headers
     */
    private function configureCurlHandle($curlHandle, &$responseHeaders)
    {
        curl_setopt($curlHandle, CURLOPT_URL, $this->requestURL);
        curl_setopt($curlHandle, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curlHandle, CURLOPT_HEADER, false);
        curl_setopt($curlHandle, CURLINFO_HEADER_OUT, true);
        curl_setopt($curlHandle, CURLOPT_CONNECTTIMEOUT_MS, Configuration::get('timeout') ?: 5000);
        curl_setopt(
            $curlHandle,
            CURLOPT_HEADERFUNCTION,
            // @codingStandardsIgnoreStart
            function ($curlHandle, $header) use (&$responseHeaders) {
                $responseHeaders[] = $header;
                return strlen($header);
            }
            // @codingStandardsIgnoreEnd
        );

        if ($this->username && $this->password) {
            curl_setopt($curlHandle, CURLOPT_HTTPAUTH, CURLAUTH_BASIC);
            curl_setopt($curlHandle, CURLOPT_USERPWD, "$this->username:$this->password");
        }
    }
}

