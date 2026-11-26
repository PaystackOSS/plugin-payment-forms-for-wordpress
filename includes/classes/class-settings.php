<?php
/**
 * The main plugin class, this will return the and instance of the class.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Plugin Settings class.
 */
class Settings {

	const OPTION_GROUP = 'kkd-pff-paystack-settings-group';

	const UNCONFIRMED_OPTION = 'pff_paystack_rk_unconfirmed';

	const DASHBOARD_URL = 'https://dashboard.paystack.com/v2/developers/api-configuration';

	/**
	 * Restricted key, secret key, public key and disconnect field names for each mode.
	 *
	 * @internal
	 */
	const SECRET_PAIRS = array(
		'test' => array(
			'rk'         => 'trk',
			'sk'         => 'tsk',
			'pk'         => 'tpk',
			'disconnect' => 'trk_disconnect',
		),
		'live' => array(
			'rk'         => 'lrk',
			'sk'         => 'lsk',
			'pk'         => 'lpk',
			'disconnect' => 'lrk_disconnect',
		),
	);

	/**
	 * Holdes the array of settings fields.
	 *
	 * @var array
	 */
	private $fields = array();

	/**
	 * Per-mode save decisions made once per request, keyed by mode.
	 *
	 * @var array
	 */
	private $decisions = array();

	/**
	 * Verifies a restricted key; function( $key, $mode, $deadline ) returning a Restricted_Key verdict.
	 *
	 * @var callable|null
	 */
	private $validator = null;

	/**
	 * Construct the class.
	 */
	public function __construct() {
		$this->fields = array(
			'general' => array(
				'mode' => array(
					'title'   => esc_html__( 'Mode', 'pff-paystack' ),
					'type'    => 'select',
					'default' => 'test',
				),
				'trk' => array(
					'title'   => esc_html__( 'Test Restricted Key', 'pff-paystack' ),
					'type'    => 'password',
					'prefix'  => 'rk_test_',
					'default' => '',
				),
				'lrk' => array(
					'title'   => esc_html__( 'Live Restricted Key', 'pff-paystack' ),
					'type'    => 'password',
					'prefix'  => 'rk_live_',
					'default' => '',
				),
				'tsk' => array(
					'title'   => esc_html__( 'Test Secret Key', 'pff-paystack' ),
					'type'    => 'password',
					'prefix'  => 'sk_test_',
					'default' => '',
				),
				'tpk' => array(
					'title'   => esc_html__( 'Test Public Key', 'pff-paystack' ),
					'type'    => 'text',
					'prefix'  => 'pk_test_',
					'default' => '',
				),
				'lsk' => array(
					'title'   => esc_html__( 'Live Secret Key', 'pff-paystack' ),
					'type'    => 'password',
					'prefix'  => 'sk_live_',
					'default' => '',
				),
				'lpk' => array(
					'title'   => esc_html__( 'Live Public Key', 'pff-paystack' ),
					'type'    => 'text',
					'prefix'  => 'pk_live_',
					'default' => '',
				),
			),
			'fees' => array(
				'prc' => array(	
					'title'   => esc_html__( 'Percentage', 'pff-paystack' ),
					'type'    => 'text',
					'default' => 1.5,
				),
				'ths' => array(	
					'title'   => wp_kses_post( __( 'Threshold <br> <small>(amount above which Paystack adds the fixed amount below)</small>', 'pff-paystack' ) ),
					'type'    => 'text',
					'default' => 2500,
				),
				'adc' => array(	
					'title'   => wp_kses_post( __( 'Additional Charge <br> <small> (amount added to percentage fee when transaction amount is above threshold) </small>', 'pff-paystack' ) ),
					'type'    => 'text',
					'default' => 100,
				),
				'cap' => array(	
					'title'   => wp_kses_post( __( 'Cap <br> <small> (maximum charge paystack can charge on your transactions)', 'pff-paystack' ) ),
					'type'    => 'text',
					'default' => 2000,
				),
			),
		);
		add_action( 'admin_menu', [ $this, 'register_settings_page' ] );
		add_action( 'admin_menu', [ $this, 'register_settings_fields' ] );
		add_action( 'admin_init', [ $this, 'register_secret_key_guards' ] );
	}

	/**
	 * Returns the capability required to view and save the settings.
	 *
	 * @return string
	 */
	public static function get_capability() {
		return apply_filters( 'option_page_capability_' . self::OPTION_GROUP, 'manage_options' );
	}

	/**
	 * Overrides the restricted key validator.
	 *
	 * @param callable $validator function( $key, $mode, $deadline ) returning a verdict array.
	 * @return void
	 */
	public function set_validator( $validator ) {
		$this->validator = $validator;
	}

