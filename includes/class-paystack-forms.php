<?php
/**
 * The main plugin class, this will return the and instance of the class.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

/**
 * Plugin class.
 *
 * @package  Accommodation
 */
class Payment_Forms {

	/**
	 * Holds class isntance
	 *
	 * @var      object|\paystack\payment_forms\Payment_Forms
	 */
	protected static $instance = null;

	/**
	 * Initialize the plugin by setting localization, filters, and
	 * administration functions.
	 *
	 * @access private
	 */
	private function __construct() {
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return    object|\paystack\payment_forms\Payment_Forms
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}
}