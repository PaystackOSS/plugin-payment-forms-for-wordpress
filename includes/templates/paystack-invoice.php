<?php
require_once ABSPATH . 'wp-load.php';

$code = isset( $_GET['code'] ) ? sanitize_text_field( wp_unslash( $_GET['code'] ) ) : '';

function kkd_format_metadata( $data ) {
	$new  = json_decode( $data );
	$text = '';
	
	if ( is_array( $new ) && array_key_exists( 0, $new ) ) {
		foreach ( $new as $item ) {
			if ( 'text' === $item->type ) {
				$text .= sprintf(
					'<div class="span12 unit">
						<label class="label inline">%s:</label>
						<strong>%s</strong>
					</div>',
					esc_html( $item->display_name ),
					esc_html( $item->value )
				);
			} else {
				$text .= sprintf(
					'<div class="span12 unit">
						<label class="label inline">%s:</label>
						<strong><a target="_blank" href="%s">%s</a></strong>
					</div>',
					esc_html( $item->display_name ),
					esc_url( $item->value ),
					__( 'link', 'pff-paystack' )
				);
			}
		}
	} elseif ( is_object( $new ) ) {
		if ( count( get_object_vars( $new ) ) > 0 ) {
			foreach ( $new as $key => $item ) {
				$text .= sprintf(
					'<div class="span12 unit">
						<label class="label inline">%s:</label>
						<strong>%s</strong>
					</div>',
					esc_html( $key ),
					esc_html( $item )
				);
			}
		}
	}
	return $text;
}

global $wpdb;
$table  = $wpdb->prefix . PFF_PAYSTACK_TABLE;
$record = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM {$table} WHERE txn_code = %s", $code ) );

if ( array_key_exists( 0, $record ) ) {
	get_header();
	$dbdata   = $record[0];
	$currency = get_post_meta( $dbdata->post_id, '_currency', true );
	?>
	<div class="content-area main-content" id="primary">
		<main role="main" class="site-main" id="main">
			<div class="blog_post">
				<article class="post-4 page type-page status-publish hentry" id="post-4">
					<form action="<?php echo esc_url( admin_url( 'admin-ajax.php' ) ); ?>" method="post" enctype="multipart/form-data" class="j-forms retry-form" id="pf-form" novalidate="">
						<input type="hidden" name="action" value="kkd_pff_paystack_retry_action">
						<input type="hidden" name="code" value="<?php echo esc_html( $code ); ?>" />
						<div class="content">

							<div class="divider-text gap-top-20 gap-bottom-45">
								<span><?php _e('Payment Invoice', 'pff-paystack'); ?></span>
							</div>

							<div class="j-row">
								<div class="span12 unit">
									<label class="label inline"><?php _e('Email:', 'pff-paystack'); ?></label>
									<strong><a href="mailto:<?php echo esc_attr( $dbdata->email ); ?>"><?php echo esc_html( $dbdata->email ); ?></a></strong>
								</div>
								<div class="span12 unit">
									<label class="label inline"><?php _e('Amount:', 'pff-paystack'); ?></label>
									<strong><?php echo esc_html( $currency . number_format( $dbdata->amount ) ); ?></strong>
								</div>
								<?php echo kkd_format_metadata( $dbdata->metadata ); ?>
								
								<div class="span12 unit">
									<label class="label inline"><?php _e('Date:', 'pff-paystack'); ?></label>
									<strong><?php echo esc_html( $dbdata->created_at ); ?></strong>
								</div>
								<?php if ( 1 === intval( $dbdata->paid ) ) { ?>
									<div class="span12 unit">
										<label class="label inline"><?php _e('Payment Status:', 'pff-paystack'); ?></label>
										<strong><?php _e('Successful', 'pff-paystack'); ?></strong>
									</div>
								<?php } ?>

							</div>
						</div>

						<div class="footer">
							<small><span style="color: red;">*</span> <?php _e('are compulsory', 'pff-paystack'); ?></small><br>
							<img class="paystack-cardlogos size-full wp-image-1096" alt="cardlogos" src="<?php echo esc_url( plugins_url( '../images/logos@2x.png', __FILE__ ) ); ?>">
							<?php if ( 0 === intval( $dbdata->paid ) ) { ?>
								<button type="submit" class="primary-btn" id='submitbtn'><?php _e('Retry Payment', 'pff-paystack'); ?></button>
							<?php } ?>

						</div>
					</form>
				</article>
			</div>
		</main>
	</div>
	<?php
	get_footer();
} else {
	die(esc_html__('Invoice code invalid', 'pff-paystack'));
}
