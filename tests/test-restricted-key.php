<?php
// Run: php tests/test-restricted-key.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options']  = array();
$GLOBALS['pff_updates']  = array();
$GLOBALS['pff_wp_calls'] = array();
$GLOBALS['pff_wp_reply'] = null;

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['pff_options'] ) ? $GLOBALS['pff_options'][ $name ] : $default;
	}
}
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$GLOBALS['pff_options'][ $name ] = $value;
		$GLOBALS['pff_updates'][]        = array( $name, $value, $autoload );
		return true;
	}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {
		public $message = 'secret transport detail';
	}
}
if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args ) {
		$GLOBALS['pff_wp_calls'][] = array( $url, $args );
		return $GLOBALS['pff_wp_reply'];
	}
}
if ( ! function_exists( 'wp_remote_retrieve_response_code' ) ) {
	function wp_remote_retrieve_response_code( $r ) {
		return $r['code'];
	}
}
if ( ! function_exists( 'wp_remote_retrieve_body' ) ) {
	function wp_remote_retrieve_body( $r ) {
		return $r['body'];
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-api.php';
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key.php';

use paystack\payment_forms\Restricted_Key as RK;
use paystack\payment_forms\API;

define( 'EXPECTED', 185 );

$deny = RK::SCOPE_DENIED_MESSAGE;
$mor  = RK::MOR_MESSAGE;
$KEY  = 'rk_test_' . str_repeat( 'a1B2', 6 );

function probe_for( $scope ) {
	foreach ( RK::PROBES as $p ) {
		if ( $p['scope'] === $scope ) {
			return $p;
		}
	}
	return null;
}

function r( $status, $message = null ) {
	return array( 'status' => $status, 'message' => $message );
}

// Fake transport: $overrides maps scope => response; default is a plausible all-granted 200.
function run_validate( $overrides, $default = null, $key = null, &$sent = null, $clock = null ) {
	global $KEY;
	$sent = array();
	$default = null === $default ? r( 200 ) : $default;
	$req = function ( $method, $path, $body, $timeout, $k ) use ( $overrides, $default, &$sent ) {
		$scope = null;
		foreach ( RK::PROBES as $p ) {
			if ( $p['path'] === $path ) {
				$scope = $p['scope'];
			}
		}
		$sent[] = array( $scope, $method, $path, $body, $timeout, $k );
		return array_key_exists( $scope, $overrides ) ? $overrides[ $scope ] : $default;
	};
	return RK::validate( null === $key ? $KEY : $key, 'test', $req, $clock );
}

// A key with nothing granted beyond the required scopes.
$ok_map = array(
	'plans:write' => r( 400, RK::PLAN_VALIDATION_MESSAGE ),
);
foreach ( RK::PROBES as $p ) {
	if ( ! $p['required'] ) {
		$ok_map[ $p['scope'] ] = r( 403, $deny );
	}
}

function ok_reply_for_path( $path ) {
	global $ok_map;
	foreach ( RK::PROBES as $p ) {
		if ( $p['path'] === $path ) {
			return $ok_map[ $p['scope'] ];
		}
	}
	return r( 200 );
}
$ok_map['transactions:read'] = r( 200 );

// Shape.
check( 'valid test key', true, RK::is_valid_shape( $KEY, 'test' ) );
check( 'valid live key', true, RK::is_valid_shape( 'rk_live_' . str_repeat( 'Z9', 10 ), 'live' ) );
check( 'wrong mode', false, RK::is_valid_shape( $KEY, 'live' ) );
check( 'uppercase prefix', false, RK::is_valid_shape( 'RK_TEST_' . str_repeat( 'a', 20 ), 'test' ) );
check( 'empty', false, RK::is_valid_shape( '', 'test' ) );
check( 'non-string', false, RK::is_valid_shape( array( $KEY ), 'test' ) );
check( 'body 15 chars', false, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 15 ), 'test' ) );
check( 'body 16 chars', true, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 16 ), 'test' ) );
check( 'body 256 chars', true, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 256 ), 'test' ) );
check( 'body 257 chars', false, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 257 ), 'test' ) );
foreach ( array( '&', '<', '"', "'", '>', '-', ' ', ':' ) as $sym ) {
	check( "symbol $sym", false, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 16 ) . $sym, 'test' ) );
}
// Real Paystack restricted keys put an underscore at varying positions in the body.
check( 'real shape, underscore after 9', true, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 9 ) . '_' . str_repeat( 'B', 30 ), 'test' ) );
check( 'real shape, underscore after 25', true, RK::is_valid_shape( 'rk_live_' . str_repeat( 'c', 25 ) . '_' . str_repeat( 'D', 14 ), 'live' ) );
check( 'real shape, wrong mode', false, RK::is_valid_shape( 'rk_test_' . str_repeat( 'a', 9 ) . '_' . str_repeat( 'B', 30 ), 'live' ) );
check( 'trailing newline', false, RK::is_valid_shape( $KEY . "\n", 'test' ) );
check( 'sk key', false, RK::is_valid_shape( 'sk_test_' . str_repeat( 'a', 20 ), 'test' ) );
check( 'unknown mode', false, RK::is_valid_shape( $KEY, 'staging' ) );

