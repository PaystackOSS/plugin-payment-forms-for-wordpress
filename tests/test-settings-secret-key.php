<?php
// Run: php tests/test-settings-secret-key.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_errors'] = array();
if ( ! function_exists( 'add_settings_error' ) ) {
	function add_settings_error( $setting, $code, $message, $type = 'error' ) {
		$GLOBALS['pff_errors'][] = array( $setting, $code, $message, $type );
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-settings.php';

define( 'EXPECTED', 27 );

$s = new \paystack\payment_forms\Settings();

// Blank keeps old.
check( 'blank keeps old', 'sk_test_old', $s->filter_secret_key( '', 'sk_test_old', 'tsk' ) );
check( 'blank no error', 0, count( $GLOBALS['pff_errors'] ) );

// tsk.
check( 'tsk accepts test key', 'sk_test_abc', $s->filter_secret_key( 'sk_test_abc', 'sk_test_old', 'tsk' ) );
check( 'tsk accept no error', 0, count( $GLOBALS['pff_errors'] ) );
check( 'tsk rejects live key', 'sk_test_old', $s->filter_secret_key( 'sk_live_abc', 'sk_test_old', 'tsk' ) );
check( 'error recorded', 1, count( $GLOBALS['pff_errors'] ) );
check( 'error group', 'kkd-pff-paystack-settings-group', $GLOBALS['pff_errors'][0][0] );
check( 'error code', 'pff-paystack-invalid-tsk', $GLOBALS['pff_errors'][0][1] );
check( 'error type', 'error', $GLOBALS['pff_errors'][0][3] );
check( 'error names prefix', true, false !== strpos( $GLOBALS['pff_errors'][0][2], 'sk_test_' ) );
check( 'tsk rejects password', 'sk_test_old', $s->filter_secret_key( 'password', 'sk_test_old', 'tsk' ) );
check( 'second error recorded', 2, count( $GLOBALS['pff_errors'] ) );
check( 'tsk rejects wrong case prefix', '', $s->filter_secret_key( 'SK_TEST_abc', '', 'tsk' ) );

// lsk mirror.
$GLOBALS['pff_errors'] = array();
check( 'lsk accepts live key', 'sk_live_abc', $s->filter_secret_key( 'sk_live_abc', 'sk_live_old', 'lsk' ) );
check( 'lsk no error', 0, count( $GLOBALS['pff_errors'] ) );
check( 'lsk rejects test key', 'sk_live_old', $s->filter_secret_key( 'sk_test_abc', 'sk_live_old', 'lsk' ) );
check( 'lsk rejects password', 'sk_live_old', $s->filter_secret_key( 'password', 'sk_live_old', 'lsk' ) );
check( 'lsk error code', 'pff-paystack-invalid-lsk', $GLOBALS['pff_errors'][0][1] );
check( 'lsk blank keeps old', 'sk_live_old', $s->filter_secret_key( '', 'sk_live_old', 'lsk' ) );

// Unknown option and password field without prefix accept any non-blank.
$GLOBALS['pff_errors'] = array();
check( 'unknown option accepts', 'anything', $s->filter_secret_key( 'anything', 'old', 'nope' ) );
$GLOBALS['pff_filter_overrides'] = array(
	'kkd_pff_paystack_settings_fields' => array(
		'general' => array(
			'custom_secret' => array( 'title' => 'Custom', 'type' => 'password', 'default' => '' ),
		),
	),
);
check( 'no-prefix password accepts any', 'whatever', $s->filter_secret_key( 'whatever', 'old', 'custom_secret' ) );
check( 'no-prefix password blank keeps old', 'old', $s->filter_secret_key( '', 'old', 'custom_secret' ) );
check( 'no errors for accepted', 0, count( $GLOBALS['pff_errors'] ) );
$GLOBALS['pff_filter_overrides'] = array();

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
