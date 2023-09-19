<?php

global $post;

$days    = get_post_meta( $post->ID, '_startdate_days', true );
$plan    = get_post_meta( $post->ID, '_startdate_plan_code', true );
$enabled = get_post_meta( $post->ID, '_startdate_enabled', true );

if ( empty( $days ) ) {
	$days = '';
}
if ( empty( $plan ) ) {
	$plan = '';
}
if ( empty( $enabled ) ) {
	$enabled = 0;
}
?>

<div>
	<p>
		<label for="start_date_days" class="post-attributes-label"><?php esc_html_e( 'Number of Days', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="number" name="_startdate_days" id="start_date_days" value="<?php echo esc_attr( $days ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="start_date_plan_code" class="post-attributes-label"><?php esc_html_e( 'Plan', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_startdate_plan_code" id="start_date_plan_code" value="<?php echo esc_attr( $plan ); ?>" class="widefat"/>
</div>

<div>
	<p>
		<label for="start_date_enabled" class="post-attributes-label">
			<input name="_startdate_enabled" id="start_date_enabled" type="checkbox" value="1" <?php checked( 1, esc_attr( $enabled ) ); ?>>
			<?php esc_html_e( 'Enable', 'payment-forms-for-paystack' ); ?>
		</label>
	</p>
</div>