// Probe table.
$sensitive = array_filter( RK::PROBES, function ( $p ) { return ! $p['required']; } );
check( 'probe count', 16, count( RK::PROBES ) );
check( 'sensitive count', 14, count( $sensitive ) );
// Cloudflare answers POST /verifications with an HTML 403 for every client, so it can't be probed.
check( 'no verifications probe', array(), array_filter( RK::PROBES, function ( $p ) { return 'verifications' === $p['path']; } ) );
check( 'no account:read', null, probe_for( 'account:read' ) );
check( 'required label', 'Transactions: Read, Plans: Write', RK::required_scopes_label() );
check( 'label known', 'Disputes and refunds', RK::label( 'disputes_refunds' ) );
check( 'label unknown', '', RK::label( 'nope' ) );
$missing_labels = array_filter( array_map( function ( $p ) { return RK::label( $p['scope'] ); }, RK::PROBES ), function ( $l ) { return '' === $l; } );
check( 'every probe has a label', array(), $missing_labels );
check( 'probes carry no label', false, isset( RK::PROBES[0]['label'] ) );
check( 'baseline first', 'GET transaction?perPage=1', RK::PROBES[0]['method'] . ' ' . RK::PROBES[0]['path'] );
check( 'plan probe', 'POST plan {}', RK::PROBES[1]['method'] . ' ' . RK::PROBES[1]['path'] . ' ' . RK::PROBES[1]['body'] );

// Classify: absent only on the exact scope 403.
$gen = probe_for( 'customers' );
$base = probe_for( 'transactions:read' );
check( 'scope 403 absent', 'absent', RK::classify( $gen, 403, $deny, 'present' ) );
check( 'other 403 indeterminate', 'indeterminate', RK::classify( $gen, 403, 'IP not whitelisted', 'present' ) );
check( '403 no message indeterminate', 'indeterminate', RK::classify( $gen, 403, null, 'present' ) );
check( 'scope message on non-403 not absent', 'present', RK::classify( $gen, 400, $deny, 'present' ) );
check( 'generic 200 present', 'present', RK::classify( $gen, 200, null, 'present' ) );
check( 'generic 400 present', 'present', RK::classify( $gen, 400, 'anything', 'present' ) );
check( 'generic 404 indeterminate', 'indeterminate', RK::classify( $gen, 404, null, 'present' ) );
check( 'generic 429 indeterminate', 'indeterminate', RK::classify( $gen, 429, null, 'present' ) );
check( 'generic 500 indeterminate', 'indeterminate', RK::classify( $gen, 500, null, 'present' ) );
check( 'generic 302 indeterminate', 'indeterminate', RK::classify( $gen, 302, null, 'present' ) );
check( 'status 0 indeterminate', 'indeterminate', RK::classify( $gen, 0, null, 'present' ) );
check( 'baseline 200 present', 'present', RK::classify( $base, 200, null, null ) );
check( 'baseline 400 not present', 'indeterminate', RK::classify( $base, 400, null, null ) );
check( 'baseline 401 invalid', 'invalid', RK::classify( $base, 401, null, null ) );
check( 'baseline scope 403 absent', 'absent', RK::classify( $base, 403, $deny, null ) );
check( '401 sensitive after 2xx baseline present', 'present', RK::classify( $gen, 401, null, 'present' ) );
check( '401 required after 2xx baseline indeterminate', 'indeterminate', RK::classify( probe_for( 'plans:write' ), 401, null, 'present' ) );
check( '401 sensitive after 403 baseline indeterminate', 'indeterminate', RK::classify( $gen, 401, null, 'absent' ) );
check( '401 required after 403 baseline indeterminate', 'indeterminate', RK::classify( probe_for( 'plans:write' ), 401, null, 'absent' ) );

