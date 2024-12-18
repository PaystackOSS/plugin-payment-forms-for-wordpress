<?php
/**
 * A class of helper functions that are used in many places.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Helper class.
 */
class Helpers {

	/**
	 * Holds class isntance
	 *
	 * @var object \paystack\payment_forms\Helpers
	 */
	protected static $instance = null;

	/**
	 * The array of meta keys and their default values.
	 *
	 * @var array
	 */
	protected $defaults = [];

	/**
	 * An array of the allowed HTML tags
	 *
	 * @var array
	 */
	protected $allowed_html = [];

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->defaults = [
			'amount'              => 0,
			'paybtn'              => __( 'Pay', 'pff-paystack' ),
			'successmsg'          => __( 'Thank you for paying!', 'pff-paystack' ),
			'txncharge'           => 'merchant',
			'loggedin'            => '',
			'currency'            => 'NGN',
			'filelimit'           => 2,
			'redirect'            => '',
			'minimum'             => 0,
			'usevariableamount'   => 0,
			'variableamount'      => 'Please configure your options:0,None:0',
			'hidetitle'           => 0,
			'loggedin'            => 'no',
			'recur'               => 'no',
			'recurplan'           => '',
			'subject'             => __( 'Thank you for your payment', 'pff-paystack' ),
			'heading'             => __( 'We\'ve received your payment', 'pff-paystack' ),
			'message'             => __( 'Your payment was received and we appreciate it.', 'pff-paystack' ),
			'sendreceipt'         => 'yes',
			'sendinvoice'         => 'yes',
			'usequantity'         => 'no',
			'useinventory'        => 'no',
			'inventory'           => 0,
			'sold'                => 0,
			'quantity'            => 10,
			'quantityunit'        => __( 'Quantity', 'pff-paystack' ),
			'useagreement'        => 'no',
			'agreementlink'       => '',
			'subaccount'          => '',
			'txnbearer'           => 'account',
			'merchantamount'      => '',
			'startdate_days'      => '',
			'startdate_plan_code' => '',
			'startdate_enabled'   => 0,
		];

