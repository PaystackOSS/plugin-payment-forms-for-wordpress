<?php
/**
 * The shortcodes for the frontend display
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Shortcodes class, will only render for the frontend.
 */
class Shortcodes {

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			return;
		}

		add_shortcode('paystack_form', [ $this, 'form_shortcode' ] );
		add_shortcode('pff-paystack', [ $this, 'form_shortcode' ] );
	}

	/**
	 * Generates the form output, will run the individual shortcodes.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function kkd_pff_paystack_form_shortcode( $atts ) {
		ob_start();
	
		// Ensure the current user is populated
		global $current_user;
		wp_get_current_user();
		$user_id = $current_user->ID;
		$email = sanitize_email($current_user->user_email);
		$fname = sanitize_text_field($current_user->user_firstname);
		$lname = sanitize_text_field($current_user->user_lastname);
		$fullname = $fname || $lname ? trim($fname . ' ' . $lname) : '';
	
		// Use array access for shortcode attributes
		$atts = shortcode_atts(array('id' => 0), $atts, 'paystack_form');
		$id = intval($atts['id']); // Ensure $id is an integer
	
		$pk = Kkd_Pff_Paystack_Public::fetchPublicKey();
		if (!$pk) {
			$settingslink = esc_url(get_admin_url(null, 'edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php'));
			echo "<h5>You must set your Paystack API keys first <a href='{$settingslink}'>settings</a></h5>";
			return ob_get_clean(); // Return early to avoid further processing
		}
	
		if ($id > 0) {
			$obj = get_post($id);
			if ($obj && $obj->post_type === 'paystack_form') {
				// Fetch and sanitize meta values
				$meta_keys = [
					'_amount', '_successmsg', '_paybtn', '_loggedin', '_txncharge', 
					'_currency', '_recur', '_recurplan', '_usequantity', '_quantity', 
					'_useagreement', '_agreementlink', '_minimum', '_variableamount', 
					'_usevariableamount', '_hidetitle'
				];
				$meta = [];
				foreach ($meta_keys as $key) {
					$meta[$key] = sanitize_text_field(get_post_meta($id, $key, true));
				}
	
				// Ensure minimum defaults are set
				$meta['_minimum'] = $meta['_minimum'] === "" ? 0 : $meta['_minimum'];
				$meta['_usevariableamount'] = $meta['_usevariableamount'] === "" ? 0 : $meta['_usevariableamount'];
				$meta['_usequantity'] = $meta['_usequantity'] === "" ? 'no' : $meta['_usequantity'];
				$minimum = floatval($meta['_minimum']);
				$currency = $meta['_currency'] === "" ? 'NGN' : $meta['_currency'];
				$txncharge = floatval($meta['_txncharge']);
				// Process variable amount options if applicable
				$paymentoptions = [];
				if ($meta['_usevariableamount'] == 1) {
					$paymentoptions = explode(',', $meta['_variableamount']);
					$paymentoptions = array_map('sanitize_text_field', $paymentoptions);
				}
				$showbtn = true;
				$planerrorcode = 'Input Correct Recurring Plan Code';
				$recur = $meta['_recur'];
				$recurplan = $meta['_recurplan'];
				if ($meta['_recur']== 'plan') {
					if ($meta['_recurplan'] == '' || $meta['_recurplan'] == '') {
						$showbtn = false;
					} else {
						$plan =    kkd_pff_paystack_fetch_plan($meta['_recurplan']);
						if (isset($plan->data->amount)) {
							$planamount = $plan->data->amount/100;
						} else {
							$showbtn = false;
						}
					}
				}
				// Check if the form should be displayed based on user login status
				$show_form = ($user_id != 0 && $meta['_loggedin'] == 'yes') || $meta['_loggedin'] == 'no';
	
				if ($show_form) {
					// Form title
					if ($meta['_hidetitle'] != 1) {
						echo "<h1 id='pf-form" . esc_attr($id) . "'>" . esc_html($obj->post_title) . "</h1>";
					}
	
					// Start form output
					echo '<form version="' . esc_attr(KKD_PFF_PAYSTACK_VERSION) . '" enctype="multipart/form-data" action="' . esc_url(admin_url('admin-ajax.php')) . '" method="post" class="paystack-form j-forms" novalidate>
						  <div class="j-row">';
	
					// Hidden inputs
					echo '<input type="hidden" name="action" value="kkd_pff_paystack_submit_action">
						  <input type="hidden" name="pf-id" value="' . esc_attr($id) . '" />
						  <input type="hidden" name="pf-user_id" value="' . esc_attr($user_id) . '" />
						  <input type="hidden" name="pf-recur" value="' . esc_attr($meta['_recur']) . '" />';
	
					// Full Name input
					echo '<div class="span12 unit">
						  <label class="label">Full Name <span>*</span></label>
						  <div class="input">
							  <input type="text" name="pf-fname" placeholder="First & Last Name" value="' . esc_attr($fullname) . '" required>
						  </div>
					  </div>';
	
					// Email input
					echo '<div class="span12 unit">
						  <label class="label">Email <span>*</span></label>
						  <div class="input">
							  <input type="email" name="pf-pemail" placeholder="Enter Email Address" id="pf-email" value="' . esc_attr($email) . '" ' . ($meta['_loggedin'] == 'yes' ? 'readonly' : '') . ' required>
						  </div>
					  </div>';
	
					// Amount selection with consideration for variable amounts, minimum payments, and recurring plans
					echo '<div class="span12 unit">
					<label class="label">Amount (' . esc_html($currency);
					if ($minimum == 0 && $amount != 0 && $usequantity == 'yes') {
						echo ' ' . esc_html(number_format($amount));
					}
					echo ') <span>*</span></label>
					<div class="input">';
	
					if ($usevariableamount == 0) {
						if ($minimum == 1) {
							echo '<small> Minimum payable amount <b style="font-size:87% !important;">' . esc_html($currency) . '  ' . esc_html(number_format($amount)) . '</b></small>';
						}
						if ($recur == 'plan') {
							if ($showbtn) {
								echo '<input type="text" name="pf-amount" value="' . esc_attr($planamount) . '" id="pf-amount" readonly required />';
							} else {
								echo '<div class="span12 unit">
								<label class="label" style="font-size:18px;font-weight:600;line-height: 20px;">' . esc_html($planerrorcode) . '</label>
							</div>';
							}
						} elseif ($recur == 'optional') {
							echo '<input type="text" name="pf-amount" class="pf-number" id="pf-amount" value="0" required />';
						} else {
							echo '<input type="text" name="pf-amount" class="pf-number" value="' . esc_attr($amount == 0 ? "0" : $amount) . '" id="pf-amount" ' . ($amount != 0 && $minimum != 1 ? 'readonly' : '') . ' required />';
						}
					} else {
						if ($usevariableamount == "") {
							echo "Form Error, set variable amount string";
						} else {
							if (count($paymentoptions) > 0) {
								echo '<div class="select">
								<input type="hidden"  id="pf-vname" name="pf-vname" />
								<input type="hidden"  id="pf-amount" />
								<select class="form-control" id="pf-vamount" name="pf-amount">';
								foreach ($paymentoptions as $option) {
									list($optionName, $optionValue) = explode(':', $option);
									echo '<option value="' . esc_attr($optionValue) . '">' . esc_html($optionName) . '(' . esc_html(number_format($optionValue)) . ')</option>';
								}
								echo '</select> <i></i> </div>';
							}
						}
					}
	
					// Transaction charge notice
					if ($txncharge != 'merchant' && $recur != 'plan') {
						echo '<small>Transaction Charge: <b class="pf-txncharge"></b>, Total:<b  class="pf-txntotal"></b></small>';
					}
	
					echo '</div></div>';
	
					// Quantity selection
					if ($recur == 'no' && $usequantity == 'yes' && ($usevariableamount == 1 || $amount != 0)) {
						echo '<div class="span12 unit">
						<label class="label">Quantity</label>
						<div class="select">
							<input type="hidden" value="' . esc_attr($amount) . '" id="pf-qamount"/>
							<select class="form-control" id="pf-quantity" name="pf-quantity">';
						for ($i = 1; $i <= $quantity; $i++) {
							echo '<option value="' . esc_attr($i) . '">' . esc_html($i) . '</option>';
						}
						echo '</select> <i></i> </div></div>';
					}
	
					// Recurring payment options
					if ($recur == 'optional') {
						echo '<div class="span12 unit">
				<label class="label">Recurring Payment</label>
				<div class="select">
					<select class="form-control" name="pf-interval">';
						$intervals = ['no' => 'None', 'daily' => 'Daily', 'weekly' => 'Weekly', 'monthly' => 'Monthly', 'biannually' => 'Biannually', 'annually' => 'Annually'];
						foreach ($intervals as $intervalValue => $intervalName) {
							echo '<option value="' . esc_attr($intervalValue) . '">' . esc_html($intervalName) . '</option>';
						}
						echo '</select> <i></i> </div></div>';
					}
	
					// Plan details for recurring payments
					if ($recur == 'plan' && $showbtn) {
						echo '<input type="hidden" name="pf-plancode" value="' . esc_attr($recurplan) . '" />';
						echo '<div class="span12 unit">
				<label class="label" style="font-size:18px;font-weight:600;line-height: 20px;">' . esc_html($plan->data->name) . ' ' . esc_html($plan->data->interval) . ' recurring payment - ' . esc_html($plan->data->currency) . ' ' . esc_html(number_format($planamount)) . '</label>
			</div>';
					}
					echo(do_shortcode($obj->post_content));
	
					// Agreement terms
					if ($useagreement == 'yes') {
						echo '<div class="span12 unit">
			<label class="checkbox">
				<input type="checkbox" name="agreement" id="pf-agreement" required value="yes">
				<i></i>
				Accept terms <a target="_blank" href="' . esc_url($agreementlink) . '">Link</a>
			</label>
		</div><br>';
					}
	
	
					// Form submission controls
					echo '<div class="span12 unit">
		<small><span style="color: red;">*</span> are compulsory</small><br />
		<img src="' . esc_url(plugins_url('../images/logos@2x.png', __FILE__)) . '" alt="cardlogos" class="paystack-cardlogos size-full wp-image-1096" />
		<button type="reset" class="secondary-btn">Reset</button>';
					if ($showbtn) {
						echo '<button type="submit" class="primary-btn">' . esc_html($paybtn) . '</button>';
					}
					echo '</div></div></form>';
				} else {
					echo "<h5>You must be logged in to make a payment.</h5>";
				}
			} else {
				echo "<h5>Invalid Paystack form ID or the form does not exist.</h5>";
			}
		} else {
			echo "<h5>No Paystack form ID provided.</h5>";
		}
	
		return ob_get_clean();
	}

}