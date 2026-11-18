<?php
// Run: php tests/test-settings-page.php

require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options'] = array();
$GLOBALS['pff_fields']  = null;

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
$GLOBALS['pff_settings_errors'] = array();
if ( ! function_exists( 'get_settings_errors' ) ) {
	function get_settings_errors( $setting = '' ) {
		return $GLOBALS['pff_settings_errors'];
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

define( 'EXPECTED', 82 );

$RK_T = 'rk_test_DISTINCTIVEtestrestrictedkey0001';
$RK_L = 'rk_live' . '_DISTINCTIVElive_restrictedkey0002';
$SK_T = 'sk_test_DISTINCTIVEtestsecretkeyvalue0003';
$SK_L = 'sk_live' . '_DISTINCTIVElivesecretkeyvalue0004';

function render_raw( $options, $errors = array() ) {
	$GLOBALS['pff_options']         = $options;
	$GLOBALS['pff_settings_errors'] = $errors;
	$s                              = new Settings();
	ob_start();
	$s->output_settings_page();
	return ob_get_clean();
}
function strip_script( $html ) {
	return preg_replace( '#<script\b.*?</script>#s', '', $html );
}
function render( $options, $errors = array() ) {
	return strip_script( render_raw( $options, $errors ) );
}
function err( $code ) {
	return array( array( 'setting' => Settings::OPTION_GROUP, 'code' => $code, 'message' => 'm', 'type' => 'error' ) );
}
function has( $html, $needle ) {
	return false !== strpos( $html, $needle );
}

// secret_keys_expanded truth table.
$truth = array(
	array( 'nothing stored', array(), array(), false ),
	array( 'rk only', array( 'trk' => $RK_T ), array(), false ),
	array( 'sk only', array( 'tsk' => $SK_T ), array(), true ),
	array( 'live sk only', array( 'lsk' => $SK_L ), array(), true ),
	array( 'rk and sk same mode', array( 'trk' => $RK_T, 'tsk' => $SK_T ), array(), true ),
	array( 'test rk, live sk', array( 'trk' => $RK_T, 'lsk' => $SK_L ), array(), true ),
	array( 'empty strings', array( 'trk' => '', 'tsk' => '' ), array(), false ),
	array( 'error invalid-tsk', array( 'trk' => $RK_T ), err( 'pff-paystack-invalid-tsk' ), true ),
	array( 'error invalid-lsk', array( 'trk' => $RK_T ), err( 'pff-paystack-invalid-lsk' ), true ),
	array( 'error sk-ignored', array( 'trk' => $RK_T ), err( 'pff-paystack-sk-ignored-tsk' ), true ),
	array( 'error rk-remove', array( 'trk' => $RK_T ), err( 'pff-paystack-rk-remove-trk' ), true ),
	array( 'error rk-remove-ignored', array( 'trk' => $RK_T ), err( 'pff-paystack-rk-remove-ignored-trk' ), true ),
	array( 'unrelated error', array( 'trk' => $RK_T ), err( 'pff-paystack-rk-trk' ), false ),
	array( 'invalid-trk error is not an sk error', array( 'trk' => $RK_T ), err( 'pff-paystack-invalid-trk' ), false ),
);
foreach ( $truth as $row ) {
	$GLOBALS['pff_options'] = $row[1];
	check( 'expanded: ' . $row[0], $row[3], ( new Settings() )->secret_keys_expanded( $row[2] ) );
}
$GLOBALS['pff_options'] = array();
check( 'expanded: default errors arg', false, ( new Settings() )->secret_keys_expanded() );

// Page-level: errors reach the render.
check( 'sk error expands page', false, has( render( array( 'trk' => $RK_T ), err( 'pff-paystack-sk-ignored-tsk' ) ), 'data-collapsed="1"' ) );
check( 'rk-only collapses page', true, has( render( array( 'trk' => $RK_T ) ), 'data-collapsed="1"' ) );

// Nothing stored.
$html = render( array() );
check( 'trk input', true, has( $html, 'name="trk"' ) );
check( 'lrk input', true, has( $html, 'name="lrk"' ) );
check( 'trk placeholder', true, has( $html, 'placeholder="rk_test_…"' ) );
check( 'lrk placeholder', true, has( $html, 'placeholder="rk_live_…"' ) );
check( 'autofill attrs', 4, substr_count( $html, 'autocomplete="new-password" data-1p-ignore data-lpignore="true" spellcheck="false"' ) );
check( 'no autocomplete off', false, has( $html, 'autocomplete="off"' ) );
foreach ( array( 'trk', 'lrk', 'tsk', 'lsk', 'tpk', 'lpk' ) as $k ) {
	check( "label/id pair $k", true, has( $html, '<label for="pff-' . $k . '">' ) && has( $html, 'id="pff-' . $k . '" name="' . $k . '"' ) );
}
check( 'no disabled or hidden input', 0, preg_match( '#<input[^>]*\b(disabled|hidden)\b#', $html ) );
check( 'sk container not hidden', 0, preg_match( '#id="pff-secret-keys"[^>]*\bhidden\b#', $html ) );
check( 'no remove description when nothing stored', false, has( $html, 'To switch back to a secret key' ) );
check( 'no remove checkbox', false, has( $html, 'trk_remove' ) || has( $html, 'lrk_remove' ) );
check( 'tpk input', true, has( $html, 'name="tpk"' ) );
check( 'lpk input', true, has( $html, 'name="lpk"' ) );
check( 'mode select', true, has( $html, '<select class="form-control" name="mode"' ) );
check( 'fees kept', true, has( $html, 'name="prc"' ) );
check( 'steps list', true, has( $html, '<ol' ) );
check( 'dashboard link in steps and top', 2, substr_count( $html, 'href="' . Settings::DASHBOARD_URL . '" target="_blank" rel="noopener noreferrer"' ) );
check( 'dashboard url value', 'https://dashboard.paystack.com/v2/developers/api-configuration', Settings::DASHBOARD_URL );
check( 'old link gone', false, has( $html, '#/settings/developers' ) );
check( 'scopes text', true, has( $html, 'Turn on only these permissions: ' . Restricted_Key::required_scopes_label() . '. Leave every other permission off, including Account: Write.' ) );
check( 'toggle button', true, has( $html, 'type="button" class="button-link" id="pff-secret-keys-toggle" aria-expanded="false" aria-controls="pff-secret-keys" hidden' ) );
check( 'sk container collapsed, nothing stored', true, has( $html, 'id="pff-secret-keys" data-collapsed="1"' ) );
check( 'sk inputs present', true, has( $html, 'name="tsk"' ) && has( $html, 'name="lsk"' ) );

// Restricted key stored.
$html = render( array( 'trk' => $RK_T, 'lrk' => $RK_L ) );
check( 'remove test checkbox', true, has( $html, 'name="trk_remove" value="1"' ) );
check( 'remove live checkbox', true, has( $html, 'name="lrk_remove" value="1"' ) );
check( 'remove label', true, has( $html, 'Remove saved restricted key' ) );
check( 'remove description', 2, substr_count( $html, "To switch back to a secret key, also paste one under 'Use secret keys instead'." ) );
check( 'saved hint', true, has( $html, 'Saved key: rk_test_' ) );
check( 'collapsed with rk', true, has( $html, 'data-collapsed="1"' ) );
check( 'trk value not echoed', false, has( $html, $RK_T ) );
check( 'lrk value not echoed', false, has( $html, $RK_L ) );

// Only the stored mode gets a remove checkbox.
$html = render( array( 'trk' => $RK_T ) );
check( 'only test remove', true, has( $html, 'trk_remove' ) && ! has( $html, 'lrk_remove' ) );

// sk only: expanded, hint shown, value not echoed.
$html = render( array( 'tsk' => $SK_T, 'lsk' => $SK_L ) );
check( 'not collapsed with sk only', false, has( $html, 'data-collapsed="1"' ) );
check( 'sk hint', true, has( $html, 'Saved key: sk_test_' ) );
check( 'tsk value not echoed', false, has( $html, $SK_T ) );
check( 'lsk value not echoed', false, has( $html, $SK_L ) );
check( 'no remove checkbox for sk', false, has( $html, '_remove' ) );

// Mixed: test rk + live sk expands.
$html = render( array( 'trk' => $RK_T, 'lsk' => $SK_L ) );
check( 'mixed not collapsed', false, has( $html, 'data-collapsed="1"' ) );

// Script is static.
$html = render_raw( array( 'trk' => $RK_T, 'lsk' => $SK_L ) );
preg_match( '#<script>(.*?)</script>#s', $html, $m );
$script = isset( $m[1] ) ? $m[1] : '';
check( 'script present', true, '' !== $script );
check( 'script has no php', false, has( $script, '<?php' ) || has( $script, '<?=' ) );
check( 'script identical across states', $script, ( function () {
	preg_match( '#<script>(.*?)</script>#s', render_raw( array( 'tsk' => 'sk_test_x', 'tpk' => 'pk_test_y', 'mode' => 'live' ) ), $m2 );
	return $m2[1];
} )() );
foreach ( array( $RK_T, $SK_L, 'sk_test_x', 'pk_test_y' ) as $v ) {
	check( 'script lacks option value ' . $v, false, has( $script, $v ) );
}
check( 'script removes hidden', true, has( $script, 'box.hidden = false' ) );
check( 'script sets aria-expanded', true, has( $script, "setAttribute('aria-expanded', 'true')" ) );
check( 'script expands on remove checkbox', true, has( $script, "querySelectorAll('.pff-rk-remove')" ) && has( $script, 'if (this.checked)' ) );

// Third-party general field via the public filter.
global $pff_filter_overrides;
$base                 = ( new Settings() )->get_settings_fields();
$base['general']['x_secret'] = array( 'title' => 'Extra Secret', 'type' => 'password', 'default' => '' );
$base['general']['x_text']   = array( 'title' => 'Extra Text', 'type' => 'text', 'default' => 'dflt' );
$pff_filter_overrides = array( 'kkd_pff_paystack_settings_fields' => $base );
$html = render( array( 'x_secret' => 'THIRDPARTYSECRETVALUE', 'x_text' => 'saved-text' ) );
check( 'third-party password field', true, has( $html, 'name="x_secret"' ) );
check( 'third-party password not echoed', false, has( $html, 'THIRDPARTYSECRETVALUE' ) );
check( 'third-party password hint', true, has( $html, 'Saved key: ' ) );
check( 'third-party text field with value', true, has( $html, 'name="x_text" value="saved-text"' ) );
check( 'third-party after public keys', true, strpos( $html, 'name="x_text"' ) > strpos( $html, 'name="lpk"' ) );
$pff_filter_overrides = array();

// Unknown keys absent from the fields array are skipped without notices.
unset( $base['general']['trk'], $base['general']['lrk'], $base['general']['tsk'], $base['general']['lsk'] );
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
check( 'no toggle when sk absent', false, has( $html, 'pff-secret-keys' ) );
check( 'pk still rendered', true, has( $html, 'name="tpk"' ) );

// No rk rendered: the sk rows stay expanded.
$base['general']['tsk'] = array( 'title' => 'Test Secret Key', 'type' => 'password', 'prefix' => 'sk_test_', 'default' => '' );
$pff_filter_overrides = array( 'kkd_pff_paystack_settings_fields' => $base );
$html = render( array() );
check( 'sk rendered without rk', true, has( $html, 'name="tsk"' ) );
check( 'no rk: not collapsed', false, has( $html, 'data-collapsed' ) );

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