	/**
	 * Decides each mode's key save once, then attaches the filters that apply it.
	 *
	 * @return void
	 */
	public function register_secret_key_guards() {
		// Same derivation as core options.php, so the guard matches whatever core will save.
		// phpcs:disable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- the option_page check only gates the nonce check below.
		$option_page = '';
		if ( ! empty( $_REQUEST['option_page'] ) && is_string( $_REQUEST['option_page'] ) ) {
			$option_page = sanitize_text_field( $_REQUEST['option_page'] );
		}
		// phpcs:enable WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput
		if ( self::OPTION_GROUP !== $option_page ) {
			// Core's All Settings page saves every option, so it must not reach the keys.
			if ( isset( $GLOBALS['pagenow'] ) && 'options.php' === $GLOBALS['pagenow'] ) {
				foreach ( self::SECRET_PAIRS as $pair ) {
					foreach ( array( $pair['rk'], $pair['sk'], $pair['pk'] ) as $name ) {
						add_filter( "pre_update_option_{$name}", [ $this, 'keep_stored_key' ], 10, 2 );
					}
				}
			}
			return;
		}

		// Attached before the auth gate so an undecided save keeps the old keys instead of storing them unverified.
		foreach ( self::SECRET_PAIRS as $pair ) {
			add_filter( "pre_update_option_{$pair['rk']}", [ $this, 'filter_restricted_key' ], 10, 3 );
			add_filter( "pre_update_option_{$pair['sk']}", [ $this, 'filter_mode_key' ], 10, 3 );
			add_filter( "pre_update_option_{$pair['pk']}", [ $this, 'filter_mode_key' ], 10, 3 );
		}

		foreach ( $this->get_settings_fields() as $fields ) {
			foreach ( $fields as $key => $args ) {
				if ( null === $this->locate_key_option( $key ) && ! empty( $args['type'] ) && 'password' === $args['type'] ) {
					add_filter( "pre_update_option_{$key}", [ $this, 'filter_secret_key' ], 10, 3 );
				}
			}
		}

		// Probing is a network write, so only a verified admin save may trigger it.
		$on_options_page = isset( $GLOBALS['pagenow'] ) && 'options.php' === $GLOBALS['pagenow'];
		if ( ! $on_options_page || ! current_user_can( self::get_capability() ) ) {
			return;
		}
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- this is the nonce read.
		$nonce = isset( $_REQUEST['_wpnonce'] ) && is_string( $_REQUEST['_wpnonce'] ) ? sanitize_text_field( wp_unslash( $_REQUEST['_wpnonce'] ) ) : '';
		if ( '' === $nonce || ! wp_verify_nonce( $nonce, self::OPTION_GROUP . '-options' ) ) {
			return;
		}

		// Only keys that will be probed take a slice of the budget.
		$submitted = 0;
		foreach ( self::SECRET_PAIRS as $mode => $pair ) {
			if ( Restricted_Key::is_valid_shape( $this->read_post_field( $pair['rk'] ), $mode ) ) {
				$submitted++;
			}
		}
		// One save shares one budget: probes run in sequence and each mode ends at its own slice boundary.
		$start = microtime( true );
		$slice = $submitted > 0 ? Restricted_Key::BUDGET_SECONDS / $submitted : Restricted_Key::BUDGET_SECONDS;
		$taken = 0;

		$this->decisions = array();
		foreach ( self::SECRET_PAIRS as $mode => $pair ) {
			$rk_in = $this->read_post_field( $pair['rk'] );
			if ( Restricted_Key::is_valid_shape( $rk_in, $mode ) ) {
				$taken++;
			}
			$this->decisions[ $mode ] = $this->decide_mode(
				$mode,
				$rk_in,
				$this->read_post_field( $pair['sk'] ),
				$this->read_post_field( $pair['pk'] ),
				'1' === $this->read_post_field( $pair['disconnect'] ),
				$start + $slice * $taken
			);
		}

		foreach ( $this->decisions as $decision ) {
			foreach ( $decision['messages'] as $message ) {
				add_settings_error( self::OPTION_GROUP, $message[0], $message[1], isset( $message[2] ) ? $message[2] : 'error' );
			}
		}
	}

	/**
	 * Reads a submitted field the way update_option will receive it.
	 *
	 * @param string $name Field name.
	 * @return string '' when absent or not a string.
	 */
	private function read_post_field( $name ) {
		// phpcs:ignore WordPress.Security.NonceVerification -- the nonce is verified by the caller.
		if ( ! isset( $_POST[ $name ] ) || ! is_string( $_POST[ $name ] ) ) {
			return '';
		}
		// phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput -- sanitised by sanitise_field.
		return $this->sanitise_field( wp_unslash( $_POST[ $name ] ) );
	}