		$this->allowed_html = array(
			'small' => array(
				'href' => true,
				'target' => true
			),
			'a' => array(
				'href' => true,
				'target' => true
			),
			'p' => array(),
			'input' => array(
				'type' => true,
				'name' => true,
				'value' => true,
				'class' => true,
				'checked' => true
			),
			'br' => array(),
			'label' => array(
				'for' => true
			),
			'code' => array(),
			'select' => array(
				'class' => true,
				'name' => true,
				'id' => true,
				'style' => true
			),
			'option' => array(
				'value' => true,
				'selected' => true
			),
			'textarea' => array(
				'rows' => true,
				'name' => true,
				'class' => true
			)
			);
	}

	/**
	 * Return an instance of this class.
	 *
	 * @return object \paystack\payment_forms\Payment_Forms
	 */
	public static function get_instance() {
		// If the single instance hasn't been set, set it now.
		if ( null == self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	// GETTERS

	/**
	 * Returns the fee settings save or the default values.
	 *
	 * @return array
	 */
	public function get_fees() {
		$ret = [];
		$ret['prc'] = intval( floatval( esc_attr( get_option( 'prc', PFF_PAYSTACK_PERCENTAGE ) ) ) * 100 ) / 10000;
		$ret['ths'] = intval( floatval( esc_attr( get_option( 'ths', PFF_PAYSTACK_CROSSOVER_TOTAL ) ) ) * 100 );
		$ret['adc'] = intval( floatval( esc_attr( get_option( 'adc', PFF_PAYSTACK_ADDITIONAL_CHARGE ) ) ) * 100 );
		$ret['cap'] = intval( floatval( esc_attr( get_option( 'cap', PFF_PAYSTACK_LOCAL_CAP ) ) ) * 100 );
		return $ret;
	}

	/**
	 * Gets the public key from the settings.
	 *
	 * @return string
	 */
	public function get_public_key() {
		$mode =  esc_attr( get_option( 'mode' ) );
		if ( 'test' === $mode ) {
			$key = esc_attr( get_option( 'tpk', '' ) );
		} else {
			$key = esc_attr( get_option( 'lpk', '' ) );
		}
		return $key;
	}

	/**
	 * Fetch an array of the payments by the form ID.
	 *
	 * @param integer $form_id
	 * @param array $args
	 * @return array
	 */
	public function get_payments_by_id( $form_id = 0, $args = array() ) {
        global $wpdb;
		$results = array();
		if ( 0 === $form_id ) {
			return $results;
		}

		$defaults = array(
			'paid'     => '1', 
			'order'    => 'desc',
			'orderby'  => 'created_at',
		);
		$args  = wp_parse_args( $args, $defaults );
        $table = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		$order = strtoupper( $args['order'] );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * 
				FROM %i 
				WHERE post_id = %d 
				AND paid = %s
				ORDER BY %i $order", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$table,
				$form_id,
				$args['paid'],
				$args['orderby'],
			)
		);
		return $results;
	}

	/**
	 * Gets the payments count for the current form.
	 *
	 * @param int|string $form_id
	 * @return int
	 */
	public function get_payments_count( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		$num   = wp_cache_get( 'form_payments_' . $form_id, 'pff_paystack' );
		if ( false === $num ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$num = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM %i
					WHERE post_id = %d
					AND paid = '1'",
					$table,
					$form_id
				)
			);
			wp_cache_set( 'form_payments_' . $form_id, $num, 'pff_paystack', 60*5 );
		}
		return $num;
	}

	/**
	 * Returns an array | string of the countries
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_countries( $implode = false ) {
		$countries = [
			__( "Afghanistan", 'pff-paystack' ),
			__( "Albania", 'pff-paystack' ),
			__( "Algeria", 'pff-paystack' ),
			__( "American Samoa", 'pff-paystack' ),
			__( "Andorra", 'pff-paystack' ),
			__( "Angola", 'pff-paystack' ),
			__( "Anguilla", 'pff-paystack' ),
			__( "Antarctica", 'pff-paystack' ),
			__( "Antigua and Barbuda", 'pff-paystack' ),
			__( "Argentina", 'pff-paystack' ),
			__( "Armenia", 'pff-paystack' ),
			__( "Aruba", 'pff-paystack' ),
			__( "Australia", 'pff-paystack' ),
			__( "Austria", 'pff-paystack' ),
			__( "Azerbaijan", 'pff-paystack' ),
			__( "Bahamas", 'pff-paystack' ),
			__( "Bahrain", 'pff-paystack' ),
			__( "Bangladesh", 'pff-paystack' ),
			__( "Barbados", 'pff-paystack' ),
			__( "Belarus", 'pff-paystack' ),
			__( "Belgium", 'pff-paystack' ),
			__( "Belize", 'pff-paystack' ),
			__( "Benin", 'pff-paystack' ),
			__( "Bermuda", 'pff-paystack' ),
			__( "Bhutan", 'pff-paystack' ),
			__( "Bolivia", 'pff-paystack' ),
			__( "Bosnia and Herzegovina", 'pff-paystack' ),
			__( "Botswana", 'pff-paystack' ),
			__( "Bouvet Island", 'pff-paystack' ),
			__( "Brazil", 'pff-paystack' ),
			__( "British Indian Ocean Territory", 'pff-paystack' ),
			__( "Brunei Darussalam", 'pff-paystack' ),
			__( "Bulgaria", 'pff-paystack' ),
			__( "Burkina Faso", 'pff-paystack' ),
			__( "Burundi", 'pff-paystack' ),
			__( "Cambodia", 'pff-paystack' ),
			__( "Cameroon", 'pff-paystack' ),
			__( "Canada", 'pff-paystack' ),
			__( "Cape Verde", 'pff-paystack' ),
			__( "Cayman Islands", 'pff-paystack' ),
			__( "Central African Republic", 'pff-paystack' ),
			__( "Chad", 'pff-paystack' ),
			__( "Chile", 'pff-paystack' ),
			__( "China", 'pff-paystack' ),
			__( "Christmas Island", 'pff-paystack' ),
			__( "Cocos (Keeling) Islands", 'pff-paystack' ),
			__( "Colombia", 'pff-paystack' ),
			__( "Comoros", 'pff-paystack' ),
			__( "Congo", 'pff-paystack' ),
			__( "Congo, The Democratic Republic of The", 'pff-paystack' ),
			__( "Cook Islands", 'pff-paystack' ),
			__( "Costa Rica", 'pff-paystack' ),
			__( "Cote D'ivoire", 'pff-paystack' ),
			__( "Croatia", 'pff-paystack' ),
			__( "Cuba", 'pff-paystack' ),
			__( "Cyprus", 'pff-paystack' ),
			__( "Czech Republic", 'pff-paystack' ),
			__( "Denmark", 'pff-paystack' ),
			__( "Djibouti", 'pff-paystack' ),
			__( "Dominica", 'pff-paystack' ),
			__( "Dominican Republic", 'pff-paystack' ),
			__( "Ecuador", 'pff-paystack' ),
			__( "Egypt", 'pff-paystack' ),
			__( "El Salvador", 'pff-paystack' ),
			__( "Equatorial Guinea", 'pff-paystack' ),
			__( "Eritrea", 'pff-paystack' ),
			__( "Estonia", 'pff-paystack' ),
			__( "Ethiopia", 'pff-paystack' ),
			__( "Falkland Islands (Malvinas)", 'pff-paystack' ),
			__( "Faroe Islands", 'pff-paystack' ),
			__( "Fiji", 'pff-paystack' ),
			__( "Finland", 'pff-paystack' ),
			__( "France", 'pff-paystack' ),
			__( "French Guiana", 'pff-paystack' ),
			__( "French Polynesia", 'pff-paystack' ),
			__( "French Southern Territories", 'pff-paystack' ),
			__( "Gabon", 'pff-paystack' ),
			__( "Gambia", 'pff-paystack' ),
			__( "Georgia", 'pff-paystack' ),
			__( "Germany", 'pff-paystack' ),
			__( "Ghana", 'pff-paystack' ),
			__( "Gibraltar", 'pff-paystack' ),
			__( "Greece", 'pff-paystack' ),
			__( "Greenland", 'pff-paystack' ),
			__( "Grenada", 'pff-paystack' ),
			__( "Guadeloupe", 'pff-paystack' ),
			__( "Guam", 'pff-paystack' ),
			__( "Guatemala", 'pff-paystack' ),
			__( "Guinea", 'pff-paystack' ),
			__( "Guinea-bissau", 'pff-paystack' ),
			__( "Guyana", 'pff-paystack' ),
			__( "Haiti", 'pff-paystack' ),
			__( "Heard Island and Mcdonald Islands", 'pff-paystack' ),
			__( "Holy See (Vatican City State)", 'pff-paystack' ),
			__( "Honduras", 'pff-paystack' ),
			__( "Hong Kong", 'pff-paystack' ),
			__( "Hungary", 'pff-paystack' ),
			__( "Iceland", 'pff-paystack' ),
			__( "India", 'pff-paystack' ),
			__( "Indonesia", 'pff-paystack' ),
			__( "Iran, Islamic Republic of", 'pff-paystack' ),
			__( "Iraq", 'pff-paystack' ),
			__( "Ireland", 'pff-paystack' ),
			__( "Israel", 'pff-paystack' ),
			__( "Italy", 'pff-paystack' ),
			__( "Jamaica", 'pff-paystack' ),
			__( "Japan", 'pff-paystack' ),
			__( "Jordan", 'pff-paystack' ),
			__( "Kazakhstan", 'pff-paystack' ),
			__( "Kenya", 'pff-paystack' ),
			__( "Kiribati", 'pff-paystack' ),
			__( "Korea, Democratic People's Republic of", 'pff-paystack' ),
			__( "Korea, Republic of", 'pff-paystack' ),
			__( "Kuwait", 'pff-paystack' ),
			__( "Kyrgyzstan", 'pff-paystack' ),
			__( "Lao People's Democratic Republic", 'pff-paystack' ),
			__( "Latvia", 'pff-paystack' ),
			__( "Lebanon", 'pff-paystack' ),
			__( "Lesotho", 'pff-paystack' ),
			__( "Liberia", 'pff-paystack' ),
			__( "Libyan Arab Jamahiriya", 'pff-paystack' ),
			__( "Liechtenstein", 'pff-paystack' ),
			__( "Lithuania", 'pff-paystack' ),
			__( "Luxembourg", 'pff-paystack' ),
			__( "Macao", 'pff-paystack' ),
			__( "Macedonia, The Former Yugoslav Republic of", 'pff-paystack' ),
			__( "Madagascar", 'pff-paystack' ),
			__( "Malawi", 'pff-paystack' ),
			__( "Malaysia", 'pff-paystack' ),
			__( "Maldives", 'pff-paystack' ),
			__( "Mali", 'pff-paystack' ),
			__( "Malta", 'pff-paystack' ),
			__( "Marshall Islands", 'pff-paystack' ),
			__( "Martinique", 'pff-paystack' ),
			__( "Mauritania", 'pff-paystack' ),
			__( "Mauritius", 'pff-paystack' ),
			__( "Mayotte", 'pff-paystack' ),
			__( "Mexico", 'pff-paystack' ),
			__( "Micronesia, Federated States of", 'pff-paystack' ),
			__( "Moldova, Republic of", 'pff-paystack' ),
			__( "Monaco", 'pff-paystack' ),
			__( "Mongolia", 'pff-paystack' ),
			__( "Montserrat", 'pff-paystack' ),
			__( "Morocco", 'pff-paystack' ),
			__( "Mozambique", 'pff-paystack' ),
			__( "Myanmar", 'pff-paystack' ),
			__( "Namibia", 'pff-paystack' ),
			__( "Nauru", 'pff-paystack' ),
			__( "Nepal", 'pff-paystack' ),
			__( "Netherlands", 'pff-paystack' ),
			__( "Netherlands Antilles", 'pff-paystack' ),
			__( "New Caledonia", 'pff-paystack' ),
			__( "New Zealand", 'pff-paystack' ),
			__( "Nicaragua", 'pff-paystack' ),
			__( "Niger", 'pff-paystack' ),
			__( "Nigeria", 'pff-paystack' ),
			__( "Niue", 'pff-paystack' ),
			__( "Norfolk Island", 'pff-paystack' ),
			__( "Northern Mariana Islands", 'pff-paystack' ),
			__( "Norway", 'pff-paystack' ),
			__( "Oman", 'pff-paystack' ),
			__( "Pakistan", 'pff-paystack' ),
			__( "Palau", 'pff-paystack' ),
			__( "Palestinian Territory, Occupied", 'pff-paystack' ),
			__( "Panama", 'pff-paystack' ),
			__( "Papua New Guinea", 'pff-paystack' ),
			__( "Paraguay", 'pff-paystack' ),
			__( "Peru", 'pff-paystack' ),
			__( "Philippines", 'pff-paystack' ),
			__( "Pitcairn", 'pff-paystack' ),
			__( "Poland", 'pff-paystack' ),
			__( "Portugal", 'pff-paystack' ),
			__( "Puerto Rico", 'pff-paystack' ),
			__( "Qatar", 'pff-paystack' ),
			__( "Reunion", 'pff-paystack' ),
			__( "Romania", 'pff-paystack' ),
			__( "Russian Federation", 'pff-paystack' ),
			__( "Rwanda", 'pff-paystack' ),
			__( "Saint Helena", 'pff-paystack' ),
			__( "Saint Kitts and Nevis", 'pff-paystack' ),
			__( "Saint Lucia", 'pff-paystack' ),
			__( "Saint Pierre and Miquelon", 'pff-paystack' ),
			__( "Saint Vincent and The Grenadines", 'pff-paystack' ),
			__( "Samoa", 'pff-paystack' ),
			__( "San Marino", 'pff-paystack' ),
			__( "Sao Tome and Principe", 'pff-paystack' ),
			__( "Saudi Arabia", 'pff-paystack' ),
			__( "Senegal", 'pff-paystack' ),
			__( "Serbia and Montenegro", 'pff-paystack' ),
			__( "Seychelles", 'pff-paystack' ),
			__( "Sierra Leone", 'pff-paystack' ),
			__( "Singapore", 'pff-paystack' ),
			__( "Slovakia", 'pff-paystack' ),
			__( "Slovenia", 'pff-paystack' ),
			__( "Solomon Islands", 'pff-paystack' ),
			__( "Somalia", 'pff-paystack' ),
			__( "South Africa", 'pff-paystack' ),
			__( "South Georgia and The South Sandwich Islands", 'pff-paystack' ),
			__( "Spain", 'pff-paystack' ),
			__( "Sri Lanka", 'pff-paystack' ),
			__( "Sudan", 'pff-paystack' ),
			__( "Suriname", 'pff-paystack' ),
			__( "Svalbard and Jan Mayen", 'pff-paystack' ),
			__( "Swaziland", 'pff-paystack' ),
			__( "Sweden", 'pff-paystack' ),
			__( "Switzerland", 'pff-paystack' ),
			__( "Syrian Arab Republic", 'pff-paystack' ),
			__( "Taiwan, Province of China", 'pff-paystack' ),
			__( "Tajikistan", 'pff-paystack' ),
			__( "Tanzania, United Republic of", 'pff-paystack' ),
			__( "Thailand", 'pff-paystack' ),
			__( "Timor-leste", 'pff-paystack' ),
			__( "Togo", 'pff-paystack' ),
			__( "Tokelau", 'pff-paystack' ),
			__( "Tonga", 'pff-paystack' ),
			__( "Trinidad and Tobago", 'pff-paystack' ),
			__( "Tunisia", 'pff-paystack' ),
			__( "Turkey", 'pff-paystack' ),
			__( "Turkmenistan", 'pff-paystack' ),
			__( "Turks and Caicos Islands", 'pff-paystack' ),
			__( "Tuvalu", 'pff-paystack' ),
			__( "Uganda", 'pff-paystack' ),
			__( "Ukraine", 'pff-paystack' ),
			__( "United Arab Emirates", 'pff-paystack' ),
			__( "United Kingdom", 'pff-paystack' ),
			__( "United States", 'pff-paystack' ),
			__( "United States Minor Outlying Islands", 'pff-paystack' ),
			__( "Uruguay", 'pff-paystack' ),
			__( "Uzbekistan", 'pff-paystack' ),
			__( "Vanuatu", 'pff-paystack' ),
			__( "Venezuela", 'pff-paystack' ),
			__( "Viet Nam", 'pff-paystack' ),
			__( "Virgin Islands; British", 'pff-paystack' ),
			__( "Virgin Islands; U.S.", 'pff-paystack' ),
			__( "Wallis and Futuna", 'pff-paystack' ),
			__( "Western Sahara", 'pff-paystack' ),
			__( "Yemen", 'pff-paystack' ),
			__( "Zambia", 'pff-paystack' ),
			__( "Zimbabwe", 'pff-paystack' ),
		];	
		if ( $implode ) {
			$countries = implode( ',', $countries );
		}
		return $countries;
	}

	/**
	 * Returns the states available.
	 *
	 * @param boolean $implode
	 * @return array|string
	 */
	public function get_states( $implode = false ) {
		$states = [
			__( 'Abia', 'pff-paystack' ),
			__( 'Adamawa', 'pff-paystack' ),
			__( 'Akwa Ibom', 'pff-paystack' ),
			__( 'Anambra', 'pff-paystack' ),
			__( 'Bauchi', 'pff-paystack' ),
			__( 'Bayelsa', 'pff-paystack' ),
			__( 'Benue', 'pff-paystack' ),
			__( 'Borno', 'pff-paystack' ),
			__( 'Cross River', 'pff-paystack' ),
			__( 'Delta', 'pff-paystack' ),
			__( 'Ebonyi', 'pff-paystack' ),
			__( 'Edo', 'pff-paystack' ),
			__( 'Ekiti', 'pff-paystack' ),
			__( 'Enugu', 'pff-paystack' ),
			__( 'FCT', 'pff-paystack' ),
			__( 'Gombe', 'pff-paystack' ),
			__( 'Imo', 'pff-paystack' ),
			__( 'Jigawa', 'pff-paystack' ),
			__( 'Kaduna', 'pff-paystack' ),
			__( 'Kano', 'pff-paystack' ),
			__( 'Katsina', 'pff-paystack' ),
			__( 'Kebbi', 'pff-paystack' ),
			__( 'Kogi', 'pff-paystack' ),
			__( 'Kwara', 'pff-paystack' ),
			__( 'Lagos', 'pff-paystack' ),
			__( 'Nasarawa', 'pff-paystack' ),
			__( 'Niger', 'pff-paystack' ),
			__( 'Ogun', 'pff-paystack' ),
			__( 'Ondo', 'pff-paystack' ),
			__( 'Osun', 'pff-paystack' ),
			__( 'Oyo', 'pff-paystack' ),
			__( 'Plateau', 'pff-paystack' ),
			__( 'Rivers', 'pff-paystack' ),
			__( 'Sokoto', 'pff-paystack' ),
			__( 'Taraba', 'pff-paystack' ),
			__( 'Yobe', 'pff-paystack' ),
			__( 'Zamfara', 'pff-paystack' ),
		];
		if ( $implode ) {
			$states = implode( ',', $states );
		}
		return $states;
	}

	/**
	 * Returns the meta fields and their default values.
	 *
	 * @return array
	 */
	public function get_meta_defaults() {
		return $this->defaults;
	}

	/**
	 * Returns the allowed HTML for wp_kses()
	 *
	 * @return array
	 */
	public function get_allowed_html() {
		return $this->allowed_html;
	}

	/**
	 * Retrieve the user's IP address.
	 *
	 * @return string User's IP address.
	 */
	public function get_the_user_ip() {
		$ip = '';

		if ( ! empty( $_SERVER['HTTP_CLIENT_IP'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_CLIENT_IP'] ) );
		} elseif ( ! empty( $_SERVER['HTTP_X_FORWARDED_FOR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['HTTP_X_FORWARDED_FOR'] ) );
		} elseif ( ! empty( $_SERVER['REMOTE_ADDR'] ) ) {
			$ip = sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) );
		}

		return $ip;
	}

	
	/**
	 * Get the DB records by the transaction code supplied.
	 *
	 * @param string $code
	 * @return object
	 */
	public function get_db_record( $code, $column = 'txn_code' ) {
		global $wpdb;
		$return = false;
		$table  = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$record = $wpdb->get_results(
			$wpdb->prepare(
					"SELECT * 
					FROM %i 
					WHERE %i = %s"
				,
				$table,
				$column,
				$code
			), 'OBJECT' );

		if ( ! empty( $record ) && isset( $record[0] ) ) {
			$return = $record[0];
		}
		return $return;
	}

	// FUNCTIONS

	/**
	 * Gets the current forms meta fields values and set the defaults if needed.
	 *
	 * @param WP_Post $post
	 * @return array
	 */
	public function parse_meta_values( $post ) {
		$new_values = [];
		foreach ( $this->defaults as $key => $default ) {
			$value = get_post_meta( $post->ID, '_' . $key, true );
			if ( false !== $value && ! empty( $value ) ) {
				$new_values[ $key ] = $value;
			}
		}

		$meta = wp_parse_args( $new_values, $this->defaults );
		if ( '' === $meta['inventory'] || '0' === $meta['inventory'] ) {
			if ( '' !== $meta['sold'] ) {
				$meta['inventory'] = $meta['sold'];
			} else {
				$meta['inventory'] = '1';
			}
		}

		// Strip any text from the variable amount field.
		if ( isset( $meta['usevariableamount'] ) && is_string( $meta['usevariableamount'] ) ) {
			$meta['usevariableamount'] = (int) $meta['usevariableamount'];
		}

		$meta['minimum']   = (int) $meta['minimum'];
		//$meta['txncharge'] = floatval( $meta['txncharge'] );
		return $meta;
	}

	/**
	 * Take an array of the submitted form values and formats it for a paystack request.
	 *
	 * @param array $metadata
	 * @return void
	 */
	public function format_meta_as_custom_fields( $metadata ) {
		$fields = array();

		foreach ( $metadata as $key => $value ) {
			if ( is_array( $value ) ) {
				$value = implode( ', ', $value );
			}

			switch ( $key ) {
				case 'pf-fname':
					$fields[] = array(
						'display_name'  => __( 'Full Name', 'pff-paystack' ),
						'variable_name' => 'Full_Name',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-plancode':
					$fields[] = array(
						'display_name'  => __( 'Plan', 'pff-paystack' ),
						'variable_name' => 'Plan',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-vname':
					$fields[] = array(
						'display_name'  => __( 'Payment Option', 'pff-paystack' ),
						'variable_name' => 'Payment Option',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-interval':
					$fields[] = array(
						'display_name'  => __( 'Plan Interval', 'pff-paystack' ),
						'variable_name' => 'Plan Interval',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				case 'pf-quantity':
					$fields[] = array(
						'display_name'  => __( 'Quantity', 'pff-paystack' ),
						'variable_name' => 'Quantity',
						'type'          => 'text',
						'value'         => $value,
					);
					break;

				default:
					$display_name = ucwords( str_replace( array( '_', '-', 'pf' ), ' ', $key ) );
					$fields[] = array(
						'display_name'  => $display_name,
						'variable_name' => $key,
						'type'          => 'text',
						'value'         => (string) $value,
					);
					break;
			}
		}
		return $fields;
	}

	/**
	 * Formats the metadata for output on the retry form page.
	 *
	 * @param string $data
	 * @return string
	 */
	public function format_meta_as_display_fields( $data ) {
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

	/**
	 * Generate a new Paystack code.
	 *
	 * @param int $length Length of the code to generate. Default 10.
	 * @return string Generated code.
	 */
	public function generate_new_code( $length = 10 ) {
		$characters        = '06EFGHI9KL' . time() . 'MNOPJRSUVW01YZ923234' . time() . 'ABCD5678QXT';
		$characters_length = strlen( $characters );
		$random_string     = '';

		for ( $i = 0; $i < $length; $i++ ) {
			$random_string .= $characters[ wp_rand( 0, $characters_length - 1 ) ];
		}

		return time() . '_' . $random_string;
	}

	/**
	 * Check if the given code exists in the database.
	 *
	 * @param string $code The code to check.
	 * @global wpdb $wpdb WordPress database abstraction object.
	 * @return bool True if the code exists, false otherwise.
	 */
	public function check_code( $code ) {
		global $wpdb;
		$table = $wpdb->prefix . PFF_PAYSTACK_TABLE;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery
		$o_exist = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM %i WHERE txn_code = %s",
				$table,
				$code
			)
		);
		return ( count( $o_exist ) > 0 );
	}


	/**
	 * Takes the amount and processes the "transactional" fees.
	 *
	 * @param integer $amount
	 * @return integer
	 */
	public function process_transaction_fees( $amount ) {
		$fees = $this->get_fees();
		$pc   = new Transaction_Fee(
			$fees['prc'],
			$fees['adc'],
			$fees['ths'],
			$fees['cap']
		);
		return $pc->add_for_ngn( $amount );
	}
}