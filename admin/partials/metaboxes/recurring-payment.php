<?php

global $post;

$recur     = get_post_meta( $post->ID, '_recur', true );
$recurplan = get_post_meta( $post->ID, '_recurplan', true );

if ( empty( $recur ) ) {
	$recur = 'no';
}
if ( empty( $recurplan ) ) {
	$recurplan = '';
}
?>

<div>
	<p>
		<label for="recurring_payment_status" class="post-attributes-label"><?php esc_html_e( 'Recurring Payment', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_recur" id="recurring_payment_status">
		<option value="no" <?php selected( 'no', $recur ); ?>><?php esc_html_e( 'None', 'payment-forms-for-paystack' ); ?></option>
		<option value="optional" <?php selected( 'optional', $recur ); ?>><?php esc_html_e( 'Optional Recurring', 'payment-forms-for-paystack' ); ?></option>
		<option value="plan" <?php selected( 'plan', $recur ); ?>><?php esc_html_e( 'Paystack Plan', 'payment-forms-for-paystack' ); ?></option>
	</select>
</div>

<div>
	<p>
		<label for="recurring_payment_plan_code" class="post-attributes-label"><?php esc_html_e( 'Paystack Recur Plan code', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_recurplan" id="recurring_payment_plan_code" value="<?php echo esc_attr( $recurplan ); ?>" class="widefat"/>
	<p class="description"><small><?php esc_html_e( 'Plan amount must match amount on extra form description.', 'payment-forms-for-paystack' ); ?></small>
	</p>
</div>
