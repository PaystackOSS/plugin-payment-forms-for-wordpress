<?php
/**
 * A request to retrieve the "Plan" codes from your Paystack Account.
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
class Request_Plan extends API {

	/**
	 * Construct the class.
	 */
	public function __construct() {
		parent::__construct();
		$this->set_module( 'plan' );
	}

	/**
	 * Send a request to Paystack and get the Plan Object.
	 *
	 * @return boolean|object
	 */
	public function fetch_plan( $code = '' ) {
		$plan = false;
		if ( '' === $code || ! $this->api_ready() ) {
			return false;
		}
		$plan = $this->get_request( $code );
		return $plan;
	}

	public function plan_exists( $code = '' ) {

	}
}