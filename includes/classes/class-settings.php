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

	const DASHBOARD_URL = 'https://dashboard.paystack.com/v2/developers/api-configuration';

	/**
	 * Restricted key, secret key and remove-restricted-key field names for each mode.
	 *
	 * @internal
	 */
	const SECRET_PAIRS = array(
		'test' => array(
			'rk'     => 'trk',
			'sk'     => 'tsk',
			'remove' => 'trk_remove',
		),
		'live' => array(
			'rk'     => 'lrk',
			'sk'     => 'lsk',
			'remove' => 'lrk_remove',
		),
	);

	/**
	 * Collapses the secret key section and wires its toggle; printed with wp_print_inline_script_tag so CSP plugins can add a nonce; without JS the section stays open and the server rules still apply.
	 */
	const SECRET_KEYS_JS = <<<'JS'
(function () {
	var box = document.getElementById('pff-secret-keys');
	var btn = document.getElementById('pff-secret-keys-toggle');
	if (!box || !btn || box.getAttribute('data-collapsed') !== '1') {
		return;
	}
	var inputs = box.querySelectorAll('input');
	var removes = document.querySelectorAll('.pff-rk-remove');
	function setDisabled(state) {
		for (var i = 0; i < inputs.length; i++) {
			inputs[i].disabled = state;
		}
	}
	function expand() {
		box.hidden = false;
		box.removeAttribute('data-collapsed');
		setDisabled(false);
		btn.setAttribute('aria-expanded', 'true');
		btn.hidden = true;
	}
	box.hidden = true;
	setDisabled(true);
	btn.hidden = false;
	btn.addEventListener('click', expand);
	for (var i = 0; i < removes.length; i++) {
		removes[i].addEventListener('change', function () {
			if (this.checked) {
				expand();
			}
		});
	}
})();
JS;

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
	 * Verifies a restricted key; function( $key, $mode ) returning a Restricted_Key verdict.
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
	 * @param callable $validator function( $key, $mode ) returning a verdict array.
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
			return;
		}

		// Attached before the auth gate so an undecided rk save keeps the old key instead of storing it unverified.
		foreach ( self::SECRET_PAIRS as $pair ) {
			add_filter( "pre_update_option_{$pair['rk']}", [ $this, 'filter_restricted_key' ], 10, 3 );
			add_filter( "pre_update_option_{$pair['sk']}", [ $this, 'filter_secret_key' ], 10, 3 );
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

		$this->decisions = array();
		foreach ( self::SECRET_PAIRS as $mode => $pair ) {
			$this->decisions[ $mode ] = $this->decide_mode(
				$mode,
				$this->read_post_field( $pair['rk'] ),
				$this->read_post_field( $pair['sk'] ),
				'1' === $this->read_post_field( $pair['remove'] )
			);
		}

		foreach ( $this->decisions as $decision ) {
			foreach ( $decision['messages'] as $message ) {
				add_settings_error( self::OPTION_GROUP, $message[0], $message[1], 'error' );
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
	 * Works out what to do with one mode's restricted and secret key submissions.
	 *
	 * 'rk' is none|accept|reject|remove and 'sk' is none|accept|reject|ignore; 'messages' are array( code, text ) pairs.
	 *
	 * @param string $mode   'test' or 'live'.
	 * @param string $rk_in  Submitted restricted key.
	 * @param string $sk_in  Submitted secret key.
	 * @param bool   $remove Whether the remove-restricted-key box was ticked.
	 * @return array
	 */
	private function decide_mode( $mode, $rk_in, $sk_in, $remove ) {
		$pair     = self::SECRET_PAIRS[ $mode ];
		$decision = array(
			'rk'       => 'none',
			'rk_value' => '',
			'sk'       => 'none',
			'messages' => array(),
		);
		$stored_rk = (string) get_option( $pair['rk'], '' );
		$stored_sk = (string) get_option( $pair['sk'], '' );
		$sk_error  = '' !== $sk_in ? $this->prefix_error( $pair['sk'], $sk_in ) : '';

		if ( '' !== $rk_in ) {
			$message = $this->check_restricted_key( $rk_in, $mode );
			$accept  = '' === $message;

			$decision['sk'] = 'ignore';
			if ( $accept ) {
				$decision['rk']       = 'accept';
				$decision['rk_value'] = $rk_in;
			} else {
				$decision['rk']         = 'reject';
				$decision['messages'][] = array( "pff-paystack-rk-{$pair['rk']}", $message );
			}
			if ( $remove ) {
				$decision['messages'][] = array(
					"pff-paystack-rk-remove-ignored-{$pair['rk']}",
					esc_html__( 'Remove was ignored because a new restricted key was submitted.', 'pff-paystack' ),
				);
			}
			if ( '' !== $sk_in ) {
				$decision['messages'][] = array(
					"pff-paystack-sk-ignored-{$pair['sk']}",
					$accept
						? esc_html__( 'The secret key was not saved because a restricted key was saved for this mode.', 'pff-paystack' )
						: esc_html__( 'The secret key was not saved because a restricted key was also submitted.', 'pff-paystack' ),
				);
			}
		} elseif ( $remove && '' !== $stored_rk ) {
			if ( '' !== $sk_error ) {
				$decision['sk']         = 'reject';
				$decision['messages'][] = array( "pff-paystack-invalid-{$pair['sk']}", $sk_error );
			} elseif ( '' !== $sk_in ) {
				$decision['rk'] = 'remove';
				$decision['sk'] = 'accept';
			} elseif ( '' !== $stored_sk ) {
				$decision['rk'] = 'remove';
			} else {
				$decision['messages'][] = array(
					"pff-paystack-rk-remove-{$pair['rk']}",
					esc_html__( "To remove the restricted key, paste a secret key for this mode under 'Use secret keys instead'. The restricted key was kept.", 'pff-paystack' ),
				);
			}
		} elseif ( '' !== $stored_rk && '' !== $sk_in ) {
			$decision['sk']         = 'ignore';
			$decision['messages'][] = array(
				"pff-paystack-sk-ignored-{$pair['sk']}",
				esc_html__( 'Remove the restricted key first to use a secret key. The secret key was not saved; other settings were saved.', 'pff-paystack' ),
			);
		} elseif ( '' !== $sk_error ) {
			$decision['sk']         = 'reject';
			$decision['messages'][] = array( "pff-paystack-invalid-{$pair['sk']}", $sk_error );
		} elseif ( '' !== $sk_in ) {
			$decision['sk'] = 'accept';
		}

		return $decision;
	}

	/**
	 * Verifies a submitted restricted key.
	 *
	 * @param string $key  Submitted key.
	 * @param string $mode 'test' or 'live'.
	 * @return string The error message, or '' when the key is accepted.
	 */
	private function check_restricted_key( $key, $mode ) {
		$validator = null !== $this->validator ? $this->validator : [ Restricted_Key::class, 'validate' ];
		$verdict   = call_user_func( $validator, $key, $mode );
		$code      = isset( $verdict['code'] ) ? $verdict['code'] : 'unverifiable';
		$reason    = isset( $verdict['reason'] ) ? $verdict['reason'] : null;
		$scopes    = isset( $verdict['scopes'] ) && is_array( $verdict['scopes'] ) ? implode( ', ', array_map( 'esc_html', $verdict['scopes'] ) ) : '';
		$sensitive = isset( $verdict['sensitive_scopes'] ) && is_array( $verdict['sensitive_scopes'] ) ? implode( ', ', array_map( 'esc_html', $verdict['sensitive_scopes'] ) ) : '';

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			$probes = isset( $verdict['probes'] ) && is_string( $verdict['probes'] ) ? $verdict['probes'] : '';
			error_log( 'pff-paystack rk check mode=' . $mode . ' code=' . $code . ' probes=' . $probes ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- debug only, never the key.
		}

		switch ( $code ) {
			case 'ok':
				return '';
			case 'invalid_shape':
				if ( 'secret_key' === $reason ) {
					return esc_html__( "This is a secret key. Paste it under 'Use secret keys instead', or create a restricted key. The previous key was kept; other settings were saved.", 'pff-paystack' );
				}
				if ( 'wrong_mode' === $reason ) {
					return 'test' === $mode
						? esc_html__( 'This is a live restricted key; paste it in the Live Restricted Key field. The previous key was kept; other settings were saved.', 'pff-paystack' )
						: esc_html__( 'This is a test restricted key; paste it in the Test Restricted Key field. The previous key was kept; other settings were saved.', 'pff-paystack' );
				}
				return sprintf(
					/* translators: %s: required key prefix */
					esc_html__( 'Restricted keys start with %s followed by letters and numbers. The previous key was kept; other settings were saved.', 'pff-paystack' ),
					'rk_' . $mode . '_'
				);
			case 'invalid_key':
				return esc_html__( "This restricted key is invalid, or your Paystack account isn't enabled for restricted keys yet. The previous key was kept; other settings were saved.", 'pff-paystack' );
			case 'missing_scope':
				$message = sprintf(
					/* translators: %s: list of permissions */
					esc_html__( 'Your restricted key is missing: %s. Turn these on in your Paystack Dashboard. The previous key was kept; other settings were saved.', 'pff-paystack' ),
					$scopes
				);
				if ( '' !== $sensitive ) {
					$message .= sprintf(
						/* translators: %s: list of permissions */
						esc_html__( ' Also turn off: %s.', 'pff-paystack' ),
						$sensitive
					);
				}
				return $message;
			case 'sensitive_scope':
				return sprintf(
					/* translators: %s: list of permissions */
					esc_html__( "Your restricted key has permissions this plugin doesn't need: %s. Turn them off, then save again. The previous key was kept; other settings were saved.", 'pff-paystack' ),
					$scopes
				);
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
	 * @return array|null array( mode, 'rk'|'sk' ), or null for any other option.
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
			delete_option( self::SECRET_PAIRS[ $mode ]['sk'] );
			Restricted_Key::clear_denied( $mode );
			return $new;
		}
		if ( 'remove' === $decision['rk'] ) {
			Restricted_Key::clear_denied( $mode );
			return '';
		}
		return $old;
	}

	/**
	 * Keeps the stored key when the submitted one is blank, ignored or has the wrong prefix.
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

		$located = $this->locate_key_option( $option );
		$is_sk   = null !== $located && 'sk' === $located[1];
		if ( $is_sk ) {
			$mode     = $located[0];
			$sk_state = isset( $this->decisions[ $mode ] ) ? $this->decisions[ $mode ]['sk'] : 'none';
			$rk_state = isset( $this->decisions[ $mode ] ) ? $this->decisions[ $mode ]['rk'] : 'none';

			if ( '' !== (string) get_option( self::SECRET_PAIRS[ $mode ]['rk'], '' ) && 'accept' !== $sk_state && 'remove' !== $rk_state ) {
				return $old;
			}
			if ( 'ignore' === $sk_state || 'reject' === $sk_state ) {
				return $old;
			}
			if ( 'accept' === $sk_state ) {
				Restricted_Key::clear_denied( $mode );
				return $new;
			}
		}

		$error = $this->prefix_error( $option, (string) $new );
		if ( '' !== $error ) {
			add_settings_error( self::OPTION_GROUP, "pff-paystack-invalid-{$option}", $error, 'error' );
			return $old;
		}

		if ( $is_sk ) {
			Restricted_Key::clear_denied( $located[0] );
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
	 * Whether the secret key section should start open: a mode holds a secret key, or a save just reported a problem with one.
	 *
	 * @internal
	 *
	 * @param array $errors Settings errors, as returned by get_settings_errors().
	 * @return bool
	 */
	public function secret_keys_expanded( $errors = array() ) {
		$prefixes = array( 'pff-paystack-sk-ignored-', 'pff-paystack-rk-remove-' );
		foreach ( self::SECRET_PAIRS as $pair ) {
			if ( '' !== (string) get_option( $pair['sk'], '' ) ) {
				return true;
			}
			$prefixes[] = "pff-paystack-invalid-{$pair['sk']}";
		}

		foreach ( $errors as $error ) {
			$code = isset( $error['code'] ) ? (string) $error['code'] : '';
			foreach ( $prefixes as $prefix ) {
				if ( 0 === strpos( $code, $prefix ) ) {
					return true;
				}
			}
		}
		return false;
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
	 * @param string $key         Option name.
	 * @param array  $field       Field definition.
	 * @param string $remove_name Name of the remove-saved-key checkbox (restricted keys only), or ''.
	 * @return void
	 */
	private function render_key_row( $key, $field, $remove_name = '' ) {
		$prefix = isset( $field['prefix'] ) ? $field['prefix'] : '';
		?>
		<tr valign="top">
			<th scope="row"><label for="<?php echo esc_attr( 'pff-' . $key ); ?>"><?php echo wp_kses_post( $field['title'] ); ?></label></th>
			<td>
				<input type="password" id="<?php echo esc_attr( 'pff-' . $key ); ?>" name="<?php echo esc_attr( $key ); ?>" value="" placeholder="<?php echo esc_attr( $prefix . '…' ); ?>" autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false" />
				<?php $this->render_saved_hint( $key, $prefix ); ?>
				<?php if ( '' !== $remove_name && '' !== (string) get_option( $key, '' ) ) { ?>
					<p><label><input type="checkbox" class="pff-rk-remove" name="<?php echo esc_attr( $remove_name ); ?>" value="1" /> <?php esc_html_e( 'Remove saved restricted key', 'pff-paystack' ); ?></label></p>
					<p class="description"><?php esc_html_e( "To switch back to a secret key, also paste one under 'Use secret keys instead'.", 'pff-paystack' ); ?></p>
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
		$rk_keys         = array_column( self::SECRET_PAIRS, 'rk' );
		$sk_keys         = array_column( self::SECRET_PAIRS, 'sk' );
		$remove_names    = array_combine( $rk_keys, array_column( self::SECRET_PAIRS, 'remove' ) );
		$known           = array_merge( array( 'mode', 'tpk', 'lpk' ), $rk_keys, $sk_keys );
		$has_rk          = (bool) array_intersect( $rk_keys, array_keys( $general ) );
		$has_sk          = (bool) array_intersect( $sk_keys, array_keys( $general ) );
		$collapsed       = $has_rk && ! $this->secret_keys_expanded( get_settings_errors( self::OPTION_GROUP ) );
		$dashboard_url   = esc_url( self::DASHBOARD_URL );
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
						function ( $key, $field ) use ( $remove_names ) {
							$this->render_key_row( $key, $field, $remove_names[ $key ] );
						}
					);
					?>
				<?php } ?>

				<?php $this->render_rows( $general, array( 'tpk', 'lpk' ), [ $this, 'render_field_row' ] ); ?>

				<?php if ( $has_sk ) { ?>
					<p><button type="button" class="button-link" id="pff-secret-keys-toggle" aria-expanded="false" aria-controls="pff-secret-keys" hidden><?php esc_html_e( 'Use secret keys instead', 'pff-paystack' ); ?></button></p>
					<div id="pff-secret-keys"<?php if ( $collapsed ) { ?> data-collapsed="1"<?php } ?>>
						<?php
						$this->render_rows(
							$general,
							$sk_keys,
							function ( $key, $field ) {
								$this->render_key_row( $key, $field );
							}
						);
						?>
					</div>
				<?php } ?>

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
			<?php
			// Collapsing is done by the script, not in markup, so the fields stay visible and submittable without JS.
			if ( function_exists( 'wp_print_inline_script_tag' ) ) {
				wp_print_inline_script_tag( self::SECRET_KEYS_JS );
			} else {
				echo '<script>' . self::SECRET_KEYS_JS . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- static string.
			}
			?>
		</div>
		<?php
	}

	/**
	 * Returns the array of settings fields.
	 *
	 * @return array
	 */
	public function get_settings_fields() {
		return apply_filters( 'kkd_pff_paystack_settings_fields', $this->fields );
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
