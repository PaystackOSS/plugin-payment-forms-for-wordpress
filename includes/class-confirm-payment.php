<?php
/**
 * The functions to handle the confirm payment action
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Form Submit Class
 */
class Confirm_Payment {

	/**
	 * The helpers class.
	 *
	 * @var object
	 */
	public $helpers;

	/**
	 * Holds the current form meta
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Hold the current transaction response
	 *
	 * @var boolean|object
	 */
	protected $transaction = false;

	/**
	 * Holds the current payment meta retrieved from the DB.
	 *
	 * @var object
	 */
	protected $payment_meta;

	/**
	 * The current form ID we are processing the payment for.
	 *
	 * @var integer
	 */
	protected $form_id = 0;

	/**
	 * The amount paid at checkout.
	 *
	 * @var integer
	 */
	protected $amount = 0;

	/**
	 * The amount save in the form meta
	 *
	 * @var integer
	 */
	protected $oamount = 0;

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_pff_paystack_confirm_payment', [ $this, 'confirm_payment' ] );
		add_action( 'wp_ajax_nopriv_pff_paystack_confirm_payment', [ $this, 'confirm_payment' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function setup_data( $payment ) {
		$this->payment_meta = $payment;
		$this->helpers      = new Helpers();
		$this->meta         = $this->helpers->parse_meta_values( get_post( $this->payment_meta->post_id ) );
		$this->amount       = $this->payment_meta->amount;
		$this->oamount      = $this->meta['amount'];
		$this->form_id      = $this->payment_meta->post_id;

		if ( 'customer' === $this->meta['txncharge'] ) {
			$this->oamount = $this->helpers->process_transaction_fees( $this->oamount );
		}
	}
	
	/**
	 * Confirm Payment Functionality.
	 */
	public function confirm_payment() {
		if ( trim( $_POST['code'] ) === '' ) {
			$response = array(
				'error' => true,
				'error_message' => __( 'Did you make a payment?', 'pff-paystack' ),
			);
	
			exit( wp_json_encode( $response ) );
		}
	
	
		$code   = sanitize_text_field( $_POST['code'] );
		$record = $this->get_db_record( $code );

		if ( false !== $record ) {
			
			$this->setup_data( $record );

			// Verify our transaction with the Paystack API.
			$transaction = pff_paystack()->classes['transaction-verify']->verify_transaction( $code );

			if ( ! empty( $transaction ) && isset( $transaction['data'] )  ) {
				$transaction['data'] = json_decode( $transaction['data'] );
				if ( 'success' === $transaction['data']->status ) {
					$this->update_sold_inventory();
					$response = $this->update_payment_dates( $transaction['data'] );
				}
			} else {
				$response = [
					'message' => __( 'Failed to connect to Paystack.', 'pff-paystack' ),
					'result'  => 'failed',
				];	
			}
	
		} else {
			$response = [
				'message' => __( 'Payment Verification Failed', 'pff-paystack' ),
				'result'  => 'failed',
			];
		}
	

		// Create plan and send reciept.
		if ( 'success' === $response['result'] ) {
			
			// Create a plan that the user will be subscribed to.
			
			/*$pstk_logger = new kkd_pff_paystack_plugin_tracker( 'pff-paystack', Kkd_Pff_Paystack_Public::fetchPublicKey() );
			$pstk_logger->log_transaction_success( $code );*/

			$this->maybe_create_subscription();
	
			
			$sendreceipt = $this->meta['sendreceipt'];
			if ( 'yes' === $sendreceipt ) {
				$decoded = json_decode( $this->payment_meta->metadata );
				$fullname = $decoded[1]->value;

				/**
				 * Allow 3rd Party Plugins to hook into the email sending.
				 * 
				 * 10: Email_Receipt::send_receipt();
				 * 11: Email_Receipt_Owner::send_receipt_owner();
				 */
				do_action( 'pff_paystack_send_receipt',
					$this->payment_meta->post_id,
					$this->payment_meta->currency,
					$this->payment_meta->amount_paid,
					$fullname,
					$this->payment_meta->email,
					$this->payment_meta->reference,
					$this->payment_meta->metadata
				);
			}
		}
	
		if ( 'success' === $response['result'] && '' !== $this->meta['redirect'] ) {
			$response['result'] = 'success2';
			$response['link']   = $this->meta['redirect'];
		}
	
		echo wp_json_encode( $response );
		die();
	}

	/**
	 * Update the sold invetory with the amount of payments made.
	 *
	 * @return void
	 */
	protected function update_sold_inventory() {
		$usequantity = $this->meta['usequantity'];
		$sold        = $this->meta['sold'];

		if ( 'yes' === $usequantity ) {
			$quantity = $_POST['quantity'];
			$sold     = $this->meta['sold'];

			if ( '' === $sold ) {
				$sold = '0';
			}
			$sold += $quantity;
		} else {
			$sold++;
		}

		if ( $this->meta['sold'] ) {
			update_post_meta( $this->form_id, '_sold', $sold );
		} else {
			add_post_meta( $this->form_id, '_sold', $sold, true );
		}
	}

	/**
	 * Updates the paid Date for the current record.
	 *
	 * @param object $data
	 * @return array
	 */
	protected function update_payment_dates( $data ) {
		global $wpdb;
		$table  = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		$return = [
			'message' => __( 'DB not updated.', 'pff-paystack' ),
			'result' => 'failed',
		];

		$customer_code  = $data->customer->customer_code;
		$amount_paid    = $data->amount / 100;
		$paystack_ref   = $data->reference;
		$paid_at        = $data->transaction_date;
		if ( 'optional' === $this->meta['recur'] || 'plan' === $this->meta['recur'] ) {
			$wpdb->update(
				$table,
				array(
					'paid'    => 1,
					'amount'  => $amount_paid,
					'paid_at' => $paid_at,
				),
				array( 'txn_code' => $paystack_ref )
			);
			$return = [
				'message' => $this->meta['successmsg'],
				'result' => 'success',
			];
		} else {
			// If this the price paid was free, or if it was a variable amount.
			if ( 0 === (int) $this->oamount || 1 === $this->meta['usevariableamount'] ) {
				$wpdb->update(
					$table,
					array(
						'paid'    => 1,
						'amount'  => $amount_paid,
						'paid_at' => $paid_at,
					),
					array( 'txn_code' => $paystack_ref )
				);
				$return = [
					'message' => $this->meta['successmsg'],
					'result' => 'success',
				];
			} else {
				if ( $this->oamount !== $amount_paid ) {
					$return = [
						'message' => sprintf( __( 'Invalid amount Paid. Amount required is %s<b>%s</b>', 'pff-paystack' ), $this->meta['currency'], number_format( $this->oamount ) ),
						'result' => 'failed',
					];
				} else {
					$wpdb->update(
						$table,
						array(
							'paid'    => 1,
							'paid_at' => $paid_at,
						),
						array( 'txn_code' => $paystack_ref )
					);
					$return = [
						'message' => $this->meta['successmsg'],
						'result' => 'success',
					];
				}
			}
		}
		return $return;
	}

	protected function maybe_create_subscription() {
		// Create a "subscription" and attach it to the current plan code.
		if ( 1 == $this->meta['startdate_enabled'] && ! empty( $this->meta['startdate_days'] ) && ! empty( $this->meta['startdate_plan_code'] ) ) {
			$start_date = date( 'c', strtotime( '+' . $this->meta['startdate_days'] . ' days' ) );
			$body       = array(
				'start_date' => $start_date,
				'plan'       => $this->meta['startdate_plan_code'],
				'customer'   => $this->payment_meta->email,
			);

			$created_sub = pff_paystack()->classes['request-subscription']->create_subscription( $body );
			if ( false !== $created_sub ) {
				// Nothing defined for this.
			}
		}
	}

	private function get_db_record( $code ) {
		global $wpdb;
		$return = false;
		$table  = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		$record = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * 
					FROM %i 
					WHERE txn_code = %s"
				,
				$table,
				$code
			), 'OBJECT' );

		if ( ! empty( $record ) && isset( $record[0] ) ) {
			$return = $record[0];
		}
		return $return;
	}

	private function update_db_record( $code ) {
		$updated = false;
		return $updated;
	}
}