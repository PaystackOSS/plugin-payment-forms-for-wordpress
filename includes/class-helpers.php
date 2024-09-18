<?php
/**
 * A class of helper functions that are used in many places.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Helper class.
 */
class Helpers {

	/**
	 * Construct the class.
	 */
	public function __construct() {
	}

	/**
	 * Fetch an array of the plans by the form ID.
	 *
	 * @param integer $form_id
	 * @param string $paid '1' or '0'
	 * @return array
	 */
	public function get_payments_by_id( $form_id = 0, $paid = '1' ) {
        global $wpdb;
		$results = array();
		if ( 0 === $form_id ) {
			return $results;
		}
        $table   = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * 
				FROM %i 
				WHERE post_id = %d 
				AND paid = %s",
				$table,
				$form_id,
				$paid
			)
		);
		return $results;
	}

	/**
	 * Gets the payments count for the current form.
	 *
	 * @param int|string $form_id
	 * @return int
	 */
	public function get_payments_count( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		$num   = wp_cache_get( 'form_payments_' . $form_id, 'pff_paystack' );
		if ( false === $num ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$num = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM %i
					WHERE post_id = %d
					AND paid = '1'",
					$table,
					$form_id
				)
			);
			wp_cache_set( 'form_payments_' . $form_id, $num, 'pff_paystack', 60*5 );
		}
		return $num;
	}
}