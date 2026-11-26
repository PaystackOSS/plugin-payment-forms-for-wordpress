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

if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return false;
	}
}
// Replies come from $GLOBALS['pff_fake'], keyed by the path after the API base URL.
if ( ! function_exists( 'wp_remote_request' ) ) {
	function wp_remote_request( $url, $args ) {
		$path = substr( $url, strlen( \paystack\payment_forms\API::BASE_URL ) );
		return isset( $GLOBALS['pff_fake'][ $path ] ) ? $GLOBALS['pff_fake'][ $path ] : array( 'code' => 500, 'body' => '' );
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
require dirname( __DIR__ ) . '/includes/classes/class-settings.php';
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key.php';
require dirname( __DIR__ ) . '/includes/classes/class-helpers.php';

use paystack\payment_forms\Helpers;
use paystack\payment_forms\Restricted_Key;
use paystack\payment_forms\Settings;

define( 'EXPECTED', 269 );

$GLOBALS['pff_calls']   = array();
$GLOBALS['pff_verdict'] = array();

// 'AUTO' stands for a well-formed public key of the mode being checked.
function stub_validator( $key, $mode, $deadline = null ) {
	$GLOBALS['pff_calls'][] = array( $key, $mode, $deadline );
	$verdict                = $GLOBALS['pff_verdict'];
	if ( 'AUTO' === $verdict['public_key'] ) {
		$verdict['public_key'] = 'pk_' . $mode . '_abcdefghijklmnop1234';
	}
	return $verdict;
}

function verdict( $code, $scopes = array(), $reason = null, $sensitive = array(), $probes = '', $pk = 'AUTO', $unconfirmed = false ) {
	$GLOBALS['pff_verdict'] = array(
		'code'             => $code,
		'scopes'           => $scopes,
		'reason'           => $reason,
		'sensitive_scopes' => $sensitive,
		'probes'           => $probes,
		'public_key'       => $pk,
		'unconfirmed'      => $unconfirmed,
	);
}
verdict( 'ok' );

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

function sorted( $keys ) {
	ksort( $keys );
	return $keys;
}

function keys_of( $out ) {
	$keys = array();
	foreach ( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk' ) as $k ) {
		$keys[ $k ] = array_key_exists( $k, $out ) ? $out[ $k ] : false;
	}
	ksort( $keys );
	return $keys;
}

$RK   = 'rk_test_abcdefghijklmnop1234';
$RK2  = 'rk_test_zzzzzzzzzzzzzzzzzzzz';
$LRK  = 'rk_live' . '_abcdefghijklmnop1234';
$PK   = 'pk_test_abcdefghijklmnop1234';
$LPK  = 'pk_live' . '_abcdefghijklmnop1234';
$SK   = 'sk_test_storedstoredstored99';
$LSK  = 'sk_live' . '_storedstoredstored99';
$PK0  = 'pk_test_oldoldoldoldoldold01';
$LPK0 = 'pk_live' . '_oldoldoldoldoldold01';
$ALL  = array( 'lpk' => $LPK0, 'lsk' => $LSK, 'tpk' => $PK0, 'tsk' => $SK );

// Each rejecting verdict keeps the old rk and every stored sk and pk, and names its reason.
$old = array_merge( $ALL, array( 'trk' => 'rk_test_oldoldoldoldoldold' ) );
foreach ( array(
	array( 'invalid_shape', array(), null, 'starts with rk_test_' ),
	array( 'invalid_key', array(), null, "isn't enabled" ),
	array( 'missing_scope', array( 'Plans: Write' ), null, 'missing: Plans: Write' ),
	array( 'sensitive_scope', array( 'Transfers', 'Balance' ), null, 'Transfers, Balance' ),
	array( 'no_public_key', array(), null, "didn't return a public key" ),
	array( 'unverifiable', array(), 'blocked', 'IP whitelist' ),
	array( 'unverifiable', array(), 'transient', 'try again' ),
) as $case ) {
	verdict( $case[0], $case[1], $case[2] );
	$out = save( array( 'trk' => $RK ), $old );
	check( "{$case[0]}/{$case[2]} keeps old rk", $old['trk'], $out['trk'] );
	check( "{$case[0]}/{$case[2]} keeps every sk and pk", array_intersect_key( $old, $ALL ), array_intersect_key( $out, $ALL ) );
	check( "{$case[0]}/{$case[2]} error code", array( 'pff-paystack-rk-trk' ), error_codes() );
	check( "{$case[0]}/{$case[2]} message", true, false !== stripos( error_text(), $case[3] ) );
	check( "{$case[0]}/{$case[2]} kept phrasing", true, false !== strpos( error_text(), 'The previous key was kept' ) );
}
check( 'message never has the key', false, strpos( error_text(), $RK ) );

// An ok verdict whose public key is missing or malformed is rejected as no_public_key.
foreach ( array(
	'null'       => null,
	'live pk'    => $LPK,
	'short'      => 'pk_test_short',
	'bad chars'  => 'pk_test_abcdefghijklmnop!234',
	'trailing nl' => "pk_test_abcdefghijklmnop1234\n",
	'array'      => array( $PK ),
) as $label => $bad_pk ) {
	verdict( 'ok', array(), null, array(), '', $bad_pk );
	$out = save( array( 'trk' => $RK ), $old );
	check( "ok with $label pk keeps old rk", $old['trk'], $out['trk'] );
	check( "ok with $label pk keeps pk and sk", array_intersect_key( $old, $ALL ), array_intersect_key( $out, $ALL ) );
	check( "ok with $label pk is no_public_key", true, false !== strpos( error_text(), "Paystack didn't return a public key for this restricted key. Paste it again and save; if it keeps happening, contact Paystack support. The previous key was kept; other settings were saved." ) );
}
verdict( 'ok' );

// Each invalid_shape reason has its own copy, and a bad shape never reaches the network.
$old = array( 'trk' => 'rk_test_oldoldoldoldoldold' );
verdict( 'invalid_shape', array(), 'secret_key' );
$out = save( array( 'trk' => 'sk_test_abcdefghijklmnop1234' ), $old );
check( 'secret_key reason keeps old', $old['trk'], $out['trk'] );
check( 'secret_key reason message', true, false !== strpos( error_text(), 'This is a secret key' ) );
check( 'secret_key reason has no toggle wording', false, strpos( error_text(), 'Use secret keys instead' ) );
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
check( 'real validator malformed message', true, false !== strpos( error_text(), 'starts with rk_test_' ) );

verdict( 'ok' );

// Accepted rk stores the verdict pk and empties the sk: add path (nothing stored) and update path.
$out = save( array( 'trk' => $RK ), array( 'tsk' => $SK, 'tpk' => $PK0, 'lsk' => $LSK, 'lpk' => $LPK0 ) );
check( 'accept add path stores rk', $RK, $out['trk'] );
check( 'accept add path used add', 'add', $GLOBALS['pff_paths']['trk'] );
check( 'accept stores the verdict pk', $PK, $out['tpk'] );
check( 'accept empties the sk', '', $out['tsk'] );
check( 'accept leaves other mode keys', array( $LSK, $LPK0 ), array( $out['lsk'], $out['lpk'] ) );
check( 'accept no errors', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'trk' => $RK ), array() );
check( 'first save stores rk', $RK, $out['trk'] );
check( 'first save adds the pk', array( $PK, 'add' ), array( $out['tpk'], $GLOBALS['pff_paths']['tpk'] ) );
check( 'first save leaves the other mode unset', array( false, false ), array( isset( $out['lpk'] ), isset( $out['lsk'] ) ) );
$out = save( array( 'trk' => $RK2 ), array( 'trk' => $RK, 'tpk' => $PK0, 'tsk' => $SK ) );
check( 'accept update path stores rk', $RK2, $out['trk'] );
check( 'accept update path used update', 'update', $GLOBALS['pff_paths']['trk'] );
check( 'accept update path replaces pk', $PK, $out['tpk'] );
$out = save( array( 'trk' => $RK ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'unchanged resubmit still replaces pk and clears sk', array( $PK, '' ), array( $out['tpk'], $out['tsk'] ) );
$out = save( array( 'lrk' => $LRK ), array( 'lsk' => $LSK, 'tsk' => $SK, 'lpk' => $LPK0 ) );
check( 'live mode accepted', $LRK, $out['lrk'] );
check( 'live mode stores its pk and clears lsk only', array( $LPK, '', $SK ), array( $out['lpk'], $out['lsk'], $out['tsk'] ) );
check( 'validator got live mode', array( $LRK, 'live' ), array_slice( $GLOBALS['pff_calls'][0], 0, 2 ) );
$out = save( array( 'trk' => $RK, 'lrk' => $LRK ), $ALL );
check( 'both modes accepted', array( $RK, $LRK, $PK, $LPK, '', '' ), array( $out['trk'], $out['lrk'], $out['tpk'], $out['lpk'], $out['tsk'], $out['lsk'] ) );

// Blank, null and absent submissions never blank a stored key.
$full = array( 'lpk' => $LPK0, 'tpk' => $PK0, 'trk' => $RK, 'lrk' => $LRK );
$full_keys = sorted( array_merge( array( 'tsk' => false, 'lsk' => false ), $full ) );
$out       = save( array(), $full );
check( 'empty POST keeps everything', $full_keys, keys_of( $out ) );
$out = save( array( 'trk' => '', 'lrk' => '', 'tsk' => '', 'tpk' => '', 'lsk' => '', 'lpk' => '' ), $full );
check( 'blank POST keeps everything', $full_keys, keys_of( $out ) );
check( 'blank POST is silent', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'tsk' => array( 'sk_test_x' ), 'tpk' => array( $PK ), 'trk' => array( $RK ) ), $full );
check( 'array POST keeps everything', $full_keys, keys_of( $out ) );
check( 'array POST does not probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array(), $ALL );
check( 'sk-only site keeps keys on an empty POST', $ALL, array_intersect_key( keys_of( $out ), $ALL ) );
$out = save( array( 'mode' => 'live', 'prc' => '2' ), $ALL );
check( 'unrelated save keeps keys', $ALL, array_intersect_key( keys_of( $out ), $ALL ) );
foreach ( array( 'unverifiable', 'missing_scope', 'sensitive_scope', 'no_public_key', 'invalid_key' ) as $code ) {
	verdict( $code, array( 'Plans: Write' ), 'transient' );
	$out = save( array( 'trk' => $RK, 'tsk' => 'sk_test_new', 'tpk' => 'pk_test_newnewnewnewnewnew' ), $ALL );
	check( "rejected $code rk keeps sk and pk", $ALL, array_intersect_key( keys_of( $out ), $ALL ) );
	check( "rejected $code rk is not stored", false, isset( $out['trk'] ) );
}
verdict( 'ok' );

// Without capability, nonce or options.php nothing is decided, so nothing is stored or blanked.
$GLOBALS['pff_can'] = false;
$out = save( array( 'trk' => $RK, 'tsk' => 'sk_test_new', 'tpk' => 'pk_test_newnewnewnewnewnew' ), $ALL );
check( 'no capability no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'no capability stores nothing', sorted( array_merge( $ALL, array( 'trk' => false, 'lrk' => false ) ) ), keys_of( $out ) );
$GLOBALS['pff_can'] = true;
$out = save( array( 'trk' => $RK, 'tsk' => 'sk_test_new', '_wpnonce' => 'bad' ), $ALL );
check( 'bad nonce no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'bad nonce stores nothing', sorted( array_merge( $ALL, array( 'trk' => false, 'lrk' => false ) ) ), keys_of( $out ) );
$out = save( array( 'trk' => $RK, '_wpnonce' => array( 'good' ) ), $ALL );
check( 'array nonce no probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'trk' => $RK, 'tsk' => 'sk_test_new', 'trk_disconnect' => '1' ), $ALL, array( 'pagenow' => 'admin-ajax.php' ) );
check( 'wrong pagenow no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'wrong pagenow stores nothing and disconnects nothing', sorted( array_merge( $ALL, array( 'trk' => false, 'lrk' => false ) ) ), keys_of( $out ) );
$out = save( array( 'trk' => $RK, 'option_page' => 'other' ), array() );
check( 'other option page no probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'other option page registers only the keep guards', array( 'pre_update_option_trk', 'pre_update_option_tsk', 'pre_update_option_tpk', 'pre_update_option_lrk', 'pre_update_option_lsk', 'pre_update_option_lpk' ), array_keys( array_filter( $GLOBALS['pff_filters'], function ( $k ) {
	return 0 === strpos( $k, 'pre_update_option_' );
}, ARRAY_FILTER_USE_KEY ) ) );

// Disconnect empties that mode's rk, sk and pk, and nothing else.
$out = save( array( 'trk_disconnect' => '1' ), array_merge( $ALL, array( 'trk' => $RK, 'lrk' => $LRK ) ) );
check( 'disconnect empties the test mode', array( '', '', '' ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );
check( 'disconnect leaves the live mode (its leftover sk is cleared)', array( $LRK, '', $LPK0 ), array( $out['lrk'], $out['lsk'], $out['lpk'] ) );
check( 'disconnect does not probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'disconnecting the active mode warns', array( 'pff-paystack-disconnect-active-trk', 'warning' ), array( $GLOBALS['pff_errors'][0][1], $GLOBALS['pff_errors'][0][3] ) );
$out = save( array( 'lrk_disconnect' => '1' ), $ALL );
check( 'disconnect an sk-only live mode', array( '', '', $SK, $PK0 ), array( $out['lsk'], $out['lpk'], $out['tsk'], $out['tpk'] ) );
check( 'disconnect leaves no key to resolve', '', ( function ( $out ) {
	$GLOBALS['pff_options'] = $out;
	return Helpers::get_secret_key( 'live' );
} )( $out ) );
$out = save( array( 'trk_disconnect' => '0' ), array_merge( $ALL, array( 'trk' => $RK ) ) );
check( 'disconnect flag must be 1', array( $RK, '', $PK0 ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );
$out = save( array( 'trk_disconnect' => '1' ), array() );
check( 'disconnect with nothing stored writes no key', array(), array_intersect_key( $out, array_flip( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk' ) ) ) );
$out = save( array( 'trk' => $RK2, 'trk_disconnect' => '1' ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'submitted rk beats disconnect', array( $RK2, '', $PK ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );
check( 'submitted rk beats disconnect notice', array( 'pff-paystack-disconnect-ignored-trk' ), error_codes() );
check( 'ignored disconnect notice is a warning', 'warning', $GLOBALS['pff_errors'][0][3] );
verdict( 'missing_scope', array( 'Plans: Write' ) );
$out = save( array( 'trk' => $RK2, 'trk_disconnect' => '1' ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'rejected rk with disconnect disconnects nothing', array( $RK, $SK, $PK0 ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );
verdict( 'ok' );
$out = save( array( 'trk_disconnect' => '1', 'tsk' => 'sk_test_new' ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'disconnect beats a submitted sk', array( '', '', '' ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );

// A secret or public key is saved only for a mode that shows those rows: a stored sk and no rk.
$NEWSK = 'sk_test_newnewnewnewnewnew';
$NEWPK = 'pk_test_newnewnewnewnewnew';
$out   = save( array( 'tsk' => $NEWSK, 'tpk' => $NEWPK ), $ALL );
check( 'sk mode saves a new sk and pk', array( $NEWSK, $NEWPK ), array( $out['tsk'], $out['tpk'] ) );
check( 'sk mode save is silent', 0, count( $GLOBALS['pff_errors'] ) );
check( 'sk mode save does not probe', 0, count( $GLOBALS['pff_calls'] ) );
$out = save( array( 'tsk' => '', 'tpk' => $NEWPK ), $ALL );
check( 'sk mode saves pk alone', array( $SK, $NEWPK ), array( $out['tsk'], $out['tpk'] ) );
$out = save( array( 'tsk' => $NEWSK, 'tpk' => $PK0 ), $ALL );
check( 'sk mode saves sk alone, pk unchanged', array( $NEWSK, $PK0 ), array( $out['tsk'], $out['tpk'] ) );
check( 'resubmitting the pre-filled pk is silent', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'tpk' => 'legacy-pk' ), array_merge( $ALL, array( 'tpk' => 'legacy-pk' ) ) );
check( 'an unchanged legacy pk is not an error', array( 'legacy-pk', 0 ), array( $out['tpk'], count( $GLOBALS['pff_errors'] ) ) );
$out = save( array( 'tsk' => 'sk_live_wrong', 'tpk' => 'pk_live_wrongwrongwrongwrong' ), $ALL );
check( 'wrong-prefix sk and pk keep stored', array( $SK, $PK0 ), array( $out['tsk'], $out['tpk'] ) );
check( 'wrong-prefix error codes', array( 'pff-paystack-invalid-tsk', 'pff-paystack-invalid-tpk' ), error_codes() );
check( 'sk error names prefix', true, false !== strpos( error_text(), 'sk_test_' ) );
check( 'pk error names prefix', true, false !== strpos( $GLOBALS['pff_errors'][1][2], 'pk_test_' ) );
$out = save( array( 'tsk' => 'SK_TEST_abc' ), $ALL );
check( 'wrong case prefix keeps stored sk', $SK, $out['tsk'] );
$out = save( array( 'tsk' => $NEWSK, 'tpk' => $NEWPK ), array() );
check( 'no stored sk: rows are not shown, submission ignored', array( false, false ), array( isset( $out['tsk'] ), isset( $out['tpk'] ) ) );
check( 'no stored sk: ignored silently', 0, count( $GLOBALS['pff_errors'] ) );
$out = save( array( 'tsk' => $NEWSK, 'tpk' => $NEWPK ), array_merge( $ALL, array( 'trk' => $RK ) ) );
check( 'stored rk: sk and pk submission ignored, the leftover sk is cleared', array( '', $PK0 ), array( $out['tsk'], $out['tpk'] ) );
$out = save( array( 'lsk' => 'sk_live_newnewnewnewnewnew' ), $ALL );
check( 'live sk saves for its own mode', array( 'sk_live_newnewnewnewnewnew', $SK ), array( $out['lsk'], $out['tsk'] ) );

// Submitted sk and pk are ignored, with a message, when a restricted key is submitted for that mode.
$out = save( array( 'trk' => $RK, 'tsk' => $NEWSK, 'tpk' => $NEWPK ), $ALL );
check( 'rk wins over submitted sk and pk', array( $RK, '', $PK ), array( $out['trk'], $out['tsk'], $out['tpk'] ) );
check( 'rk with sk and pk notice', array( 'pff-paystack-keys-ignored-trk' ), error_codes() );
check( 'ignored keys notice is a warning', 'warning', $GLOBALS['pff_errors'][0][3] );
$out = save( array( 'trk' => $RK, 'tpk' => $PK0 ), $ALL );
check( 'rk with an unchanged pre-filled pk is silent', 0, count( $GLOBALS['pff_errors'] ) );

// Budget: one submitted rk gets 20 s, two get 10 s slices of the same save.
$t0 = microtime( true );
save( array( 'trk' => $RK ), array() );
$d = $GLOBALS['pff_calls'][0][2];
check( 'one rk: deadline is about 20 s out', true, $d >= $t0 + 19.9 && $d <= microtime( true ) + 20.1 );
save( array( 'trk' => $RK, 'lrk' => $LRK ), array() );
$d1 = $GLOBALS['pff_calls'][0][2];
$d2 = $GLOBALS['pff_calls'][1][2];
check( 'two rks: test slice is about 10 s', true, $d1 >= $t0 + 9.9 && $d1 <= microtime( true ) + 10.1 );
check( 'two rks: live slice ends 10 s after the test slice', true, abs( ( $d2 - $d1 ) - 10 ) < 0.001 );
save( array( 'lrk' => $LRK ), array() );
$d = $GLOBALS['pff_calls'][0][2];
check( 'one live rk: deadline is about 20 s out', true, $d >= $t0 + 19.9 && $d <= microtime( true ) + 20.1 );
check( 'a double taking two arguments still works', array( $RK, 'test' ), ( function () use ( $RK ) {
	$GLOBALS['pff_options'] = array();
	$_POST                  = array( 'option_page' => 'kkd-pff-paystack-settings-group', '_wpnonce' => 'good', 'trk' => $RK );
	$_REQUEST               = $_POST;
	$GLOBALS['pagenow']     = 'options.php';
	$seen                   = array();
	$s                      = new Settings();
	$s->set_validator( function ( $key, $mode ) use ( &$seen ) {
		$seen = array( $key, $mode );
		return array( 'code' => 'unverifiable' );
	} );
	$s->register_secret_key_guards();
	return $seen;
} )() );

// Unconfirmed extra-permission check: saved, with a warning.
verdict( 'ok', array(), null, array(), '', 'AUTO', true );
$out = save( array( 'trk' => $RK ), array() );
check( 'unconfirmed rk is stored', array( $RK, $PK ), array( $out['trk'], $out['tpk'] ) );
check( 'unconfirmed warning code and type', array( 'pff-paystack-rk-unconfirmed-trk', 'warning' ), array( $GLOBALS['pff_errors'][0][1], $GLOBALS['pff_errors'][0][3] ) );
check( 'unconfirmed warning text', "Saved. We couldn't confirm your Test restricted key has no extra permissions. Check it in your Paystack Dashboard.", error_text() );
save( array( 'lrk' => $LRK ), array() );
check( 'unconfirmed warning text (live)', "Saved. We couldn't confirm your Live restricted key has no extra permissions. Check it in your Paystack Dashboard.", error_text() );
verdict( 'ok' );
save( array( 'trk' => $RK ), array() );
check( 'confirmed rk has no warning', 0, count( $GLOBALS['pff_errors'] ) );
verdict( 'ok', array(), null, array(), '', 'AUTO', true );
verdict( 'missing_scope', array( 'Plans: Write' ), null, array(), '', 'AUTO', true );
save( array( 'trk' => $RK ), array() );
check( 'rejected rk shows no unconfirmed warning', array( 'pff-paystack-rk-trk' ), error_codes() );
verdict( 'ok' );

// The writes follow the stored rk: a guard never writes the pk or clears the sk unless that rk is stored.
$GLOBALS['pff_options'] = array( 'tsk' => $SK, 'tpk' => $PK0 );
$bare = new Settings();
$dec  = new ReflectionProperty( Settings::class, 'decisions' );
if ( PHP_VERSION_ID < 80100 ) {
	$dec->setAccessible( true );
}
$dec->setValue( $bare, array( 'test' => array( 'rk' => 'accept', 'rk_value' => $RK, 'unconfirmed' => false, 'clear_sk' => false, 'sk_value' => null, 'pk_value' => $PK, 'messages' => array() ) ) );
check( 'accepted pk is not written while the rk is not stored', $PK0, $bare->filter_mode_key( '', $PK0, 'tpk' ) );
check( 'accepted sk is not cleared while the rk is not stored', $SK, $bare->filter_mode_key( '', $SK, 'tsk' ) );
$GLOBALS['pff_options']['trk'] = $RK2;
check( 'a different stored rk does not release the pk', $PK0, $bare->filter_mode_key( '', $PK0, 'tpk' ) );
$GLOBALS['pff_options']['trk'] = $RK;
check( 'pk written once the rk is stored', $PK, $bare->filter_mode_key( null, $PK0, 'tpk' ) );
check( 'sk cleared once the rk is stored', '', $bare->filter_mode_key( null, $SK, 'tsk' ) );
check( 'rk accept with a different value keeps old', 'rk_old', $bare->filter_restricted_key( $RK2, 'rk_old', 'trk' ) );
check( 'rk accept with the verified value stores it', $RK, $bare->filter_restricted_key( $RK, 'rk_old', 'trk' ) );
check( 'other mode is undecided: keeps old', $LPK0, $bare->filter_mode_key( $LPK, $LPK0, 'lpk' ) );
$undecided = new Settings();
check( 'no decisions: pk kept', $PK0, $undecided->filter_mode_key( $NEWPK, $PK0, 'tpk' ) );
check( 'no decisions: sk kept', $SK, $undecided->filter_mode_key( $NEWSK, $SK, 'tsk' ) );
check( 'no decisions: rk kept', 'rk_old', $undecided->filter_restricted_key( $RK, 'rk_old', 'trk' ) );
check( 'unknown option is kept', 'old', $undecided->filter_mode_key( 'x', 'old', 'nope' ) );
// A decided sk or pk is written only when the stored submission still equals it; a blank or altered value keeps the old one.
$sk_mode = new Settings();
$dec->setValue( $sk_mode, array( 'test' => array( 'rk' => 'none', 'rk_value' => null, 'unconfirmed' => false, 'clear_sk' => false, 'sk_value' => $NEWSK, 'pk_value' => $NEWPK, 'messages' => array() ) ) );
check( 'decided sk: blank value keeps old', $SK, $sk_mode->filter_mode_key( '', $SK, 'tsk' ) );
check( 'decided pk: a value altered after the decision keeps old', $PK0, $sk_mode->filter_mode_key( $PK, $PK0, 'tpk' ) );

// Hook wiring: one owner per key option, always installed; third parties cannot take over sk and pk.
save( array(), array() );
$f = $GLOBALS['pff_filters'];
check( 'trk has one filter', 1, count( $f['pre_update_option_trk'] ) );
check( 'lrk has one filter', 1, count( $f['pre_update_option_lrk'] ) );
check( 'trk owner is the rk filter', 'filter_restricted_key', $f['pre_update_option_trk'][0][1] );
foreach ( array( 'tsk', 'tpk', 'lsk', 'lpk' ) as $k ) {
	check( "$k owner is the mode key filter", array( 'filter_mode_key' ), array_map( function ( $c ) {
		return $c[1];
	}, $f[ "pre_update_option_$k" ] ) );
}
check( 'rk is a password field with prefix', array( 'password', 'rk_test_', 'rk_live_' ), ( function () {
	$g = ( new Settings() )->get_settings_fields()['general'];
	return array( $g['trk']['type'], $g['trk']['prefix'], $g['lrk']['prefix'] );
} )() );
check( 'fields keep their order', array( 'mode', 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk' ), array_keys( ( new Settings() )->get_settings_fields()['general'] ) );
$all_fields = ( new Settings() )->get_settings_fields()['general'];

$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array(
			'tsk'    => array( 'title' => 'Hijack', 'type' => 'text', 'default' => '' ),
			'tpk'    => array( 'title' => 'Hijack', 'type' => 'password', 'default' => '' ),
			'trk'    => $all_fields['trk'],
			'lrk'    => $all_fields['lrk'],
			'custom' => array( 'title' => 'Custom', 'type' => 'password', 'default' => '' ),
		),
	),
);
$g = ( new Settings() )->get_settings_fields()['general'];
check( 'filtered sk and pk are replaced by ours, after the rk fields', array( 'trk', 'lrk', 'custom', 'tsk', 'tpk', 'lsk', 'lpk' ), array_keys( $g ) );
check( 'filtered sk definition is ours', array( 'password', 'sk_test_' ), array( $g['tsk']['type'], $g['tsk']['prefix'] ) );
check( 'filtered pk definition is ours', array( 'text', 'pk_test_' ), array( $g['tpk']['type'], $g['tpk']['prefix'] ) );
$out = save( array( 'custom' => 'anything', 'trk' => $RK ), array( 'custom' => 'old', 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'third-party password saves', 'anything', $out['custom'] );
check( 'third-party has generic filter only', 1, count( $GLOBALS['pff_filters']['pre_update_option_custom'] ) );
check( 'tsk has no generic filter from the hijack', array( 'filter_mode_key' ), array_map( function ( $c ) {
	return $c[1];
}, $GLOBALS['pff_filters']['pre_update_option_tsk'] ) );
check( 'rk still works with filtered fields', array( $RK, $PK, '' ), array( $out['trk'], $out['tpk'], $out['tsk'] ) );
$out = save( array( 'custom' => '' ), array( 'custom' => 'old' ) );
check( 'third-party blank keeps old', 'old', $out['custom'] );
$GLOBALS['pff_filter_overrides'] = array( 'kkd_pff_paystack_settings_fields' => array( 'general' => array() ) );
$out = save( array( 'trk' => $RK ), array( 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'rk fields removed by a filter: nothing stored, nothing blanked', array( false, $SK, $PK0 ), array( isset( $out['trk'] ), $out['tsk'], $out['tpk'] ) );
$GLOBALS['pff_filter_overrides'] = array();

// A successful save clears that mode's runtime denial: rk accepted, sk accepted, disconnect.
$denied = array( 'test' => array( 'plan' => 1 ), 'live' => array( 'plan' => 1 ) );
verdict( 'ok' );
$out = save( array( 'trk' => $RK ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rk accept clears that mode only', array( 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'lsk' => 'sk_live_newnewnewnewnewnew' ), array_merge( $ALL, array( Restricted_Key::DENIED_OPTION => $denied ) ) );
check( 'sk accept clears that mode only', array( 'test' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'trk_disconnect' => '1' ), array( 'trk' => $RK, 'tsk' => $SK, Restricted_Key::DENIED_OPTION => $denied ) );
check( 'disconnect clears that mode', array( 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
verdict( 'missing_scope', array( 'Plans: Write' ) );
$out = save( array( 'trk' => $RK ), array( Restricted_Key::DENIED_OPTION => $denied ) );
check( 'rejected rk clears nothing', array( 'test', 'live' ), array_keys( $out[ Restricted_Key::DENIED_OPTION ] ) );
$out = save( array( 'tsk' => 'sk_live_wrong' ), array_merge( $ALL, array( Restricted_Key::DENIED_OPTION => $denied ) ) );
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

// Field order must not change the outcome: the rk fields always come first.
function outcome( $post, $stored ) {
	$out = save( $post, $stored );
	$out = keys_of( $out );
	ksort( $out );
	$errors = error_codes();
	sort( $errors );
	return array( $out, $errors );
}
$reversed = array(
	'lpk'  => $all_fields['lpk'],
	'lsk'  => $all_fields['lsk'],
	'tpk'  => $all_fields['tpk'],
	'tsk'  => $all_fields['tsk'],
	'lrk'  => $all_fields['lrk'],
	'trk'  => $all_fields['trk'],
	'mode' => $all_fields['mode'],
);
$cases = array(
	'accept+sk'  => array( 'ok', array( 'trk' => $RK, 'tsk' => $NEWSK ), $ALL ),
	'reject+sk'  => array( 'missing_scope', array( 'trk' => $RK, 'tsk' => $NEWSK ), $ALL ),
	'disconnect' => array( 'ok', array( 'trk_disconnect' => '1', 'tsk' => $NEWSK ), array_merge( $ALL, array( 'trk' => $RK ) ) ),
	'first save' => array( 'ok', array( 'trk' => $RK, 'tsk' => $NEWSK ), array() ),
);
$expected_out = array(
	'accept+sk'  => array( 'trk' => $RK, 'lrk' => false, 'tsk' => '', 'tpk' => $PK, 'lsk' => $LSK, 'lpk' => $LPK0 ),
	'reject+sk'  => array_merge( $ALL, array( 'trk' => false, 'lrk' => false ) ),
	'disconnect' => array( 'trk' => '', 'lrk' => false, 'tsk' => '', 'tpk' => '', 'lsk' => $LSK, 'lpk' => $LPK0 ),
	'first save' => array( 'trk' => $RK, 'lrk' => false, 'tsk' => false, 'tpk' => $PK, 'lsk' => false, 'lpk' => false ),
);
foreach ( $expected_out as $k => $v ) {
	$expected_out[ $k ] = sorted( $v );
}
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
verdict( 'ok' );

// Saves through the real validator and a fake transport: the key is granted nothing beyond the required scopes.
function fake_api( $overrides = array(), $pk = null ) {
	$deny   = Restricted_Key::SCOPE_DENIED_MESSAGE;
	$pk     = null === $pk ? 'pk_test_abcdefghijklmnop1234' : $pk;
	$by_scope = array(
		'transactions:read' => array( 200, array() ),
		'account:read'      => array( 200, array( 'data' => array( 'public_key' => $pk ) ) ),
		'plans:write'       => array( 400, array( 'message' => Restricted_Key::PLAN_VALIDATION_MESSAGE ) ),
	);
	$GLOBALS['pff_fake'] = array();
	foreach ( Restricted_Key::PROBES as $probe ) {
		$scope = $probe['scope'];
		$reply = isset( $overrides[ $scope ] ) ? $overrides[ $scope ] : ( isset( $by_scope[ $scope ] ) ? $by_scope[ $scope ] : array( 403, array( 'message' => $deny ) ) );
		$GLOBALS['pff_fake'][ $probe['path'] ] = array( 'code' => $reply[0], 'body' => json_encode( $reply[1] ) );
	}
}
$real  = array( 'real_validator' => true );
$FLAGS = 'pff_paystack_rk_unconfirmed';

fake_api();
$out = save( array( 'trk' => $RK ), array( 'tsk' => $SK, 'tpk' => $PK0 ), $real );
check( 'real validator: ok key is stored', $RK, $out['trk'] );
check( 'real validator: ok key sets the public key', $PK, $out['tpk'] );
check( 'real validator: ok key blanks the sk', '', $out['tsk'] );
check( 'real validator: ok key is silent and sets no flag', array( 0, false ), array( count( $GLOBALS['pff_errors'] ), isset( $out[ $FLAGS ] ) ) );

fake_api();
$PUNCT = 'rk_test_Ab3-cD.e~F_gHiJkLmNoPq';
$out   = save( array( 'trk' => $PUNCT ), array(), $real );
check( 'real validator: punctuated key is stored', array( $PUNCT, $PK, 0 ), array( $out['trk'], $out['tpk'], count( $GLOBALS['pff_errors'] ) ) );

fake_api( array( 'balance' => array( 500, array() ) ) );
$out = save( array( 'trk' => $RK ), array( 'tsk' => $SK, 'tpk' => $PK0 ), $real );
check( 'real validator: lower-risk 5xx still stores the key', array( $RK, $PK ), array( $out['trk'], $out['tpk'] ) );
check( 'real validator: lower-risk 5xx warns', array( 'pff-paystack-rk-unconfirmed-trk', 'warning' ), array( $GLOBALS['pff_errors'][0][1], $GLOBALS['pff_errors'][0][3] ) );
check( 'real validator: lower-risk 5xx persists the flag', array( 'test' => true ), $out[ $FLAGS ] );
check( 'flag option is added on first set', 'add', $GLOBALS['pff_paths'][ $FLAGS ] );

fake_api( array( 'payouts' => array( 500, array() ) ) );
$old = array( 'trk' => 'rk_test_oldoldoldoldoldold', 'tsk' => $SK, 'tpk' => $PK0 );
$out = save( array( 'trk' => $RK ), $old, $real );
check( 'real validator: high-risk 5xx writes no key or flag', $old, array_intersect_key( $out, array_flip( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk', $FLAGS ) ) ) );
check( 'real validator: high-risk 5xx is rejected', array( 'pff-paystack-rk-trk' ), error_codes() );
check( 'real validator: high-risk 5xx asks to retry', true, false !== strpos( error_text(), 'try again' ) );

fake_api( array( 'account:read' => array( 200, array() ) ) );
$out = save( array( 'trk' => $RK ), $old, $real );
check( 'real validator: /ident without a pk writes no key or flag', $old, array_intersect_key( $out, array_flip( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk', $FLAGS ) ) ) );
check( 'real validator: /ident without a pk says so', true, false !== strpos( error_text(), "Paystack didn't return a public key" ) );

// The unconfirmed flag: set per mode, cleared by a confirmed accept or a disconnect, untouched by a rejection.
fake_api();
$out = save( array( 'trk' => $RK ), array( $FLAGS => array( 'test' => true, 'live' => true ) ), $real );
check( 'confirmed accept clears only that mode flag', array( 'live' => true ), $out[ $FLAGS ] );
fake_api( array( 'balance' => array( 500, array() ) ) );
$out = save( array( 'trk' => $RK ), array( $FLAGS => array( 'live' => true ) ), $real );
check( 'unconfirmed accept keeps the other mode flag', array( 'live' => true, 'test' => true ), $out[ $FLAGS ] );
$out = save( array( 'trk' => $RK ), array( 'trk' => $RK, $FLAGS => array( 'test' => true ) ), $real );
check( 'unconfirmed accept of an unchanged key does not rewrite the flag', false, isset( $GLOBALS['pff_paths'][ $FLAGS ] ) );
check( 'unconfirmed accept of an unchanged key still warns', 'pff-paystack-rk-unconfirmed-trk', $GLOBALS['pff_errors'][0][1] );
$out = save( array( 'trk_disconnect' => '1' ), array( 'trk' => $RK, $FLAGS => array( 'test' => true ) ) );
check( 'disconnect clears the flag', array(), $out[ $FLAGS ] );
fake_api( array( 'payouts' => array( 500, array() ) ) );
$out = save( array( 'trk' => $RK2 ), array( 'trk' => $RK, $FLAGS => array( 'test' => true ) ), $real );
check( 'rejected rk leaves the flag', array( 'test' => true ), $out[ $FLAGS ] );
$out = save( array( 'trk' => $RK2, 'trk_disconnect' => '1' ), array( 'trk' => $RK, $FLAGS => array( 'test' => true ) ), $real );
check( 'ignored disconnect leaves the flag', array( 'test' => true ), $out[ $FLAGS ] );
fake_api( array( 'balance' => array( 500, array() ) ) );
$out = save( array( 'trk' => $RK ), array( $FLAGS => 'corrupt' ), $real );
check( 'a corrupt flag option is replaced', array( 'test' => true ), $out[ $FLAGS ] );

// The warning is queued with the write, not when the save is decided.
$GLOBALS['pff_options'] = array();
$GLOBALS['pff_errors']  = array();
$bare = new Settings();
$dec  = new ReflectionProperty( Settings::class, 'decisions' );
if ( PHP_VERSION_ID < 80100 ) {
	$dec->setAccessible( true );
}
$dec->setValue( $bare, array( 'test' => array( 'rk' => 'accept', 'rk_value' => $RK, 'unconfirmed' => true, 'clear_sk' => false, 'sk_value' => null, 'pk_value' => $PK, 'messages' => array() ) ) );
$bare->filter_restricted_key( $RK2, 'rk_old', 'trk' );
check( 'no warning or flag when the write does not go through', array( 0, false ), array( count( $GLOBALS['pff_errors'] ), isset( $GLOBALS['pff_options'][ $FLAGS ] ) ) );
$bare->filter_restricted_key( $RK, 'rk_old', 'trk' );
check( 'warning and flag when the write goes through', array( 1, array( 'test' => true ) ), array( count( $GLOBALS['pff_errors'] ), $GLOBALS['pff_options'][ $FLAGS ] ) );
verdict( 'ok', array(), null, array(), '', 'AUTO', true );
save( array( 'trk' => $RK ), array() );
$GLOBALS['pff_errors'] = array();
$s = new Settings();
$s->set_validator( 'stub_validator' );
$_POST    = array( 'option_page' => 'kkd-pff-paystack-settings-group', '_wpnonce' => 'good', 'trk' => $RK );
$_REQUEST = $_POST;
$s->register_secret_key_guards();
check( 'deciding the save queues no unconfirmed warning', 0, count( $GLOBALS['pff_errors'] ) );
verdict( 'ok' );
check( 'run_validator is private', true, ( new ReflectionMethod( Settings::class, 'run_validator' ) )->isPrivate() );

// Budget slices count only keys that pass the shape check.
$t0 = microtime( true );
save( array( 'trk' => $RK, 'lrk' => 'rk_live_short' ), array() );
$d = $GLOBALS['pff_calls'][0][2];
check( 'a malformed second rk does not halve the budget', true, $d >= $t0 + 19.9 && $d <= microtime( true ) + 20.1 );
$t0 = microtime( true );
save( array( 'trk' => $LRK, 'lrk' => $LRK ), array() );
$d = $GLOBALS['pff_calls'][1][2];
check( 'a wrong-mode rk takes no slice', true, $d >= $t0 + 19.9 && $d <= microtime( true ) + 20.1 );
check( 'budget comes from the validator constant', 20, Restricted_Key::BUDGET_SECONDS );

// Field positions: the key fields are ours in every group and pinned in general.
$g = ( new Settings() )->get_settings_fields();
check( 'no key field outside general', array(), array_diff( array_keys( $g['fees'] ), array( 'prc', 'ths', 'adc', 'cap' ) ) );
$names = array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk', 'trk_disconnect', 'lrk_disconnect' );
$moved = array();
foreach ( $names as $n ) {
	$moved[ $n ] = array( 'title' => 'Moved', 'type' => 'password', 'default' => '' );
}
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array_merge( array( 'custom' => $all_fields['mode'] ), array( 'mode' => $all_fields['mode'], 'tsk' => $moved['tsk'], 'other' => $all_fields['mode'] ) ),
		'fees'    => array_merge( $moved, array( 'prc' => $g['fees']['prc'] ) ),
		'extra'   => array( 'lsk' => $moved['lsk'], 'z' => $all_fields['mode'] ),
	),
);
$g = ( new Settings() )->get_settings_fields();
check( 'key fields stripped from every other group', array( array( 'prc' ), array( 'z' ) ), array( array_keys( $g['fees'] ), array_keys( $g['extra'] ) ) );
check( 'general has the sk and pk at the end and no disconnect names', array( 'custom', 'mode', 'other', 'tsk', 'tpk', 'lsk', 'lpk' ), array_keys( $g['general'] ) );
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array( 'custom' => $all_fields['mode'], 'mode' => $all_fields['mode'], 'trk' => $all_fields['trk'], 'lrk' => $all_fields['lrk'], 'other' => $all_fields['mode'] ),
		'fees'    => array( 'tpk' => $moved['tpk'] ),
	),
);
check( 'rk fields are pinned right after mode', array( 'custom', 'mode', 'trk', 'lrk', 'other', 'tsk', 'tpk', 'lsk', 'lpk' ), array_keys( ( new Settings() )->get_settings_fields()['general'] ) );
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array( 'mode' => $all_fields['mode'], 'other' => $all_fields['mode'] ),
		'fees'    => array( 'trk' => $moved['trk'], 'lrk' => $moved['lrk'], 'tsk' => $moved['tsk'] ),
	),
);
check( 'rk fields moved to another group are not re-added', array( 'mode', 'other', 'tsk', 'tpk', 'lsk', 'lpk' ), array_keys( ( new Settings() )->get_settings_fields()['general'] ) );
check( 'rk fields moved to another group are gone from it', array(), ( new Settings() )->get_settings_fields()['fees'] );
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => $all_fields,
		'fees'    => array( 'trk' => $moved['trk'], 'tsk' => $moved['tsk'] ),
	),
);
$out = save( array( 'trk' => $RK ), array( 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'a rk moved into fees is still saved by the rk filter, once', array( $RK, $PK, '' ), array( $out['trk'], $out['tpk'], $out['tsk'] ) );
check( 'a rk moved into fees has one filter', 1, count( $GLOBALS['pff_filters']['pre_update_option_trk'] ) );
$GLOBALS['pff_filter_overrides'] = array();

// Core's All Settings page (options.php, another option_page) cannot write any key.
$stored = array_merge( $ALL, array( 'trk' => $RK, 'lrk' => $LRK ) );
$post   = array( 'option_page' => 'options', 'trk' => $RK2, 'lrk' => 'rk_live_zzzzzzzzzzzzzzzzzzzz', 'tsk' => $NEWSK, 'tpk' => $NEWPK, 'lsk' => 'sk_live_newnewnewnewnewnew', 'lpk' => 'pk_live_newnewnewnewnewnew' );
$out    = save( $post, $stored, array( 'pagenow' => 'admin-ajax.php' ) );
check( 'control: without the guard an other-page save would write keys', $RK2, $out['trk'] );
$out = save( $post, $stored );
check( 'all settings page cannot change any key', array_intersect_key( $stored, array_flip( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk' ) ) ), array_intersect_key( $out, array_flip( array( 'trk', 'lrk', 'tsk', 'tpk', 'lsk', 'lpk' ) ) ) );
check( 'all settings page does not probe', 0, count( $GLOBALS['pff_calls'] ) );
check( 'all settings page guards six options', 6, count( array_filter( array_keys( $GLOBALS['pff_filters'] ), function ( $k ) {
	return 0 === strpos( $k, 'pre_update_option_' );
} ) ) );
$out = save( array( 'option_page' => 'options', 'trk' => $RK2 ), array(), array( 'pagenow' => 'options.php' ) );
check( 'all settings page cannot add a key either', false, isset( $out['trk'] ) );

// A secret key left beside a stored restricted key is cleared by any decided save.
$out = save( array( 'prc' => '2' ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0, 'lrk' => $LRK, 'lsk' => $LSK, 'lpk' => $LPK0 ) );
check( 'rk and sk stored: the sk is cleared in both modes', array( '', '' ), array( $out['tsk'], $out['lsk'] ) );
check( 'rk and sk stored: rk and pk are kept', array( $RK, $PK0, $LRK, $LPK0 ), array( $out['trk'], $out['tpk'], $out['lrk'], $out['lpk'] ) );
check( 'rk and sk stored: no probe, no message', array( 0, 0 ), array( count( $GLOBALS['pff_calls'] ), count( $GLOBALS['pff_errors'] ) ) );
$out = save( array( 'tsk' => $NEWSK ), array( 'trk' => $RK, 'tsk' => $SK, 'tpk' => $PK0 ) );
check( 'rk and sk stored: a submitted sk does not survive', '', $out['tsk'] );
$out = save( array( 'prc' => '2' ), array( 'trk' => $RK, 'tpk' => $PK0 ) );
check( 'rk without an sk gains no sk row', false, isset( $out['tsk'] ) );
$out = save( array( 'prc' => '2' ), $ALL );
check( 'sk without an rk is untouched', array( $SK, $LSK ), array( $out['tsk'], $out['lsk'] ) );

// Disconnecting the active mode warns that payments will fail.
$stored = array_merge( $ALL, array( 'trk' => $RK, 'lrk' => $LRK ) );
$text   = function () {
	return $GLOBALS['pff_errors'] ? array( $GLOBALS['pff_errors'][0][1], $GLOBALS['pff_errors'][0][2], $GLOBALS['pff_errors'][0][3] ) : array();
};
save( array( 'trk_disconnect' => '1', 'mode' => 'test' ), $stored );
check( 'disconnect the submitted active test mode', array( 'pff-paystack-disconnect-active-trk', 'Test mode now has no keys; payments will fail until you save a restricted key.', 'warning' ), $text() );
save( array( 'lrk_disconnect' => '1', 'mode' => 'live' ), $stored );
check( 'disconnect the submitted active live mode', array( 'pff-paystack-disconnect-active-lrk', 'Live mode now has no keys; payments will fail until you save a restricted key.', 'warning' ), $text() );
save( array( 'lrk_disconnect' => '1', 'mode' => 'test' ), $stored );
check( 'disconnect an inactive mode is silent', array(), $text() );
save( array( 'trk_disconnect' => '1', 'mode' => 'live' ), $stored );
check( 'the submitted mode beats the saved mode', array(), $text() );
save( array( 'lrk_disconnect' => '1' ), array_merge( $stored, array( 'mode' => 'live' ) ) );
check( 'saved mode is used when none is submitted', 'pff-paystack-disconnect-active-lrk', $text()[0] );
save( array( 'trk_disconnect' => '1' ), array_merge( $stored, array( 'mode' => 'live' ) ) );
check( 'saved live mode makes a test disconnect silent', array(), $text() );
save( array( 'trk_disconnect' => '1', 'mode' => 'bogus' ), array_merge( $stored, array( 'mode' => 'live' ) ) );
check( 'an invalid submitted mode falls back to the saved one', array(), $text() );
save( array( 'trk_disconnect' => '1', 'lrk_disconnect' => '1', 'mode' => 'test' ), $stored );
check( 'disconnecting both modes warns once, for the active one', array( 'pff-paystack-disconnect-active-trk' ), error_codes() );

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
