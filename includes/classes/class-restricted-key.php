<?php
/**
 * Restricted key shape check, scope probing and runtime scope-denied tracking.
 *
 * @package    \paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Restricted_Key class.
 */
class Restricted_Key {

	const SCOPE_DENIED_MESSAGE = 'This restricted key is not permitted to perform this action';

	const PLAN_VALIDATION_MESSAGE = '"name" is required';

	const MOR_MESSAGE = 'This feature is not available to merchant of record integrations.';

	const DENIED_OPTION = 'pff_paystack_rk_denied';

	const BUDGET_SECONDS = 20;

	const PROBE_TIMEOUT = 4;

	/**
	 * Money-moving scopes: probed first among the sensitive ones, and a key is rejected unless each is definitively absent.
	 */
	const HIGH_RISK = array( 'transactions:write', 'transfers', 'payouts', 'bulk_charges' );

	/**
	 * Probes in run order; the first is the baseline, the required ones run before any sensitive one.
	 *
	 * match: success = 2xx; generic = 2xx or 400; exact = one of the status/message pairs.
	 * granted_on: extra status/message pairs that also prove the scope exists.
	 */
	const PROBES = array(
		array(
			'scope'    => 'transactions:read',
			'method'   => 'GET',
			'path'     => 'transaction?perPage=1',
			'body'     => null,
			'required' => true,
			'match'    => 'success',
		),
		array(
			'scope'    => 'account:read',
			'method'   => 'GET',
			'path'     => 'ident',
			'body'     => null,
			'required' => true,
			'match'    => 'success',
		),
		array(
			'scope'      => 'plans:write',
			'method'     => 'POST',
			'path'       => 'plan',
			'body'       => '{}',
			'required'   => true,
			'match'      => 'exact',
			'exact'      => array( array( 400, self::PLAN_VALIDATION_MESSAGE ) ),
			'granted_on' => array( array( 403, self::MOR_MESSAGE ) ),
		),
		array(
			'scope'    => 'transactions:write',
			'method'   => 'POST',
			'path'     => 'transaction/check_authorization',
			'body'     => '{}',
			'required' => false,
			'match'    => 'exact',
			'exact'    => array( array( 404, 'Sorry, this feature is no longer available.' ) ),
		),
		array(
			'scope'    => 'transfers',
			'method'   => 'GET',
			'path'     => 'transfer?perPage=1&from=2099-01-01',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'payouts',
			'method'   => 'GET',
			'path'     => 'settlement/instant/requests?perPage=1&from=2099-01-01',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'bulk_charges',
			'method'   => 'GET',
			'path'     => 'bulkcharge?perPage=1',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'disputes_refunds',
			'method'   => 'GET',
			'path'     => 'refund?perPage=1&from=2099-01-01',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'direct_debit',
			'method'   => 'GET',
			'path'     => 'directdebit/account',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'preauthorizations',
			'method'   => 'GET',
			'path'     => 'preauthorization?perPage=1',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'settlements',
			'method'   => 'GET',
			'path'     => 'settlement?perPage=1&from=2099-01-01',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'      => 'subaccounts',
			'method'     => 'GET',
			'path'       => 'subaccount?perPage=1',
			'body'       => null,
			'required'   => false,
			'match'      => 'generic',
			'granted_on' => array( array( 403, self::MOR_MESSAGE ) ),
		),
		array(
			'scope'    => 'virtual_accounts',
			'method'   => 'GET',
			'path'     => 'pay-with-transfer/custom-accounts/status',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'webhooks',
			'method'   => 'GET',
			'path'     => 'integration/webhooks/events',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'connect',
			'method'   => 'GET',
			'path'     => 'connect/platforms?perPage=1',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'customers',
			'method'   => 'GET',
			'path'     => 'customer?perPage=1&from=2099-01-01',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
		array(
			'scope'    => 'balance',
			'method'   => 'GET',
			'path'     => 'balance',
			'body'     => null,
			'required' => false,
			'match'    => 'generic',
		),
	);

	/**
	 * API module values (without the trailing slash) that may be recorded as denied.
	 */
	const MODULES = array(
		'transaction/verify',
		'plan',
		'subscription',
	);

	/**
	 * Checks the key is an rk_ key of the given mode.
	 *
	 * Paystack doesn't document the body's character set, so it is printable ASCII minus space, quotes, angle brackets and backslash, as in the sibling plugins,
	 * and minus % and &: sanitize_text_field strips %xx octets and API::secret() runs esc_attr, so either would change the key.
	 *
	 * @param mixed  $key  Submitted key.
	 * @param string $mode 'test' or 'live'.
	 * @return boolean
	 */
	public static function is_valid_shape( $key, $mode ) {
		if ( ! is_string( $key ) || ! preg_match( '/^rk_(test|live)_[\x21\x23\x24\x28-\x3b\x3d\x3f-\x5b\x5d-\x7e]{16,256}\z/', $key, $m ) ) {
			return false;
		}
		return $m[1] === $mode;
	}

	/**
	 * Checks the key is a pk_ key of the given mode.
	 *
	 * @param mixed  $pk   Public key.
	 * @param string $mode 'test' or 'live'.
	 * @return boolean
	 */
	public static function is_valid_public_key( $pk, $mode ) {
		return is_string( $pk ) && 1 === preg_match( '/^pk_' . preg_quote( $mode, '/' ) . '_[A-Za-z0-9_]{16,128}\z/', $pk );
	}

	/**
	 * Human label for a probe scope; not escaped.
	 *
	 * @param string $scope Scope id from PROBES.
	 * @return string Empty when unknown.
	 */
	public static function label( $scope ) {
		switch ( $scope ) {
			case 'transactions:read':
				return __( 'Transactions: Read', 'pff-paystack' );
			case 'account:read':
				return __( 'Account: Read', 'pff-paystack' );
			case 'plans:write':
				return __( 'Plans: Write', 'pff-paystack' );
			case 'transactions:write':
				return __( 'Transactions: Write', 'pff-paystack' );
			case 'disputes_refunds':
				return __( 'Disputes and refunds', 'pff-paystack' );
			case 'bulk_charges':
				return __( 'Bulk charges', 'pff-paystack' );
			case 'direct_debit':
				return __( 'Direct debit', 'pff-paystack' );
			case 'preauthorizations':
				return __( 'Preauthorizations', 'pff-paystack' );
			case 'settlements':
				return __( 'Settlements', 'pff-paystack' );
			case 'subaccounts':
				return __( 'Subaccounts', 'pff-paystack' );
			case 'transfers':
				return __( 'Transfers', 'pff-paystack' );
			case 'virtual_accounts':
				return __( 'Virtual accounts', 'pff-paystack' );
			case 'webhooks':
				return __( 'Webhooks', 'pff-paystack' );
			case 'connect':
				return __( 'Connect', 'pff-paystack' );
			case 'payouts':
				return __( 'Payouts', 'pff-paystack' );
			case 'customers':
				return __( 'Customers', 'pff-paystack' );
			case 'balance':
				return __( 'Balance', 'pff-paystack' );
		}
		return '';
	}

	/**
	 * Lists the required scope labels, e.g. "Transactions: Read, Account: Read, Plans: Write".
	 *
	 * @return string
	 */
	public static function required_scopes_label() {
		$labels = array();
		foreach ( self::PROBES as $probe ) {
			if ( $probe['required'] ) {
				$labels[] = self::label( $probe['scope'] );
			}
		}
		return implode( ', ', $labels );
	}

	/**
	 * Classifies one probe response.
	 *
	 * @param array       $probe            Entry from PROBES.
	 * @param integer     $status           HTTP status, 0 on transport failure.
	 * @param string|null $message          Message from the JSON body.
	 * @param string|null $baseline_outcome Baseline outcome, null when classifying the baseline.
	 * @return string present|absent|indeterminate|invalid
	 */
	public static function classify( $probe, $status, $message, $baseline_outcome ) {
		if ( self::is_scope_denied( $status, $message ) ) {
			return 'absent';
		}
		if ( 401 === $status ) {
			if ( null === $baseline_outcome ) {
				return 'invalid';
			}
			if ( 'present' === $baseline_outcome && ! $probe['required'] ) {
				return 'present';
			}
			return 'indeterminate';
		}
		if ( self::matches_present( $probe, $status, $message ) ) {
			return 'present';
		}
		return 'indeterminate';
	}

	/**
	 * Applies a probe's own present rule.
	 *
	 * @param array       $probe   Entry from PROBES.
	 * @param integer     $status  HTTP status.
	 * @param string|null $message Message from the JSON body.
	 * @return boolean
	 */
	private static function matches_present( $probe, $status, $message ) {
		$pairs = isset( $probe['granted_on'] ) ? $probe['granted_on'] : array();
		if ( 'exact' === $probe['match'] ) {
			$pairs = array_merge( $pairs, $probe['exact'] );
		} elseif ( $status >= 200 && $status < 300 ) {
			return true;
		} elseif ( 'generic' === $probe['match'] && 400 === $status ) {
			return true;
		}
		foreach ( $pairs as $pair ) {
			if ( $pair[0] === $status && $pair[1] === $message ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Reduces classified probe results to a verdict; a probe with no result counts as indeterminate.
	 *
	 * @param array $results List of array( 'scope' => string, 'outcome' => string, 'status' => int ).
	 * @return array array( 'code', 'scopes', 'reason', 'sensitive_scopes', 'probes', 'public_key', 'unconfirmed' )
	 */
	public static function decide( $results ) {
		$by_scope = array();
		foreach ( $results as $result ) {
			if ( isset( $result['scope'] ) ) {
				$by_scope[ $result['scope'] ] = $result;
			}
		}

		$missing   = array();
		$sensitive = array();
		$blocked   = false;
		$unknown   = false;
		$skipped   = false;
		$high_gap  = false;
		$trace     = array();
		foreach ( self::PROBES as $probe ) {
			$trace[] = $probe['scope'] . ':' . ( isset( $by_scope[ $probe['scope'] ] ) ? (int) $by_scope[ $probe['scope'] ]['status'] : '-' );
		}
		$probes = implode( ',', $trace );

		foreach ( self::PROBES as $probe ) {
			$result  = isset( $by_scope[ $probe['scope'] ] ) ? $by_scope[ $probe['scope'] ] : null;
			$outcome = null !== $result ? $result['outcome'] : 'indeterminate';
			$status  = null !== $result ? (int) $result['status'] : 0;
			switch ( $outcome ) {
				case 'invalid':
					return self::verdict( 'invalid_key', array(), null, array(), $probes );
				case 'absent':
					if ( $probe['required'] ) {
						$missing[] = self::label( $probe['scope'] );
					}
					break;
				case 'present':
					if ( ! $probe['required'] ) {
						$sensitive[] = self::label( $probe['scope'] );
					}
					break;
				default:
					if ( $probe['required'] ) {
						$unknown = true;
						if ( 403 === $status ) {
							$blocked = true;
						}
					} else {
						$skipped = true;
						if ( in_array( $probe['scope'], self::HIGH_RISK, true ) ) {
							$high_gap = true;
						}
					}
			}
		}

		if ( $missing ) {
			return self::verdict( 'missing_scope', $missing, null, $sensitive, $probes );
		}
		if ( $sensitive ) {
			return self::verdict( 'sensitive_scope', $sensitive, null, array(), $probes );
		}
		if ( $unknown ) {
			return self::verdict( 'unverifiable', array(), $blocked ? 'blocked' : 'transient', array(), $probes );
		}
		if ( $high_gap ) {
			return self::verdict( 'unverifiable', array(), 'transient', array(), $probes );
		}
		return self::verdict( 'ok', array(), null, array(), $probes, null, $skipped );
	}

	/**
	 * Builds a verdict array.
	 *
	 * @param string      $code             Verdict code.
	 * @param string[]    $scopes           Scope labels.
	 * @param string|null $reason           Reason for unverifiable and invalid_shape.
	 * @param string[]    $sensitive_scopes Sensitive scope labels found alongside missing ones.
	 * @param string      $probes           Key-free scope:status trace.
	 * @param string|null $public_key       Public key, only on ok verdicts.
	 * @param boolean     $unconfirmed      Whether some sensitive probes could not be answered.
	 * @return array
	 */
	private static function verdict( $code, $scopes = array(), $reason = null, $sensitive_scopes = array(), $probes = '', $public_key = null, $unconfirmed = false ) {
		return array(
			'code'             => $code,
			'scopes'           => $scopes,
			'reason'           => $reason,
			'sensitive_scopes' => $sensitive_scopes,
			'probes'           => $probes,
			'public_key'       => $public_key,
			'unconfirmed'      => $unconfirmed,
		);
	}

	/**
	 * Why a key failed the shape check.
	 *
	 * @param mixed  $key  Submitted key.
	 * @param string $mode 'test' or 'live'.
	 * @return string secret_key|wrong_mode|malformed
	 */
	private static function shape_reason( $key, $mode ) {
		if ( is_string( $key ) && 0 === strpos( $key, 'sk_' ) ) {
			return 'secret_key';
		}
		if ( self::is_valid_shape( $key, 'test' === $mode ? 'live' : 'test' ) ) {
			return 'wrong_mode';
		}
		return 'malformed';
	}

	/**
	 * Probes the key's scopes: required ones first, then the sensitive ones until the deadline.
	 *
	 * @param string        $key      Restricted key.
	 * @param string        $mode     'test' or 'live'.
	 * @param callable|null $request  function( $method, $path, $body, $timeout, $key ) returning array( 'status' => int, 'message' => string|null, 'public_key' => string|null ).
	 * @param callable|null $clock    Returns the current time in seconds as a float.
	 * @param float|null    $deadline Absolute time on $clock; defaults to now + BUDGET_SECONDS.
	 * @return array Verdict, or code 'invalid_shape' when the key shape is wrong.
	 */
	public static function validate(
		#[\SensitiveParameter]
		$key,
		$mode,
		$request = null,
		$clock = null,
		$deadline = null
	) {
		if ( ! self::is_valid_shape( $key, $mode ) ) {
			return self::verdict( 'invalid_shape', array(), self::shape_reason( $key, $mode ) );
		}
		if ( null === $request ) {
			$request = array( __CLASS__, 'send' );
		}
		$now = null !== $clock ? $clock : function () {
			return microtime( true );
		};
		if ( null === $deadline ) {
			$deadline = $now() + self::BUDGET_SECONDS;
		}

		$baseline    = null;
		$results     = array();
		$public_key  = null;
		$required_ok = true;
		foreach ( self::PROBES as $index => $probe ) {
			if ( ! $probe['required'] && ! $required_ok ) {
				break;
			}
			$remaining = $deadline - $now();
			if ( $remaining <= 0 ) {
				break;
			}

			try {
				$response = call_user_func(
					$request,
					$probe['method'],
					$probe['path'],
					$probe['body'],
					min( self::PROBE_TIMEOUT, $remaining ),
					$key
				);
			} catch ( \Throwable $e ) {
				$response = null;
			}
			$status    = isset( $response['status'] ) ? (int) $response['status'] : 0;
			$message   = isset( $response['message'] ) && is_string( $response['message'] ) ? $response['message'] : null;
			$outcome   = self::classify( $probe, $status, $message, $baseline );
			$results[] = array(
				'scope'   => $probe['scope'],
				'outcome' => $outcome,
				'status'  => $status,
			);

			if ( 'account:read' === $probe['scope'] && isset( $response['public_key'] ) && is_string( $response['public_key'] ) ) {
				$public_key = $response['public_key'];
			}
			if ( 'present' !== $outcome ) {
				$required_ok = $required_ok && ! $probe['required'];
			}
			if ( 0 === $index ) {
				if ( 'present' !== $outcome && 'absent' !== $outcome ) {
					break;
				}
				$baseline = $outcome;
			}
		}

		$verdict = self::decide( $results );
		if ( 'ok' !== $verdict['code'] ) {
			return $verdict;
		}
		if ( ! self::is_valid_public_key( $public_key, $mode ) ) {
			return self::verdict( 'no_public_key', array(), null, array(), $verdict['probes'] );
		}
		$verdict['public_key'] = $public_key;
		return $verdict;
	}

	/**
	 * Sends one probe request.
	 *
	 * @param string      $method  HTTP method.
	 * @param string      $path    Path relative to API::BASE_URL.
	 * @param string|null $body    JSON body.
	 * @param float       $timeout Seconds.
	 * @param string      $key     Restricted key.
	 * @return array array( 'status' => int, 'message' => string|null, 'public_key' => string|null )
	 */
	public static function send(
		$method,
		$path,
		$body,
		$timeout,
		#[\SensitiveParameter]
		$key
	) {
		$response = wp_remote_request(
			API::BASE_URL . $path,
			array(
				'method'      => $method,
				'headers'     => array(
					'Authorization' => 'Bearer ' . $key,
					'Content-Type'  => 'application/json',
				),
				'body'        => $body,
				'timeout'     => $timeout,
				'redirection' => 0,
			)
		);
		if ( is_wp_error( $response ) ) {
			return array(
				'status'     => 0,
				'message'    => null,
				'public_key' => null,
			);
		}

		return array(
			'status'     => (int) wp_remote_retrieve_response_code( $response ),
			'message'    => self::message_of( wp_remote_retrieve_body( $response ) ),
			'public_key' => self::public_key_of( wp_remote_retrieve_body( $response ) ),
		);
	}

	/**
	 * Extracts the string "message" from a JSON response body.
	 *
	 * @param mixed $raw_body Raw response body.
	 * @return string|null
	 */
	public static function message_of( $raw_body ) {
		$decoded = is_string( $raw_body ) ? json_decode( $raw_body, true ) : null;
		return is_array( $decoded ) && isset( $decoded['message'] ) && is_string( $decoded['message'] ) ? $decoded['message'] : null;
	}

	/**
	 * Extracts the string data.public_key from a JSON response body.
	 *
	 * @param mixed $raw_body Raw response body.
	 * @return string|null
	 */
	public static function public_key_of( $raw_body ) {
		$decoded = is_string( $raw_body ) ? json_decode( $raw_body, true ) : null;
		return is_array( $decoded ) && isset( $decoded['data']['public_key'] ) && is_string( $decoded['data']['public_key'] ) ? $decoded['data']['public_key'] : null;
	}

	/**
	 * Whether a response is Paystack's restricted-key scope denial.
	 *
	 * @param integer     $status  HTTP status.
	 * @param string|null $message Message from the JSON body.
	 * @return boolean
	 */
	public static function is_scope_denied( $status, $message ) {
		return 403 === $status && self::SCOPE_DENIED_MESSAGE === $message;
	}

	/**
	 * Gets the recorded denials as array( mode => array( module => first seen time ) ).
	 *
	 * @return array
	 */
	public static function get_denied() {
		$stored = get_option( self::DENIED_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return array();
		}

		$denied = array();
		foreach ( $stored as $mode => $entry ) {
			if ( ! is_array( $entry ) || ! in_array( $mode, array( 'test', 'live' ), true ) ) {
				continue;
			}
			foreach ( $entry as $module => $time ) {
				if ( is_string( $module ) && is_int( $time ) ) {
					$denied[ $mode ][ $module ] = $time;
				}
			}
		}
		return $denied;
	}

	/**
	 * Records that a module was denied in a mode; only whitelisted modules are stored.
	 *
	 * @param string $mode   'test' or 'live'.
	 * @param string $module The API subclass module value.
	 */
	public static function record_denied( $mode, $module ) {
		$module = is_string( $module ) ? rtrim( $module, '/' ) : '';
		if ( ! in_array( $mode, array( 'test', 'live' ), true ) || ! in_array( $module, self::MODULES, true ) ) {
			return;
		}

		$denied = self::get_denied();
		if ( isset( $denied[ $mode ][ $module ] ) ) {
			return;
		}
		$denied[ $mode ][ $module ] = time();
		update_option( self::DENIED_OPTION, $denied, false );
	}

	/**
	 * Clears a mode's recorded denial.
	 *
	 * @param string $mode 'test' or 'live'.
	 */
	public static function clear_denied( $mode ) {
		$denied = self::get_denied();
		if ( ! isset( $denied[ $mode ] ) ) {
			return;
		}
		unset( $denied[ $mode ] );
		update_option( self::DENIED_OPTION, $denied, false );
	}

	/**
	 * Human label for a recorded module.
	 *
	 * @param string $module Module value.
	 * @return string Empty when unknown.
	 */
	public static function module_label( $module ) {
		$module = is_string( $module ) ? rtrim( $module, '/' ) : '';
		switch ( $module ) {
			case 'transaction/verify':
				return __( 'Verifying payments', 'pff-paystack' );
			case 'plan':
				return __( 'Reading or creating plans', 'pff-paystack' );
			case 'subscription':
				return __( 'Creating subscriptions', 'pff-paystack' );
		}
		return '';
	}
}
