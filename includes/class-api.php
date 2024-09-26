<?php
/**
 * A class of API functions used to create send requests to and from Paystack.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin API Class
 */
class API {

	/**
	 * The API Request URL
	 *
	 * @var string
	 */
	protected $url = 'https://api.paystack.co/';

	/**
	 * The module you are try to access "plan", "subscription", "transaction".
	 *
	 * @var string
	 */
	protected $module = '';

	/**
	 * A string of additional arguments to add to the end of the URL, to be prepended after the "module".
	 *
	 * @var string
	 */
	protected $url_args = '';

	/**
	 * The Public API Key
	 *
	 * @var string
	 */
	protected $public = '';

	/**
	 * The Private API Key
	 *
	 * @var string
	 */
	private $secret = '';

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$mode = esc_attr( get_option( 'mode' ) );
		if ( $mode == 'test' ) {
			$this->public = esc_attr( get_option( 'tpk' ) );
			$this->secret = esc_attr( get_option( 'tsk' ) );
		} else {
			$this->public = esc_attr( get_option( 'lpk' ) );
			$this->secret = esc_attr( get_option( 'lsk' ) );
		}
	}

	/**
	 * Sets the module variable.
	 *
	 * @param string $module
	 * @return string
	 */
	protected function set_module( $module = '' ) {
		$this->module = $module . '/';
	}

	/**
	 * Sets the additional URl arguments.
	 *
	 * @param string $module
	 * @return string
	 */
	protected function set_url_args( $args = '' ) {
		$this->url_args = $args;
	}

	/**
	 * Gets the headers for the current request.
	 *
	 * @return array
	 */
	protected function get_headers(){
		return array(
			'Authorization' => 'Bearer ' . $this->secret
		);
	}

	/**
	 * Gets the headers for the current request.
	 *
	 * @return string
	 */
	protected function get_url(){
		return $this->url . $this->module . $this->url_args;
	}

	/**
	 * Gets the arguments for the current request.
	 *
	 * @return array
	 */
	protected function get_args(){
		return array(
			'headers'    => $this->get_headers(),
			'timeout'    => 60
		);
	}

	/**
	 * Sends the request and checkes to see is is_wp_error().
	 *
	 * @return boolean|object
	 */
	public function get_request() {
		$response = false;
		/*print_r('<pre>');
		print_r($this->get_url());
		print_r($this->get_args());
		print_r('</pre>');*/
		$request  = wp_remote_get( $this->get_url(), $this->get_args() );
		if ( ! is_wp_error( $request ) && 200 === wp_remote_retrieve_response_code( $response ) ) {
			$response = json_decode( wp_remote_retrieve_body( $request ) );
		}
		return $response;
	}

	/**
	 * Determines if all the settings have been entered.
	 *
	 * @return boolean
	 */
	public function api_ready() {
		$ready = false;
		if ( '' !== $this->secret ) {
			$ready = true;
		}
		return $ready;
	}
}