// Required plan probe.
$plan = probe_for( 'plans:write' );
check( 'plan 400 exact present', 'present', RK::classify( $plan, 400, RK::PLAN_VALIDATION_MESSAGE, 'present' ) );
check( 'plan 400 other message indeterminate', 'indeterminate', RK::classify( $plan, 400, 'other', 'present' ) );
check( 'plan 200 not present', 'indeterminate', RK::classify( $plan, 200, null, 'present' ) );
check( 'plan MoR 403 present', 'present', RK::classify( $plan, 403, $mor, 'present' ) );
check( 'plan scope 403 absent', 'absent', RK::classify( $plan, 403, $deny, 'present' ) );

// Exact-message sensitive probes.
$auth = probe_for( 'transactions:write' );
check( 'check_authorization 404 with message present', 'present', RK::classify( $auth, 404, 'Sorry, this feature is no longer available.', 'present' ) );
check( 'check_authorization 404 without message', 'indeterminate', RK::classify( $auth, 404, null, 'present' ) );
check( 'check_authorization 404 other message', 'indeterminate', RK::classify( $auth, 404, 'Not found', 'present' ) );
check( 'check_authorization 400 indeterminate', 'indeterminate', RK::classify( $auth, 400, null, 'present' ) );
$sub = probe_for( 'subaccounts' );
check( 'subaccounts MoR 403 present', 'present', RK::classify( $sub, 403, $mor, 'present' ) );
check( 'subaccounts 200 present', 'present', RK::classify( $sub, 200, null, 'present' ) );
check( 'customers MoR 403 indeterminate', 'indeterminate', RK::classify( $gen, 403, $mor, 'present' ) );

// Verdicts.
$v = run_validate( $ok_map );
check( 'verdict ok', 'ok', $v['code'] );
check( 'ok has no scopes', array(), $v['scopes'] );
check( 'ok has no reason', null, $v['reason'] );
check( 'verdict keys', array( 'code', 'scopes', 'reason', 'sensitive_scopes', 'probes' ), array_keys( $v ) );
check( 'ok has no sensitive_scopes', array(), $v['sensitive_scopes'] );

$v = run_validate( $ok_map, null, null, $sent );
check( 'ok sends all probes', count( RK::PROBES ), count( $sent ) );
check( 'ok sends baseline first', 'transaction?perPage=1', $sent[0][2] );
check( 'probes carry the key', true, $sent[3][5] === $KEY );

$v = run_validate( array(), r( 401 ) );
check( 'baseline 401 invalid_key', 'invalid_key', $v['code'] );
run_validate( array(), r( 401 ), null, $sent );
check( 'baseline 401 stops probing', 1, count( $sent ) );

