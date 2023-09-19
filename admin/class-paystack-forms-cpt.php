<?php

namespace Paystack_Plugins\Payment_Forms\Admin;

class Paystack_Forms_CPT {
	private string $plugin_name;
	private string $version;

	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version     = $version;
	}

	public function register_paystack_form_cpt() {

		$labels = array(
			'name'               => _x( 'Paystack Forms', 'paystack_form', 'payment-forms-for-paystack' ),
			'singular_name'      => _x( 'Paystack Form', 'paystack_form', 'payment-forms-for-paystack' ),
			'add_new'            => _x( 'Add New', 'paystack_form', 'payment-forms-for-paystack' ),
			'add_new_item'       => _x( 'Add Paystack Form', 'paystack_form', 'payment-forms-for-paystack' ),
			'edit_item'          => _x( 'Edit Paystack Form', 'paystack_form', 'payment-forms-for-paystack' ),
			'new_item'           => _x( 'Paystack Form', 'paystack_form', 'payment-forms-for-paystack' ),
			'view_item'          => _x( 'View Paystack Form', 'paystack_form', 'payment-forms-for-paystack' ),
			'all_items'          => _x( 'All Forms', 'paystack_form', 'payment-forms-for-paystack' ),
			'search_items'       => _x( 'Search Paystack Forms', 'paystack_form', 'payment-forms-for-paystack' ),
			'not_found'          => _x( 'No Paystack Forms found', 'paystack_form', 'payment-forms-for-paystack' ),
			'not_found_in_trash' => _x( 'No Paystack Forms found in Trash', 'paystack_form', 'payment-forms-for-paystack' ),
			'parent_item_colon'  => _x( 'Parent Paystack Form:', 'paystack_form', 'payment-forms-for-paystack' ),
			'menu_name'          => _x( 'Paystack Forms', 'paystack_form', 'payment-forms-for-paystack' ),
		);

		$args = array(
			'labels'              => $labels,
			'hierarchical'        => true,
			'description'         => __( 'Paystack Forms filterable by genre', 'payment-forms-for-paystack' ),
			'supports'            => array( 'title', 'editor' ),
			'public'              => true,
			'show_ui'             => true,
			'show_in_menu'        => true,
			'menu_position'       => 5,
			'menu_icon'           => plugins_url( '../images/logo.png', __FILE__ ),
			'show_in_nav_menus'   => false,
			'publicly_queryable'  => false,
			'exclude_from_search' => true,
			'has_archive'         => false,
			'query_var'           => true,
			'can_export'          => true,
			'rewrite'             => false,
			'comments'            => false,
			'capability_type'     => 'post',
		);

		register_post_type( 'paystack_form', $args );
	}

	public function save_payment_form_data( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		if ( empty( $_POST['payment_forms_for_paystack_nonce'] ) ) {
			return;
		}

		if ( ! wp_verify_nonce( $_POST['payment_forms_for_paystack_nonce'], 'payment-forms-for-paystack-nonce' ) ) {
			return;
		}

		$post = get_post( $post_id );

		if ( empty( $post ) ) {
			return;
		}

		// Is the user allowed to edit the post or page?
		if ( ! current_user_can( 'edit_post', $post->ID ) ) {
			return;
		}

		$form_meta['_inventory']    = $_POST['_inventory'] ?? '0';
		$form_meta['_useinventory'] = $_POST['_inventory'] ?? '0';
		$form_meta['_amount']       = $_POST['_amount'];
		$form_meta['_hidetitle']    = $_POST['_hidetitle'] ?? '0';
		$form_meta['_minimum']      = $_POST['_minimum'] ?? '0';

		$form_meta['_variableamount']    = $_POST['_variableamount'];
		$form_meta['_usevariableamount'] = $_POST['_usevariableamount'] ?? '0';

		$form_meta['_paybtn']     = $_POST['_paybtn'];
		$form_meta['_currency']   = $_POST['_currency'];
		$form_meta['_successmsg'] = $_POST['_successmsg'];
		$form_meta['_txncharge']  = $_POST['_txncharge'];
		$form_meta['_loggedin']   = $_POST['_loggedin'];
		$form_meta['_filelimit']  = $_POST['_filelimit'];
		$form_meta['_redirect']   = $_POST['_redirect'];

		$form_meta['_subject']     = $_POST['_subject'];
		$form_meta['_merchant']    = $_POST['_merchant'];
		$form_meta['_heading']     = $_POST['_heading'];
		$form_meta['_message']     = $_POST['_message'];
		$form_meta['_sendreceipt'] = $_POST['_sendreceipt'];
		$form_meta['_sendinvoice'] = $_POST['_sendinvoice'];

		$form_meta['_recur']        = $_POST['_recur'];
		$form_meta['_recurplan']    = $_POST['_recurplan'];
		$form_meta['_usequantity']  = $_POST['_usequantity'];
		$form_meta['_quantity']     = $_POST['_quantity'] ?? '1';
		$form_meta['_sold']         = $_POST['_sold'] ?? '0';
		$form_meta['_quantityunit'] = $_POST['_quantityunit'] ?? __( 'Quantity', 'payment-forms-for-paystack' );

		$form_meta['_useagreement']   = $_POST['_useagreement'];
		$form_meta['_agreementlink']  = $_POST['_agreementlink'];
		$form_meta['_subaccount']     = $_POST['_subaccount'];
		$form_meta['_txnbearer']      = $_POST['_txnbearer'];
		$form_meta['_merchantamount'] = $_POST['_merchantamount'];
		// Add values of $form_meta as custom fields

		//Custom Plan with Start Date
		$form_meta['_startdate_days']      = $_POST['_startdate_days'];
		$form_meta['_startdate_plan_code'] = $_POST['_startdate_plan_code'];
		$form_meta['_startdate_enabled']   = $_POST['_startdate_enabled'] ?? '0';

		foreach ( $form_meta as $key => $value ) { // Cycle through the $form_meta array!
			if ( 'revision' === $post->post_type ) {
				return; // Don't store custom data twice
			}
			$value = implode( ',', (array) $value ); // If $value is an array, make it a CSV (unlikely)
			if ( get_post_meta( $post->ID, $key, false ) ) { // If the custom field already has a value
				update_post_meta( $post->ID, $key, $value );
			} else { // If the custom field doesn't have a value
				add_post_meta( $post->ID, $key, $value );
			}
			if ( ! $value ) {
				delete_post_meta( $post->ID, $key ); // Delete if blank
			}
		}
	}

	public function disable_wyswyg( $default ) {

		global $post_type;

		if ( 'paystack_form' === $post_type ) {
			echo '<style>#edit-slug-box,#message p > a{display:none;}</style>';
			add_filter( 'user_can_richedit', '__return_false', 50 );
			remove_action( 'media_buttons', 'media_buttons' );
			add_filter( 'quicktags_settings', array( $this, 'remove_fullscreen_quicktag' ) );
		}

		return $default;
	}

	public function remove_fullscreen_quicktag( $qt_init ) {
		$qt_init['buttons'] = 'fullscreen';
		return $qt_init;
	}

	public function add_help_section_meta_box( $post ) {
		do_meta_boxes( null, 'paystack_payment_forms_cpt_meta_box', $post );
	}

	public function help_section_meta_box() {
		?>
		<input type="hidden" name="payment_forms_for_paystack_nonce" value="<?php echo esc_attr( wp_create_nonce( 'payment-forms-for-paystack-nonce' ) ); ?>" />
		<div>
			<p><?php esc_html_e( 'Email and Full Name field is added automatically, no need to include that.', 'payment-forms-for-paystack' ); ?></p>
			<p>
				<?php
				echo wp_kses(
					__( 'To make an input field compulsory add <code>required="required"</code> to the shortcode', 'payment-forms-for-paystack' ),
					array(
						'code' => array(),
					)
				)
				?>
			</p>
			<p>
				<?php
				echo wp_kses(
					__( 'It should look like this <code>[text name="Full Name" required="required" ]</code>', 'payment-forms-for-paystack' ),
					array(
						'code' => array(),
					)
				)
				?>
			</p>
			<p>
				<?php
				echo wp_kses(
					__( '<strong style="color:red;">Warning:</strong> Using the file input field may cause data overload on your server. Be sure you have enough server space before using it. You also have the ability to set file upload limits', 'payment-forms-for-paystack' ),
					array(
						'strong' => array(
							'style' => array(),
						),
					)
				)
				?>
			</p>
		</div>
		<?php
	}

	public function shortcode_details_meta_box( $post ) {
		?>
		<p class="description">
			<label for="paystack_payment_forms_shortcode"><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content', 'payment-forms-for-paystack' ); ?></label>
			<span class="shortcode">
				<input type="text" id="paystack_payment_forms_shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pff-paystack id=&quot;<?php echo esc_attr( $post->ID ); ?>&quot;]">
			</span>
		</p>

		<?php
	}

	public function add_extra_meta_boxes() {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['action'] ) && 'edit' === strtolower( sanitize_text_field( $_GET['action'] ) ) ) {
			add_meta_box(
				'shortcode_details_meta_box',
				__( 'Paste Shortcode on Preferred Page', 'payment-forms-for-paystack' ),
				array( $this, 'shortcode_details_meta_box' ),
				'paystack_form',
				'paystack_payment_forms_cpt_meta_box'
			);
		}

		add_meta_box(
			'help_section_meta_box',
			__( 'Help Section', 'payment-forms-for-paystack' ),
			array( $this, 'help_section_meta_box' ),
			'paystack_form',
			'paystack_payment_forms_cpt_meta_box'
		);

		add_meta_box(
			'extra_form_description_meta_box',
			__( 'Extra Form Description', 'payment-forms-for-paystack' ),
			array( $this, 'extra_form_description_meta_box' ),
			'paystack_form',
			'normal'
		);

		add_meta_box(
			'recurring_payment_meta_box',
			__( 'Recurring Payment', 'payment-forms-for-paystack' ),
			array( $this, 'recurring_payment_meta_box' ),
			'paystack_form',
			'side'
		);

		add_meta_box(
			'email_receipt_settings_meta_box',
			__( 'Email Receipt Settings', 'payment-forms-for-paystack' ),
			array( $this, 'email_receipt_settings_meta_box' ),
			'paystack_form',
			'normal'
		);

		add_meta_box(
			'quantity_payment_meta_box',
			__( 'Quantity Payment', 'payment-forms-for-paystack' ),
			array( $this, 'quantity_payment_meta_box' ),
			'paystack_form',
			'side'
		);

		add_meta_box(
			'agreement_checkbox_meta_box',
			__( 'Agreement checkbox', 'payment-forms-for-paystack' ),
			array( $this, 'agreement_checkbox_meta_box' ),
			'paystack_form',
			'side'
		);

		add_meta_box(
			'sub_account_meta_box',
			__( 'Sub Account', 'payment-forms-for-paystack' ),
			array( $this, 'sub_account_meta_box' ),
			'paystack_form',
			'side'
		);

		add_meta_box(
			'plan_start_date_meta_box',
			__( '*Special: Subscribe To Plan After Time', 'payment-forms-for-paystack' ),
			array( $this, 'plan_start_date_meta_box' ),
			'paystack_form',
			'side'
		);
	}

	public function extra_form_description_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/extra-form-description.php';
	}

	public function email_receipt_settings_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/email-receipt-settings.php';
	}

	public function recurring_payment_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/recurring-payment.php';
	}

	public function quantity_payment_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/quantity-payment.php';
	}

	public function agreement_checkbox_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/agreement-checkbox.php';
	}

	public function sub_account_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/sub-account.php';
	}

	public function plan_start_date_meta_box() {
		require_once plugin_dir_path( __DIR__ ) . 'admin/partials/metaboxes/plan-start-date.php';
	}

	public function enqueue_scripts() {
		if ( wp_script_is( 'quicktags' ) ) {
			return;
		}

		wp_enqueue_script(
			'payment-forms-for-paystack-admin-quicktags',
			plugin_dir_url( __FILE__ ) . 'js/quicktags.js',
			array( 'quicktags' ),
			$this->version,
			true,
		);
	}
}
