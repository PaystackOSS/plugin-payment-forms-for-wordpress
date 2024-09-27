<?php
/**
 * A request to verify the current transaction code.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Transaction_Verify
 */
class Request_Subscription extends API {

	/**
	 * Construct the class.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_module( 'subscription' );
	}

	/**
	 * Create a plan for the customer with their amount and interval.
	 *
	 * @return boolean|object
	 */
	public function create_subscription( $body = [] ) {
		$sub = false;
		if ( empty( $body ) || ! $this->api_ready() ) {
			return false;
		}
		$response = $this->post_request( $body );
		if ( isset( $response->status ) && true === $response->status ) {
			$sub = $response;
		}
		return $sub;
	}
}