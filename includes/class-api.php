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
	 * The Public Key
	 *
	 * @var string
	 */
	protected $public = '';

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$mode = esc_attr( get_option( 'mode' ) );
		if ( $mode == 'test' ) {
			$this->public = esc_attr( get_option( 'tsk' ) );
		} else {
			$this->public = esc_attr( get_option( 'lsk' ) );
		}
	}

	/**
	 * Sets the module variable.
	 *
	 * @param string $module
	 * @return void
	 */
	protected function set_module( $module = '' ) {
		$this->module = $module;
	}

	/**
	 * Sets the additional URl arguments.
	 *
	 * @param string $module
	 * @return void
	 */
	protected function set_url_args( $args = '' ) {
		$this->url_args = $args;
	}

	/**
	 * Gets the headers for the current request.
	 *
	 * @return void
	 */
	protected function get_headers(){
		return array(
			'Authorization' => 'Bearer ' . $this->public
		);
	}

	/**
	 * Gets the headers for the current request.
	 *
	 * @return void
	 */
	protected function get_url(){
		return $this->url . $this->module . $this->url_args;
	}

	/**
	 * Gets the arguments for the current request.
	 *
	 * @return void
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
		$request  = wp_remote_get( $this->get_url(), $this->get_args() );
		if ( ! is_wp_error( $request ) ) {
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
		var_dump($this->public);
		if ( '' !== $this->public ) {
			$ready = true;
		}
		return $ready;
	}
}