$v = run_validate( array( 'transactions:read' => r( 500 ) ), r( 200 ), null, $sent );
check( 'baseline 500 unverifiable', 'unverifiable', $v['code'] );
check( 'baseline 500 transient', 'transient', $v['reason'] );
check( 'baseline 500 stops probing', 1, count( $sent ) );
check( 'baseline 302 unverifiable', 'unverifiable', run_validate( array( 'transactions:read' => r( 302 ) ) )['code'] );
check( 'baseline 404 unverifiable', 'unverifiable', run_validate( array( 'transactions:read' => r( 404 ) ) )['code'] );
check( 'baseline 429 unverifiable', 'unverifiable', run_validate( array( 'transactions:read' => r( 429 ) ) )['code'] );
check( 'baseline status 0 unverifiable', 'unverifiable', run_validate( array( 'transactions:read' => r( 0 ) ) )['code'] );

$v = run_validate( array( 'transactions:read' => r( 403, 'Your IP is not whitelisted' ) ), r( 200 ), null, $sent );
check( 'baseline other 403 unverifiable', 'unverifiable', $v['code'] );
check( 'baseline other 403 blocked', 'blocked', $v['reason'] );

$v = run_validate( array_merge( $ok_map, array( 'transactions:read' => r( 403, $deny ) ) ), null, null, $sent );
check( 'baseline scope 403 missing_scope', 'missing_scope', $v['code'] );
check( 'baseline scope 403 names read', array( 'Transactions: Read' ), $v['scopes'] );
check( 'baseline scope 403 continues probing', count( RK::PROBES ), count( $sent ) );

$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $deny ) ) ) );
check( 'plans:write absent missing_scope', array( 'Plans: Write' ), $v['scopes'] );
check( 'missing_scope without sensitive has none', array(), $v['sensitive_scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $deny ), 'transfers' => r( 200 ), 'balance' => r( 200 ) ) ) );
check( 'missing_scope fills sensitive_scopes', array( 'Transfers', 'Balance' ), $v['sensitive_scopes'] );
check( 'missing_scope keeps scopes to the missing', array( 'Plans: Write' ), $v['scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:read' => r( 403, $deny ), 'plans:write' => r( 403, $deny ) ) ) );
check( 'both required missing', array( 'Transactions: Read', 'Plans: Write' ), $v['scopes'] );

$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $mor ) ) ) );
check( 'plans:write MoR ok', 'ok', $v['code'] );

$v = run_validate( array_merge( $ok_map, array( 'transfers' => r( 200 ), 'balance' => r( 200 ) ) ) );
check( 'sensitive_scope code', 'sensitive_scope', $v['code'] );
check( 'sensitive_scope labels', array( 'Transfers', 'Balance' ), $v['scopes'] );
check( 'sensitive_scope keeps sensitive_scopes empty', array(), $v['sensitive_scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:write' => r( 404, 'Sorry, this feature is no longer available.' ) ) ) );
check( 'transactions:write sensitive', array( 'Transactions: Write' ), $v['scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'subaccounts' => r( 403, $mor ) ) ) );
check( 'subaccounts MoR sensitive', array( 'Subaccounts' ), $v['scopes'] );

// Verdict order: missing_scope beats sensitive_scope beats unverifiable.
$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $deny ), 'transfers' => r( 200 ), 'balance' => r( 500 ) ) ) );
check( 'missing beats sensitive', 'missing_scope', $v['code'] );
$v = run_validate( array_merge( $ok_map, array( 'transfers' => r( 200 ), 'balance' => r( 500 ) ) ) );
check( 'sensitive beats unverifiable', 'sensitive_scope', $v['code'] );

