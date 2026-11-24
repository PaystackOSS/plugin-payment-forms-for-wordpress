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

define( 'EXPECTED', 267 );

$deny = RK::SCOPE_DENIED_MESSAGE;
$mor  = RK::MOR_MESSAGE;
$KEY  = 'rk_test_' . str_repeat( 'a1B2', 6 );
$PK   = 'pk_test_' . str_repeat( 'x9Y_', 6 );

function probe_for( $scope ) {
	foreach ( RK::PROBES as $p ) {
		if ( $p['scope'] === $scope ) {
			return $p;
		}
	}
	return null;
}

function r( $status, $message = null, $public_key = null ) {
	$out = array( 'status' => $status, 'message' => $message );
	if ( null !== $public_key ) {
		$out['public_key'] = $public_key;
	}
	return $out;
}

// Fake transport: $overrides maps scope => response; default is a plausible all-granted 200.
function run_validate( $overrides, $default = null, $key = null, &$sent = null, $clock = null, $deadline = null ) {
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
	return RK::validate( null === $key ? $KEY : $key, 'test', $req, $clock, $deadline );
}

// A key with nothing granted beyond the required scopes.
$ok_map = array(
	'account:read' => r( 200, null, $PK ),
	'plans:write'  => r( 400, RK::PLAN_VALIDATION_MESSAGE ),
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
check( 'probe count', 17, count( RK::PROBES ) );
check( 'sensitive count', 14, count( $sensitive ) );
// Cloudflare answers POST /verifications with an HTML 403 for every client, so it can't be probed.
check( 'no verifications probe', array(), array_filter( RK::PROBES, function ( $p ) { return 'verifications' === $p['path']; } ) );
check( 'required order', array( 'transactions:read', 'account:read', 'plans:write' ), array_slice( array_column( RK::PROBES, 'scope' ), 0, 3 ) );
check( 'required are exactly the first three', array( true, true, true, false ), array( RK::PROBES[0]['required'], RK::PROBES[1]['required'], RK::PROBES[2]['required'], RK::PROBES[3]['required'] ) );
check( 'account:read probe', 'GET ident success', RK::PROBES[1]['method'] . ' ' . RK::PROBES[1]['path'] . ' ' . RK::PROBES[1]['match'] );
check( 'account:read label', 'Account: Read', RK::label( 'account:read' ) );
check( 'required label', 'Transactions: Read, Account: Read, Plans: Write', RK::required_scopes_label() );
check( 'budget seconds', 20, RK::BUDGET_SECONDS );
check( 'label known', 'Disputes and refunds', RK::label( 'disputes_refunds' ) );
check( 'label unknown', '', RK::label( 'nope' ) );
$missing_labels = array_filter( array_map( function ( $p ) { return RK::label( $p['scope'] ); }, RK::PROBES ), function ( $l ) { return '' === $l; } );
check( 'every probe has a label', array(), $missing_labels );
check( 'probes carry no label', false, isset( RK::PROBES[0]['label'] ) );
check( 'baseline first', 'GET transaction?perPage=1', RK::PROBES[0]['method'] . ' ' . RK::PROBES[0]['path'] );
check( 'plan probe', 'POST plan {}', RK::PROBES[2]['method'] . ' ' . RK::PROBES[2]['path'] . ' ' . RK::PROBES[2]['body'] );

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
check( 'account:read 200 present', 'present', RK::classify( probe_for( 'account:read' ), 200, null, 'present' ) );
check( 'account:read 404 indeterminate', 'indeterminate', RK::classify( probe_for( 'account:read' ), 404, null, 'present' ) );
check( 'account:read scope 403 absent', 'absent', RK::classify( probe_for( 'account:read' ), 403, $deny, 'present' ) );
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
check( 'verdict keys', array( 'code', 'scopes', 'reason', 'sensitive_scopes', 'probes', 'public_key', 'unconfirmed' ), array_keys( $v ) );
check( 'ok has public key', $PK, $v['public_key'] );
check( 'ok is confirmed', false, $v['unconfirmed'] );
check( 'ok has no sensitive_scopes', array(), $v['sensitive_scopes'] );

$v = run_validate( $ok_map, null, null, $sent );
check( 'ok sends all probes', count( RK::PROBES ), count( $sent ) );
check( 'ok sends baseline first', 'transaction?perPage=1', $sent[0][2] );
check( 'probes carry the key', true, $sent[3][5] === $KEY );
check( 'required probes sent first', array( 'transactions:read', 'account:read', 'plans:write', 'transactions:write' ), array_slice( array_column( $sent, 0 ), 0, 4 ) );
check( 'probes are sent one at a time in PROBES order', array_column( RK::PROBES, 'scope' ), array_column( $sent, 0 ) );

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
check( 'baseline scope 403 still checks the other required probes only', 3, count( $sent ) );

$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $deny ) ) ) );
check( 'plans:write absent missing_scope', array( 'Plans: Write' ), $v['scopes'] );
check( 'missing_scope without sensitive has none', array(), $v['sensitive_scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $deny ), 'transfers' => r( 200 ), 'balance' => r( 200 ) ) ), null, null, $sent );
check( 'missing_scope sends no sensitive probes', 3, count( $sent ) );
check( 'missing_scope keeps scopes to the missing', array( 'Plans: Write' ), $v['scopes'] );
check( 'missing_scope has no public key', null, $v['public_key'] );
$v = run_validate( array_merge( $ok_map, array( 'account:read' => r( 403, $deny ) ) ) );
check( 'account:read absent missing_scope', array( 'Account: Read' ), $v['scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'account:read' => r( 500 ) ) ), null, null, $sent );
check( 'account:read 500 unverifiable', 'unverifiable', $v['code'] );
check( 'account:read 500 runs plans:write but no sensitive probes', 3, count( $sent ) );
check( 'account:read 500 has no public key', null, $v['public_key'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:read' => r( 403, $deny ), 'plans:write' => r( 403, $deny ) ) ) );
check( 'both required missing', array( 'Transactions: Read', 'Plans: Write' ), $v['scopes'] );

$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 403, $mor ) ) ) );
check( 'plans:write MoR ok', 'ok', $v['code'] );

$v = run_validate( array_merge( $ok_map, array( 'transfers' => r( 200 ), 'balance' => r( 200 ) ) ) );
check( 'sensitive_scope code', 'sensitive_scope', $v['code'] );
check( 'sensitive_scope has no public key', null, $v['public_key'] );
check( 'sensitive_scope labels', array( 'Transfers', 'Balance' ), $v['scopes'] );
check( 'sensitive_scope keeps sensitive_scopes empty', array(), $v['sensitive_scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:write' => r( 404, 'Sorry, this feature is no longer available.' ) ) ) );
check( 'transactions:write sensitive', array( 'Transactions: Write' ), $v['scopes'] );
$v = run_validate( array_merge( $ok_map, array( 'subaccounts' => r( 403, $mor ) ) ) );
check( 'subaccounts MoR sensitive', array( 'Subaccounts' ), $v['scopes'] );

// Verdict order: missing_scope beats sensitive_scope; an unanswered sensitive probe never rejects.
$v = run_validate( array_merge( $ok_map, array( 'transfers' => r( 200 ), 'balance' => r( 500 ) ) ) );
check( 'sensitive present wins over an indeterminate probe', 'sensitive_scope', $v['code'] );
check( 'sensitive present wins, unconfirmed stays false', false, $v['unconfirmed'] );

// Unconfirmed: a sensitive probe that can't be answered, with none present, is ok.
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 500 ) ) ) );
check( '5xx later ok', 'ok', $v['code'] );
check( '5xx later unconfirmed', true, $v['unconfirmed'] );
check( '5xx later keeps public key', $PK, $v['public_key'] );
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 0 ), 'customers' => r( 429 ), 'webhooks' => r( 302 ), 'connect' => r( 404 ) ) ) );
check( 'mixed indeterminate unconfirmed', true, $v['unconfirmed'] );
$v = run_validate( array_merge( $ok_map, array( 'balance' => r( 0 ), 'customers' => r( 403, 'IP blocked' ) ) ) );
check( 'sensitive 403 other is unconfirmed not blocked', array( 'ok', true, null ), array( $v['code'], $v['unconfirmed'], $v['reason'] ) );
$v = run_validate( array_merge( $ok_map, array( 'transactions:read' => r( 200 ), 'plans:write' => r( 403, 'IP blocked' ) ) ) );
check( 'required 403 other is blocked', array( 'unverifiable', 'blocked' ), array( $v['code'], $v['reason'] ) );
$v = run_validate( array_merge( $ok_map, array( 'plans:write' => r( 400, 'other validation' ) ) ) );
check( 'plan unexpected 400 unverifiable', 'unverifiable', $v['code'] );
$v = run_validate( array_merge( $ok_map, array( 'transactions:write' => r( 404, null ) ) ) );
check( 'check_authorization 404 no message is unverifiable', array( 'unverifiable', 'transient', false ), array( $v['code'], $v['reason'], $v['unconfirmed'] ) );

