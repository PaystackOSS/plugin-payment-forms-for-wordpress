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

	/**
	 * Send a request to the Paystack "List plans" request, and return the first usable one.
	 *
	 * @param string      $url_args Query string for the list request.
	 * @param string      $currency Only return a plan in this currency, or '' for any.
	 * @param int|null    $amount   Only return a plan for this amount in subunits, or null for any.
	 * @param string|null $interval Only return a plan with this interval, or null for any.
	 * @return boolean|object
	 */
	public function list_plans( $url_args = '', $currency = '', $amount = null, $interval = null ) {
		if ( '' === $url_args || ! $this->api_ready() ) {
			return false;
		}
		$this->set_url_args( $url_args );
		$response = $this->get_request();
		if ( ! isset( $response->data ) || ! is_array( $response->data ) ) {
			return false;
		}
		// Paystack lists archived and deleted plans too, and checkout rejects them with "Plan not found".
		// The API filter isn't trusted for the terms: the plan's amount is what checkout charges.
		foreach ( $response->data as $plan ) {
			if ( ! isset( $plan->plan_code ) || ! empty( $plan->is_archived ) || ! empty( $plan->is_deleted ) ) {
				continue;
			}
			if ( '' !== $currency && ( ! isset( $plan->currency ) || $currency !== $plan->currency ) ) {
				continue;
			}
			if ( null !== $amount && ( ! isset( $plan->amount ) || (int) $amount !== (int) $plan->amount ) ) {
				continue;
			}
			if ( null !== $interval && ( ! isset( $plan->interval ) || $interval !== $plan->interval ) ) {
				continue;
			}
			// A payment limit would end the customer's subscription early.
			if ( ! empty( $plan->invoice_limit ) ) {
				continue;
			}
			return $plan;
		}
		return false;
	}

	/**
	 * Create a plan for the customer with their amount and interval.
	 *
	 * @return boolean|object
	 */
	public function create_plan( $body = [] ) {
		$plan = false;
		if ( empty( $body ) || ! $this->api_ready() ) {
			return false;
		}
		$response = $this->post_request( $body );
		if ( isset( $response->data->plan_code ) ) {
			$plan = $response;
		}
		return $plan;
	}
}