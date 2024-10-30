<?php

class CourseStorm_API {
	protected $_subdomain;
	protected $_apiUsername;
	protected $_apiPassword;
	protected $_ch;
	protected $_defaultRequestHeaders = array(
		'Accept: application/json'
	);
	protected $_requestHeaders = array();
	protected $_responseHeaders = array();
	protected $_environment; // "live" or "dev" are possible values.
	protected $_timeoutInSeconds;
	protected $_lastResult;

	public function __construct($subdomain, $environment = 'dev') {
		$this->_subdomain = $subdomain;
		$this->_environment = $environment;
	}

	/**
	 * Get
	 *
	 * Runs a CourseStorm API call to retrieve data at a certain path.
	 *
	 * @param string $path The path to the object.  Ie, /course/{courseId}
	 * @param array $params Optional list of parameters to pass in query string.
	 * @return array|false
	 */
	public function get($path, $params = null) {
		if ($params != null) {
			$path .= '?'.http_build_query($params);
		}
		
		$this->_newRequest($path);
		$result = $this->_fetch();

		if (!$result->success) {
			$this->reportError($result->errorMessage);
			return false;
		}

		return $result->body;
	}

	public function post($path, $params) {
		return $this->_customRequest($path, $params, 'POST');
	}

	public function delete($path) {
		$this->_newRequest($path);
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, 'DELETE');

		return $this->_fetch();
	}

	public function setTimeoutInSeconds($newTimeout) {
		$this->_timeoutInSeconds = $newTimeout;
	}
	
	public function getLastResult() {
		return $this->_lastResult;
	}

	public function reportError($error) {
		// mail($this->_apiUsername, 'CourseStorm API Error for site: '.$_SERVER['SERVER_NAME'], $error);
	}

	public function setSubdomain($newSubdomain) {
		$this->_subdomain = $newSubdomain;
	}

	public function onResponseHeader($ch, $headerString) {
		if (!preg_match('/\:/', $headerString)) {
			// Skip it.
			return strlen($headerString);
		}

		list($headerKey, $headerValue) = explode(':', $headerString, 2);

		$this->_responseHeaders[trim($headerKey)] = trim($headerValue);

		return strlen($headerString);
	}

	protected function _customRequest($path, $params, $requestMethod) {
		$this->_newRequest($path);

		$json = json_encode($params);
		curl_setopt($this->_ch, CURLOPT_CUSTOMREQUEST, $requestMethod);
		curl_setopt($this->_ch, CURLOPT_POSTFIELDS, $json);

		$this->_requestHeaders[] = 'Content-Type: application/json';
		$this->_requestHeaders[] = 'Content-Length: ' . strlen($json);

		return $this->_fetch();
	}

	protected function _newRequest($path) {
		$this->_ch = curl_init();

		$tld = $this->_environment == 'dev' ? 'com' : 'com';
		curl_setopt($this->_ch, CURLOPT_URL, 'https://'.$this->_subdomain.'.coursestorm.'.$tld.'/api/v2'.$path);
		if ($this->_timeoutInSeconds) {
			curl_setopt($this->_ch, CURLOPT_TIMEOUT, $this->_timeoutInSeconds);
		}

		if ($this->_environment == 'dev') {
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYPEER, false);
			curl_setopt($this->_ch, CURLOPT_SSL_VERIFYHOST, false);
		}

		curl_setopt($this->_ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($this->_ch, CURLOPT_HEADERFUNCTION, array($this, 'onResponseHeader'));

		$this->_requestHeaders = $this->_defaultRequestHeaders;
		$this->_responseHeaders = array();
	}

	protected function _fetch() {
		curl_setopt($this->_ch, CURLOPT_HTTPHEADER, $this->_requestHeaders);

		$result = curl_exec($this->_ch);

		if (curl_errno($this->_ch)) {
			throw new Exception('cURL Error: '.curl_errno($this->_ch).' - '.curl_error($this->_ch), true);
		}

		$result = json_decode($result, true);

		$apiResult = new stdClass;
		$apiResult->success = true;
		$apiResult->errorMessage = null;
		$apiResult->body = $result;

		if (isset($this->_responseHeaders['X-Total-Page-Count'])) {
			$apiResult->resultsPageCount = intval($this->_responseHeaders['X-Total-Page-Count']);
			$apiResult->resultsTotalAvailableItems = intval($this->_responseHeaders['X-Total-Available-Results']);
		}

		$statusCode = curl_getinfo($this->_ch, CURLINFO_HTTP_CODE);
		$apiResult->statusCode = $statusCode;
		if (substr(strval($statusCode), 0, 1) != '2') {
			$apiResult->success = false;
			$apiResult->errorMessage = $result['message'];
		}
		
		$this->_lastResult = $apiResult;

		return $apiResult;
	}

	/**
	 * Get Query Params
	 *
	 * Get an array of approved query parameters
	 * 
	 * @param array $params submitted query parameters
	 * @param string|null $prefix prefix for submitted params
	 * @param array $params_whitelist approved query params
	 * @return array $return formatted query parameters
	 */
    public function getQueryParams( array $params, string $prefix = null, array $params_whitelist )
    {
		$return = [];
		
		foreach ( $params as $key => $value ) {
            $key = str_replace( $prefix, '', $key );

			switch ( $key ) {
				case 'location' :
					$new_key = 'near_city';

					// Override $key if we have a zip code
					if ( $this->_isValidZipCode($value) ) {
						$new_key = 'near_zip';
					}
					break;
				case 'term' :
					$new_key = 'search';
					break;
				default :
					$new_key = $key;
					break;
			}

			if ( in_array( $new_key, $params_whitelist ) ) {
                $return[$new_key] = $value;
            }
		}

        return $return;
    }

    private function _isValidZipCode($zipCode) {
        return (preg_match('/^[0-9]{5}(-[0-9]{4})?$/', $zipCode)) ? true : false;
    }
}