// High-risk probes must be definitively absent; any other gap is only unconfirmed.
check( 'high risk scopes', array( 'transactions:write', 'transfers', 'payouts', 'bulk_charges' ), RK::HIGH_RISK );
check( 'high risk probes follow the required ones', RK::HIGH_RISK, array_slice( array_column( RK::PROBES, 'scope' ), 3, 4 ) );
foreach ( RK::HIGH_RISK as $scope ) {
	foreach ( array( r( 500 ), r( 0 ), r( 429 ), r( 403, 'IP blocked' ) ) as $reply ) {
		$v = run_validate( array_merge( $ok_map, array( $scope => $reply ) ) );
		check( "$scope status {$reply['status']} is unverifiable", array( 'unverifiable', 'transient', null ), array( $v['code'], $v['reason'], $v['public_key'] ) );
	}
}
$v = run_validate( array_merge( $ok_map, array( 'payouts' => r( 500 ), 'customers' => r( 200 ) ) ) );
check( 'present sensitive wins over a high risk gap', 'sensitive_scope', $v['code'] );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'account:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'present', 'status' => 400 ),
	array( 'scope' => 'transactions:write', 'outcome' => 'absent', 'status' => 403 ),
	array( 'scope' => 'transfers', 'outcome' => 'absent', 'status' => 403 ),
	array( 'scope' => 'payouts', 'outcome' => 'absent', 'status' => 403 ),
	array( 'scope' => 'bulk_charges', 'outcome' => 'absent', 'status' => 403 ),
) );
check( 'decide high risk absent, rest unrun is ok unconfirmed', array( 'ok', true ), array( $v['code'], $v['unconfirmed'] ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'account:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'present', 'status' => 400 ),
	array( 'scope' => 'transactions:write', 'outcome' => 'absent', 'status' => 403 ),
	array( 'scope' => 'transfers', 'outcome' => 'absent', 'status' => 403 ),
	array( 'scope' => 'payouts', 'outcome' => 'absent', 'status' => 403 ),
) );
check( 'decide high risk unrun is unverifiable', array( 'unverifiable', 'transient' ), array( $v['code'], $v['reason'] ) );

