<?php

global $post;

$useagreement  = get_post_meta( $post->ID, '_useagreement', true );
$agreementlink = get_post_meta( $post->ID, '_agreementlink', true );

if ( empty( $useagreement ) ) {
	$useagreement = 'no';
}
if ( empty( $agreementlink ) ) {
	$agreementlink = '';
}
?>

<div>
	<p>
		<label for="agreement_checkbox_use_checkbox" class="post-attributes-label"><?php esc_html_e( 'Use Agreement Checkbox', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<select class="form-control widefat" name="_useagreement" id="agreement_checkbox_use_checkbox">
		<option value="no" <?php selected( 'no', $useagreement ); ?>>
			<?php esc_html_e( 'No', 'payment-forms-for-paystack' ); ?>
		</option>
		<option value="yes" <?php selected( 'yes', $useagreement ); ?>>
			<?php esc_html_e( 'Yes', 'payment-forms-for-paystack' ); ?>
		</option>
	</select>
</div>

<div>
	<p>
		<label for="agreement_checkbox_link" class="post-attributes-label"><?php esc_html_e( 'Agreement Page Link', 'payment-forms-for-paystack' ); ?></label>
	</p>
	<input type="text" name="_agreementlink" id="agreement_checkbox_link" value="<?php echo esc_url( $agreementlink ); ?>" class="widefat"/>
</div>