	/**
	 * Works out what to do with one mode's key submissions.
	 *
	 * 'rk' is none|accept|reject|disconnect. 'sk_value' and 'pk_value' are the values to store, or null to keep the stored one;
	 * 'messages' are array( code, text[, type] ).
	 *
	 * @param string     $mode       'test' or 'live'.
	 * @param string     $rk_in      Submitted restricted key.
	 * @param string     $sk_in      Submitted secret key.
	 * @param string     $pk_in      Submitted public key.
	 * @param bool       $disconnect Whether the disconnect box was ticked.
	 * @param float|null $deadline   Absolute time by which the restricted key check must finish.
	 * @return array
	 */
	private function decide_mode( $mode, $rk_in, $sk_in, $pk_in, $disconnect, $deadline = null ) {
		$pair      = self::SECRET_PAIRS[ $mode ];
		$decision  = array(
			'rk'          => 'none',
			'rk_value'    => '',
			'unconfirmed' => false,
			'clear_sk'    => false,
			'sk_value'    => null,
			'pk_value'    => null,
			'messages'    => array(),
		);
		$stored_pk = (string) get_option( $pair['pk'], '' );
		// The public key field is pre-filled, so an unchanged value is not a submission.
		$pk_changed = '' !== $pk_in && $pk_in !== $stored_pk;

		if ( '' !== $rk_in ) {
			$check = $this->check_restricted_key( $rk_in, $mode, $deadline );
			if ( '' === $check['message'] ) {
				$decision['rk']       = 'accept';
				$decision['rk_value'] = $rk_in;
				$decision['pk_value'] = $check['pk'];
				$decision['unconfirmed'] = $check['unconfirmed'];
			} else {
				$decision['rk']         = 'reject';
				$decision['messages'][] = array( "pff-paystack-rk-{$pair['rk']}", $check['message'] );
			}
			if ( '' !== $sk_in || $pk_changed ) {
				$decision['messages'][] = array(
					"pff-paystack-keys-ignored-{$pair['rk']}",
					esc_html__( 'The secret and public keys you entered were not saved because a restricted key was submitted for this mode.', 'pff-paystack' ),
					'warning',
				);
			}
			if ( $disconnect ) {
				$decision['messages'][] = array(
					"pff-paystack-disconnect-ignored-{$pair['rk']}",
					esc_html__( 'Disconnect was ignored because a restricted key was submitted for this mode.', 'pff-paystack' ),
					'warning',
				);
			}
		} elseif ( $disconnect ) {
			$decision['rk'] = 'disconnect';
			if ( $this->active_mode() === $mode ) {
				$decision['messages'][] = array(
					"pff-paystack-disconnect-active-{$pair['rk']}",
					sprintf(
						/* translators: %s: Test or Live */
						esc_html__( '%s mode now has no keys; payments will fail until you save a restricted key.', 'pff-paystack' ),
						'test' === $mode ? esc_html__( 'Test', 'pff-paystack' ) : esc_html__( 'Live', 'pff-paystack' )
					),
					'warning',
				);
			}
		} elseif ( '' !== (string) get_option( $pair['rk'], '' ) ) {
			// A secret key left beside a restricted key is never used, so a save removes it.
			$decision['clear_sk'] = '' !== (string) get_option( $pair['sk'], '' );
		} elseif ( '' !== (string) get_option( $pair['sk'], '' ) && '' === (string) get_option( $pair['rk'], '' ) ) {
			$sk_error = '' !== $sk_in ? $this->prefix_error( $pair['sk'], $sk_in ) : '';
			if ( '' !== $sk_error ) {
				$decision['messages'][] = array( "pff-paystack-invalid-{$pair['sk']}", $sk_error );
			} elseif ( '' !== $sk_in ) {
				$decision['sk_value'] = $sk_in;
			}
			$pk_error = $pk_changed ? $this->prefix_error( $pair['pk'], $pk_in ) : '';
			if ( '' !== $pk_error ) {
				$decision['messages'][] = array( "pff-paystack-invalid-{$pair['pk']}", $pk_error );
			} elseif ( $pk_changed ) {
				$decision['pk_value'] = $pk_in;
			}
		}

		return $decision;
	}

	/**
	 * The mode this save leaves active: the submitted one, else the saved one.
	 *
	 * @return string 'test' or 'live'
	 */
	private function active_mode() {
		$submitted = $this->read_post_field( 'mode' );
		if ( in_array( $submitted, array( 'test', 'live' ), true ) ) {
			return $submitted;
		}
		return 'live' === get_option( 'mode', 'test' ) ? 'live' : 'test';
	}