// Public key shape.
$pk24 = 'pk_test_' . str_repeat( 'a', 24 );
check( 'valid public key', true, RK::is_valid_public_key( $pk24, 'test' ) );
check( 'public key with underscore', true, RK::is_valid_public_key( 'pk_live_' . str_repeat( 'a_', 12 ), 'live' ) );
check( 'public key wrong mode', false, RK::is_valid_public_key( $pk24, 'live' ) );
check( 'public key trailing newline', false, RK::is_valid_public_key( $pk24 . "\n", 'test' ) );
check( 'public key too short', false, RK::is_valid_public_key( 'pk_test_' . str_repeat( 'a', 15 ), 'test' ) );
check( 'public key non-string', false, RK::is_valid_public_key( array( $pk24 ), 'test' ) );
check( 'public key null', false, RK::is_valid_public_key( null, 'test' ) );

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
check( 'decide empty has no public key', null, $v['public_key'] );
check( 'decide empty probes all not run', true, count( RK::PROBES ) === substr_count( $v['probes'], ':-' ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'present', 'status' => 400 ),
) );
check( 'decide partial results unverifiable', 'unverifiable', $v['code'] );
check( 'decide partial results transient', 'transient', $v['reason'] );
check( 'decide partial probes', 0, strpos( $v['probes'], 'transactions:read:200,account:read:-,plans:write:400,transactions:write:-,' ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'account:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'present', 'status' => 400 ),
) );
check( 'decide required only is unverifiable', array( 'unverifiable', 'transient', false ), array( $v['code'], $v['reason'], $v['unconfirmed'] ) );
$v = RK::decide( array(
	array( 'scope' => 'transactions:read', 'outcome' => 'present', 'status' => 200 ),
	array( 'scope' => 'plans:write', 'outcome' => 'absent', 'status' => 403 ),
) );
check( 'decide partial still reports missing_scope', 'missing_scope', $v['code'] );

