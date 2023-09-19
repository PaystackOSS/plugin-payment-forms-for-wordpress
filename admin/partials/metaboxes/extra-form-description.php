<?php

global $post;

$amount            = get_post_meta( $post->ID, '_amount', true );
$paybtn            = get_post_meta( $post->ID, '_paybtn', true );
$successmsg        = get_post_meta( $post->ID, '_successmsg', true );
$txncharge         = get_post_meta( $post->ID, '_txncharge', true );
$loggedin          = get_post_meta( $post->ID, '_loggedin', true );
$currency          = get_post_meta( $post->ID, '_currency', true );
$filelimit         = get_post_meta( $post->ID, '_filelimit', true );
$redirect          = get_post_meta( $post->ID, '_redirect', true );
$minimum           = get_post_meta( $post->ID, '_minimum', true );
$usevariableamount = get_post_meta( $post->ID, '_usevariableamount', true );
$variableamount    = get_post_meta( $post->ID, '_variableamount', true );
$hidetitle         = get_post_meta( $post->ID, '_hidetitle', true );

if ( empty( $amount ) ) {
	$amount = 0;
}
if ( empty( $filelimit ) ) {
	$filelimit = 2;
}
if ( empty( $paybtn ) ) {
	$paybtn = 'Pay';
}
if ( empty( $successmsg ) ) {
	$successmsg = 'Thank you for paying!';
}
if ( empty( $currency ) ) {
	$currency = 'NGN';
}
if ( empty( $txncharge ) ) {
	$txncharge = 'merchant';
}
if ( empty( $minimum ) ) {
	$minimum = 0;
}
if ( empty( $usevariableamount ) ) {
	$usevariableamount = 0;
}
if ( empty( $hidetitle ) ) {
	$hidetitle = 0;
}
?>

<div>
	<p>
		<label for="extra_form_description_hide_form_title" class="post-attributes-label">
			<input name="_hidetitle" id="extra_form_description_hide_form_title" type="checkbox" value="1" <?php checked( 1, esc_attr( $hidetitle ) ); ?>>
			<?php esc_html_e( 'Hide the form title', 'payment-forms-for-paystack' ); ?>
		</label>
	</p>
</div>

