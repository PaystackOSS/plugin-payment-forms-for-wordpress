<?php
// Run: php tests/test-settings-restricted-key.php

// Recording versions must exist before bootstrap, which only defines no-op fallbacks.
$GLOBALS['pff_filters']     = array();
$GLOBALS['pff_filter_args'] = array();
if ( ! function_exists( 'add_filter' ) ) {
	function add_filter( $tag, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['pff_filters'][ $tag ][]     = $callback;
		$GLOBALS['pff_filter_args'][ $tag ][] = $args;
	}
}
if ( ! function_exists( 'add_action' ) ) {
	function add_action( $tag, $callback, $priority = 10, $args = 1 ) {
		$GLOBALS['pff_filters'][ $tag ][] = $callback;
	}
}

require __DIR__ . '/bootstrap.php';

define( 'WP_DEBUG', true );
$GLOBALS['pff_log'] = tempnam( sys_get_temp_dir(), 'pffrk' );
ini_set( 'error_log', $GLOBALS['pff_log'] );

$GLOBALS['pff_options'] = array();
$GLOBALS['pff_paths']   = array();
$GLOBALS['pff_errors']  = array();
$GLOBALS['pff_can']     = true;
$GLOBALS['pff_nonce']   = 'good';

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['pff_options'] ) ? $GLOBALS['pff_options'][ $name ] : $default;
	}
}
if ( ! function_exists( 'delete_option' ) ) {
	function delete_option( $name ) {
		unset( $GLOBALS['pff_options'][ $name ] );
		return true;
	}
}
// Mirrors core: filter first (honouring accepted_args), then skip when unchanged, else add or update.
if ( ! function_exists( 'update_option' ) ) {
	function update_option( $name, $value, $autoload = null ) {
		$old = get_option( $name, false );
		$tag = "pre_update_option_{$name}";
		if ( isset( $GLOBALS['pff_filters'][ $tag ] ) ) {
			foreach ( $GLOBALS['pff_filters'][ $tag ] as $i => $cb ) {
				$args  = array_slice( array( $value, $old, $name ), 0, $GLOBALS['pff_filter_args'][ $tag ][ $i ] );
				$value = call_user_func_array( $cb, $args );
			}
		}
		if ( $value === $old ) {
			return false;
		}
		$GLOBALS['pff_paths'][ $name ]   = false === $old ? 'add' : 'update';
		$GLOBALS['pff_options'][ $name ] = $value;
		return true;
	}
}
if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		$GLOBALS['pff_errors'][] = array( $setting, $code, $message, $type );
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return $GLOBALS['pff_can'];
	}
}
if ( ! function_exists( 'wp_verify_nonce' ) ) {
	function wp_verify_nonce( $nonce, $action ) {
		return 'good' === $nonce && 'kkd-pff-paystack-settings-group-options' === $action;
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $v ) {
		return $v;
	}
}
if ( ! function_exists( 'sanitize_key' ) ) {
	function sanitize_key( $v ) {
		return strtolower( preg_replace( '/[^A-Za-z0-9_\-]/', '', $v ) );
	}
}
if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $v ) {
		return trim( preg_replace( '/%[a-f0-9]{2}/i', '', strip_tags( $v ) ) );
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-settings.php';
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key.php';
require dirname( __DIR__ ) . '/includes/classes/class-helpers.php';

use paystack\payment_forms\Helpers;
use paystack\payment_forms\Restricted_Key;
use paystack\payment_forms\Settings;

define( 'EXPECTED', 152 );

$GLOBALS['pff_calls']   = array();
$GLOBALS['pff_verdict'] = array( 'code' => 'ok', 'scopes' => array(), 'reason' => null );

function stub_validator( $key, $mode ) {
	$GLOBALS['pff_calls'][] = array( $key, $mode );
	return $GLOBALS['pff_verdict'];
}

function verdict( $code, $scopes = array(), $reason = null, $sensitive = array(), $probes = '' ) {
	$GLOBALS['pff_verdict'] = array(
		'code'             => $code,
		'scopes'           => $scopes,
		'reason'           => $reason,
		'sensitive_scopes' => $sensitive,
		'probes'           => $probes,
	);
}

// Simulates one options.php save: guards run at admin_init, then every registered option is updated.
function save( $post, $stored = array(), $opts = array() ) {
	$GLOBALS['pff_options'] = $stored;
	$GLOBALS['pff_errors']  = array();
	$GLOBALS['pff_calls']   = array();
	$GLOBALS['pff_filters']     = array();
	$GLOBALS['pff_filter_args'] = array();
	$GLOBALS['pff_paths']       = array();
	$GLOBALS['pagenow']         = array_key_exists( 'pagenow', $opts ) ? $opts['pagenow'] : 'options.php';
	$_GET                       = array_key_exists( 'query', $opts ) ? $opts['query'] : array();
	// A null value drops a default so a test can leave it out of the POST body.
	$_POST                      = array_filter(
		array_merge(
			array(
				'option_page' => 'kkd-pff-paystack-settings-group',
				'_wpnonce'    => 'good',
			),
			$post
		),
		function ( $v ) {
			return null !== $v;
		}
	);
	$_REQUEST                   = array_merge( $_GET, $_POST );

	$s = new Settings();
	if ( empty( $opts['real_validator'] ) ) {
		$s->set_validator( 'stub_validator' );
	}
	$s->register_secret_key_guards();
	foreach ( $s->get_settings_fields() as $fields ) {
		foreach ( array_keys( $fields ) as $name ) {
			$v = isset( $_POST[ $name ] ) ? $_POST[ $name ] : null;
			update_option( $name, $s->sanitise_field( $v ) );
		}
	}
	return $GLOBALS['pff_options'];
}

function error_codes() {
	return array_map(
		function ( $e ) {
			return $e[1];
		},
		$GLOBALS['pff_errors']
	);
}

function error_text() {
	return $GLOBALS['pff_errors'] ? $GLOBALS['pff_errors'][0][2] : '';
}

$RK  = 'rk_test_abcdefghijklmnop1234';
$RK2 = 'rk_test_zzzzzzzzzzzzzzzzzzzz';
$LRK = 'rk_live' . '_abcdefghijklmnop1234';

// Each rejecting verdict keeps the old rk and names its reason.
$old = array( 'trk' => 'rk_test_oldoldoldoldoldold' );
foreach ( array(
	array( 'invalid_shape', array(), null, 'start with rk_test_' ),
	array( 'invalid_key', array(), null, "isn't enabled" ),
	array( 'missing_scope', array( 'Plans: Write' ), null, 'missing: Plans: Write' ),
	array( 'sensitive_scope', array( 'Transfers', 'Balance' ), null, 'Transfers, Balance' ),
	array( 'unverifiable', array(), 'blocked', 'IP whitelist' ),
	array( 'unverifiable', array(), 'transient', 'try again' ),
) as $case ) {
	verdict( $case[0], $case[1], $case[2] );
	$out = save( array( 'trk' => $RK ), $old );
	check( "{$case[0]}/{$case[2]} keeps old rk", $old['trk'], $out['trk'] );
	check( "{$case[0]}/{$case[2]} error code", array( 'pff-paystack-rk-trk' ), error_codes() );
	check( "{$case[0]}/{$case[2]} message", true, false !== stripos( error_text(), $case[3] ) );
	check( "{$case[0]}/{$case[2]} kept phrasing", true, false !== strpos( error_text(), 'The previous key was kept' ) );
}
check( 'message never has the key', false, strpos( error_text(), $RK ) );

// Each invalid_shape reason has its own copy, and a bad shape never reaches the network.
$old = array( 'trk' => 'rk_test_oldoldoldoldoldold' );
verdict( 'invalid_shape', array(), 'secret_key' );
$out = save( array( 'trk' => 'sk_test_abcdefghijklmnop1234' ), $old );
check( 'secret_key reason keeps old', $old['trk'], $out['trk'] );
check( 'secret_key reason message', true, false !== strpos( error_text(), 'This is a secret key' ) );
verdict( 'invalid_shape', array(), 'wrong_mode' );
save( array( 'trk' => $LRK ), $old );
check( 'wrong_mode reason message (test)', true, false !== strpos( error_text(), 'This is a live restricted key; paste it in the Live Restricted Key field.' ) );
save( array( 'lrk' => $RK ), array() );
check( 'wrong_mode reason message (live)', true, false !== strpos( error_text(), 'This is a test restricted key; paste it in the Test Restricted Key field.' ) );
$out = save( array( 'trk' => 'sk_test_abcdefghijklmnop1234' ), $old, array( 'real_validator' => true ) );
check( 'real validator sk in rk field keeps old', $old['trk'], $out['trk'] );
check( 'real validator sk in rk field message', true, false !== strpos( error_text(), 'This is a secret key' ) );
save( array( 'trk' => $LRK ), $old, array( 'real_validator' => true ) );
check( 'real validator wrong mode message', true, false !== strpos( error_text(), 'live restricted key' ) );
save( array( 'trk' => 'rk_test_short' ), $old, array( 'real_validator' => true ) );
check( 'real validator malformed message', true, false !== strpos( error_text(), 'start with rk_test_' ) );

// Missing scope also names sensitive scopes to turn off.
verdict( 'missing_scope', array( 'Plans: Write' ), null, array( 'Transfers', 'Balance' ) );
save( array( 'trk' => $RK ), $old );
check( 'missing_scope appends sensitive', true, false !== strpos( error_text(), 'Also turn off: Transfers, Balance.' ) );
verdict( 'missing_scope', array( 'Plans: Write' ) );
save( array( 'trk' => $RK ), $old );
check( 'missing_scope without sensitive has no extra', false, strpos( error_text(), 'Also turn off' ) );
verdict( 'ok' );

// Accepted rk deletes the sk: add path (trk absent) and update path (trk present).
$out = save( array( 'trk' => $RK ), array( 'tsk' => 'sk_test_stored', 'lsk' => 'sk_live_stored' ) );
check( 'accept add path stores rk', $RK, $out['trk'] );
check( 'accept add path used add', 'add', $GLOBALS['pff_paths']['trk'] );
check( 'accept add path deletes sk', false, isset( $out['tsk'] ) );
check( 'accept leaves other mode sk', 'sk_live_stored', $out['lsk'] );
check( 'accept no errors', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'trk' => $RK2 ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'accept update path stores rk', $RK2, $out['trk'] );
check( 'accept update path used update', 'update', $GLOBALS['pff_paths']['trk'] );
check( 'accept update path deletes sk', false, isset( $out['tsk'] ) );
$out = save( array( 'trk' => $RK ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'unchanged resubmit still deletes sk', false, isset( $out['tsk'] ) );
$out = save( array( 'lrk' => $LRK ), array( 'lsk' => 'sk_live_stored', 'tsk' => 'sk_test_stored' ) );
check( 'live mode accepted', $LRK, $out['lrk'] );
check( 'live mode deletes lsk only', array( false, 'sk_test_stored' ), array( isset( $out['lsk'] ), $out['tsk'] ) );
check( 'validator got live mode', array( $LRK, 'live' ), $GLOBALS['pff_calls'][0] );

// sk ignored while an rk is stored or submitted.
verdict( 'ok' );
$out = save( array( 'tsk' => 'sk_test_new' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'stored rk ignores sk', 'sk_test_stored', $out['tsk'] );
check( 'stored rk keeps rk', $RK, $out['trk'] );
check( 'stored rk sk notice', array( 'pff-paystack-sk-ignored-tsk' ), error_codes() );
check( 'stored rk notice copy', true, false !== strpos( error_text(), 'Remove the restricted key first' ) );
check( 'stored rk does not probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'tsk' => '' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'blank sk with stored rk is silent', 0, count( $GLOBALS['pff_errors'] ) );

// Both submitted: the sk is ignored whatever the rk verdict, and nothing is deleted on a non-ok rk.
foreach ( array( 'ok', 'unverifiable', 'missing_scope' ) as $code ) {
	verdict( $code, array( 'Plans: Write' ), 'transient' );
	$out = save( array( 'trk' => $RK, 'tsk' => 'sk_test_new' ), array( 'tsk' => 'sk_test_stored' ) );
	if ( 'ok' === $code ) {
		check( "both/$code stores rk", $RK, $out['trk'] );
		check( "both/$code sk gone", false, isset( $out['tsk'] ) );
		check( "both/$code only the saved-for-mode notice", array( 'pff-paystack-sk-ignored-tsk' ), error_codes() );
		check( "both/$code saved wording", true, false !== strpos( error_text(), 'because a restricted key was saved for this mode' ) );
	} else {
		check( "both/$code rk not stored", false, isset( $out['trk'] ) );
		check( "both/$code sk untouched", 'sk_test_stored', $out['tsk'] );
		check( "both/$code notices", array( 'pff-paystack-rk-trk', 'pff-paystack-sk-ignored-tsk' ), error_codes() );
		check( "both/$code also-submitted wording", true, false !== strpos( $GLOBALS['pff_errors'][1][2], 'because a restricted key was also submitted' ) );
		check( "both/$code no remove-first wording", false, strpos( $GLOBALS['pff_errors'][1][2], 'Remove the restricted key first' ) );
	}
}

// Remove is honoured only while the mode keeps a key; a submitted rk wins over it.
verdict( 'ok' );
$out = save( array( 'trk_remove' => '1' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored', 'lrk' => $LRK ) );
check( 'remove with stored sk empties rk', '', $out['trk'] );
check( 'remove keeps the stored sk', 'sk_test_stored', $out['tsk'] );
check( 'remove leaves other mode', $LRK, $out['lrk'] );
check( 'remove does not probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'remove with stored sk is silent', 0, count( $GLOBALS['pff_errors'] ) );
check( 'resolution after remove falls to sk', 'sk_test_stored', ( function () use ( $out ) {
	$GLOBALS['pff_options'] = $out;
	return Helpers::get_secret_key( 'test' );
} )() );
$out = save( array( 'trk_remove' => '1' ), array( 'trk' => $RK ) );
check( 'remove without any sk keeps rk', $RK, $out['trk'] );
check( 'remove without any sk notice code', array( 'pff-paystack-rk-remove-trk' ), error_codes() );
check( 'remove without any sk notice copy', true, false !== strpos( error_text(), "To remove the restricted key, paste a secret key for this mode under 'Use secret keys instead'. The restricted key was kept." ) );
$out = save( array( 'trk_remove' => '1', 'tsk' => 'sk_test_new' ), array( 'trk' => $RK ) );
check( 'remove + valid sk removes rk', '', $out['trk'] );
check( 'remove + valid sk saves sk', 'sk_test_new', $out['tsk'] );
check( 'remove + valid sk is silent', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'trk_remove' => '1', 'tsk' => 'sk_live_wrong' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'remove + wrong-prefix sk keeps rk', $RK, $out['trk'] );
check( 'remove + wrong-prefix sk keeps stored sk', 'sk_test_stored', $out['tsk'] );
check( 'remove + wrong-prefix sk rejects sk', array( 'pff-paystack-invalid-tsk' ), error_codes() );
$out = save( array( 'trk_remove' => '1', 'tsk' => 'sk_live_wrong' ), array( 'trk' => $RK ) );
check( 'remove + wrong-prefix sk without stored sk keeps rk', $RK, $out['trk'] );
check( 'remove + wrong-prefix sk stores no sk', false, isset( $out['tsk'] ) );
$out = save( array( 'trk_remove' => '1' ), array() );
check( 'remove with nothing stored adds nothing', false, isset( $out['trk'] ) );
check( 'remove with nothing stored is silent', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'trk_remove' => '0' ), array( 'trk' => $RK ) );
check( 'remove flag must be 1', $RK, $out['trk'] );
$out = save( array( 'trk' => $RK2, 'trk_remove' => '1' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) );
check( 'new rk beats remove flag', $RK2, $out['trk'] );

// Blank rk without a stored rk leaves sk behaviour as today.
$out = save( array( 'tsk' => 'sk_test_new' ), array() );
check( 'sk saves without rk', 'sk_test_new', $out['tsk'] );
$out = save( array( 'tsk' => 'sk_live_wrong' ), array( 'tsk' => 'sk_test_stored' ) );
check( 'sk prefix check still applies', 'sk_test_stored', $out['tsk'] );
check( 'sk prefix error code', array( 'pff-paystack-invalid-tsk' ), error_codes() );
$out = save( array( 'tsk' => '' ), array( 'tsk' => 'sk_test_stored' ) );
check( 'blank sk keeps stored', 'sk_test_stored', $out['tsk'] );
check( 'no probe for sk only', 0, count( $GLOBALS['pff_calls'] ) );

// Array input is treated as blank.
$out = save( array( 'trk' => array( $RK ) ), array( 'trk' => 'rk_test_oldoldoldoldoldold' ) );
check( 'array rk keeps old', 'rk_test_oldoldoldoldoldold', $out['trk'] );
check( 'array rk does not probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'array rk no errors', 0, count( $GLOBALS['pff_errors'] ) );

// No probing without capability, a valid nonce or options.php; the rk is not stored either.
$GLOBALS['pff_can'] = false;
$out = save( array( 'trk' => $RK ), array() );
check( 'no capability no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'no capability rk not stored', false, isset( $out['trk'] ) );
$GLOBALS['pff_can'] = true;
$out = save( array( 'trk' => $RK, '_wpnonce' => 'bad' ), array() );
check( 'bad nonce no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'bad nonce rk not stored', false, isset( $out['trk'] ) );
$out = save( array( 'trk' => $RK, '_wpnonce' => array( 'good' ) ), array() );
check( 'array nonce no probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'trk' => $RK ), array(), array( 'pagenow' => 'admin-ajax.php' ) );
check( 'wrong pagenow no probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'trk' => $RK, 'option_page' => 'other' ), array() );
check( 'other option page no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'other option page registers nothing', array(), array_keys( array_filter( $GLOBALS['pff_filters'], function ( $k ) {
	return 0 === strpos( $k, 'pre_update_option_' );
}, ARRAY_FILTER_USE_KEY ) ) );

// Hook wiring: exactly one owner per rk option, generic guard on the rest.
save( array(), array() );
$f = $GLOBALS['pff_filters'];
check( 'trk has one filter', 1, count( $f['pre_update_option_trk'] ) );
check( 'lrk has one filter', 1, count( $f['pre_update_option_lrk'] ) );
check( 'trk owner is the rk filter', 'filter_restricted_key', $f['pre_update_option_trk'][0][1] );
check( 'tsk owner is the sk filter', array( 'filter_secret_key' ), array_map( function ( $c ) {
	return $c[1];
}, $f['pre_update_option_tsk'] ) );
check( 'rk is a password field with prefix', array( 'password', 'rk_test_', 'rk_live_' ), ( function () {
	$g = ( new Settings() )->get_settings_fields()['general'];
	return array( $g['trk']['type'], $g['trk']['prefix'], $g['lrk']['prefix'] );
} )() );
check( 'rk fields precede sk fields', array( 'mode', 'trk', 'lrk', 'tsk' ), array_slice( array_keys( ( new Settings() )->get_settings_fields()['general'] ), 0, 4 ) );

// A third-party password field keeps the generic behaviour.
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array(
			'trk'    => array( 'title' => 'T', 'type' => 'password', 'prefix' => 'rk_test_', 'default' => '' ),
			'lrk'    => array( 'title' => 'L', 'type' => 'password', 'prefix' => 'rk_live_', 'default' => '' ),
			'custom' => array( 'title' => 'Custom', 'type' => 'password', 'default' => '' ),
		),
	),
);
$out = save( array( 'custom' => 'anything', 'trk' => $RK ), array( 'custom' => 'old', 'tsk' => 'sk_test_stored' ) );
check( 'third-party password saves', 'anything', $out['custom'] );
check( 'third-party has generic filter only', 1, count( $GLOBALS['pff_filters']['pre_update_option_custom'] ) );
check( 'rk still works with filtered fields', $RK, $out['trk'] );
$out = save( array( 'custom' => '' ), array( 'custom' => 'old' ) );
check( 'third-party blank keeps old', 'old', $out['custom'] );
$GLOBALS['pff_filter_overrides'] = array();

// A successful save clears that mode's runtime denial: rk accepted, sk accepted, rk removed.
$denied = array( 'test' => array( 'plan' => 1 ), 'live' => array( 'plan' => 1 ) );
verdict( 'ok' );
$out = save( array( 'trk' => $RK ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rk accept clears that mode only', array( 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'lsk' => 'sk_live_new' ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'sk accept clears that mode only', array( 'test' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'trk_remove' => '1' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored', Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rk remove clears that mode', array( 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
verdict( 'missing_scope', array( 'Plans: Write' ) );
$out = save( array( 'trk' => $RK ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rejected rk clears nothing', array( 'test', 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'tsk' => 'sk_live_wrong' ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rejected sk clears nothing', array( 'test', 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );

// Crafted option_page still maps to the guarded group; a nonce in the query string counts.
verdict( 'ok' );
$out = save( array( 'trk' => $RK, 'option_page' => 'kkd-pff-paystack-settings-group%41' ), array() );
check( 'crafted option_page still guarded', $RK, $out['trk'] );
check( 'crafted option_page probed', 1, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'trk' => $RK, '_wpnonce' => null ), array(), array( 'query' => array( '_wpnonce' => 'good' ) ) );
check( 'nonce only in query string is accepted', $RK, $out['trk'] );
$out = save( array( 'trk' => $RK, '_wpnonce' => null ), array() );
check( 'no nonce anywhere is not accepted', false, isset( $out['trk'] ) );

// Direct filter calls without any decisions.
$GLOBALS['pff_options'] = array( 'trk' => $RK );
$bare = new Settings();
check( 'sk saved directly with stored rk and no decisions', 'sk_test_old', $bare->filter_secret_key( 'sk_test_new', 'sk_test_old', 'tsk' ) );
check( 'other mode sk unaffected by this rk', 'sk_live_new', $bare->filter_secret_key( 'sk_live_new', 'sk_live_old', 'lsk' ) );
$GLOBALS['pff_options'] = array();
check( 'sk saved directly without rk', 'sk_test_new', $bare->filter_secret_key( 'sk_test_new', 'sk_test_old', 'tsk' ) );

// An accepted rk applies only to the exact value that was verified.
$dec = new ReflectionProperty( Settings::class, 'decisions' );
if ( PHP_VERSION_ID < 80100 ) {
	$dec->setAccessible( true );
}
$GLOBALS['pff_options'] = array( 'tsk' => 'sk_test_stored' );
$dec->setValue( $bare, array( 'test' => array( 'rk' => 'accept', 'rk_value' => $RK, 'sk' => 'ignore', 'messages' => array() ) ) );
check( 'accept with a different value keeps old', 'rk_old', $bare->filter_restricted_key( $RK2, 'rk_old', 'trk' ) );
check( 'accept with a different value does not delete sk', 'sk_test_stored', $GLOBALS['pff_options']['tsk'] );
check( 'accept with the verified value stores it', $RK, $bare->filter_restricted_key( $RK, 'rk_old', 'trk' ) );
check( 'accept with the verified value deletes sk', false, isset( $GLOBALS['pff_options']['tsk'] ) );

// Field order must not change the outcome.
function outcome( $post, $stored ) {
	$out = save( $post, $stored );
	$out = array_intersect_key( $out, array_flip( array( 'trk', 'lrk', 'tsk', 'lsk' ) ) );
	ksort( $out );
	$errors = error_codes();
	sort( $errors );
	return array( $out, $errors );
}
$all_fields = array(
	'mode' => array( 'title' => 'Mode', 'type' => 'select', 'default' => 'test' ),
	'trk'  => array( 'title' => 'Test Restricted Key', 'type' => 'password', 'prefix' => 'rk_test_', 'default' => '' ),
	'lrk'  => array( 'title' => 'Live Restricted Key', 'type' => 'password', 'prefix' => 'rk_live_', 'default' => '' ),
	'tsk'  => array( 'title' => 'Test Secret Key', 'type' => 'password', 'prefix' => 'sk_test_', 'default' => '' ),
	'lsk'  => array( 'title' => 'Live Secret Key', 'type' => 'password', 'prefix' => 'sk_live_', 'default' => '' ),
);
$reversed = array(
	'mode' => $all_fields['mode'],
	'tsk'  => $all_fields['tsk'],
	'trk'  => $all_fields['trk'],
	'lsk'  => $all_fields['lsk'],
	'lrk'  => $all_fields['lrk'],
);
$cases = array(
	'accept+sk'            => array( 'ok', array( 'trk' => $RK, 'tsk' => 'sk_test_new' ), array( 'tsk' => 'sk_test_stored' ) ),
	'reject+sk'            => array( 'missing_scope', array( 'trk' => $RK, 'tsk' => 'sk_test_new' ), array( 'tsk' => 'sk_test_stored' ) ),
	'remove+sk'            => array( 'ok', array( 'trk_remove' => '1', 'tsk' => 'sk_test_new' ), array( 'trk' => $RK, 'tsk' => 'sk_test_stored' ) ),
	'accept+sk first save' => array( 'ok', array( 'trk' => $RK, 'tsk' => 'sk_test_new' ), array() ),
);
$expected_out = array(
	'accept+sk'            => array( 'trk' => $RK ),
	'reject+sk'            => array( 'tsk' => 'sk_test_stored' ),
	'remove+sk'            => array( 'trk' => '', 'tsk' => 'sk_test_new' ),
	'accept+sk first save' => array( 'trk' => $RK ),
);
foreach ( $cases as $name => $case ) {
	verdict( $case[0], array( 'Plans: Write' ) );
	$GLOBALS['pff_filter_overrides'] = array();
	$normal                          = outcome( $case[1], $case[2] );
	$GLOBALS['pff_filter_overrides'] = array( 'kkd_pff_paystack_settings_fields' => array( 'general' => $reversed ) );
	$flipped                         = outcome( $case[1], $case[2] );
	$GLOBALS['pff_filter_overrides'] = array();
	check( "$name same result in reversed field order", $normal, $flipped );
	check( "$name result", $expected_out[ $name ], $normal[0] );
}

// Debug log names the mode, verdict and probes but never the key.
file_put_contents( $GLOBALS['pff_log'], '' );
verdict( 'ok', array(), null, array(), 'transactions:read:200,plans:write:400' );
save( array( 'trk' => $RK ), array() );
$log = (string) file_get_contents( $GLOBALS['pff_log'] );
check( 'debug log line', true, false !== strpos( $log, 'pff-paystack rk check mode=test code=ok probes=transactions:read:200,plans:write:400' ) );
check( 'debug log has no key', false, strpos( $log, $RK ) );
unlink( $GLOBALS['pff_log'] );

// Helpers::get_secret_key.
$GLOBALS['pff_options'] = array();
check( 'absent options give empty string', '', Helpers::get_secret_key( 'test' ) );
$GLOBALS['pff_options'] = array( 'trk' => '', 'tsk' => 'sk_test_a', 'lrk' => '', 'lsk' => 'sk_live_a' );
check( 'empty rk falls to sk (test)', 'sk_test_a', Helpers::get_secret_key( 'test' ) );
check( 'empty rk falls to sk (live)', 'sk_live_a', Helpers::get_secret_key( 'live' ) );
$GLOBALS['pff_options'] = array( 'trk' => $RK, 'tsk' => 'sk_test_a', 'lrk' => $LRK, 'lsk' => 'sk_live_a' );
check( 'rk preferred (test)', $RK, Helpers::get_secret_key( 'test' ) );
check( 'rk preferred (live)', $LRK, Helpers::get_secret_key( 'live' ) );
check( 'empty mode is live', $LRK, Helpers::get_secret_key( '' ) );
check( 'false mode is live', $LRK, Helpers::get_secret_key( false ) );
$GLOBALS['pff_options'] = array( 'tsk' => 'sk_test_a', 'lsk' => 'sk_live_a' );
check( 'absent rk falls to sk', 'sk_test_a', Helpers::get_secret_key( 'test' ) );
check( 'false mode falls to lsk', 'sk_live_a', Helpers::get_secret_key( false ) );
$GLOBALS['pff_options'] = array( 'tsk' => 'sk_test_a' );
check( 'live with nothing stored', '', Helpers::get_secret_key( 'live' ) );

// mask_key with an rk.
$s = new Settings();
$m = new ReflectionMethod( $s, 'mask_key' );
if ( PHP_VERSION_ID < 80100 ) {
	$m->setAccessible( true );
}
check( 'mask rk', "rk_test_\u{2026}1234", $m->invoke( $s, $RK, 'rk_test_' ) );
check( 'mask rk wrong mode', "\u{2022}\u{2022}\u{2022}\u{2022}", $m->invoke( $s, $LRK, 'rk_test_' ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
