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
class Form_Shortcode {

	/**
	 * The helper class.
	 *
	 * @var Helpers
	 */
	public $helpers;

	/**
	 * Holds the array of meta fields and their stored values.
	 *
	 * @var array
	 */
	protected $meta = [];

	/**
	 * Holds the array of the current user data.
	 *
	 * @var array
	 */
	protected $user = [];

	/**
	 * Holds the current form Object
	 *
	 * @var WP_Post
	 */
	protected $form;

	/**
	 * If we should show the submit button, if this is false there is most likely a config error with the form.
	 *
	 * @var boolean
	 */
	public $show_btn = true;

	/**
	 * If the current form has a plan or not.
	 *
	 * @var boolean
	 */
	public $has_plan = false;

	/**
	 * A plan object as per the Paystack API Fetch request
	 * @link https://paystack.com/docs/api/plan
	 * @var object
	 */
	public $plan = false;

	/**
	 * Holds the array of payment options available.
	 *
	 * @var array
	 */
	protected $paymentoptions = [];

	/**
	 * Constructor
	 */
	public function __construct() {
		if ( is_admin() ) {
			return;
		}
		add_shortcode( 'paystack_form', [ $this, 'form_shortcode' ] );
		add_shortcode( 'pff-paystack', [ $this, 'form_shortcode' ] );
	}

	/**
	 * Generates the form output, will run the individual shortcodes.
	 *
	 * @param array $atts
	 * @return string
	 */
	public function form_shortcode( $atts ) {
		// Use array access for shortcode attributes
		$defaults = array(
			'id' => 0
		);
		$atts = shortcode_atts(
			$defaults,
			$atts,
			'paystack_form'
		);
		$id            = intval( $atts['id'] ); // Ensure $id is an integer
		$this->helpers = Helpers::get_instance();
	
		/*$pk = Pff_Paystack_Public::fetchPublicKey();
		if (!$pk) {
			$settingslink = esc_url(get_admin_url(null, 'edit.php?post_type=paystack_form&page=class-paystack-forms-admin.php'));
			echo "<h5>You must set your Paystack API keys first <a href='{$settingslink}'>settings</a></h5>";
			return ob_get_clean(); // Return early to avoid further processing
		}*/

		// Store our items in an array and not an object.
		$html = [];
	
		if ( $id > 0 ) {
			$obj = get_post( $id );
			if ( null !== $obj && 'paystack_form' === get_post_type( $obj ) ) {
				
				$this->form = $obj;
				$this->set_user_details();
				$this->set_meta_data( $obj );
				
				// Check if the form should be displayed based on user login status
				$show_form = $this->should_show_form();
	
				if ( $show_form ) {
					// Form title
					if ( $this->meta['hidetitle'] != 1 ) {
						$html[] = "<h1 id='pf-form" . esc_attr( $id ) . "'>" . esc_html( $obj->post_title ) . "</h1>";
					}
	
					// Start form output
					$html[] = '<form version="' . esc_attr( PFF_PAYSTACK_VERSION ) . '" enctype="multipart/form-data" action="' . esc_url( admin_url( 'admin-ajax.php' ) ) . '" method="post" class="paystack-form j-forms" novalidate>
						  <div class="j-row">';
	
					// Hidden Fields
					$html[] = $this->get_hidden_fields();
					// User fields
					$html[] = $this->get_fullname_field();
					$html[] = $this->get_email_field();

					// Amount selection with consideration for variable amounts, minimum payments, and recurring plans
					$html[] = $this->get_amount_field();

					$html[] = $this->get_quantity_field();
	
					// Recurring payment options
					$html[] = $this->get_recurring_field();
					$html[] = $this->get_recurring_plan_fields();
					
					$html[] = do_shortcode( $obj->post_content );

					$html[] = $this->get_agreement_field();

					$html[] = $this->get_form_footer();
	
					$html[] = '</div></form>';
				} else {
					$html[] = '<h5>' . __( 'You must be logged in to make a payment.', 'pff-paystack' ) . '</h5>';
				}
			} else {
				$html[] = '<h5>' . __( 'Invalid Paystack form ID or the form does not exist.', 'pff-paystack' ) . '</h5>';
			}
		} else {
			$html[] = '<h5>' . __( 'No Paystack form ID provided.', 'pff-paystack' ) . '</h5>';
		}

		$html = implode( '', $html );
	
		return $html;
	}

