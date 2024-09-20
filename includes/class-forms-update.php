<?php
/**
 * The class that will update the forms data.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Registers the additional functions for the WP Dashboard Forms List
 */
class Forms_Update {

	/**
	 * Holds the meta field keys and the default values.
	 *
	 * @var array
	 */
	public $defaults = [];

	/**
	 * Holds the meta values for the current form, using the meta key as the index.
	 *
	 * @var array
	 */
	public $meta = [];

	/**
	 * Holds the allowed HTML for output.
	 *
	 * @var array
	 */
	public $allowed_html = [];

	/**
	 * The helpers class
	 *
	 * @var paystack\payment_forms\Helpers
	 */
	public $helpers = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->set_vars();
		add_action( 'admin_head', [ $this, 'setup_actions' ] );
		add_filter( 'admin_head', [ $this, 'disable_wyswyg' ], 10, 1 );

		// Default Content.
		add_filter('default_content', [ $this, 'default_content' ], 10, 2);

		// Define the meta boxes.
		add_action( 'edit_form_after_title', [ $this, 'metabox_action' ] );
		add_action( 'add_meta_boxes', [ $this, 'register_meta_boxes' ] );

		// Save the Meta boxes
		add_action( 'save_post', [ $this, 'save_post_meta' ], 1, 2 );
	}

	/**
	 * Sets useable variables like the fields.
	 *
	 * @return void
	 */
	public function set_vars() {
		$this->helpers      = Helpers::get_instance();
		$this->defaults     = $this->helpers->get_meta_defaults();
		$this->allowed_html = $this->helpers->get_allowed_html();
	}
	
	/**
	 * Add the phone number as the default content when a form is created.
	 *
	 * @param string $content
	 * @param WP_Post $post
	 * @return string
	 */
	public function default_content( $content, $post ) {
		switch ( $post->post_type ) {
			case 'paystack_form':
				$content = '[text name="' . __( 'Phone Number', 'paystack_forms' ) . '"]';
				break;
			default:
				$content = '';
				break;
		}
		return $content;
	}

	/**
	 * Run some actions on admin_head
	 *
	 * @return void
	 */
	public function setup_actions() {
		add_filter( 'user_can_richedit', '__return_false', 50 );
		add_filter( 'quicktags_settings', [ $this, 'remove_fullscreen' ], 10, 1 );

		remove_action( 'media_buttons', 'media_buttons' );
		remove_meta_box( 'postimagediv', 'post', 'side' );

		add_action( 'admin_print_footer_scripts', [ $this, 'shortcode_buttons_script' ] );
	}

	/**
	 * Outputs CSS to hide the WYSIWYG
	 *
	 * @param string $default
	 * @return string
	 */
	public function disable_wyswyg( $default ) {
		if ( 'paystack_form' === get_post_type() ) {
			?> 
			<style>#edit-slug-box,#message p > a{display:none;}</style>
			<?php
		}
		return $default;
	}

	/**
	 * Remove the fullscreen option
	 *
	 * @param array $arguments
	 * @return array
	 */
	public function remove_fullscreen( $arguments ) {
		$arguments['buttons'] = 'fullscreen';
		return $arguments;
	}

	/**
	 * Outputs the QuickTags scripts needed to generate the field shortcodes.
	 *
	 * @return void
	 */
	public function shortcode_buttons_script() {
		if ( wp_script_is( 'quicktags' ) ) {
			?>
			<script type="text/javascript">
			//this function is used to retrieve the selected text from the text editor
			function getSel() {
				var txtarea = document.getElementById( "content" );
				var start = txtarea.selectionStart;
				var finish = txtarea.selectionEnd;
				return txtarea.value.substring(start, finish);
			}
			
			QTags.addButton(
				"t_shortcode",
				"Insert Text",
				insertText
			);
			
			function insertText() {
				QTags.insertContent('[text name="Text Title"]');
			}
			QTags.addButton(
				"ta_shortcode",
				"Insert Textarea",
				insertTextarea
			);
			
			function insertTextarea() {
				QTags.insertContent('[textarea name="Text Title"]');
			}
			QTags.addButton(
				"s_shortcode",
				"Insert Select Dropdown",
				insertSelectb
			);
			
			function insertSelectb() {
				QTags.insertContent('[select name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"r_shortcode",
				"Insert Radio Options",
				insertRadiob
			);
			
			function insertRadiob() {
				QTags.insertContent('[radio name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"cb_shortcode",
				"Insert Checkbox Options",
				insertCheckboxb
			);
			
			function insertCheckboxb() {
				QTags.insertContent('[checkbox name="Text Title" options="option 1,option 2,option 3"]');
			}
			QTags.addButton(
				"dp_shortcode",
				"Insert Datepicker",
				insertDatepickerb
			);
			
			function insertDatepickerb() {
				QTags.insertContent('[datepicker name="Datepicker Title"]');
			}
			QTags.addButton(
				"i_shortcode",
				"Insert File Upload",
				insertInput
			);
			
			function insertInput() {
				QTags.insertContent('[input name="File Name"]');
			}
			QTags.addButton(
				"ngs_shortcode",
				"Insert Nigerian States",
				insertSelectStates
			);
			
			function insertSelectStates() {
				QTags.insertContent(
					'[select name="State" options="<?php echo esc_attr( $this->helpers->get_states( true ) ); ?>"]'
				);
			}
			QTags.addButton(
				"ctys_shortcode",
				"Insert All Countries",
				insertSelectCountries
			);
			
			function insertSelectCountries() {
				QTags.insertContent(
					'[select  name="country" options="<?php echo esc_attr( $this->helpers->get_countries( true ) ); ?>"] '
				);
			}
			
			//
			</script>
			<?php
		}
	}

	/**
	 * Adds in a custom action to allow us to hook into just under the forms title.
	 *
	 * @param \WP_Post $post
	 * @return void
	 */
	public function metabox_action( $post ) {
		$this->parse_meta_values( $post );
		do_meta_boxes( null, 'pff-paystack-metabox-holder', $post );
	}

	/**
	 * Registers our custom metaboxes.
	 *
	 * @return void
	 */
	public function register_meta_boxes() {
		// Register the information boxes.
		if ( isset( $_GET['action'] ) ) {
			add_meta_box( 'pff_paystack_editor_details_box', __( 'Paste shortcode on preferred page', 'paystack_form' ), [ $this, 'shortcode_details' ], 'paystack_form', 'pff-paystack-metabox-holder' );
		}
		add_meta_box( 'pff_paystack_editor_help_box', __( 'Help Section', 'paystack_forms' ), [ $this, 'help_details' ], 'paystack_form', 'pff-paystack-metabox-holder' );

		// Add in our "normal" meta boxes
		add_meta_box( 'form_data', __( 'Extra Form Description', 'paystack_forms' ), [ $this, 'form_data' ], 'paystack_form', 'normal', 'default' );
		add_meta_box( 'email_data', __( 'Email Receipt Settings', 'paystack_forms' ), [ $this, 'email_data' ], 'paystack_form', 'normal', 'default' );

		// Add in our "side" meta boxes
		add_meta_box( 'recuring_data', __( 'Recurring Payment', 'paystack_forms' ), [ $this, 'recur_data' ], 'paystack_form', 'side', 'default' );
		add_meta_box( 'quantity_data', __( 'Quantity Payment', 'paystack_forms' ), [ $this, 'quantity_data' ], 'paystack_form', 'side', 'default' );
		add_meta_box( 'agreement_data', __( 'Agreement checkbox', 'paystack_forms' ), [ $this, 'agreement_data' ], 'paystack_form', 'side', 'default' );
		add_meta_box( 'subaccount_data', __( 'Sub Account', 'paystack_forms' ), [ $this, 'subaccount_data' ], 'paystack_form', 'side', 'default' );
		add_meta_box( 'plan_data', __( '*Special: Subscribe to plan after time', 'paystack_forms' ), [ $this, 'plan_data' ], 'paystack_form', 'side', 'default' );		
			
	}
	
	/**
	 * Output the shortcode details
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function shortcode_details( $post ) {
		?>
		<p class="description">
			<label for="wpcf7-shortcode"><?php esc_html_e( 'Copy this shortcode and paste it into your post, page, or text widget content:', 'paystack_forms' ); ?></label>
			<span class="shortcode wp-ui-highlight">
				<input type="text" id="wpcf7-shortcode" onfocus="this.select();" readonly="readonly" class="large-text code" value="[pff-paystack id=&quot;<?php echo esc_html( $post->ID ); ?>&quot;]">
			</span>
		</p>
		<?php
	}

	/**
	 * Outputs the help details below the title.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function help_details( $post ) {
			// We shall output 1 Nonce Field for all of our metaboxes.
			wp_nonce_field( 'pff-paystack-save-form', 'pff_paystack_save' );
			?>
			<div class="awesome-meta-admin">
				<?php echo wp_kses_post( __( 'Email and Full Name field is added automatically, no need to include that.<br /><br />
				To make an input field compulsory add <code> required="required" </code> to the shortcode <br /><br />
				It should look like this <code> [text name="Full Name" required="required" ]</code><br /><br />' ) ) ; ?>

				<?php echo wp_kses_post( __( '<b style="color:red;">Warning:</b> Using the file input field may cause data overload on your server.
				Be sure you have enough server space before using it. You also have the ability to set file upload limits.' ) ) ; ?>
			</div>
		<?php
	}

	/**
	 * Gets the current meta fields and set the defaults if needed.
	 *
	 * @param WP_Post $post
	 * @return void
	 */
	public function parse_meta_values( $post ) {
		$this->meta = $this->helpers->parse_meta_values( $post );
	}

	/**
	 * Outputs the Extra Form Description Meta Box.
	 *
	 * @return void
	 */
	public function form_data() {
		$html = [];

		if ($this->meta['hidetitle'] == 1) {
			$html[] = '<label><input name="_hidetitle" type="checkbox" value="1" checked> ' . __('Hide the form title', 'paystack_forms') . ' </label>';
		} else {
			$html[] = '<label><input name="_hidetitle" type="checkbox" value="1" > ' . __('Hide the form title', 'paystack_forms') . ' </label>';
		}
		$html[] = '<br>';
		$html[] = '<p>Currency:</p>';
		$html[] = '<select class="form-control" name="_currency" style="width:100%;">
					<option value="NGN" ' . $this->is_option_selected( 'NGN', $this->meta['currency'] ) . '>' . __('Nigerian Naira', 'paystack_forms') . '</option>
					<option value="GHS" ' . $this->is_option_selected( 'GHS', $this->meta['currency'] ) . '>' . __('Ghanaian Cedis', 'paystack_forms') . '</option>
					<option value="ZAR" ' . $this->is_option_selected( 'ZAR', $this->meta['currency'] ) . '>' . __('South African Rand', 'paystack_forms') . '</option>
					<option value="KES" ' . $this->is_option_selected( 'KES', $this->meta['currency'] ) . '>' . __('Kenyan Shillings', 'paystack_forms') . '</option>
					<option value="XOF" ' . $this->is_option_selected( 'XOF', $this->meta['currency'] ) . '>' . __('West African CFA Franc', 'paystack_forms') . '</option>
					<option value="RWF" ' . $this->is_option_selected( 'RWF', $this->meta['currency'] ) . '>' . __('Rwandan Franc', 'paystack_forms') . '</option>
					<option value="EGP" ' . $this->is_option_selected( 'EGP', $this->meta['currency'] ) . '>' . __('Egyptian Pound', 'paystack_forms') . '</option>
					<option value="USD" ' . $this->is_option_selected( 'USD', $this->meta['currency'] ) . '>' . __('US Dollars', 'paystack_forms') . '</option>
			  </select>';

		$html[] = '<small>' . __('Ensure you are activated for the currency you are selecting. Check <a href="https://support.paystack.com/hc/en-us/articles/360009973799-Can-I-accept-payments-in-US-Dollars-USD" target="_blank">here</a> for more information.', 'paystack_forms') . '</small>';
		$html[] = '<p>' . __('Amount to be paid(Set 0 for customer input):', 'paystack_forms') . '</p>';
		$html[] = '<input type="number" name="_amount" value="' . $this->meta['amount'] . '" class="widefat pf-number" />';
		if ($this->meta['minimum'] == 1) {
			$html[] = '<br><label><input name="_minimum" type="checkbox" value="1" checked> ' . __('Make amount minimum payable', 'paystack_forms') . ' </label>';
		} else {
			$html[] = '<br><label><input name="_minimum" type="checkbox" value="1"> ' . __('Make amount minimum payable', 'paystack_forms') . ' </label>';
		}
		$html[] = '<p>' . __('Variable Dropdown Amount:', 'paystack_forms') . '<code><label>' . __('Format(option:amount):  Option 1:10000,Option 2:3000 Separate options with "," ', 'paystack_forms') . '</label></code></p>';
		$html[] = '<input type="text" name="_variableamount" value="' . $this->meta['variableamount'] . '" class="widefat " />';
		if ($this->meta['usevariableamount'] == 1) {
			$html[] = '<br><label><input name="_usevariableamount" type="checkbox" value="1" checked> ' . __('Use dropdown amount option', 'paystack_forms') . ' </label>';
		} else {
			$html[] = '<br><label><input name="_usevariableamount" type="checkbox" value="1"> ' . __('Use dropdown amount option', 'paystack_forms') . ' </label>';
		}
		$html[] = '<p>' . __('Pay button Description:', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_paybtn" value="' . $this->meta['paybtn'] . '" class="widefat" />';
		$html[] = '<p>' . __('Add Extra Charge:', 'paystack_forms') . '</p>';
		$html[] = '<select class="form-control" name="_txncharge" id="parent_id" style="width:100%;">
							<option value="merchant"' . $this->is_option_selected('merchant', $this->meta['txncharge']) . '> ' . __('No, do not add', 'paystack_forms') . '</option>
							<option value="customer" ' . $this->is_option_selected('customer', $this->meta['txncharge']) . '> ' . __('Yes, add it', 'paystack_forms') . '</option>
						</select>
					<br><small>' . __('This allows you include an extra charge to cushion the effect of the transaction fee. <a href="', 'paystack_forms') . get_admin_url() . "edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php#paystack_setting_fees" . '">' . __('Configure', 'paystack_forms') . '</a></small>';
		$html[] = '<p>' . __('User logged In:', 'paystack_forms') . '</p>';
		$html[] = '<select class="form-control" name="_loggedin" id="parent_id" style="width:100%;">
							<option value="no" ' . $this->is_option_selected('no', $this->meta['loggedin']) . '> ' . __('User must not be logged in', 'paystack_forms') . '</option>
							<option value="yes"' . $this->is_option_selected('yes', $this->meta['loggedin']) . '> ' . __('User must be logged In', 'paystack_forms') . '</option>
						</select>';
		$html[] = '<p>' . __('Success Message after Payment', 'paystack_forms') . '</p>';
		$html[] = '<textarea rows="3"  name="_successmsg"  class="widefat" >' . $this->meta['successmsg'] . '</textarea>';
		$html[] = '<p>' . __('File Upload Limit(MB):', 'paystack_forms') . '</p>';
		$html[] = '<input type="number" name="_filelimit" value="' . $this->meta['filelimit'] . '" class="widefat  pf-number" />';
		$html[] = '<p>' . __('Redirect to page link after payment(keep blank to use normal success message):', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_redirect" value="' . $this->meta['redirect'] . '" class="widefat" />';
		
		// To output the concatenated $html array content
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Checks to see if the curren value is selected.
	 *
	 * @param string $value
	 * @param string $compare
	 * @return string
	 */
	public function is_option_selected( $value, $compare ) {
		if ( $value == $compare ) {
			$result = "selected";
		} else {
			$result = "";
		}
		return $result;
	}

	/**
	 * Output the recurring data meta box.
	 *
	 * @return void
	 */
	public function recur_data(){
		$html   = [];
		$html[] = '<p>' . __('Recurring Payment:', 'paystack_forms') . '</p>';
		$html[] = '<select class="form-control" name="_recur" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['recur']) . '>' . __('None', 'paystack_forms') . '</option>
					  <option value="optional" ' . $this->is_option_selected('optional', $this->meta['recur']) . '>' . __('Optional Recurring', 'paystack_forms') . '</option>
					  <option value="plan" ' . $this->is_option_selected('plan', $this->meta['recur']) . '>' . __('Paystack Plan', 'paystack_forms') . '</option>
					</select>';
		$html[] = '<p>' . __('Paystack Recur Plan code:', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_recurplan" value="' . $this->meta['recurplan'] . '" class="widefat" />
				   <small>' . __('Plan amount must match amount on extra form description.', 'paystack_forms') . '</small>';
		
		// Output the accumulated HTML
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the email metabox
	 *
	 * @return void
	 */
	public function email_data() {
		$html   = [];
		// Echo out the field
		$html[] = '<p>' . __('Send an invoice when a payment is attempted:', 'paystack_forms') . '</p>';
		$html[] = '<select class="form-control" name="_sendinvoice" id="parent_id" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['sendinvoice']) . '>' . __('Don\'t send', 'paystack_forms') . '</option>
					  <option value="yes" ' . $this->is_option_selected('yes', $this->meta['sendinvoice']) . '>' . __('Send', 'paystack_forms') . '</option>
				   </select>';
		$html[] = '<p>' . __('Send Email Receipt:', 'paystack_forms') . '</p>';
		$html[] = '<select class="form-control" name="_sendreceipt" id="parent_id" style="width:100%;">
					  <option value="no" ' . $this->is_option_selected('no', $this->meta['sendreceipt']) . '>' . __('Don\'t send', 'paystack_forms') . '</option>
					  <option value="yes" ' . $this->is_option_selected('yes', $this->meta['sendreceipt']) . '>' . __('Send', 'paystack_forms') . '</option>
				   </select>';
		$html[] = '<p>' . __('Email Subject:', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_subject" value="' . $this->meta['subject'] . '" class="widefat" />';
		$html[] = '<p>' . __('Merchant Name on Receipt:', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_merchant" value="' . $this->meta['merchant'] . '" class="widefat" />';
		$html[] = '<p>' . __('Email Heading:', 'paystack_forms') . '</p>';
		$html[] = '<input type="text" name="_heading" value="' . $this->meta['heading'] . '" class="widefat" />';
		$html[] = '<p>' . __('Email Body/Message:', 'paystack_forms') . '</p>';
		$html[] = '<textarea rows="6" name="_message" class="widefat">' . $this->meta['message'] . '</textarea>';
		
		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the quantity metabox
	 *
	 * @return void
	 */
	public function quantity_data() {
		$html   = [];

		// Echo out the field
		$html[] = '<small>' . __('Allow your users pay in multiple quantity', 'paystack_forms') . '</small>
			<p>' . __('Quantified Payment:', 'paystack_forms') . '</p>';

		if ($this->meta['recur'] != "no") {
			$html[] = '<select disabled class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['usequantity']) . '>' . __('No', 'paystack_forms') . '</option>
				</select>';
		} else {
			$html[] = '<select class="form-control" name="_usequantity" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['usequantity']) . '>' . __('No', 'paystack_forms') . '</option>
				<option value="yes" ' . $this->is_option_selected('yes', $this->meta['usequantity']) . '>' . __('Yes', 'paystack_forms') . '</option>
				</select>';
		}

		if ($this->meta['usequantity'] == "yes") {

			$html[] = '<p>' . __('Max payable quantity:', 'paystack_forms') . '</p>';
			$html[] = '<input type="number" min="1"  name="_quantity" value="' . $this->meta['quantity'] . '" class="widefat  pf-number" /><small>' . __('Your users only get to pay in quantities if the from amount is not set to zero and recur is set to none.', 'paystack_forms') . '</small>';
			$html[] = '<p>' . __('Unit of quantity:', 'paystack_forms') . '</p>';
			$html[] = '<input type="text" name="_quantityunit" value="' . $this->meta['quantityunit'] . '" class="widefat" /><small>' . __('What is the unit of this quantity? Default is <code>Quantity</code>.', 'paystack_forms') . '</small>';

			$html[] = '<p>' . __('Inventory Payment:', 'paystack_forms') . '</p>';
			$html[] = '<select class="form-control" name="_useinventory" style="width:100%;">
				<option value="no" ' . $this->is_option_selected('no', $this->meta['useinventory']) . '>' . __('No', 'paystack_forms') . '</option>
				<option value="yes" ' . $this->is_option_selected('yes', $this->meta['useinventory']) . '>' . __('Yes', 'paystack_forms') . '</option>
				</select>
				<small>' . __('Set maximum available items in stock', 'paystack_forms') . '</small>';
		}

		if ($this->meta['useinventory'] == "yes" && $this->meta['usequantity'] == "yes") {
			$html[] = '<p>' . __('Total Inventory', 'paystack_forms') . '</p>';
			$html[] = '<input type="number" min="' . $this->meta['sold'] . '" name="_inventory" value="' . $this->meta['inventory'] . '" class="widefat  pf-number" />';
			$html[] = '<p>' . __('Already sold', 'paystack_forms') . '</p>';
			$html[] = '<input type="number" name="_sold" value="' . $this->meta['sold'] . '" class="widefat  pf-number" />
				<small></small>
				<br/>';
		}

		echo wp_kses( implode( '', $html ), $this->allowed_html ); 
	}

	/**
	 * Add the agreement metabox
	 *
	 * @return void
	 */
	public function agreement_data() {
		$html   = [];
		
		// Add components to the $html array
		$html[] = '<p>' . __( 'Use agreement checkbox:', 'paystack_forms' ) . '</p>';
		$html[] = '<select class="form-control" name="_useagreement" style="width:100%;">
					<option value="no" ' . $this->is_option_selected('no', $this->meta['useagreement']) . '>' . __( 'No', 'paystack_forms' ) . '</option>
					<option value="yes" ' . $this->is_option_selected('yes', $this->meta['useagreement']) . '>' . __( 'Yes', 'paystack_forms' ) . '</option>
				</select>';
		$html[] = '<p>' . __( 'Agreement Page Link:', 'paystack_forms' ) . '</p>';
		$html[] = '<input type="text" name="_agreementlink" value="' . $this->meta['agreementlink']  . '" class="widefat" />';
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Output the Subaccount metabox.
	 *
	 * @return void
	 */
	public function subaccount_data() {
		$html   = [];
		// Add components to the $html array
		$html[] = '<p>' . __( 'Sub Account code:', 'paystack_forms' ) . '</p>';
		$html[] = '<input type="text" name="_subaccount" value="' . $this->meta['subaccount']  . '" class="widefat" />';
		$html[] = '<p>' . __( 'Transaction Charge bearer:', 'paystack_forms' ) . '</p>';
		$html[] = '<select class="form-control" name="_txnbearer" id="parent_id" style="width:100%;">
					<option value="account" ' . $this->is_option_selected('account', $this->meta['txnbearer']) . '>' . __( 'Merchant (default)', 'paystack_forms' ) . '</option>
					<option value="subaccount" ' . $this->is_option_selected('subaccount', $this->meta['txnbearer']) . '>' . __( 'Sub Account', 'paystack_forms' ) . '</option>
				</select>';
		$html[] = '<p>' . __( 'Merchant Amount:', 'paystack_forms' ) . '</p>';
		$html[] = '<input type="text" name="_merchantamount" value="' . $this->meta['merchantamount'] . '" class="widefat" />';
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Output the Plan metabox
	 *
	 * @return void
	 */
	public function plan_data() {
		$html   = [];
		$html[] = '<p>' . __( 'User subscribes to plan after number of days:', 'paystack_forms' ) . '</p>';
		$html[] = '<p>' . __( 'Number of days:', 'paystack_forms' ) . '</p>';
		$html[] = '<input type="number" name="_startdate_days" value="' . $this->meta['startdate_days'] . '" class="widefat pf-number" />';
		$html[] = '<p>' . __( 'Plan:', 'paystack_forms' ) . '</p>';
		$html[] = '<input type="text" name="_startdate_plan_code" value="' . $this->meta['startdate_plan_code'] . '" class="widefat" />';
		
		if ($this->meta['startdate_enabled'] == 1) {
			$html[] = '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1" checked> ' . __( 'Enable', 'paystack_forms' ) . ' </label></p>';
		} else {
			$html[] = '<p><br><label><input name="_startdate_enabled" type="checkbox" value="1"> ' . __( 'Enable', 'paystack_forms' ) . ' </label></p>';
		}
		echo wp_kses( implode( '', $html ), $this->allowed_html );
	}

	/**
	 * Saves the post meta field stored in the $defaults variable.
	 *
	 * @param int|string $post_id
	 * @param WP_Post $post
	 * @return void
	 */
	public function save_post_meta( $form_id, $post ) {

		if ( ! isset( $_POST['pff_paystack_save'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['pff_paystack_save'] ) ), 'pff-paystack-save-form' ) ) {
			return $form_id;
		}

		// Is the user allowed to edit the post or page?
		if ( ! current_user_can('edit_post', $form_id ) ) {
			return $form_id;
		}

		// Cycle through our fields and save the information.
		foreach ( $this->defaults as $key => $default ) { 
			if ( $post->post_type == 'revision' ) {
				return; // Don't store custom data twice
			}

			if ( isset( $_POST[ '_' . $key ] ) ) {
				$value = sanitize_text_field( $_POST[ '_' . $key ] );
			} else {
				$value = $default;
			}

			$value = implode( ',', (array) $value ); // If $value is an array, make it a CSV (unlikely)
			if ( get_post_meta( $form_id, '_' . $key, false ) ) { // If the custom field already has a value
				update_post_meta( $form_id, '_' . $key, $value );
			} else { // If the custom field doesn't have a value
				add_post_meta( $form_id, '_' . $key, $value );
			}
			if ( ! $value ) {
				delete_post_meta( $form_id, '_' . $key ); // Delete if blank
			}
		}
	}
}
