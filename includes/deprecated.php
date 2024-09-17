<?php
/**
 * The deprecated functions that might be in use.
 */

/**
 * The old plugin initilizer.
 *
 * @return \paystack\payment_forms\Payment_Forms
 * @deprecated 3.4.2
 */
function kkd_pff_paystack_run_paystack_forms() {
    return \paystack\payment_forms\Payment_Forms::get_instance();
}