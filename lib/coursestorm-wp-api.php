<?php

/**
 * CourseStorm WP API
 * 
 * Port of the CourseStorm API to be compatible with the WordPress HTTP API
 */
class CourseStorm_WP_API extends CourseStorm_API {
	protected $_tld;
	protected $_uri;
	protected $_path;
	protected $_args;

	public function __construct($subdomain, $environment = 'dev') {
		parent::__construct($subdomain, $environment);

		$this->_tld = COURSESTORM_TLD;
		$this->_uri = 'https://'.$this->_subdomain.'.coursestorm.'.$this->_tld.'/api/v2';
	}
    
  	protected function _newRequest($path, $args = []) {
		$this->_path = $this->_uri . $path;

		if ($this->_timeoutInSeconds) {
			$this->_args['timeout'] = $this->_timeoutInSeconds;
		}

		if ($this->_environment == 'dev') {
			add_action('http_api_curl', function( $handle ){
				//Don't verify SSL certs
				curl_setopt($handle, CURLOPT_SSL_VERIFYPEER, false);
				curl_setopt($handle, CURLOPT_SSL_VERIFYHOST, false);
			}, 10);
		}

		$this->_args['timeout'] = '60';
		$this->_args['headers'] = $this->_defaultRequestHeaders;
		$this->_args['headers']['X-WordPress-Plugin-Version'] = COURSESTORM_PLUGIN_VERSION;
	}

	protected function _fetch() {
		$result = wp_remote_get( $this->_path, $this->_args );

		if ('200' != $statusCode = wp_remote_retrieve_response_code($result)) {
			if ( is_wp_error($result) ) {
				throw new Exception($result->get_error_message(), true);
			}
		}

		$apiResult = new stdClass;
		$apiResult->success = true;
		$apiResult->errorMessage = null;
		$apiResult->body = json_decode(wp_remote_retrieve_body($result));
		$this->_responseHeaders = wp_remote_retrieve_headers($result);

		if (isset($this->_responseHeaders['x-total-page-count'])) {
			$apiResult->resultsPageCount = intval($this->_responseHeaders['x-total-page-count']);
			$apiResult->resultsTotalAvailableItems = intval($this->_responseHeaders['x-total-available-results']);
		}

		$apiResult->statusCode = $statusCode;
		if (substr(strval($statusCode), 0, 1) != '2') {
			$apiResult->success = false;
			$apiResult->errorMessage = isset($result['message']) ? $result['message'] : $result;
		}
		
		$this->_lastResult = $apiResult;

		return $apiResult;
	}
}