<div>
	<p>
		<label for="extra_form_description_currency" class="post-attributes-label"><?php esc_html_e( 'Currency', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_currency" id="extra_form_description_currency">
		<option value="NGN" <?php selected( 'NGN', $currency ); ?>><?php esc_html_e( 'Nigerian Naira', 'payment-forms-for-paystack' ); ?></option>
		<option value="GHS" <?php selected( 'GHS', $currency ); ?>><?php esc_html_e( 'Ghanaian Cedis', 'payment-forms-for-paystack' ); ?></option>
		<option value="ZAR" <?php selected( 'ZAR', $currency ); ?>><?php esc_html_e( 'South African Rand', 'payment-forms-for-paystack' ); ?></option>
		<option value="KES" <?php selected( 'KES', $currency ); ?>><?php esc_html_e( 'Kenyan Shillings', 'payment-forms-for-paystack' ); ?></option>
		<option value="USD" <?php selected( 'USD', $currency ); ?>><?php esc_html_e( 'US Dollars', 'payment-forms-for-paystack' ); ?></option>
	</select>
	<p class="description">
		<small>
			<?php
				echo wp_kses(
					__( 'Ensure you are activated for the currency you are selecting. Check <a href="https://support.paystack.com/hc/en-us/articles/360009973799-Can-I-accept-payments-in-US-Dollars-USD-" target="_blank"><em>here</em></a> for more information.', 'payment-forms-for-paystack' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				)
            ?>
		</small>
	</p>
</div>

<div>
	<p>
		<label for="extra_form_description_amount" class="post-attributes-label"><?php esc_html_e( 'Amount To Be Paid. (Set 0 For Customer Input)', 'payment-forms-for-paystack' ); ?></label>
	</p>

	<input type="number" name="_amount" id="extra_form_description_amount" value="<?php echo esc_attr( $amount ); ?>" class="widefat"/>

	<div style="margin-top:0.5em;">
		<label for="extra_form_description_minimum">
			<input name="_minimum" id="extra_form_description_minimum" type="checkbox" value="1" <?php checked( 1, esc_attr( $minimum ) ); ?>>
			<?php esc_html_e( 'Make amount minimum payable', 'payment-forms-for-paystack' ); ?>
		</label>
	</div>
</div>

<div>
	<p>
		<label for="extra_form_description_variable_amount" class="post-attributes-label">
			<?php
			echo wp_kses(
				__( 'Variable Dropdown Amount:<code>Format(option:amount): Option 1:10000,Option 2:3000 Separate options with ","</code>', 'payment-forms-for-paystack' ),
				array(
					'code' => array(),
				)
			)
			?>
		</label>
	</p>
	<input type="text" name="_variableamount" id="extra_form_description_variable_amount" value="<?php echo esc_attr( $variableamount ); ?>" class="widefat"/>
	<div style="margin-top:0.5em;">
		<label for="extra_form_description_use_variable_amount">
			<input name="_usevariableamount" id="extra_form_description_use_variable_amount" type="checkbox" value="1" <?php checked( 1, esc_attr( $usevariableamount ) ); ?>>
			<?php esc_html_e( 'Use dropdown amount option', 'payment-forms-for-paystack' ); ?>
		</label>
	</div>
</div>

<div>
	<p>
		<label for="extra_form_description_pay_button_description" class="post-attributes-label"><?php esc_html_e( 'Pay Button Description', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_paybtn" id="extra_form_description_pay_button_description" value="<?php echo esc_attr( $paybtn ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="extra_form_description_add_extra_charge" class="post-attributes-label"><?php esc_html_e( 'Add Extra Charge', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_txncharge" id="extra_form_description_add_extra_charge">
		<option value="merchant" <?php selected( 'merchant', $txncharge ); ?>>
			<?php esc_html_e( 'No, do not add', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="customer" <?php selected( 'customer', $txncharge ); ?>>
			<?php esc_html_e( 'Yes, add it', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
	<p class="description">
		<small>
			<?php
			printf(
				wp_kses(
					/* translators: Payment Forms for Paystack settings URL */
					__( 'This allows you to include an extra charge to cushion the effect of the transaction fee. <a href="%s" target="_blank"><em>Configure</em></a>', 'payment-forms-for-paystack' ),
					array(
						'a' => array(
							'href'   => array(),
							'target' => array(),
						),
					)
				),
				esc_url( get_admin_url() . 'edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php#paystack_setting_fees' )
			);
			?>
		</small>
	</p>
</div>

<div>
	<p>
		<label for="extra_form_description_user_logged_in" class="post-attributes-label"><?php esc_html_e( 'User Logged In', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_loggedin" id="extra_form_description_user_logged_in">
		<option value="no" <?php selected( 'no', $loggedin ); ?>>
			<?php esc_html_e( 'User must not be logged in', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="yes" <?php selected( 'yes', $loggedin ); ?>>
			<?php esc_html_e( 'User must be logged in', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
</div>

<div>
	<p>
		<label for="extra_form_description_success_message" class="post-attributes-label"><?php esc_html_e( 'Success Message After Payment', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<textarea rows="6" name="_successmsg" id="extra_form_description_success_message" class="widefat"><?php echo esc_html( $successmsg ); ?></textarea>
</div>

<div>
	<p>
		<label for="extra_form_description_file_size_limit" class="post-attributes-label"><?php esc_html_e( 'File Upload Limit (MB)', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="number" name="_filelimit" id="extra_form_description_file_size_limit" value="<?php echo esc_attr( $filelimit ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="extra_form_description_redirect_url" class="post-attributes-label"><?php esc_html_e( 'Redirect To Page Link After Payment. (Keep Blank to Use Normal Success Message)', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="url" name="_redirect" id="extra_form_description_redirect_url" value="<?php echo esc_url( $redirect ); ?>" class="widefat"/>
</div>
