<?php
// Run: php tests/test-settings-secret-key.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_errors'] = array();
if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		$GLOBALS['pff_errors'][] = array( $setting, $code, $message, $type );
	}
}

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return $default;
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-settings.php';
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key.php';

define( 'EXPECTED', 31 );

$s = new \paystack\payment_forms\Settings();

// Third-party password fields: blank keeps old, a wrong prefix is rejected, anything else is stored.
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array(
			'custom_secret' => array( 'title' => 'Custom', 'type' => 'password', 'default' => '' ),
			'prefixed'      => array( 'title' => 'Prefixed', 'type' => 'password', 'prefix' => 'zz_', 'default' => '' ),
		),
	),
);
check( 'blank keeps old', 'old', $s->filter_secret_key( '', 'old', 'custom_secret' ) );
check( 'no-prefix password accepts any', 'whatever', $s->filter_secret_key( 'whatever', 'old', 'custom_secret' ) );
check( 'prefixed field accepts its prefix', 'zz_abc', $s->filter_secret_key( 'zz_abc', 'old', 'prefixed' ) );
check( 'no errors for accepted', 0, count( $GLOBALS['pff_errors'] ) );
check( 'prefixed field rejects another prefix', 'old', $s->filter_secret_key( 'yy_abc', 'old', 'prefixed' ) );
check( 'error recorded', 1, count( $GLOBALS['pff_errors'] ) );
check( 'error group', 'kkd-pff-paystack-settings-group', $GLOBALS['pff_errors'][0][0] );
check( 'error code', 'pff-paystack-invalid-prefixed', $GLOBALS['pff_errors'][0][1] );
check( 'error type', 'error', $GLOBALS['pff_errors'][0][3] );
check( 'error names prefix', true, false !== strpos( $GLOBALS['pff_errors'][0][2], 'zz_' ) );
check( 'blank is silent', 1, count( $GLOBALS['pff_errors'] ) );
$GLOBALS['pff_filter_overrides'] = array();

// The sk and pk options belong to filter_mode_key; without a decision they keep the stored value.
foreach ( array( 'tsk', 'tpk', 'lsk', 'lpk' ) as $option ) {
	check( "$option undecided keeps old", 'old', $s->filter_mode_key( 'sk_test_new', 'old', $option ) );
	check( "$option undecided blank keeps old", 'old', $s->filter_mode_key( '', 'old', $option ) );
	check( "$option undecided null keeps old", 'old', $s->filter_mode_key( null, 'old', $option ) );
}
check( 'mode key filter ignores other options', 'old', $s->filter_mode_key( 'x', 'old', 'custom_secret' ) );
check( 'undecided sk adds no error', 1, count( $GLOBALS['pff_errors'] ) );

// Fields: sk and pk definitions carry their prefixes and the pk is a plain text field.
$general = $s->get_settings_fields()['general'];
check( 'sk prefixes', array( 'sk_test_', 'sk_live_' ), array( $general['tsk']['prefix'], $general['lsk']['prefix'] ) );
check( 'pk prefixes and type', array( 'pk_test_', 'pk_live_', 'text', 'text' ), array( $general['tpk']['prefix'], $general['lpk']['prefix'], $general['tpk']['type'], $general['lpk']['type'] ) );

// mask_key (private).
$m = new ReflectionMethod( $s, 'mask_key' );
if ( PHP_VERSION_ID < 80100 ) {
	$m->setAccessible( true );
}
check( 'mask long with prefix', "sk_test_\u{2026}wxyz", $m->invoke( $s, 'sk_test_abcdefghijklmnopwxyz', 'sk_test_' ) );
check( 'mask legacy key without prefix', "\u{2022}\u{2022}\u{2022}\u{2022}", $m->invoke( $s, 'abcdefghijklmnopqrstuvwxyz', '' ) );
check( 'mask wrong prefix', "\u{2022}\u{2022}\u{2022}\u{2022}", $m->invoke( $s, 'sk_live_abcdefghijklmnopwxyz', 'sk_test_' ) );
check( 'mask short prefixed', "sk_test_\u{2022}\u{2022}\u{2022}\u{2022}", $m->invoke( $s, 'sk_test_abcdwxyz', 'sk_test_' ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