// Probes diagnostic.
$v = run_validate( $ok_map );
check( 'probes starts with baseline', 0, strpos( $v['probes'], 'transactions:read:200,account:read:200,plans:write:400,transactions:write:403,' ) );
check( 'probes lists every scope', count( RK::PROBES ), count( explode( ',', $v['probes'] ) ) );
$v = run_validate( array(), r( 401 ) );
check( 'probes marks unrun', true, 0 === strpos( $v['probes'], 'transactions:read:401,account:read:-,plans:write:-,' ) );
check( 'probes never has the key', false, false !== strpos( $v['probes'], $KEY ) );
check( 'invalid_key keeps all seven keys', array( 'code', 'scopes', 'reason', 'sensitive_scopes', 'probes', 'public_key', 'unconfirmed' ), array_keys( $v ) );
$v = run_validate( $ok_map );
check( 'public key not in probes', false, false !== strpos( $v['probes'], 'pk_' ) );

// Public key: only from account:read, only when well-formed for the mode.
$noise = array();
foreach ( RK::PROBES as $p ) {
	if ( 'account:read' !== $p['scope'] ) {
		$noise[ $p['scope'] ] = $ok_map[ $p['scope'] ] + array( 'public_key' => 'pk_test_' . str_repeat( 'Z', 24 ) );
	}
}
$v = run_validate( $noise + $ok_map );
check( 'public key only from account:read', $PK, $v['public_key'] );
$v = run_validate( array( 'account:read' => r( 200 ) ) + $noise + $ok_map );
check( 'other probes public key ignored without account:read pk', array( 'no_public_key', null ), array( $v['code'], $v['public_key'] ) );
$bad_pks = array(
	'wrong mode'  => 'pk_live_' . str_repeat( 'a', 24 ),
	'sk instead'  => 'sk_test_' . str_repeat( 'a', 24 ),
	'too short'   => 'pk_test_' . str_repeat( 'a', 15 ),
	'too long'    => 'pk_test_' . str_repeat( 'a', 129 ),
	'symbol'      => 'pk_test_' . str_repeat( 'a', 16 ) . '-',
	'newline'     => 'pk_test_' . str_repeat( 'a', 24 ) . "\n",
	'empty'       => '',
);
foreach ( $bad_pks as $label => $bad ) {
	$v = run_validate( array( 'account:read' => array( 'status' => 200, 'message' => null, 'public_key' => $bad ) ) + $ok_map );
	check( "pk $label is no_public_key", array( 'no_public_key', null, array() ), array( $v['code'], $v['public_key'], $v['scopes'] ) );
}
$v = run_validate( array( 'account:read' => array( 'status' => 200, 'message' => null, 'public_key' => array( 'x' ) ) ) + $ok_map );
check( 'non-string pk is no_public_key', 'no_public_key', $v['code'] );
check( 'no_public_key keeps the probes trace', 0, strpos( $v['probes'], 'transactions:read:200,account:read:200,' ) );
check( 'pk 128 chars ok', 'ok', run_validate( array( 'account:read' => r( 200, null, 'pk_test_' . str_repeat( 'a', 128 ) ) ) + $ok_map )['code'] );
check( 'pk 16 chars ok', 'ok', run_validate( array( 'account:read' => r( 200, null, 'pk_test_' . str_repeat( 'a', 16 ) ) ) + $ok_map )['code'] );
$v = RK::validate( 'rk_live_' . str_repeat( 'a1B2', 6 ), 'live', function ( $m, $path ) use ( $ok_map ) {
	return 'ident' === $path ? r( 200, null, 'pk_live_' . str_repeat( 'q_1', 8 ) ) : ok_reply_for_path( $path );
} );
check( 'live pk accepted for live mode', array( 'ok', 'pk_live_' . str_repeat( 'q_1', 8 ) ), array( $v['code'], $v['public_key'] ) );
$v = run_validate( array( 'account:read' => r( 200 ), 'balance' => r( 200 ) ) + $ok_map );
check( 'sensitive_scope wins over a missing pk', 'sensitive_scope', $v['code'] );

