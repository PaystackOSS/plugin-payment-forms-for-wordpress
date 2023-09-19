<?php

global $post;

$subaccount     = get_post_meta( $post->ID, '_subaccount', true );
$txnbearer      = get_post_meta( $post->ID, '_txnbearer', true );
$merchantamount = get_post_meta( $post->ID, '_merchantamount', true );

if ( empty( $subaccount ) ) {
	$subaccount = '';
}
if ( empty( $merchantamount ) ) {
	$merchantamount = '';
}
?>

<div>
	<p>
		<label for="sub_account_code" class="post-attributes-label"><?php esc_html_e( 'Sub Account Code', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_subaccount" id="sub_account_code" value="<?php echo esc_attr( $subaccount ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="sub_account_txn_bearer" class="post-attributes-label"><?php esc_html_e( 'Transaction Charge Bearer', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_txnbearer" id="sub_account_txn_bearer">
		<option value="no" <?php selected( 'account', $txnbearer ); ?>>
			<?php esc_html_e( 'Merchant (default)', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="yes" <?php selected( 'subaccount', $txnbearer ); ?>>
			<?php esc_html_e( 'Sub Account', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
</div>

<div>
	<p>
		<label for="sub_account_merchant_amount" class="post-attributes-label"><?php esc_html_e( 'Merchant Amount', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_merchantamount" id="sub_account_merchant_amount" value="<?php echo esc_attr( $merchantamount ); ?>" class="widefat"/>
</div>