// Unverifiable.
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 500 ) ) ) );
check( '5xx later unverifiable', 'unverifiable', $v['code'] );
check( '5xx later transient', 'transient', $v['reason'] );
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 0 ), 'customers' => r( 429 ), 'webhooks' => r( 302 ), 'payouts' => r( 404 ) ) ) );
check( 'mixed transient', 'transient', $v['reason'] );
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 0 ), 'customers' => r( 403, 'IP blocked' ) ) ) );
check( 'any 403 indeterminate is blocked', 'blocked', $v['reason'] );
$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 400, 'other validation' ) ) ) );
check( 'plan unexpected 400 unverifiable', 'unverifiable', $v['code'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:write' => r( 404, null ) ) ) );
check( 'check_authorization 404 no message unverifiable', 'unverifiable', $v['code'] );

// 401 after a baseline.
$v = run_validate( array( 'customers' => r( 401 ) ) + $ok_map );
check( '401 sensitive after 2xx baseline', 'sensitive_scope', $v['code'] );
$v = run_validate( array( 'plans:write' => r( 401 ) ) + $ok_map );
check( '401 required after 2xx baseline unverifiable', 'unverifiable', $v['code'] );

// Shape short-circuits before any request.
$v = run_validate( $ok_map, null, 'sk_test_' . str_repeat( 'a', 20 ), $sent );
check( 'bad shape code', 'invalid_shape', $v['code'] );
check( 'bad shape sends nothing', 0, count( $sent ) );
check( 'invalid_shape reason secret_key', 'secret_key', $v['reason'] );
check( 'invalid_shape reason wrong_mode', 'wrong_mode', RK::validate( 'rk_live_' . str_repeat( 'a', 20 ), 'test', function () { throw new Exception( 'sent' ); } )['reason'] );
check( 'invalid_shape reason malformed', 'malformed', RK::validate( 'rk_test_short', 'test', function () { throw new Exception( 'sent' ); } )['reason'] );
check( 'invalid_shape reason malformed for non-string', 'malformed', RK::validate( array(), 'test', function () { throw new Exception( 'sent' ); } )['reason'] );
check( 'invalid_shape live key wrong mode', 'wrong_mode', RK::validate( $KEY, 'live', function () { throw new Exception( 'sent' ); } )['reason'] );
check( 'invalid_shape sensitive_scopes empty', array(), $v['sensitive_scopes'] );
check( 'wrong mode sends nothing', 'invalid_shape', RK::validate( $KEY, 'live', function () { throw new Exception( 'sent' ); } )['code'] );

// decide() treats a probe with no result as indeterminate.
$v = RK::decide( array() );
check( 'decide empty unverifiable', 'unverifiable', $v['code'] );
check( 'decide empty transient', 'transient', $v['reason'] );
check( 'decide empty probes all not run', true, count( RK::PROBES ) === substr_count( $v['probes'], ':-' ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'present', 'status' => 400 ),
) );
check( 'decide partial results unverifiable', 'unverifiable', $v['code'] );
check( 'decide partial results transient', 'transient', $v['reason'] );
check( 'decide partial probes', 0, strpos( $v['probes'], 'transactions:read:200,plans:write:400,transactions:write:-,' ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'absent', 'status' => 403 ),
) );
check( 'decide partial still reports missing_scope', 'missing_scope', $v['code'] );

// Probes diagnostic.
$v = run_validate( $ok_map );
check( 'probes starts with baseline', 0, strpos( $v['probes'], 'transactions:read:200,plans:write:400,transactions:write:403,' ) );
check( 'probes lists every scope', count( RK::PROBES ), count( explode( ',', $v['probes'] ) ) );
$v = run_validate( array(), r( 401 ) );
check( 'probes marks unrun', true, 0 === strpos( $v['probes'], 'transactions:read:401,plans:write:-,' ) );
check( 'probes never has the key', false, false !== strpos( $v['probes'], $KEY ) );
check( 'invalid_key keeps all five keys', array( 'code', 'scopes', 'reason', 'sensitive_scopes', 'probes' ), array_keys( $v ) );