// Budget clamp: correct per-path replies, so only the deadline can make the result indeterminate.
$t     = 0.0;
$clock = function () use ( &$t ) {
	return $t;
};
$slow = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += 9.0;
	return ok_reply_for_path( $path );
};
$GLOBALS['pff_timeouts'] = array();
$v = RK::validate( $KEY, 'test', $slow, $clock );
check( 'budget: first timeout capped at 4', 4, $GLOBALS['pff_timeouts'][0] );
check( 'budget: second timeout capped at 4', 4, $GLOBALS['pff_timeouts'][1] );
check( 'budget: third timeout clamped to remaining', 2.0, $GLOBALS['pff_timeouts'][2] );
check( 'budget: sensitive probes after deadline not sent', 3, count( $GLOBALS['pff_timeouts'] ) );
check( 'budget spent after required is unverifiable', 'unverifiable', $v['code'] );
check( 'budget spent after required is not unconfirmed', false, $v['unconfirmed'] );
check( 'budget spent after required has no public key', null, $v['public_key'] );
check( 'budget probes show unrun', true, false !== strpos( $v['probes'], 'plans:write:400,transactions:write:-,transfers:-,payouts:-,bulk_charges:-,disputes_refunds:-' ) );

$t = 0.0;
$GLOBALS['pff_timeouts'] = array();
$fast = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += ( count( $GLOBALS['pff_timeouts'] ) >= 8 ? 30.0 : 1.0 );
	return ok_reply_for_path( $path );
};
$v = RK::validate( $KEY, 'test', $fast, $clock );
check( 'budget spent after the high risk probes is ok unconfirmed', array( 'ok', true ), array( $v['code'], $v['unconfirmed'] ) );
check( 'budget stops further sends', 8, count( $GLOBALS['pff_timeouts'] ) );
check( 'budget mid-sensitive probes show done and unrun', true, false !== strpos( $v['probes'], 'bulk_charges:403,disputes_refunds:403,direct_debit:-' ) );
$t = 0.0;
$GLOBALS['pff_timeouts'] = array();
$mid = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += ( count( $GLOBALS['pff_timeouts'] ) >= 5 ? 30.0 : 1.0 );
	return ok_reply_for_path( $path );
};
$v = RK::validate( $KEY, 'test', $mid, $clock );
check( 'budget spent mid high risk is unverifiable', array( 'unverifiable', 'transient', 5 ), array( $v['code'], $v['reason'], count( $GLOBALS['pff_timeouts'] ) ) );

// A sensitive probe answered before the deadline still rejects while later ones are unconfirmed.
$t = 0.0;
$GLOBALS['pff_timeouts'] = array();
$cut = function ( $method, $path, $body, $timeout, $k ) use ( &$t ) {
	$GLOBALS['pff_timeouts'][] = $timeout;
	$t += ( count( $GLOBALS['pff_timeouts'] ) >= 8 ? 30.0 : 1.0 );
	return 'refund?perPage=1&from=2099-01-01' === $path ? r( 200 ) : ok_reply_for_path( $path );
};
$v = RK::validate( $KEY, 'test', $cut, $clock );
check( 'present sensitive wins though later probes are cut off', array( 'sensitive_scope', array( 'Disputes and refunds' ), false ), array( $v['code'], $v['scopes'], $v['unconfirmed'] ) );

// An absolute deadline is honoured; a past one sends nothing.
$t = 100.0;
$GLOBALS['pff_timeouts'] = array();
$v = RK::validate( $KEY, 'test', $slow, $clock, 105.0 );
check( 'explicit deadline clamps the first timeout', 4, $GLOBALS['pff_timeouts'][0] );
check( 'explicit deadline stops after 1 probe', 1, count( $GLOBALS['pff_timeouts'] ) );
check( 'explicit deadline leaves required unverifiable', 'unverifiable', $v['code'] );
$t = 100.0;
$GLOBALS['pff_timeouts'] = array();
$v = RK::validate( $KEY, 'test', $slow, $clock, 100.0 );
check( 'past deadline sends nothing', 0, count( $GLOBALS['pff_timeouts'] ) );
check( 'past deadline unverifiable', 'unverifiable', $v['code'] );
$t = 100.0;
$GLOBALS['pff_timeouts'] = array();
RK::validate( $KEY, 'test', $slow, $clock );
check( 'default deadline is now plus budget', 3, count( $GLOBALS['pff_timeouts'] ) );

