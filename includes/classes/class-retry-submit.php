<?php
/**
 * The functions to handle the form submission, this will trigger the payment requests to paystack.
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
class Retry_Submit {

	/**
	 * The helpers class.
	 *
	 * @var \paystack\payment_forms\Helpers
	 */
	public $helpers;

	/**
	 * Holds the current form meta
	 *
	 * @var array
	 */
	protected $meta = array();

	/**
	 * Holds the current retry meta from the DB
	 *
	 * @var object
	 */
	protected $retry_meta;

	/**
	 * Holds the current form id
	 *
	 * @var array
	 */
	protected $form_id = 0;

	/**
	 * Holds the current transaction code.
	 *
	 * @var string
	 */
	public $code = '';

	/**
	 * Holds the new code to use.
	 *
	 * @var string
	 */
	public $new_code = '';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'wp_ajax_pff_paystack_retry_action', [ $this, 'retry_action' ] );
		add_action( 'wp_ajax_nopriv_pff_paystack_retry_action', [ $this, 'retry_action' ] );
	}

	/**
	 * Undocumented function
	 *
	 * @return void
	 */
	protected function setup_data() {
		$this->helpers   = new Helpers();
		$this->code      = sanitize_text_field( $_POST['code'] );
		$this->new_code  = $this->generate_code() . '_2';

		$retry_record    = $this->helpers->get_db_record( $this->code );
		if ( false !== $retry_record ) {
			$this->retry_meta = $retry_record;
			$this->form_id    = $this->retry_meta->post_id;
			$this->meta       = $this->helpers->parse_meta_values( get_post( $this->form_id ) );
		}
	}

	/**
	 * The action for the retry form.
	 *
	 * @return void
	 */
	public function retry_action() {
		if ( '' === trim( $_POST['code'] ) ) {
			$response = array(
				'result'  => 'failed',
				'message' => __( 'Code is required', 'pff-paystack' ),
			);

			// Exit here, for not processing further because of the error.
			exit( wp_json_encode( $response ) );
		}
		do_action( 'kkd_pff_paystack_before_save' );

		/**
		 * Setup our data to be processed.
		 */
		$this->setup_data();

		if ( 0 !== $this->form_id ) {
			$subaccount          = $this->meta['subaccount'];
			$txnbearer           = $this->meta['txnbearer'];
			$transaction_charge  = (int) $this->meta['merchantamount'];
			$transaction_charge *= 100;
			$fixedmetadata       = json_decode( $this->retry_meta->metadata );
			foreach ( $fixedmetadata as $nkey => $nvalue ) {
				if ( 'Quantity' === $nvalue->variable_name ) {
					$quantity = $nvalue->value;
				}
				if ( 'Full_Name' === $nvalue->variable_name ) {
					$fullname = $nvalue->value;
				}
			}
		}

		if ( empty( $this->meta['subaccount'] ) ) {
			$subaccount         = null;
			$txnbearer          = null;
			$transaction_charge = null;
		}

		if ( empty( $transaction_charge ) || 0 === $transaction_charge ) {
			$transaction_charge = null;
		}

		$response = array(
			'result'             => 'success',
			'code'               => $this->new_code,
			'plan'               => $this->retry_meta->plan,
			'quantity'           => $quantity,
			'email'              => $this->retry_meta->email,
			'name'               => $fullname,
			'total'              => $this->retry_meta->amount * 100,
			'custom_fields'      => $fixedmetadata,
			'currency'           => $this->meta['currency'],
			'subaccount'         => $subaccount,
			'txnbearer'          => $txnbearer,
			'transaction_charge' => $transaction_charge,
		);

		echo wp_json_encode( $response );

		die();
	}

	/**
	 * Generate a unique Paystack code that does not yet exist in the database.
	 *
	 * @return string Generated unique code.
	 */
	public function generate_code() {
		do {
			$code = $this->helpers->generate_new_code();
			$check = $this->helpers->check_code( $code );
		} while ( $check );

		return $code;
	}

	/**
	 * Updates the DB row with the new transaction code.
	 *
	 * @return void
	 */
	protected function update_retry_code() {
		global $wpdb;
		$return = false;
		$table  = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		$sql = $wpdb->prepare(
			"UPDATE %i SET txn_code_2 = %s WHERE txn_code = %s",
			$table,
			$this->new_code,
			$this->code
		);
		$return = $wpdb->query( $sql );
		return $return;
	}
}