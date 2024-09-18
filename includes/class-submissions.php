<?php
namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Submissions class.
 */
class Submissions {

	/**
	 * Constructor
	 */
	public function __construct() {
        add_action( 'admin_menu', [ $this, 'register_submissions_page' ] );
	}

	/**
	 * Register the submissions page
	 *
	 * @return void
	 */
	public function register_submissions_page() {
		add_submenu_page( 'edit.php?post_type=paystack_form', __( 'Submissions', 'paystack_forms' ), __( 'Submissions', 'paystack_forms' ), 'administrator', 'submissions', [ $this, 'output_submissions_page' ] );
		remove_submenu_page( 'edit.php?post_type=paystack_form', 'submissions' );
	}

	/**
	 * Outputs the Submissions page displaying the WP List table.
	 *
	 * @return void
	 */
	public function output_submissions_page() {
		if ( ! isset( $_GET['form'] ) ) { 
			return __( 'No form set', 'paystack_forms' );
		}

		$form_id  = sanitize_text_field( wp_unslash( $_GET['form'] ) );
		$form     = get_post( $form_id );

		if ( 'paystack_form' === get_post_type( $form ) ) {
			$amount    = get_post_meta( $form_id, '_amount', true );
			$thankyou  = get_post_meta( $form_id, '_successmsg', true );
			$paybtn    = get_post_meta( $form_id, '_paybtn', true );
			$loggedin  = get_post_meta( $form_id, '_loggedin', true );
			$txncharge = get_post_meta( $form_id, '_txncharge', true );
	
			$payments_table = $this->get_payments_list_table();
			$data           = $payments_table->prepare_items();
			?>
			<div class="info-panel">
				<div class="info-panel-content">
				<h1 style="margin: 0px;"><?php echo esc_html( get_the_title( $form ) ); ?> <?php esc_html_e( 'Payments', 'paystack_forms' ); ?></h1>
					<p class="about-description">
						<?php esc_html_e( 'All payments made for this form', 'paystack_forms' ); ?>
					</p>
					<?php if ( $data > 0 ) { ?>
						<form action="<?php echo esc_html( admin_url( 'admin-post.php' ) ); ?>" method="post">
							<input type="hidden" name="action" value="kkd_pff_export_excel">
							<input type="hidden" name="form_id" value="<?php echo esc_html( $form_id ); ?>">
							<button type="submit" class="button button-primary button-hero load-customize"><?php esc_html_e( 'Export Data to Excel', 'paystack_forms' ); ?></button>
						</form>
					<?php } ?>
					<br />
					<br />
				</div>
			</div>
			<div class="wrap">
				<div id="icon-users" class="icon32"></div>
				<?php $payments_table->display(); ?>
			</div>
			<?php
		}
	}

	/**
	 * Create a new instance of the Payments List Table.
	 *
	 * @return \paystack\payment_forms\Payments_List_Table
	 */
	public function get_payments_list_table() {
		if ( ! class_exists( 'WP_List_Table' ) ) {
			include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';
		}
		include_once KKD_PFF_PAYSTACK_PLUGIN_PATH . '/includes/class-payments-list-table.php';
		return new Payments_List_Table();
	}
}
