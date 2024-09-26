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
		$this->set_url_args( $code );
		$response = $this->get_request();
		if ( $this->is_plan_valid( $response ) ) {
			$plan = $response;
		}
		return $plan;
	}

	/**
	 * Reviews the plan parameters to see if the plan is active.
	 *
	 * @param object $plan
	 * @return boolean
	 */
	public function is_plan_valid( $plan ) {
		if ( null === $plan ) {
			return false;
		}
		if ( ! isset( $plan->status ) || false === $plan->status ) {
			return false;
		}
		if ( ! isset( $plan->data->is_archived ) || true === $plan->data->is_archived ) {
			return false;
		}
		if ( ! isset( $plan->data->is_deleted ) || true === $plan->data->is_deleted ) {
			return false;
		}
		return true;
	}
}