<?php

global $post;

$subject      = get_post_meta( $post->ID, '_subject', true );
$merchant     = get_post_meta( $post->ID, '_merchant', true );
$heading      = get_post_meta( $post->ID, '_heading', true );
$message      = get_post_meta( $post->ID, '_message', true );
$send_receipt = get_post_meta( $post->ID, '_sendreceipt', true );
$send_invoice = get_post_meta( $post->ID, '_sendinvoice', true );

if ( empty( $subject ) ) {
	$subject = __( 'Thank you for your payment', 'payment-forms-for-paystack' );
}
if ( empty( $send_receipt ) ) {
	$send_receipt = 'yes';
}
if ( empty( $send_invoice ) ) {
	$send_invoice = 'yes';
}
if ( empty( $heading ) ) {
	$heading = __( 'We&#8217;ve received your payment', 'payment-forms-for-paystack' );
}
if ( empty( $message ) ) {
	$message = __( 'Your payment was received and we appreciate it.', 'payment-forms-for-paystack' );
}
?>

<div>
	<p>
		<label for="email_receipt_send_invoice" class="post-attributes-label">
			<?php esc_html_e( 'Send an Invoice When a Payment Is Attempted', 'payment-forms-for-paystack' ); ?>
		</label>
	</p>
	<select class="form-control widefat" name="_sendinvoice" id="email_receipt_send_invoice">
		<option value="no" <?php selected( 'no', $send_invoice ); ?>>
			<?php esc_html_e( 'Don&#8217;t Send', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="yes" <?php selected( 'yes', $send_invoice ); ?>>
			<?php esc_html_e( 'Send', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
</div>

<div>
	<p>
		<label for="email_receipt_send_receipt" class="post-attributes-label"><?php esc_html_e( 'Send Email Receipt', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_sendreceipt" id="email_receipt_send_receipt">
		<option value="no" <?php selected( 'no', $send_receipt ); ?>>
			<?php esc_html_e( 'Don&#8217;t Send', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="yes" <?php selected( 'yes', $send_receipt ); ?>>
			<?php esc_html_e( 'Send', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
</div>

<div>
	<p>
		<label for="email_receipt_email_subject" class="post-attributes-label"><?php esc_html_e( 'Email Subject', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_subject" id="email_receipt_email_subject" value="<?php echo esc_attr( $subject ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="email_receipt_merchant_name" class="post-attributes-label"><?php esc_html_e( 'Merchant Name on Receipt', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_merchant" id="email_receipt_merchant_name" value="<?php echo esc_attr( $merchant ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="email_receipt_heading" class="post-attributes-label"><?php esc_html_e( 'Email Heading', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_heading" id="email_receipt_heading" value="<?php echo esc_attr( $heading ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="email_receipt_message" class="post-attributes-label"><?php esc_html_e( 'Email Body/Message', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<textarea rows="6" name="_message" id="email_receipt_message" class="widefat"><?php echo esc_html( $message ); ?></textarea>
</div>