	/**
	 * Verifies a submitted restricted key.
	 *
	 * @param string     $key      Submitted key.
	 * @param string     $mode     'test' or 'live'.
	 * @param float|null $deadline Absolute time by which the check must finish.
	 * @return array 'message' is the error or '' when accepted, 'pk' the public key to store, 'unconfirmed' whether the extra-permission check was cut short.
	 */
	private function check_restricted_key( $key, $mode, $deadline = null ) {
		$result    = array(
			'message'     => '',
			'pk'          => '',
			'unconfirmed' => false,
		);
		$validator = null !== $this->validator ? $this->validator : [ $this, 'run_validator' ];
		$verdict   = call_user_func( $validator, $key, $mode, $deadline );
		$code      = isset( $verdict['code'] ) ? $verdict['code'] : 'unverifiable';
		$reason    = isset( $verdict['reason'] ) ? $verdict['reason'] : null;
		$scopes    = isset( $verdict['scopes'] ) && is_array( $verdict['scopes'] ) ? implode( ', ', array_map( 'esc_html', $verdict['scopes'] ) ) : '';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$probes = isset( $verdict['probes'] ) && is_string( $verdict['probes'] ) ? $verdict['probes'] : '';
			error_log( 'pff-paystack rk check mode=' . $mode . ' code=' . $code . ' probes=' . $probes ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug only, never the key.
		}

		if ( 'ok' === $code ) {
			$pk = isset( $verdict['public_key'] ) && is_string( $verdict['public_key'] ) ? $verdict['public_key'] : '';
			if ( Restricted_Key::is_valid_public_key( $pk, $mode ) ) {
				$result['pk']          = $pk;
				$result['unconfirmed'] = ! empty( $verdict['unconfirmed'] );
				return $result;
			}
			$code = 'no_public_key';
		}

		$result['message'] = $this->rejection_message( $code, $reason, $mode, $scopes );
		return $result;
	}

	/**
	 * Runs the real restricted key check.
	 *
	 * @param string     $key      Restricted key.
	 * @param string     $mode     'test' or 'live'.
	 * @param float|null $deadline Absolute time by which the check must finish.
	 * @return array Verdict.
	 */
	private function run_validator( $key, $mode, $deadline = null ) {
		return Restricted_Key::validate( $key, $mode, null, null, $deadline );
	}

	/**
	 * Builds the message for a rejected restricted key.
	 *
	 * @param string      $code      Verdict code.
	 * @param string|null $reason    Verdict reason.
	 * @param string      $mode      'test' or 'live'.
	 * @param string      $scopes    Escaped, comma separated missing or sensitive permissions.
	 * @return string
	 */
	private function rejection_message( $code, $reason, $mode, $scopes ) {
		switch ( $code ) {
			case 'invalid_shape':
				if ( 'secret_key' === $reason ) {
					return esc_html__( 'This is a secret key. Create a restricted key in your Paystack Dashboard and paste that instead. The previous key was kept; other settings were saved.', 'pff-paystack' );
				}
				if ( 'wrong_mode' === $reason ) {
					return 'test' === $mode
						? esc_html__( 'This is a live restricted key; paste it in the Live Restricted Key field. The previous key was kept; other settings were saved.', 'pff-paystack' )
						: esc_html__( 'This is a test restricted key; paste it in the Test Restricted Key field. The previous key was kept; other settings were saved.', 'pff-paystack' );
				}
				return sprintf(
					/* translators: %s: required key prefix */
					esc_html__( 'This doesn\'t look like a restricted key. Paste the full key from your Paystack Dashboard; it starts with %s. The previous key was kept; other settings were saved.', 'pff-paystack' ),
					'rk_' . $mode . '_'
				);
			case 'invalid_key':
				return esc_html__( "This restricted key is invalid, or your Paystack account isn't enabled for restricted keys yet. The previous key was kept; other settings were saved.", 'pff-paystack' );
			case 'missing_scope':
				return sprintf(
					/* translators: %s: list of permissions */
					esc_html__( 'Your restricted key is missing: %s. Turn these on in your Paystack Dashboard. The previous key was kept; other settings were saved.', 'pff-paystack' ),
					$scopes
				);
			case 'sensitive_scope':
				return sprintf(
					/* translators: %s: list of permissions */
					esc_html__( "Your restricted key has permissions this plugin doesn't need: %s. Turn them off, then save again. The previous key was kept; other settings were saved.", 'pff-paystack' ),
					$scopes
				);
			case 'no_public_key':
				return esc_html__( "Paystack didn't return a public key for this restricted key. Paste it again and save; if it keeps happening, contact Paystack support. The previous key was kept; other settings were saved.", 'pff-paystack' );
			default:
				if ( 'blocked' === $reason ) {
					return esc_html__( "Paystack blocked the check. If you've set an IP whitelist in your Paystack Dashboard, allow this server's IP, then save again. The previous key was kept; other settings were saved.", 'pff-paystack' );
				}
				return esc_html__( "We couldn't verify this restricted key right now. Please try again. The previous key was kept; other settings were saved.", 'pff-paystack' );
		}
	}

