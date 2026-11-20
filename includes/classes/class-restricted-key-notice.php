<?php
/**
 * Warns administrators when Paystack has refused a request for a missing restricted-key permission.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricted key notice class.
 */
class Restricted_Key_Notice {

	/**
	 * Construct the class.
	 */
	public function __construct() {
		add_action( 'admin_notices', array( $this, 'admin_notice' ) );
	}

	/**
	 * Display the admin notice.
	 *
	 * @return void
	 */
	public function admin_notice() {
		if ( ! current_user_can( Settings::get_capability() ) ) {
			return;
		}

		$screen = get_current_screen();
		if ( ! $screen || ( 'dashboard' !== $screen->id && 'plugins' !== $screen->id && 'paystack_form' !== $screen->post_type ) ) {
			return;
		}

		foreach ( Restricted_Key::get_denied() as $mode => $modules ) {
			$labels = array();
			foreach ( array_keys( $modules ) as $module ) {
				$label = Restricted_Key::module_label( $module );
				if ( '' !== $label ) {
					$labels[] = esc_html( $label );
				}
			}
			if ( ! $labels ) {
				continue;
			}

			$message = sprintf(
				/* translators: 1: test or live, 2: the features that were refused. */
				esc_html__( 'Paystack refused a request in %1$s mode because your restricted key is missing a permission needed for: %2$s. Update the key\'s permissions in your Paystack Dashboard, then paste the key again and save in Paystack Forms settings.', 'pff-paystack' ),
				esc_html( $mode ),
				implode( ', ', $labels )
			);

			printf(
				'<div class="notice notice-error"><p>%1$s <a href="%2$s">%3$s</a> | <a href="%4$s" target="_blank" rel="noopener noreferrer">%5$s</a></p></div>',
				$message, // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above.
				esc_url( admin_url( 'edit.php?post_type=paystack_form&page=settings' ) ),
				esc_html__( 'Open settings', 'pff-paystack' ),
				esc_url( Settings::DASHBOARD_URL ),
				esc_html__( 'Open Paystack Dashboard', 'pff-paystack' )
			);
		}
	}
}