	/**
	 * Set the user deteails based on the logged in wp_user
	 *
	 * @return void
	 */
	public function set_user_details() {
		// Ensure the current user is populated
		$this->user['logged_in'] = false;
		$this->user['id']        = 0;
		$this->user['fullname']  = '';
		$this->user['email']     = '';	

		if ( is_user_logged_in() ) {
			$current_user           = wp_get_current_user();
			$user['logged_in']      = true;
			$this->user['id']       = $current_user->ID;
			$this->user['email']    = $current_user->user_email;
			$this->user['fname']    = sanitize_text_field( $current_user->user_firstname );
			$this->user['lname']    = sanitize_text_field( $current_user->user_lastname );
			$this->user['fullname'] = $this->user['fname'] || $this->user['lname'] ? trim( $this->user['fname'] . ' ' . $this->user['lname'] ) : '';
		}
	}

	/**
	 * Set the user deteails based on the logged in wp_user
	 *
	 * @return void
	 */
	public function set_meta_data( $obj ) {
		$this->meta = $this->helpers->parse_meta_values( $obj );
		if ( 1 === $this->meta['usevariableamount'] ) {
			$this->meta['paymentoptions'] = explode( ',', $this->meta['variableamount'] );
			
			$this->meta['paymentoptions'] = array_map( 'sanitize_text_field', $this->meta['paymentoptions'] );
		}

		$this->meta['planerrorcode'] = __( 'Input Correct Recurring Plan Code', 'pff-paystack' );

		if ( 'plan' === $this->meta['recur'] ) {
			if ( '' === $this->meta['recurplan'] ) {
				$this->show_btn = false;
			} else {
				$this->plan = pff_paystack()->classes['request-plan']->fetch_plan( $this->meta['recurplan'] );
				if ( false !== $this->plan && isset( $this->plan->data->amount ) ) {
					$this->has_plan = true;
					$this->meta['planamount'] = $this->plan->data->amount / 100;
				} else {
					$this->show_btn = false;
				}
			}
		}
	}

	/**
	 * If we should show the form or not.
	 *
	 * @return boolean
	 */
	public function should_show_form() {
		$show_form = false;
		if ( 'no' === $this->meta['loggedin'] || ( 'yes' === $this->meta['loggedin'] &&  true === $this->user['logged_in'] ) ) {
			$show_form = true;
		}
		return $show_form;
	}

	/**
	 * Return the Hidden fields needed.
	 * @return string
	 */
	public function get_hidden_fields() {
		// Hidden inputs
		$html = '<input type="hidden" name="action" value="pff_paystack_submit_action">
				<input type="hidden" name="pf-id" value="' . esc_attr( $this->form->ID ) . '" />
				<input type="hidden" name="pf-user_id" value="' . esc_attr( $this->user['id'] ) . '" />
				<input type="hidden" name="pf-recur" value="' . esc_attr( $this->meta['recur'] ) . '" />
				<input type="hidden" name="pf-currency" id="pf-currency" value="' . $this->meta['currency'] . '" />';
		return $html;
	}
	
	/**
	 * Return the Fullname field.
	 *
	 * @return string
	 */
	public function get_fullname_field() {
		$html = '<div class="span12 unit">
			<label class="label">' . __( 'Full Name', 'pff-paystack' ) . ' <span>*</span></label>
			<div class="input">
				<input type="text" name="pf-fname" placeholder="' . __( 'First & Last Name', 'pff-paystack' ) . '" value="' . esc_attr( $this->user['fullname'] ) . '" required>
			</div>
		</div>';
		return $html;
	}
	