	/**
	 * Checks a submitted key against its field's required prefix.
	 *
	 * @param string $option Option name.
	 * @param string $value  Submitted key.
	 * @return string The error message, or '' when the prefix matches or none is required.
	 */
	private function prefix_error( $option, $value ) {
		$field = null;
		foreach ( $this->get_settings_fields() as $fields ) {
			if ( isset( $fields[ $option ] ) ) {
				$field = $fields[ $option ];
				break;
			}
		}

		$prefix = isset( $field['prefix'] ) ? $field['prefix'] : '';
		if ( '' === $prefix || 0 === strpos( $value, $prefix ) ) {
			return '';
		}
		return sprintf(
			/* translators: 1: field title, 2: required key prefix */
			esc_html__( 'Invalid %1$s: it must start with %2$s. The previous key was kept; other settings were saved.', 'pff-paystack' ),
			wp_strip_all_tags( $field['title'] ),
			$prefix
		);
	}

	/**
	 * Finds the mode and role of a key option.
	 *
	 * @param string $option Option name.
	 * @return array|null array( mode, 'rk'|'sk'|'pk'|'disconnect' ), or null for any other option.
	 */
	private function locate_key_option( $option ) {
		foreach ( self::SECRET_PAIRS as $mode => $pair ) {
			foreach ( $pair as $role => $name ) {
				if ( $name === $option ) {
					return array( $mode, $role );
				}
			}
		}
		return null;
	}