// A throwing transport is a status 0 probe, not a fatal.
$boom = function () {
	throw new RuntimeException( 'boom' );
};
$v = RK::validate( $KEY, 'test', $boom );
check( 'throwing transport unverifiable', 'unverifiable', $v['code'] );
check( 'throwing transport transient', 'transient', $v['reason'] );
check( 'throwing transport probes status 0', true, 0 === strpos( $v['probes'], 'transactions:read:0,account:read:-' ) );
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
	run_validate( array( 'account:read' => r( 200, null, 'pk_test_' . str_repeat( 'a', 24 ) ) ) + $ok_map ),
) );
check( 'key not in verdicts', false, strpos( $leak, $KEY ) );

// Default transport.
$GLOBALS['pff_wp_reply'] = array( 'code' => 403, 'body' => wp_json_encode( array( 'message' => $deny, 'extra' => $KEY ) ) );
$out = RK::send( 'POST', 'plan', '{}', 3.5, $KEY );
check( 'send returns status, message and public key only', array( 'status' => 403, 'message' => $deny, 'public_key' => null ), $out );
$call = $GLOBALS['pff_wp_calls'][0];
check( 'send url', API::BASE_URL . 'plan', $call[0] );
check( 'send redirection 0', 0, $call[1]['redirection'] );
check( 'send method', 'POST', $call[1]['method'] );
check( 'send timeout', 3.5, $call[1]['timeout'] );
check( 'send auth header', 'Bearer ' . $KEY, $call[1]['headers']['Authorization'] );
check( 'send content type', 'application/json', $call[1]['headers']['Content-Type'] );
check( 'send body', '{}', $call[1]['body'] );
$GLOBALS['pff_wp_reply'] = new WP_Error();
check( 'send WP_Error status 0 no text', array( 'status' => 0, 'message' => null, 'public_key' => null ), RK::send( 'GET', 'balance', null, 4, $KEY ) );
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => 'not json' );
check( 'send non-json message null', array( 'status' => 200, 'message' => null, 'public_key' => null ), RK::send( 'GET', 'balance', null, 4, $KEY ) );
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => wp_json_encode( array( 'message' => 'Ident', 'data' => array( 'public_key' => $PK, 'secret' => 'sk_test_hidden' ) ) ) );
check( 'send keeps data.public_key only', array( 'status' => 200, 'message' => 'Ident', 'public_key' => $PK ), RK::send( 'GET', 'ident', null, 4, $KEY ) );
// Shape of a real GET /ident 200 for a restricted key with Account: Read (recorded 2026-11-24).
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => wp_json_encode( array( 'status' => true, 'message' => 'Integration details', 'data' => array( 'id' => 123456, 'public_key' => $PK ) ) ) );
check( 'send reads public key from real /ident shape', array( 'status' => 200, 'message' => 'Integration details', 'public_key' => $PK ), RK::send( 'GET', 'ident', null, 4, $KEY ) );
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => wp_json_encode( array( 'data' => array( 'public_key' => array( 'x' ) ) ) ) );
check( 'send non-string public key null', null, RK::send( 'GET', 'ident', null, 4, $KEY )['public_key'] );
$GLOBALS['pff_wp_reply'] = array( 'code' => 400, 'body' => wp_json_encode( array( 'message' => array( 'x' ) ) ) );
check( 'send non-string message null', null, RK::send( 'GET', 'balance', null, 4, $KEY )['message'] );
$GLOBALS['pff_wp_calls'] = array();
$GLOBALS['pff_wp_reply'] = array( 'code' => 200, 'body' => '{}' );
RK::validate( $KEY, 'test' );
check( 'default validate uses wp_remote_request', 3, count( $GLOBALS['pff_wp_calls'] ) );

// public_key_of.
check( 'public_key_of string', 'pk_x', RK::public_key_of( '{"data":{"public_key":"pk_x"}}' ) );
check( 'public_key_of top-level ignored', null, RK::public_key_of( '{"public_key":"pk_x"}' ) );
check( 'public_key_of data is a string', null, RK::public_key_of( '{"data":"x"}' ) );
check( 'public_key_of not json', null, RK::public_key_of( 'nope' ) );
check( 'public_key_of non-string input', null, RK::public_key_of( null ) );

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