// Budget clamp: correct per-path replies, so only the deadline can make the result indeterminate.
$t     = 0.0;
$clock = function () use ( &$t ) {
	return $t;
};
$slow = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += 5.0;
	return ok_reply_for_path( $path );
};
$GLOBALS['pff_timeouts'] = array();
$v = RK::validate( $KEY, 'test', $slow, $clock );
check( 'budget: first timeout capped at 4', 4, $GLOBALS['pff_timeouts'][0] );
check( 'budget: second timeout capped at 4', 4, $GLOBALS['pff_timeouts'][1] );
check( 'budget: third timeout clamped to remaining', 2.0, $GLOBALS['pff_timeouts'][2] );
check( 'budget: probes after deadline not sent', 3, count( $GLOBALS['pff_timeouts'] ) );
check( 'budget spent unverifiable', 'unverifiable', $v['code'] );
check( 'budget spent transient', 'transient', $v['reason'] );
check( 'budget probes show unrun', true, false !== strpos( $v['probes'], 'transactions:write:403,disputes_refunds:-' ) );

$t = 0.0;
$GLOBALS['pff_timeouts'] = array();
$fast = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += ( count( $GLOBALS['pff_timeouts'] ) >= 3 ? 20.0 : 1.0 );
	return ok_reply_for_path( $path );
};
$v = RK::validate( $KEY, 'test', $fast, $clock );
check( 'budget spent mid-run is unverifiable', 'unverifiable', $v['code'] );
check( 'budget mid-run transient', 'transient', $v['reason'] );
check( 'budget stops further sends', 3, count( $GLOBALS['pff_timeouts'] ) );

// A throwing transport is a status 0 probe, not a fatal.
$boom = function () {
	throw new RuntimeException( 'boom' );
};
$v = RK::validate( $KEY, 'test', $boom );
check( 'throwing transport unverifiable', 'unverifiable', $v['code'] );
check( 'throwing transport transient', 'transient', $v['reason'] );
check( 'throwing transport probes status 0', true, 0 === strpos( $v['probes'], 'transactions:read:0,plans:write:-' ) );
$v = RK::validate( $KEY, 'test', function () {
	return null;
} );
check( 'null transport result unverifiable', 'unverifiable', $v['code'] );

// Key never leaks into a returned value.
$leak = serialize( array(
	run_validate( $ok_map ),
	run_validate( array(), r( 401 ) ),
	run_validate( array( 'balance' => r( 403, 'echo ' . $KEY ) ) + $ok_map ),
	run_validate( array( 'customers' => r( 200 ) ) + $ok_map ),
) );
check( 'key not in verdicts', false, strpos( $leak, $KEY ) );

// Default transport.
$GLOBALS['pff_wp_reply'] = array( 'code' => 403, 'body' => wp_json_encode( array( 'message' => $deny, 'extra' => $KEY ) ) );
$out = RK::send( 'POST', 'plan', '{}', 3.5, $KEY );
check( 'send returns status and message only', array( 'status' => 403, 'message' => $deny ), $out );
$call = $GLOBALS['pff_wp_calls'][0];
check( 'send url', API::BASE_URL . 'plan', $call[0] );
check( 'send redirection 0', 0, $call[1]['redirection'] );
check( 'send method', 'POST', $call[1]['method'] );
check( 'send timeout', 3.5, $call[1]['timeout'] );
check( 'send auth header', 'Bearer ' . $KEY, $call[1]['headers']['Authorization'] );
check( 'send content type', 'application/json', $call[1]['headers']['Content-Type'] );
check( 'send body', '{}', $call[1]['body'] );
$GLOBALS['pff_wp_reply'] = new WP_Error();
check( 'send WP_Error status 0 no text', array( 'status' => 0, 'message' => null ), RK::send( 'GET', 'balance', null, 4, $KEY ) );
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => 'not json' );
check( 'send non-json message null', array( 'status' => 200, 'message' => null ), RK::send( 'GET', 'balance', null, 4, $KEY ) );
$GLOBALS['pff_wp_reply'] = array( 'code' => 400, 'body' => wp_json_encode( array( 'message' => array( 'x' ) ) ) );
check( 'send non-string message null', null, RK::send( 'GET', 'balance', null, 4, $KEY )['message'] );
$GLOBALS['pff_wp_calls'] = array();
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => '{}' );
RK::validate( $KEY, 'test' );
check( 'default validate uses wp_remote_request', count( RK::PROBES ), count( $GLOBALS['pff_wp_calls'] ) );