	/**
	 * Applies the mode's restricted key decision to its option.
	 *
	 * @param mixed  $new    Submitted value.
	 * @param mixed  $old    Stored value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function filter_restricted_key( $new, $old, $option ) {
		$located = $this->locate_key_option( $option );
		if ( null === $located || ! isset( $this->decisions[ $located[0] ] ) ) {
			return $old;
		}
		$mode     = $located[0];
		$decision = $this->decisions[ $mode ];

		if ( 'accept' === $decision['rk'] ) {
			if ( $new !== $decision['rk_value'] ) {
				return $old;
			}
			Restricted_Key::clear_denied( $mode );
			$this->set_unconfirmed( $mode, $decision['unconfirmed'] );
			if ( $decision['unconfirmed'] ) {
				add_settings_error(
					self::OPTION_GROUP,
					"pff-paystack-rk-unconfirmed-{$option}",
					sprintf(
						/* translators: %s: Test or Live */
						esc_html__( "Saved. We couldn't confirm your %s restricted key has no extra permissions. Check it in your Paystack Dashboard.", 'pff-paystack' ),
						'test' === $mode ? esc_html__( 'Test', 'pff-paystack' ) : esc_html__( 'Live', 'pff-paystack' )
					),
					'warning'
				);
			}
			return $new;
		}
		if ( 'disconnect' === $decision['rk'] ) {
			Restricted_Key::clear_denied( $mode );
			$this->set_unconfirmed( $mode, false );
			return false === $old ? $old : '';
		}
		return $old;
	}

	/**
	 * Keeps a key option unchanged; used where only our own settings page may write it.
	 *
	 * @param mixed $new Submitted value.
	 * @param mixed $old Stored value.
	 * @return mixed
	 */
	public function keep_stored_key( $new, $old ) {
		return $old;
	}

	/**
	 * Gets the modes whose stored restricted key was accepted without a full permission check.
	 *
	 * @return array array( mode => true )
	 */
	private function get_unconfirmed() {
		$stored = get_option( self::UNCONFIRMED_OPTION, array() );
		$flags  = array();
		foreach ( is_array( $stored ) ? $stored : array() as $mode => $flag ) {
			if ( isset( self::SECRET_PAIRS[ $mode ] ) && true === $flag ) {
				$flags[ $mode ] = true;
			}
		}
		return $flags;
	}

	/**
	 * Sets or clears a mode's unconfirmed flag, writing only on change.
	 *
	 * @param string $mode 'test' or 'live'.
	 * @param bool   $flag Whether the stored key is unconfirmed.
	 * @return void
	 */
	private function set_unconfirmed( $mode, $flag ) {
		$flags = $this->get_unconfirmed();
		if ( $flag === isset( $flags[ $mode ] ) ) {
			return;
		}
		if ( $flag ) {
			$flags[ $mode ] = true;
		} else {
			unset( $flags[ $mode ] );
		}
		update_option( self::UNCONFIRMED_OPTION, $flags, false );
	}

	/**
	 * Applies the mode's decision to its secret or public key option.
	 *
	 * Runs after the restricted key option is processed, so the public key is written and the secret key cleared only once that key is stored.
	 *
	 * @param mixed  $new    Submitted value.
	 * @param mixed  $old    Stored value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function filter_mode_key( $new, $old, $option ) {
		$located = $this->locate_key_option( $option );
		if ( null === $located || ! isset( $this->decisions[ $located[0] ] ) || ! in_array( $located[1], array( 'sk', 'pk' ), true ) ) {
			return $old;
		}
		list( $mode, $role ) = $located;
		$decision            = $this->decisions[ $mode ];

		// An option that was never stored stays absent rather than gaining an empty row.
		if ( 'disconnect' === $decision['rk'] ) {
			return false === $old ? $old : '';
		}
		if ( 'accept' === $decision['rk'] ) {
			if ( (string) get_option( self::SECRET_PAIRS[ $mode ]['rk'], '' ) !== $decision['rk_value'] ) {
				return $old;
			}
			if ( 'pk' === $role ) {
				return $decision['pk_value'];
			}
			return false === $old ? $old : '';
		}

		if ( 'sk' === $role && $decision['clear_sk'] ) {
			return false === $old ? $old : '';
		}

		$value = $decision[ $role . '_value' ];
		if ( null === $value || $new !== $value ) {
			return $old;
		}
		if ( 'sk' === $role ) {
			Restricted_Key::clear_denied( $mode );
		}
		return $value;
	}

	/**
	 * Keeps the stored value when a third-party secret field is submitted blank or with the wrong prefix.
	 *
	 * @param mixed  $new    Submitted value.
	 * @param mixed  $old    Stored value.
	 * @param string $option Option name.
	 * @return mixed
	 */
	public function filter_secret_key( $new, $old, $option ) {
		if ( '' === $new ) {
			return $old;
		}

		$error = $this->prefix_error( $option, (string) $new );
		if ( '' !== $error ) {
			add_settings_error( self::OPTION_GROUP, "pff-paystack-invalid-{$option}", $error, 'error' );
			return $old;
		}

		return $new;
	}

	/**
	 * Builds the masked hint for a stored secret key.
	 *
	 * @param string $value  Stored key.
	 * @param string $prefix Expected key prefix, or '' if none.
	 * @return string
	 */
	private function mask_key( $value, $prefix ) {
		if ( '' === $prefix || 0 !== strpos( $value, $prefix ) ) {
			return '••••';
		}
		if ( strlen( $value ) < strlen( $prefix ) + 20 ) {
			return $prefix . '••••';
		}
		return $prefix . '…' . substr( $value, -4 );
	}

	/**
	 * Registers our settings sub page under the Paystack Forms menu item.
	 *
	 * @return void
	 */
	public function register_settings_page() {
		add_submenu_page( 'edit.php?post_type=paystack_form', esc_html__( 'Settings', 'pff-paystack' ), esc_html__( 'Settings', 'pff-paystack' ), self::get_capability(), 'settings', [ $this, 'output_settings_page' ] );
	}

	/**
	 * Registers our Settings fields with the WP API.
	 *
	 * @return void
	 */
	public function register_settings_fields() {
		$fields = $this->get_settings_fields();
		// Run through each group, and the fields in there.
		foreach ( $fields as $group => $fields ) {
			foreach ( $fields as $field_key => $args ) {
				register_setting( self::OPTION_GROUP, $field_key, [ $this, 'sanitise_field' ] );
			}
		}
	}

	/**
	 * Prints the masked hint for a stored key; the value itself is never echoed.
	 *
	 * @param string $key    Option name.
	 * @param string $prefix Expected key prefix, or '' if none.
	 * @return void
	 */
	private function render_saved_hint( $key, $prefix ) {
		$saved_key = (string) get_option( $key, '' );
		if ( '' === $saved_key ) {
			return;
		}
		/* translators: %s: masked saved key */
		?>
		<p class="description"><?php echo esc_html( sprintf( __( 'Saved key: %s. Leave blank to keep it.', 'pff-paystack' ), $this->mask_key( $saved_key, $prefix ) ) ); ?></p>
		<?php
	}

	/**
	 * Prints a settings row for a restricted or secret key field, which is never echoed back, only masked.
	 *
	 * @param string $key             Option name.
	 * @param array  $field           Field definition.
	 * @param string $disconnect_name Name of the disconnect checkbox (restricted keys only), or ''.
	 * @param bool   $can_disconnect  Whether the mode holds any saved key to disconnect.
	 * @param bool   $unconfirmed     Whether the saved key was accepted without a full permission check.
	 * @return void
	 */
	private function render_key_row( $key, $field, $disconnect_name = '', $can_disconnect = false, $unconfirmed = false ) {
		$prefix = isset( $field['prefix'] ) ? $field['prefix'] : '';
		?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo esc_attr( 'pff-' . $key ); ?>"><?php echo wp_kses_post( $field['title'] ); ?></label></th>
			<td>
				<input type="password" id="<?php echo esc_attr( 'pff-' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="" placeholder="<?php echo esc_attr( $prefix . '…' ); ?>" autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false" />
				<?php $this->render_saved_hint( $key, $prefix ); ?>
				<?php if ( $unconfirmed ) { ?>
					<p class="description"><?php esc_html_e( "Not fully checked: we couldn't confirm this key has no extra permissions. Paste it again and save to re-check.", 'pff-paystack' ); ?></p>
				<?php } ?>
				<?php if ( '' !== $disconnect_name && $can_disconnect ) { ?>
					<p><label><input type="checkbox" name="<?php echo esc_attr( $disconnect_name ); ?>" value="1" /> <?php esc_html_e( 'Disconnect this mode (clears its saved keys)', 'pff-paystack' ); ?></label></p>
				<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Prints a generic settings row: a password field shows only a masked hint, anything else shows its saved value.
	 *
	 * @param string $key   Option name.
	 * @param array  $field Field definition.
	 * @return void
	 */
	private function render_field_row( $key, $field ) {
		?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo esc_attr( 'pff-' . $key ); ?>"><?php echo wp_kses_post( $field['title'] ); ?></label></th>
			<td>
			<?php if ( 'password' === $field['type'] ) { ?>
				<input type="password" id="<?php echo esc_attr( 'pff-' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="" autocomplete="new-password" />
				<?php $this->render_saved_hint( $key, isset( $field['prefix'] ) ? $field['prefix'] : '' ); ?>
			<?php } else { ?>
				<input type="<?php echo esc_attr( $field['type'] ); ?>" id="<?php echo esc_attr( 'pff-' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( $key, isset( $field['default'] ) ? $field['default'] : '' ) ); ?>" />
			<?php } ?>
			</td>
		</tr>
		<?php
	}

	/**
	 * Prints a form table of the given fields that are present, if any.
	 *
	 * @param array    $general  General fields.
	 * @param array    $keys     Option names to render, in order.
	 * @param callable $renderer function( $key, $field ) printing one row.
	 * @return void
	 */
	private function render_rows( $general, $keys, $renderer ) {
		$present = array_values( array_intersect( $keys, array_keys( $general ) ) );
		if ( ! $present ) {
			return;
		}
		?>
		<table class="form-table paystack_setting_page">
			<?php
			foreach ( $present as $key ) {
				call_user_func( $renderer, $key, $general[ $key ] );
			}
			?>
		</table>
		<?php
	}

	public function output_settings_page() {
		if ( ! current_user_can( self::get_capability() ) ) {
			return;
		}
		$settings_fields = $this->get_settings_fields();
		$general         = isset( $settings_fields['general'] ) ? $settings_fields['general'] : array();
		$rk_keys          = array_column( self::SECRET_PAIRS, 'rk' );
		$disconnect_names = array_combine( $rk_keys, array_column( self::SECRET_PAIRS, 'disconnect' ) );
		$known            = array_merge( array( 'mode' ), $rk_keys, array_column( self::SECRET_PAIRS, 'sk' ), array_column( self::SECRET_PAIRS, 'pk' ) );
		$has_rk           = (bool) array_intersect( $rk_keys, array_keys( $general ) );
		$holders          = array();
		foreach ( self::SECRET_PAIRS as $pair ) {
			if ( '' !== (string) get_option( $pair['rk'], '' ) || '' !== (string) get_option( $pair['sk'], '' ) ) {
				$holders[] = $pair['rk'];
			}
		}
		$unconfirmed      = $this->get_unconfirmed();
		$dashboard_url    = esc_url( self::DASHBOARD_URL );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'Paystack Forms Settings', 'pff-paystack' ); ?></h1>
			<?php settings_errors(); ?>
			<h2><?php esc_html_e( 'API Keys Settings', 'pff-paystack' ); ?></h2>

			<?php /* translators: %s: Paystack Dashboard URL */ ?>
			<span><?php echo wp_kses_post( sprintf( __( 'Get your API Keys <a href="%s" target="_blank" rel="noopener noreferrer">here</a>', 'pff-paystack' ), $dashboard_url ) ); ?> </span>

			<form method="post" action="options.php">
				<?php
					settings_fields( self::OPTION_GROUP );
					do_settings_sections( self::OPTION_GROUP );
				?>
				<?php if ( isset( $general['mode'] ) ) { ?>
					<?php $saved_mode = get_option( 'mode', isset( $general['mode']['default'] ) ? $general['mode']['default'] : 'test' ); ?>
					<table class="form-table paystack_setting_page">
						<tr valign="top">
							<th scope="row"><?php echo wp_kses_post( $general['mode']['title'] ); ?></th>
							<td>
								<select class="form-control" name="mode" id="parent_id">
									<option value="test" <?php echo esc_attr( $this->is_option_selected( 'test', $saved_mode ) ); ?>><?php esc_html_e( 'Test Mode', 'pff-paystack' ); ?></option>
									<option value="live" <?php echo esc_attr( $this->is_option_selected( 'live', $saved_mode ) ); ?>><?php esc_html_e( 'Live Mode', 'pff-paystack' ); ?></option>
								</select>
							</td>
						</tr>
					</table>
				<?php } ?>

				<?php if ( $has_rk ) { ?>
					<h2><?php esc_html_e( 'Restricted keys', 'pff-paystack' ); ?></h2>
					<ol class="pff-rk-steps">
						<li><a href="<?php echo $dashboard_url; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- escaped above. ?>" target="_blank" rel="noopener noreferrer"><?php esc_html_e( 'Create a restricted key in your Paystack Dashboard', 'pff-paystack' ); ?></a></li>
						<?php /* translators: %s: list of required permissions */ ?>
						<li><?php echo esc_html( sprintf( __( 'Turn on only these permissions: %s. Leave every other permission off, including Account: Write.', 'pff-paystack' ), Restricted_Key::required_scopes_label() ) ); ?></li>
						<li><?php esc_html_e( 'Paste a test key for Test mode and a live key for Live mode.', 'pff-paystack' ); ?></li>
					</ol>
					<?php
					$this->render_rows(
						$general,
						$rk_keys,
						function ( $key, $field ) use ( $disconnect_names, $holders, $unconfirmed ) {
							$mode = $this->locate_key_option( $key )[0];
							$this->render_key_row( $key, $field, $disconnect_names[ $key ], in_array( $key, $holders, true ), isset( $unconfirmed[ $mode ] ) && '' !== (string) get_option( $key, '' ) );
						}
					);
					?>
				<?php } ?>

				<?php
				foreach ( self::SECRET_PAIRS as $pair ) {
					$stored_sk = (string) get_option( $pair['sk'], '' );
					if ( '' === $stored_sk || '' !== (string) get_option( $pair['rk'], '' ) ) {
						continue;
					}
					?>
					<?php /* translators: %s: masked saved secret key */ ?>
					<p class="description"><?php echo esc_html( sprintf( __( 'This mode uses a secret key (%s). Save a restricted key to switch; the secret and public keys are then replaced.', 'pff-paystack' ), $this->mask_key( $stored_sk, $general[ $pair['sk'] ]['prefix'] ) ) ); ?></p>
					<?php
					$this->render_rows(
						$general,
						array( $pair['sk'], $pair['pk'] ),
						function ( $key, $field ) use ( $pair ) {
							if ( $key === $pair['sk'] ) {
								$this->render_key_row( $key, $field );
							} else {
								$this->render_field_row( $key, $field );
							}
						}
					);
				}
				?>

				<?php $this->render_rows( $general, array_diff( array_keys( $general ), $known ), [ $this, 'render_field_row' ] ); ?>
				<hr>
				<table class="form-table paystack_setting_page" id="paystack_setting_fees">
					<h2><?php esc_html_e( 'Fees Settings', 'pff-paystack' ); ?></h2>
					<?php
					$fees = isset( $settings_fields['fees'] ) ? $settings_fields['fees'] : array();
					foreach ( $fees as $key => $field ) {
						?>
						<tr valign="top">
							<th scope="row"><?php echo wp_kses_post( $field['title'] ); ?></th>
							<td>
								<input type="<?php echo esc_attr( $field['type'] ); ?>" name="<?php echo esc_attr( $key ); ?>" value="<?php echo esc_attr( get_option( $key, isset( $field['default'] ) ? $field['default'] : '' ) ); ?>" />
							</td>
						</tr>
						<?php
					}
					?>
				</table>

				<?php submit_button(); ?>

			</form>
		</div>
		<?php
	}

	/**
	 * Returns the array of settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		$fields = apply_filters( 'kkd_pff_paystack_settings_fields', $this->fields );
		if ( ! is_array( $fields ) ) {
			$fields = $this->fields;
		}

		// Our key fields keep fixed positions: options.php stores in field order and the guards need the rk stored before the sk and pk.
		$had_rk = array();
		foreach ( $fields as $group => $group_fields ) {
			if ( ! is_array( $group_fields ) ) {
				continue;
			}
			foreach ( self::SECRET_PAIRS as $pair ) {
				if ( 'general' === $group && isset( $group_fields[ $pair['rk'] ] ) ) {
					$had_rk[ $pair['rk'] ] = $this->fields['general'][ $pair['rk'] ];
				}
				foreach ( $pair as $name ) {
					unset( $group_fields[ $name ] );
				}
			}
			$fields[ $group ] = $group_fields;
		}

		$general = isset( $fields['general'] ) && is_array( $fields['general'] ) ? $fields['general'] : array();
		$pinned  = array();
		foreach ( $general as $name => $args ) {
			$pinned[ $name ] = $args;
			if ( 'mode' === $name ) {
				$pinned += $had_rk;
			}
		}
		if ( ! isset( $pinned['mode'] ) ) {
			$pinned = $had_rk + $pinned;
		}
		foreach ( self::SECRET_PAIRS as $pair ) {
			foreach ( array( $pair['sk'], $pair['pk'] ) as $name ) {
				$pinned[ $name ] = $this->fields['general'][ $name ];
			}
		}
		$fields['general'] = $pinned;
		return $fields;
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
	 * Sanitises the field name
	 *
	 * @param mixed $value
	 * @return string
	 */
	public function sanitise_field( $value ) {
		return sanitize_text_field( is_scalar( $value ) ? (string) $value : '' );
	}
}
