<?php

namespace Paystack_Plugins\Payment_Forms;

class Paystack_Plugin_Tracker {

	public static function log_transaction( $txn_ref ) {

		$public_key = paystack_forms_get_public_key();

		if ( empty( $public_key ) ) {
			return;
		}

		$url = 'https://plugin-tracker.paystackintegrations.com/log/charge_success';

		$body = array(
			'public_key'            => $public_key,
			'plugin_name'           => 'pff-paystack',
			'transaction_reference' => $txn_ref,
		);

		$args = array(
			'body' => $body,
		);

		wp_remote_post( $url, $args );
	}
}
