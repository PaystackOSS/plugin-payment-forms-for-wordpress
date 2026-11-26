<?php
// Run: php tests/test-settings-page.php

require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options'] = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['pff_options'] ) ? $GLOBALS['pff_options'][ $name ] : $default;
	}
}
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $v ) {
		return htmlspecialchars( (string) $v, ENT_QUOTES );
	}
}
if ( ! function_exists( 'esc_html_e' ) ) {
	function esc_html_e( $text ) {
		echo $text;
	}
}
foreach ( array( 'settings_errors', 'settings_fields', 'do_settings_sections', 'submit_button' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		eval( "function $fn() {}" );
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return true;
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-settings.php';
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key.php';

use paystack\payment_forms\Restricted_Key;
use paystack\payment_forms\Settings;

define( 'EXPECTED', 75 );

$RK_T = 'rk_test_DISTINCTIVEtestrestrictedkey0001';
$RK_L = 'rk_live' . '_DISTINCTIVElive_restrictedkey0002';
$SK_T = 'sk_test_DISTINCTIVEtestsecretkeyvalue0003';
$SK_L = 'sk_live' . '_DISTINCTIVElivesecretkeyvalue0004';
$PK_T = 'pk_test_DISTINCTIVEtestpublickeyvalue0005';
$PK_L = 'pk_live' . '_DISTINCTIVElivepublickeyvalue0006';

function render( $options ) {
	$GLOBALS['pff_options'] = $options;
	$s                      = new Settings();
	ob_start();
	$s->output_settings_page();
	return ob_get_clean();
}
function has( $html, $needle ) {
	return false !== strpos( $html, $needle );
}
$AUTOFILL = 'autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false"';

// Nothing stored: only the restricted key rows.
$html = render( array() );
check( 'trk input', true, has( $html, 'name="trk"' ) );
check( 'lrk input', true, has( $html, 'name="lrk"' ) );
check( 'trk placeholder', true, has( $html, 'placeholder="rk_test_…"' ) );
check( 'lrk placeholder', true, has( $html, 'placeholder="rk_live_…"' ) );
check( 'autofill attrs on both rk rows only', 2, substr_count( $html, $AUTOFILL ) );
check( 'no autocomplete off', false, has( $html, 'autocomplete="off"' ) );
foreach ( array( 'trk', 'lrk' ) as $k ) {
	check( "label/id pair $k", true, has( $html, '<label for="pff-' . $k . '">' ) && has( $html, 'id="pff-' . $k . '" name="' . $k . '"' ) );
}
foreach ( array( 'tsk', 'lsk', 'tpk', 'lpk' ) as $k ) {
	check( "no $k row without a stored sk", false, has( $html, 'name="' . $k . '"' ) );
}
check( 'no disconnect without a stored key', false, has( $html, '_disconnect' ) || has( $html, 'Disconnect this mode' ) );
check( 'no secret key note', false, has( $html, 'This mode uses a secret key' ) );
check( 'no toggle, container, script or collapse', false, has( $html, 'pff-secret-keys' ) || has( $html, '<script' ) || has( $html, '<button' ) || has( $html, 'data-collapsed' ) );
check( 'no disabled or hidden input', 0, preg_match( '#<input[^>]*\b(disabled|hidden)\b#', $html ) );
check( 'mode select', true, has( $html, '<select class="form-control" name="mode"' ) );
check( 'fees kept', true, has( $html, 'name="prc"' ) );
check( 'steps list', true, has( $html, '<ol' ) );
check( 'dashboard link in steps and top', 2, substr_count( $html, 'href="' . Settings::DASHBOARD_URL . '" target="_blank" rel="noopener noreferrer"' ) );
check( 'dashboard url value', 'https://dashboard.paystack.com/v2/developers/api-configuration', Settings::DASHBOARD_URL );
check( 'old link gone', false, has( $html, '#/settings/developers' ) );
check( 'scopes text', true, has( $html, 'Turn on only these permissions: ' . Restricted_Key::required_scopes_label() . '. Leave every other permission off, including Account: Write.' ) );
check( 'mode, then steps, then restricted keys', true, strpos( $html, 'name="mode"' ) < strpos( $html, 'pff-rk-steps' ) && strpos( $html, 'pff-rk-steps' ) < strpos( $html, 'name="trk"' ) && strpos( $html, 'name="trk"' ) < strpos( $html, 'name="lrk"' ) );

// Restricted keys stored: a disconnect box per mode, the key itself never echoed, no secret rows.
$html = render( array( 'trk' => $RK_T, 'lrk' => $RK_L ) );
check( 'disconnect test checkbox', true, has( $html, 'name="trk_disconnect" value="1"' ) );
check( 'disconnect live checkbox', true, has( $html, 'name="lrk_disconnect" value="1"' ) );
check( 'disconnect label', 2, substr_count( $html, 'Disconnect this mode (clears its saved keys)' ) );
check( 'saved hint', true, has( $html, 'Saved key: rk_test_' ) );
check( 'trk value not echoed', false, has( $html, $RK_T ) );
check( 'lrk value not echoed', false, has( $html, $RK_L ) );
check( 'no secret rows with an rk', false, has( $html, 'name="tsk"' ) || has( $html, 'name="lpk"' ) || has( $html, 'This mode uses a secret key' ) );

// The unconfirmed flag shows under that mode's rk row only, and only while the rk is stored.
$NOTE = 'Not fully checked: we couldn\'t confirm this key has no extra permissions. Paste it again and save to re-check.';
$html = render( array( 'trk' => $RK_T, 'lrk' => $RK_L, 'pff_paystack_rk_unconfirmed' => array( 'test' => true ) ) );
check( 'unconfirmed note shown once', 1, substr_count( $html, $NOTE ) );
check( 'unconfirmed note sits in the test row', true, strpos( $html, 'name="trk"' ) < strpos( $html, $NOTE ) && strpos( $html, $NOTE ) < strpos( $html, 'name="lrk"' ) );
check( 'no note without the flag', false, has( render( array( 'trk' => $RK_T ) ), $NOTE ) );
check( 'no note when the flagged mode has no rk', false, has( render( array( 'lrk' => $RK_L, 'pff_paystack_rk_unconfirmed' => array( 'test' => true ) ) ), $NOTE ) );

// Only the stored mode gets a disconnect box.
$html = render( array( 'trk' => $RK_T ) );
check( 'only test disconnect', true, has( $html, 'trk_disconnect' ) && ! has( $html, 'lrk_disconnect' ) );
$html = render( array( 'trk' => '', 'lrk' => '' ) );
check( 'empty strings are not stored keys', false, has( $html, '_disconnect' ) );

// Secret keys stored: note, sk and pk rows, and a disconnect box on the rk row.
$html = render( array( 'tsk' => $SK_T, 'lsk' => $SK_L, 'tpk' => $PK_T, 'lpk' => $PK_L ) );
check( 'test note', true, has( $html, 'This mode uses a secret key (sk_test_…0003). Save a restricted key to switch; the secret and public keys are then replaced.' ) );
check( 'live note', true, has( $html, 'This mode uses a secret key (sk_live_…0004). Save a restricted key to switch; the secret and public keys are then replaced.' ) );
foreach ( array( 'tsk', 'lsk' ) as $k ) {
	check( "sk row $k is a masked password input", true, has( $html, 'type="password" id="pff-' . $k . '" name="' . $k . '" value=""' ) );
}
check( 'pk rows are text inputs holding the public key', true, has( $html, 'type="text" id="pff-tpk" name="tpk" value="' . $PK_T . '"' ) && has( $html, 'type="text" id="pff-lpk" name="lpk" value="' . $PK_L . '"' ) );
foreach ( array( 'tsk', 'lsk', 'tpk', 'lpk' ) as $k ) {
	check( "label/id pair $k", true, has( $html, '<label for="pff-' . $k . '">' ) && has( $html, 'id="pff-' . $k . '" name="' . $k . '"' ) );
}
check( 'sk hint', true, has( $html, 'Saved key: sk_test_' ) );
check( 'tsk value not echoed', false, has( $html, $SK_T ) );
check( 'lsk value not echoed', false, has( $html, $SK_L ) );
check( 'autofill attrs on rk and sk rows', 4, substr_count( $html, $AUTOFILL ) );
check( 'disconnect boxes for sk modes', true, has( $html, 'name="trk_disconnect"' ) && has( $html, 'name="lrk_disconnect"' ) );
check( 'rows follow the rk rows', true, strpos( $html, 'name="lrk"' ) < strpos( $html, 'name="tsk"' ) && strpos( $html, 'name="tsk"' ) < strpos( $html, 'name="tpk"' ) && strpos( $html, 'name="tpk"' ) < strpos( $html, 'name="lsk"' ) );
check( 'no toggle or script with sk', false, has( $html, 'pff-secret-keys' ) || has( $html, '<script' ) );

// Only the mode with a stored sk and no rk shows its rows.
$html = render( array( 'tsk' => $SK_T ) );
check( 'test sk only: test rows', true, has( $html, 'name="tsk"' ) && has( $html, 'name="tpk"' ) );
check( 'test sk only: no live rows', false, has( $html, 'name="lsk"' ) || has( $html, 'name="lpk"' ) );
check( 'test sk only: disconnect for test only', true, has( $html, 'trk_disconnect' ) && ! has( $html, 'lrk_disconnect' ) );
$html = render( array( 'trk' => $RK_T, 'tsk' => $SK_T, 'tpk' => $PK_T ) );
check( 'rk and sk in one mode: rows hidden', false, has( $html, 'name="tsk"' ) || has( $html, 'name="tpk"' ) || has( $html, 'This mode uses a secret key' ) );
check( 'rk and sk in one mode: public key not echoed', false, has( $html, $PK_T ) );
check( 'rk and sk in one mode: disconnect shown', true, has( $html, 'name="trk_disconnect"' ) );
$html = render( array( 'trk' => $RK_T, 'lsk' => $SK_L ) );
check( 'test rk, live sk: live rows only', true, has( $html, 'name="lsk"' ) && has( $html, 'name="lpk"' ) && ! has( $html, 'name="tsk"' ) && ! has( $html, 'name="tpk"' ) );
check( 'test rk, live sk: one note', 1, substr_count( $html, 'This mode uses a secret key' ) );
$html = render( array( 'tpk' => $PK_T ) );
check( 'a stored pk alone shows no secret rows', false, has( $html, 'name="tpk"' ) );
$html = render( array( 'tsk' => 'legacykey' ) );
check( 'unprefixed legacy sk is masked in the note', true, has( $html, 'This mode uses a secret key (••••).' ) );

// Third-party general field via the public filter.
global $pff_filter_overrides;
$base                        = ( new Settings() )->get_settings_fields();
$base['general']['x_secret'] = array( 'title' => 'Extra Secret', 'type' => 'password', 'default' => '' );
$base['general']['x_text']   = array( 'title' => 'Extra Text', 'type' => 'text', 'default' => 'dflt' );
$pff_filter_overrides        = array( 'kkd_pff_paystack_settings_fields' => $base );
$html = render( array( 'x_secret' => 'THIRDPARTYSECRETVALUE', 'x_text' => 'saved-text', 'tsk' => $SK_T ) );
check( 'third-party password field', true, has( $html, 'name="x_secret"' ) );
check( 'third-party password not echoed', false, has( $html, 'THIRDPARTYSECRETVALUE' ) );
check( 'third-party password hint', true, has( $html, 'Saved key: ' ) );
check( 'third-party text field with value', true, has( $html, 'name="x_text" value="saved-text"' ) );
check( 'third-party after the key rows', true, strpos( $html, 'name="x_text"' ) > strpos( $html, 'name="tpk"' ) );
check( 'third-party fields rendered once', 1, substr_count( $html, 'name="x_text"' ) );

// A filter cannot redefine or add sk and pk rows: they are ours.
$hijack = array(
	'general' => array(
		'mode' => $base['general']['mode'],
		'trk'  => $base['general']['trk'],
		'lrk'  => $base['general']['lrk'],
		'tsk'  => array( 'title' => 'Hijack', 'type' => 'text', 'default' => 'HIJACKDEFAULT' ),
		'lpk'  => array( 'title' => 'Hijack', 'type' => 'password', 'default' => '' ),
	),
);
$pff_filter_overrides = array( 'kkd_pff_paystack_settings_fields' => $hijack );
$html = render( array( 'tsk' => $SK_T ) );
check( 'hijacked sk is still a masked password row', true, has( $html, 'type="password" id="pff-tsk" name="tsk" value=""' ) && ! has( $html, 'HIJACK' ) );
check( 'hijack does not add live rows', false, has( $html, 'name="lpk"' ) );
$pff_filter_overrides = array();

// Fields absent from the filter output are skipped without notices.
unset( $base['general']['trk'], $base['general']['lrk'] );
$pff_filter_overrides = array( 'kkd_pff_paystack_settings_fields' => $base );
$warnings = 0;
set_error_handler(
	function () use ( &$warnings ) {
		$warnings++;
		return true;
	}
);
$html = render( array() );
restore_error_handler();
check( 'no warnings or notices', 0, $warnings );
check( 'no rk section when absent', false, has( $html, 'name="trk"' ) || has( $html, 'Restricted keys' ) );
check( 'mode select without rk', true, has( $html, 'name="mode"' ) );

// Fees absent from the fields array do not warn.
unset( $base['fees'] );
$pff_filter_overrides = array( 'kkd_pff_paystack_settings_fields' => $base );
$warnings = 0;
set_error_handler(
	function () use ( &$warnings ) {
		$warnings++;
		return true;
	}
);
$html = render( array() );
restore_error_handler();
check( 'no warnings without fees', 0, $warnings );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
