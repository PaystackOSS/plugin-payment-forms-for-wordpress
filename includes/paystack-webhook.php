<?php
// require_once('../../../../wp-load.php');
		
 

if ( ( strtoupper( $_SERVER['REQUEST_METHOD'] ) != 'POST' ) || ! array_key_exists('HTTP_X_PAYSTACK_SIGNATURE', $_SERVER) ) {
	exit;
}

$json = file_get_contents( "php://input" );

// validate event do all at once to avoid timing attack
if ( $_SERVER['HTTP_X_PAYSTACK_SIGNATURE'] !== hash_hmac( 'sha512', $json, $this->secret_key ) ) {
	exit;
}

$event = json_decode( $json );

if ( 'charge.success' == $event->event ) {

	http_response_code( 200 );

	$order_details 		= explode( '_', $event->data->reference );

	$order_id 			= (int) $order_details[0];

    $order 				= wc_get_order($order_id);

    $paystack_txn_ref 	= get_post_meta( $order_id, '_paystack_txn_ref', true );

    if ( $event->data->reference != $paystack_txn_ref ) {
    	exit;
    }

    if ( in_array( $order->get_status(), array( 'processing', 'completed', 'on-hold' ) ) ) {
		exit;
    }

	$order_total	= $order->get_total();

	$amount_paid	= $event->data->amount / 100;

	$paystack_ref 	= $event->data->reference;

	// check if the amount paid is equal to the order amount.
	if ( $order_total !=  $amount_paid ) {

		$order->update_status( 'on-hold', '' );

		add_post_meta( $order_id, '_transaction_id', $paystack_ref, true );

		$notice = 'Thank you for shopping with us.<br />Your payment transaction was successful, but the amount paid is not the same as the total order amount.<br />Your order is currently on-hold.<br />Kindly contact us for more information regarding your order and payment status.';
		$notice_type = 'notice';

		// Add Customer Order Note
        $order->add_order_note( $notice, 1 );

        // Add Admin Order Note
        $order->add_order_note('<strong>Look into this order</strong><br />This order is currently on hold.<br />Reason: Amount paid is less than the total order amount.<br />Amount Paid was <strong>&#8358;'.$amount_paid.'</strong> while the total order amount is <strong>&#8358;'.$order_total.'</strong><br />Paystack Transaction Reference: '.$paystack_ref );

		$order->reduce_order_stock();

		wc_add_notice( $notice, $notice_type );

		wc_empty_cart();

	} else {

		$order->payment_complete( $paystack_ref );

		$order->add_order_note( sprintf( 'Payment via Paystack successful (Transaction Reference: %s)', $paystack_ref ) );

		wc_empty_cart();

	}

	$this->save_card_details( $event, $order->get_user_id(), $order_id );

	exit;
}

exit;




?>