// message_of.
check( 'message_of string', 'hi', RK::message_of( '{"message":"hi"}' ) );
check( 'message_of missing', null, RK::message_of( '{"a":1}' ) );
check( 'message_of non-string message', null, RK::message_of( '{"message":["x"]}' ) );
check( 'message_of not json', null, RK::message_of( 'nope' ) );
check( 'message_of json scalar', null, RK::message_of( '5' ) );
check( 'message_of non-string input', null, RK::message_of( null ) );

// Runtime denied helpers.
check( 'is_scope_denied exact', true, RK::is_scope_denied( 403, $deny ) );
check( 'is_scope_denied other message', false, RK::is_scope_denied( 403, 'nope' ) );
check( 'is_scope_denied other status', false, RK::is_scope_denied( 401, $deny ) );
check( 'is_scope_denied null message', false, RK::is_scope_denied( 403, null ) );

check( 'get_denied empty', array(), RK::get_denied() );
RK::record_denied( 'test', 'plan/' );
check( 'record_denied stores normalised module', array( 'plan' ), array_keys( RK::get_denied()['test'] ) );
check( 'record_denied stores time', true, is_int( RK::get_denied()['test']['plan'] ) );
check( 'record_denied non-autoloaded', false, $GLOBALS['pff_updates'][0][2] );
check( 'record_denied option name', 'pff_paystack_rk_denied', $GLOBALS['pff_updates'][0][0] );
RK::record_denied( 'test', 'plan/' );
check( 'record_denied unchanged does not write', 1, count( $GLOBALS['pff_updates'] ) );
RK::record_denied( 'test', 'subscription/' );
check( 'record_denied new module writes', 2, count( $GLOBALS['pff_updates'] ) );
check( 'record_denied set holds both', array( 'plan', 'subscription' ), array_keys( RK::get_denied()['test'] ) );
RK::record_denied( 'test', 'plan' );
RK::record_denied( 'test', 'subscription' );
RK::record_denied( 'test', 'plan' );
check( 'record_denied alternating modules do not rewrite', 2, count( $GLOBALS['pff_updates'] ) );
RK::record_denied( 'live', 'transaction/verify/' );
check( 'record_denied per mode', array( 'subscription', 'transaction/verify' ), array( array_keys( RK::get_denied()['test'] )[1], array_keys( RK::get_denied()['live'] )[0] ) );
RK::record_denied( 'test', 'https://evil.example/<script>' );
RK::record_denied( 'test', '' );
RK::record_denied( 'test', null );
RK::record_denied( 'staging', 'plan' );
check( 'record_denied ignores non-whitelisted', 3, count( $GLOBALS['pff_updates'] ) );
RK::clear_denied( 'test' );
check( 'clear_denied removes mode', array( 'live' ), array_keys( RK::get_denied() ) );
check( 'clear_denied writes non-autoloaded', false, end( $GLOBALS['pff_updates'] )[2] );
RK::clear_denied( 'test' );
check( 'clear_denied absent mode does not write', 4, count( $GLOBALS['pff_updates'] ) );
$GLOBALS['pff_options']['pff_paystack_rk_denied'] = array( 'test' => array( 'plan' => 5, 5 => 6, 'subscription' => 'x' ), 'live' => 'junk', 'x' => array( 'plan' => 1 ) );
check( 'malformed entries dropped', array( 'test' => array( 'plan' => 5 ) ), RK::get_denied() );
$GLOBALS['pff_options']['pff_paystack_rk_denied'] = 'corrupt';
check( 'get_denied non-array', array(), RK::get_denied() );
check( 'module_label known', 'Reading or creating plans', RK::module_label( 'plan/' ) );
check( 'module_label unknown', '', RK::module_label( 'nope' ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