	/**
	 * Return the Email field.
	 *
	 * @return string
	 */
	public function get_email_field() {
		$html = '<div class="span12 unit">
			<label class="label">' . __( 'Email', 'pff-paystack' ) . ' <span>*</span></label>
			<div class="input">
				<input type="email" name="pf-pemail" placeholder="' . __( 'Enter Email Address', 'pff-paystack' ) . '" id="pf-email" value="' . esc_attr( $this->user['email'] ) . '" ' . ( $this->meta['loggedin'] == 'yes' ? 'readonly' : '' ) . ' required>
			</div>
		</div>';
		return $html;
	}
	
	/**
	 * Get the amount field
	 * @return string
	 */
	public function get_amount_field() {
		$html = [];
		$html[] = '<div class="span12 unit">
			<label class="label">Amount (' . esc_html( $this->meta['currency'] );

			if ( 0 === $this->meta['minimum'] && 0 !== $this->meta['amount'] && 'yes' === $this->meta['usequantity'] ) {
				$html[] = ' ' . esc_html( number_format( $this->meta['amount'] ) );
			}

			$html[] = ') <span>*</span></label>
			<div class="input">';

			// If the amount is set.
			if ( 0 === $this->meta['usevariableamount'] ) {

				if ($this->meta['minimum'] == 1) {
					$html[] = '<small> Minimum payable amount <b style="font-size:87% !important;">' . esc_html($this->meta['currency']) . '  ' . esc_html(number_format($this->meta['amount'])) . '</b></small>';
				}

				if ($this->meta['recur'] == 'plan') {
					if ( $this->show_btn ) {
						$html[] = '<input type="text" name="pf-amount" value="' . esc_attr( $this->meta['planamount'] ) . '" id="pf-amount" readonly required />';
					} else {
						$html[] = '<div class="span12 unit">
									<label class="label" style="font-size:18px;font-weight:600;line-height: 20px;">' . esc_html( $this->meta['planerrorcode'] ) . '</label>
									</div>';
					}
				} elseif ( $this->meta['recur'] == 'optional' ) {
					$html[] = '<input type="text" name="pf-amount" class="pf-number" id="pf-amount" value="0" required />';
				} else {
					$html[] = '<input type="text" name="pf-amount" class="pf-number" value="' . esc_attr( 0 === $this->meta['amount'] ? "0" : $this->meta['amount'] ) . '" id="pf-amount" ' . ( 0 !== $this->meta['amount'] && 1 !== $this->meta['minimum'] ? 'readonly' : '' ) . ' required />';
				}

			} else {

				if ( '' === $this->meta['variableamount'] || 0 === $this->meta['variableamount'] || ! is_array( $this->meta['paymentoptions'] ) ) {
					$html[] = __( 'Form Error, set variable amount string', 'pff-paystack' );
				} else if ( count( $this->meta['paymentoptions'] ) > 0 ) {
					$html[] = '<div class="select">
							<input type="hidden"  id="pf-vname" name="pf-vname" />
							<input type="hidden"  id="pf-amount" />
							<select class="form-control" id="pf-vamount" name="pf-amount">';
							foreach ( $this->meta['paymentoptions'] as $option ) {
								list( $optionName, $optionValue ) = explode( ':', $option );
								$html[] = '<option value="' . $optionValue . '" data-name="' . $optionName . '">' . $optionName . ' - ' . $this->meta['currency'] . ' ' . number_format( $optionValue ) . '</option>';
							}
					$html[] = '</select> <i></i> </div>';
				}
			}

			// Transaction charge notice
			if ( 'merchant' !== $this->meta['txncharge'] && 'plan' !== $this->meta['recur'] ) {
				$html[] = '<small>Transaction Charge: <b class="pf-txncharge"></b>, Total:<b  class="pf-txntotal"></b></small>';
			}

		$html[] = '</div></div>';

		return implode( '', $html );
	}

	/**
	 * Get the agreement checkbox and link.
	 * 
	 * @return string
	 */
	public function get_agreement_field() {
		$html = '';
		if ( $this->meta['useagreement'] == 'yes' ) {
		$html = '<div class="span12 unit">
					<label class="checkbox">
						<input type="checkbox" name="agreement" id="pf-agreement" required value="yes">
						<i></i>
						Accept terms <a target="_blank" href="' . esc_url( $this->meta['agreementlink'] ) . '">Link</a>
					</label>
				</div><br>';
		}
		return $html;
	}

	/**
	 * Get the form footer including the logos and the action buttons.
	 * 
	 * @return string
	 */
	public function get_form_footer() {
		$html = [];

		// Form submission controls
		$html[] = '<div class="span12 unit">
			<small><span style="color: red;">*</span> are compulsory</small>
			<br />
			<img src="' . esc_url( PFF_PAYSTACK_PLUGIN_URL . '/assets/images/logos@2x.png' ) . '" alt="cardlogos" class="paystack-cardlogos size-full wp-image-1096" />
			<button type="reset" class="secondary-btn">Reset</button>';
			if ($this->show_btn) {
				$html[] = '<button type="submit" class="primary-btn">' . esc_html( $this->meta['paybtn'] ) . '</button>';
			}
		$html[] = '</div>';
		return implode( '', $html );
	}

	/**
	 * Gets the quantity selector if it is set.
	 * @return string
	 */
	public function get_quantity_field() {
		$html = [];
		// Quantity selection
		if ( 'no' === $this->meta['recur'] && 'yes' === $this->meta['usequantity'] && ( 1 === $this->meta['usevariableamount'] || 0 !== $this->meta['amount'] ) ) {
			$html[] = '<div class="span12 unit">
				<label class="label">Quantity</label>
				<div class="select">
					<input type="hidden" value="' . esc_attr( $this->meta['amount'] ) . '" id="pf-qamount"/>
					<select class="form-control" id="pf-quantity" name="pf-quantity">';
				for ( $i = 1; $i <= $this->meta['quantity']; $i++ ) {
					$html[] = '<option value="' . esc_attr( $i ) . '">' . esc_html( $i ) . '</option>';
				}
			$html[] = '</select> <i></i> </div></div>';
		}
		return implode( '', $html );
	}

	/**
	 * Gets the recurring field.
	 * @return string
	 */
	public function get_recurring_field() {
		$html = [];

		if ( $this->meta['recur'] == 'optional' ) {

			$intervals = [
				'no' => 'None',
				'daily' =>'Daily',
				'weekly' => 'Weekly',
				'monthly' => 'Monthly',
				'biannually' => 'Biannually',
				'annually' => 'Annually',
			];

			$html[] = '<div class="span12 unit"><label class="label">Recurring Payment</label>
				<div class="select">
					<select class="form-control" name="pf-interval">';

				foreach ( $intervals as $intervalValue => $intervalName ) {
					$html[] = '<option value="' . esc_attr( $intervalValue ) . '">' . esc_html( $intervalName ) . '</option>';
				}
			$html[] = '</select> <i></i>
					</div>
				</div>';
		}
		return implode( '', $html );
	}

	/**
	 * Gets the recurring plan fields.
	 * 
	 * @return string
	 */
	public function get_recurring_plan_fields() {
		$html = [];
		// Plan details for recurring payments
		if ( $this->meta['recur'] == 'plan' && $this->has_plan && $this->show_btn ) {
			$html[] = '<input type="hidden" name="pf-plancode" value="' . esc_attr( $this->meta['recurplan'] ) . '" />';
			$html[] = '<div class="span12 unit">
					<label class="label" style="font-size:18px;font-weight:600;line-height: 20px;">' . esc_html( $this->plan->data->name ) . ' ' . esc_html( $this->plan->data->interval ) . ' recurring payment - ' . esc_html( $this->plan->data->currency ) . ' ' . esc_html( number_format( $this->meta['planamount'] ) ) . '</label>
				</div>';
		}

		return implode( '', $html );
	}
}