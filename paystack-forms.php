<?php
/*
  Plugin Name:  Payment Forms for Paystack
  Plugin URI:   https://github.com/PaystackHQ/Wordpress-Payment-forms-for-Paystack
  Description:  Payment Forms for Paystack allows you create forms that will be used to bill clients for goods and services via Paystack.
  Version:      4.0.0
  Author:       Paystack
  Author URI:   http://paystack.com
  License:      GPL-2.0+
  License URI:  http://www.gnu.org/licenses/gpl-2.0.txt
*/
// If this file is called directly, abort.
if (!defined('WPINC')) {
    die;
}
define( 'KKD_PFF_PAYSTACK_PLUGIN_PATH', plugins_url( __FILE__ ) );
define( 'KKD_PFF_PAYSTACK_MAIN_FILE', __FILE__ );
define( 'KKD_PFF_PAYSTACK_VERSION', '4.0.0' );
define( 'KKD_PFF_PAYSTACK_TABLE', 'paystack_forms_payments' );
define( 'KKD_PFF_PLUGIN_BASENAME', plugin_basename(__FILE__) );

include_once KKD_PFF_PAYSTACK_PLUGIN_PATH . '/includes/class-paystack-forms.php';

/**
 * Returns an instance of the Paystack Payment forms Object
 *
 * @return object \paystack\payment_forms\Payment_Forms()
 */
function kkd_pff_paystack() {
	return \paystack\payment_forms\Payment_Forms::get_instance();
}
$_GLOBAL['kkd_pff_paystack'] = kkd_pff_paystack();