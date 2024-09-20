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
			'paybtn'              => __( 'Pay', 'paystack_forms' ),
			'successmsg'          => __( 'Thank you for paying!', 'paystack_forms' ),
			'txncharge'           => 'merchant',
			'loggedin'            => '',
			'currency'            => 'NGN',
			'filelimit'           => 2,
			'redirect'            => '',
			'minimum'             => 0,
			'usevariableamount'   => 0,
			'variableamount'      => '',
			'hidetitle'           => 0,
			'loggedin'            => 'no',
			'recur'               => 'no',
			'recurplan'           => '',
			'subject'             => __( 'Thank you for your payment', 'paystack_forms' ),
			'merchant'            => '',
			'heading'             => __( 'We\'ve received your payment', 'paystack_forms' ),
			'message'             => __( 'Your payment was received and we appreciate it.', 'paystack_forms' ),
			'sendreceipt'         => 'yes',
			'sendinvoice'         => 'yes',
			'usequantity'         => 'no',
			'useinventory'        => 'no',
			'inventory'           => '0',
			'sold'                => '0',
			'quantity'            => '10',
			'quantityunit'        => __( 'Quantity', 'paystack_forms' ),
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
	 * Fetch an array of the plans by the form ID.
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
        $table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
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
		$table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
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
			__( "Afghanistan", 'paystack_forms' ),
			__( "Albania", 'paystack_forms' ),
			__( "Algeria", 'paystack_forms' ),
			__( "American Samoa", 'paystack_forms' ),
			__( "Andorra", 'paystack_forms' ),
			__( "Angola", 'paystack_forms' ),
			__( "Anguilla", 'paystack_forms' ),
			__( "Antarctica", 'paystack_forms' ),
			__( "Antigua and Barbuda", 'paystack_forms' ),
			__( "Argentina", 'paystack_forms' ),
			__( "Armenia", 'paystack_forms' ),
			__( "Aruba", 'paystack_forms' ),
			__( "Australia", 'paystack_forms' ),
			__( "Austria", 'paystack_forms' ),
			__( "Azerbaijan", 'paystack_forms' ),
			__( "Bahamas", 'paystack_forms' ),
			__( "Bahrain", 'paystack_forms' ),
			__( "Bangladesh", 'paystack_forms' ),
			__( "Barbados", 'paystack_forms' ),
			__( "Belarus", 'paystack_forms' ),
			__( "Belgium", 'paystack_forms' ),
			__( "Belize", 'paystack_forms' ),
			__( "Benin", 'paystack_forms' ),
			__( "Bermuda", 'paystack_forms' ),
			__( "Bhutan", 'paystack_forms' ),
			__( "Bolivia", 'paystack_forms' ),
			__( "Bosnia and Herzegovina", 'paystack_forms' ),
			__( "Botswana", 'paystack_forms' ),
			__( "Bouvet Island", 'paystack_forms' ),
			__( "Brazil", 'paystack_forms' ),
			__( "British Indian Ocean Territory", 'paystack_forms' ),
			__( "Brunei Darussalam", 'paystack_forms' ),
			__( "Bulgaria", 'paystack_forms' ),
			__( "Burkina Faso", 'paystack_forms' ),
			__( "Burundi", 'paystack_forms' ),
			__( "Cambodia", 'paystack_forms' ),
			__( "Cameroon", 'paystack_forms' ),
			__( "Canada", 'paystack_forms' ),
			__( "Cape Verde", 'paystack_forms' ),
			__( "Cayman Islands", 'paystack_forms' ),
			__( "Central African Republic", 'paystack_forms' ),
			__( "Chad", 'paystack_forms' ),
			__( "Chile", 'paystack_forms' ),
			__( "China", 'paystack_forms' ),
			__( "Christmas Island", 'paystack_forms' ),
			__( "Cocos (Keeling) Islands", 'paystack_forms' ),
			__( "Colombia", 'paystack_forms' ),
			__( "Comoros", 'paystack_forms' ),
			__( "Congo", 'paystack_forms' ),
			__( "Congo, The Democratic Republic of The", 'paystack_forms' ),
			__( "Cook Islands", 'paystack_forms' ),
			__( "Costa Rica", 'paystack_forms' ),
			__( "Cote D'ivoire", 'paystack_forms' ),
			__( "Croatia", 'paystack_forms' ),
			__( "Cuba", 'paystack_forms' ),
			__( "Cyprus", 'paystack_forms' ),
			__( "Czech Republic", 'paystack_forms' ),
			__( "Denmark", 'paystack_forms' ),
			__( "Djibouti", 'paystack_forms' ),
			__( "Dominica", 'paystack_forms' ),
			__( "Dominican Republic", 'paystack_forms' ),
			__( "Ecuador", 'paystack_forms' ),
			__( "Egypt", 'paystack_forms' ),
			__( "El Salvador", 'paystack_forms' ),
			__( "Equatorial Guinea", 'paystack_forms' ),
			__( "Eritrea", 'paystack_forms' ),
			__( "Estonia", 'paystack_forms' ),
			__( "Ethiopia", 'paystack_forms' ),
			__( "Falkland Islands (Malvinas)", 'paystack_forms' ),
			__( "Faroe Islands", 'paystack_forms' ),
			__( "Fiji", 'paystack_forms' ),
			__( "Finland", 'paystack_forms' ),
			__( "France", 'paystack_forms' ),
			__( "French Guiana", 'paystack_forms' ),
			__( "French Polynesia", 'paystack_forms' ),
			__( "French Southern Territories", 'paystack_forms' ),
			__( "Gabon", 'paystack_forms' ),
			__( "Gambia", 'paystack_forms' ),
			__( "Georgia", 'paystack_forms' ),
			__( "Germany", 'paystack_forms' ),
			__( "Ghana", 'paystack_forms' ),
			__( "Gibraltar", 'paystack_forms' ),
			__( "Greece", 'paystack_forms' ),
			__( "Greenland", 'paystack_forms' ),
			__( "Grenada", 'paystack_forms' ),
			__( "Guadeloupe", 'paystack_forms' ),
			__( "Guam", 'paystack_forms' ),
			__( "Guatemala", 'paystack_forms' ),
			__( "Guinea", 'paystack_forms' ),
			__( "Guinea-bissau", 'paystack_forms' ),
			__( "Guyana", 'paystack_forms' ),
			__( "Haiti", 'paystack_forms' ),
			__( "Heard Island and Mcdonald Islands", 'paystack_forms' ),
			__( "Holy See (Vatican City State)", 'paystack_forms' ),
			__( "Honduras", 'paystack_forms' ),
			__( "Hong Kong", 'paystack_forms' ),
			__( "Hungary", 'paystack_forms' ),
			__( "Iceland", 'paystack_forms' ),
			__( "India", 'paystack_forms' ),
			__( "Indonesia", 'paystack_forms' ),
			__( "Iran, Islamic Republic of", 'paystack_forms' ),
			__( "Iraq", 'paystack_forms' ),
			__( "Ireland", 'paystack_forms' ),
			__( "Israel", 'paystack_forms' ),
			__( "Italy", 'paystack_forms' ),
			__( "Jamaica", 'paystack_forms' ),
			__( "Japan", 'paystack_forms' ),
			__( "Jordan", 'paystack_forms' ),
			__( "Kazakhstan", 'paystack_forms' ),
			__( "Kenya", 'paystack_forms' ),
			__( "Kiribati", 'paystack_forms' ),
			__( "Korea, Democratic People's Republic of", 'paystack_forms' ),
			__( "Korea, Republic of", 'paystack_forms' ),
			__( "Kuwait", 'paystack_forms' ),
			__( "Kyrgyzstan", 'paystack_forms' ),
			__( "Lao People's Democratic Republic", 'paystack_forms' ),
			__( "Latvia", 'paystack_forms' ),
			__( "Lebanon", 'paystack_forms' ),
			__( "Lesotho", 'paystack_forms' ),
			__( "Liberia", 'paystack_forms' ),
			__( "Libyan Arab Jamahiriya", 'paystack_forms' ),
			__( "Liechtenstein", 'paystack_forms' ),
			__( "Lithuania", 'paystack_forms' ),
			__( "Luxembourg", 'paystack_forms' ),
			__( "Macao", 'paystack_forms' ),
			__( "Macedonia, The Former Yugoslav Republic of", 'paystack_forms' ),
			__( "Madagascar", 'paystack_forms' ),
			__( "Malawi", 'paystack_forms' ),
			__( "Malaysia", 'paystack_forms' ),
			__( "Maldives", 'paystack_forms' ),
			__( "Mali", 'paystack_forms' ),
			__( "Malta", 'paystack_forms' ),
			__( "Marshall Islands", 'paystack_forms' ),
			__( "Martinique", 'paystack_forms' ),
			__( "Mauritania", 'paystack_forms' ),
			__( "Mauritius", 'paystack_forms' ),
			__( "Mayotte", 'paystack_forms' ),
			__( "Mexico", 'paystack_forms' ),
			__( "Micronesia, Federated States of", 'paystack_forms' ),
			__( "Moldova, Republic of", 'paystack_forms' ),
			__( "Monaco", 'paystack_forms' ),
			__( "Mongolia", 'paystack_forms' ),
			__( "Montserrat", 'paystack_forms' ),
			__( "Morocco", 'paystack_forms' ),
			__( "Mozambique", 'paystack_forms' ),
			__( "Myanmar", 'paystack_forms' ),
			__( "Namibia", 'paystack_forms' ),
			__( "Nauru", 'paystack_forms' ),
			__( "Nepal", 'paystack_forms' ),
			__( "Netherlands", 'paystack_forms' ),
			__( "Netherlands Antilles", 'paystack_forms' ),
			__( "New Caledonia", 'paystack_forms' ),
			__( "New Zealand", 'paystack_forms' ),
			__( "Nicaragua", 'paystack_forms' ),
			__( "Niger", 'paystack_forms' ),
			__( "Nigeria", 'paystack_forms' ),
			__( "Niue", 'paystack_forms' ),
			__( "Norfolk Island", 'paystack_forms' ),
			__( "Northern Mariana Islands", 'paystack_forms' ),
			__( "Norway", 'paystack_forms' ),
			__( "Oman", 'paystack_forms' ),
			__( "Pakistan", 'paystack_forms' ),
			__( "Palau", 'paystack_forms' ),
			__( "Palestinian Territory, Occupied", 'paystack_forms' ),
			__( "Panama", 'paystack_forms' ),
			__( "Papua New Guinea", 'paystack_forms' ),
			__( "Paraguay", 'paystack_forms' ),
			__( "Peru", 'paystack_forms' ),
			__( "Philippines", 'paystack_forms' ),
			__( "Pitcairn", 'paystack_forms' ),
			__( "Poland", 'paystack_forms' ),
			__( "Portugal", 'paystack_forms' ),
			__( "Puerto Rico", 'paystack_forms' ),
			__( "Qatar", 'paystack_forms' ),
			__( "Reunion", 'paystack_forms' ),
			__( "Romania", 'paystack_forms' ),
			__( "Russian Federation", 'paystack_forms' ),
			__( "Rwanda", 'paystack_forms' ),
			__( "Saint Helena", 'paystack_forms' ),
			__( "Saint Kitts and Nevis", 'paystack_forms' ),
			__( "Saint Lucia", 'paystack_forms' ),
			__( "Saint Pierre and Miquelon", 'paystack_forms' ),
			__( "Saint Vincent and The Grenadines", 'paystack_forms' ),
			__( "Samoa", 'paystack_forms' ),
			__( "San Marino", 'paystack_forms' ),
			__( "Sao Tome and Principe", 'paystack_forms' ),
			__( "Saudi Arabia", 'paystack_forms' ),
			__( "Senegal", 'paystack_forms' ),
			__( "Serbia and Montenegro", 'paystack_forms' ),
			__( "Seychelles", 'paystack_forms' ),
			__( "Sierra Leone", 'paystack_forms' ),
			__( "Singapore", 'paystack_forms' ),
			__( "Slovakia", 'paystack_forms' ),
			__( "Slovenia", 'paystack_forms' ),
			__( "Solomon Islands", 'paystack_forms' ),
			__( "Somalia", 'paystack_forms' ),
			__( "South Africa", 'paystack_forms' ),
			__( "South Georgia and The South Sandwich Islands", 'paystack_forms' ),
			__( "Spain", 'paystack_forms' ),
			__( "Sri Lanka", 'paystack_forms' ),
			__( "Sudan", 'paystack_forms' ),
			__( "Suriname", 'paystack_forms' ),
			__( "Svalbard and Jan Mayen", 'paystack_forms' ),
			__( "Swaziland", 'paystack_forms' ),
			__( "Sweden", 'paystack_forms' ),
			__( "Switzerland", 'paystack_forms' ),
			__( "Syrian Arab Republic", 'paystack_forms' ),
			__( "Taiwan, Province of China", 'paystack_forms' ),
			__( "Tajikistan", 'paystack_forms' ),
			__( "Tanzania, United Republic of", 'paystack_forms' ),
			__( "Thailand", 'paystack_forms' ),
			__( "Timor-leste", 'paystack_forms' ),
			__( "Togo", 'paystack_forms' ),
			__( "Tokelau", 'paystack_forms' ),
			__( "Tonga", 'paystack_forms' ),
			__( "Trinidad and Tobago", 'paystack_forms' ),
			__( "Tunisia", 'paystack_forms' ),
			__( "Turkey", 'paystack_forms' ),
			__( "Turkmenistan", 'paystack_forms' ),
			__( "Turks and Caicos Islands", 'paystack_forms' ),
			__( "Tuvalu", 'paystack_forms' ),
			__( "Uganda", 'paystack_forms' ),
			__( "Ukraine", 'paystack_forms' ),
			__( "United Arab Emirates", 'paystack_forms' ),
			__( "United Kingdom", 'paystack_forms' ),
			__( "United States", 'paystack_forms' ),
			__( "United States Minor Outlying Islands", 'paystack_forms' ),
			__( "Uruguay", 'paystack_forms' ),
			__( "Uzbekistan", 'paystack_forms' ),
			__( "Vanuatu", 'paystack_forms' ),
			__( "Venezuela", 'paystack_forms' ),
			__( "Viet Nam", 'paystack_forms' ),
			__( "Virgin Islands; British", 'paystack_forms' ),
			__( "Virgin Islands; U.S.", 'paystack_forms' ),
			__( "Wallis and Futuna", 'paystack_forms' ),
			__( "Western Sahara", 'paystack_forms' ),
			__( "Yemen", 'paystack_forms' ),
			__( "Zambia", 'paystack_forms' ),
			__( "Zimbabwe", 'paystack_forms' ),
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
			__( 'Abia', 'paystack_forms' ),
			__( 'Adamawa', 'paystack_forms' ),
			__( 'Akwa Ibom', 'paystack_forms' ),
			__( 'Anambra', 'paystack_forms' ),
			__( 'Bauchi', 'paystack_forms' ),
			__( 'Bayelsa', 'paystack_forms' ),
			__( 'Benue', 'paystack_forms' ),
			__( 'Borno', 'paystack_forms' ),
			__( 'Cross River', 'paystack_forms' ),
			__( 'Delta', 'paystack_forms' ),
			__( 'Ebonyi', 'paystack_forms' ),
			__( 'Edo', 'paystack_forms' ),
			__( 'Ekiti', 'paystack_forms' ),
			__( 'Enugu', 'paystack_forms' ),
			__( 'FCT', 'paystack_forms' ),
			__( 'Gombe', 'paystack_forms' ),
			__( 'Imo', 'paystack_forms' ),
			__( 'Jigawa', 'paystack_forms' ),
			__( 'Kaduna', 'paystack_forms' ),
			__( 'Kano', 'paystack_forms' ),
			__( 'Katsina', 'paystack_forms' ),
			__( 'Kebbi', 'paystack_forms' ),
			__( 'Kogi', 'paystack_forms' ),
			__( 'Kwara', 'paystack_forms' ),
			__( 'Lagos', 'paystack_forms' ),
			__( 'Nasarawa', 'paystack_forms' ),
			__( 'Niger', 'paystack_forms' ),
			__( 'Ogun', 'paystack_forms' ),
			__( 'Ondo', 'paystack_forms' ),
			__( 'Osun', 'paystack_forms' ),
			__( 'Oyo', 'paystack_forms' ),
			__( 'Plateau', 'paystack_forms' ),
			__( 'Rivers', 'paystack_forms' ),
			__( 'Sokoto', 'paystack_forms' ),
			__( 'Taraba', 'paystack_forms' ),
			__( 'Yobe', 'paystack_forms' ),
			__( 'Zamfara', 'paystack_forms' ),
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
			if ( $meta['sold'] !== "" ) {
				$meta['inventory'] = $meta;
			} else {
				$meta['inventory'] = '1';
			}
		}

		// Strip any text from the variable amount field.
		if ( isset( $meta['variableamount'] ) && is_string( $meta['variableamount'] ) ) {
			$meta['variableamount'] = (int) $meta['variableamount'];
		}

		$meta['minimum']   = floatval( $meta['minimum'] );
		$meta['txncharge'] = floatval( $meta['txncharge'] );

		return $meta;
	}
}