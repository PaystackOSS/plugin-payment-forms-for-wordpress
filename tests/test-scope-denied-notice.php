<?php
// Run: php tests/test-scope-denied-notice.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options'] = array( 'mode' => 'test' );
$GLOBALS['pff_updates'] = array();
$GLOBALS['pff_reply']   = null;
$GLOBALS['pff_can']     = true;

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
if ( ! function_exists( 'esc_attr' ) ) {
	function esc_attr( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'current_user_can' ) ) {
	function current_user_can( $cap ) {
		return $GLOBALS['pff_can'];
	}
}
if ( ! function_exists( 'admin_url' ) ) {
	function admin_url( $path ) {
		return 'https://example.test/wp-admin/' . $path;
	}
}
if ( ! class_exists( 'WP_Error' ) ) {
	class WP_Error {}
}
if ( ! function_exists( 'is_wp_error' ) ) {
	function is_wp_error( $thing ) {
		return $thing instanceof WP_Error;
	}
}
if ( ! function_exists( 'wp_remote_get' ) ) {
	function wp_remote_get( $url, $args ) {
		return $GLOBALS['pff_reply'];
	}
}
if ( ! function_exists( 'wp_remote_post' ) ) {
	function wp_remote_post( $url, $args ) {
		return $GLOBALS['pff_reply'];
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
require dirname( __DIR__ ) . '/includes/classes/class-restricted-key-notice.php';

// Namespaced stubs are eval'd so the rest of the file stays in the global namespace.
eval(
	'namespace paystack\\payment_forms;
	if ( ! class_exists( __NAMESPACE__ . "\\Settings" ) ) {
		class Settings {
			public static function get_capability() {
				return "manage_options";
			}
		}
	}
	if ( ! class_exists( __NAMESPACE__ . "\\Helpers" ) ) {
		class Helpers {
			public static function get_secret_key( $mode ) {
				return "rk_" . $mode . "_secret";
			}
		}
	}
	class Test_API extends API {
		public function __construct( $module ) {
			parent::__construct();
			$this->set_module( $module );
			$this->set_url_args( "?secret=1&x=<script>" );
		}
	}'
);

use paystack\payment_forms\Restricted_Key as RK;
use paystack\payment_forms\Restricted_Key_Notice;
use paystack\payment_forms\Test_API;

define( 'EXPECTED', 24 );

function reply( $code, $message ) {
	$GLOBALS['pff_reply'] = array(
		'code' => $code,
		'body' => json_encode( array( 'message' => $message ) ),
	);
}
function denied() {
	return RK::get_denied();
}
function render() {
	ob_start();
	( new Restricted_Key_Notice() )->admin_notice();
	return ob_get_clean();
}

$deny = RK::SCOPE_DENIED_MESSAGE;

reply( 200, $deny );
check( 'get 200 not recorded', array(), denied() );
reply( 403, 'Some other forbidden' );
( new Test_API( 'plan' ) )->get_request();
check( 'other 403 not recorded', array(), denied() );
$GLOBALS['pff_reply'] = new WP_Error();
check( 'wp_error returns false', false, ( new Test_API( 'plan' ) )->get_request() );
check( 'wp_error not recorded', array(), denied() );

reply( 403, $deny );
check( 'scope 403 returns false on get', false, ( new Test_API( 'plan' ) )->get_request() );
check( 'scope 403 records module', array( 'plan' ), array_keys( denied()['test'] ) );
check( 'recorded module holds its first-seen time', true, is_int( denied()['test']['plan'] ) );
check( 'one write', 1, count( $GLOBALS['pff_updates'] ) );
( new Test_API( 'plan' ) )->post_request( array( 'a' => 1 ) );
check( 'same denial writes nothing more', 1, count( $GLOBALS['pff_updates'] ) );
check( 'post 403 returns false', false, ( new Test_API( 'plan' ) )->post_request( array( 'a' => 1 ) ) );
( new Test_API( 'subscription' ) )->post_request( array( 'a' => 1 ) );
check( 'new module writes', 2, count( $GLOBALS['pff_updates'] ) );
( new Test_API( 'plan' ) )->post_request( array( 'a' => 1 ) );
( new Test_API( 'subscription' ) )->post_request( array( 'a' => 1 ) );
check( 'alternating modules do not rewrite', 2, count( $GLOBALS['pff_updates'] ) );
check( 'both modules kept for the mode', array( 'plan', 'subscription' ), array_keys( denied()['test'] ) );

reply( 200, 'ok' );
check( 'success still returns body', 'ok', ( new Test_API( 'plan' ) )->get_request()->message );

reply( 403, $deny );
$GLOBALS['pff_options']['mode'] = 'live';
( new Test_API( 'transaction/verify' ) )->get_request();
check( 'live mode recorded separately', array( 'test', 'live' ), array_keys( denied() ) );
( new Test_API( 'unknown' ) )->get_request();
check( 'unknown module ignored', array( 'transaction/verify' ), array_keys( denied()['live'] ) );

$html = render();
check( 'one notice per mode', 2, substr_count( $html, 'notice notice-error' ) );
check( 'test entry lists both modules', true, false !== strpos( $html, 'in test mode because your restricted key is missing a permission needed for creating plans, creating subscriptions.' ) );
check( 'live entry copy', true, false !== strpos( $html, 'in live mode' ) && false !== strpos( $html, 'verifying payments' ) );
check( 'links to settings', true, false !== strpos( $html, 'href="https://example.test/wp-admin/edit.php?post_type=paystack_form&page=settings"' ) );

$GLOBALS['pff_can'] = false;
check( 'capability gate hides notice', '', render() );
$GLOBALS['pff_can'] = true;

$GLOBALS['pff_options']['pff_paystack_rk_denied'] = array(
	'test' => array( '<script>alert(1)</script>' => 1 ),
	'live' => array( 'plan' => 1, '<b>x</b>' => 2 ),
);
$html = render();
check( 'unknown stored module skipped', 1, substr_count( $html, 'notice notice-error' ) );
check( 'no raw stored markup', false, strpos( $html, '<script>' ) || strpos( $html, '<b>' ) );

$GLOBALS['pff_options']['pff_paystack_rk_denied'] = array( 'live' => array( 'module' => 'plan', 'time' => 1 ) );
$html = render();
check( 'old-shape record still renders', true, false !== strpos( $html, 'in live mode' ) && false !== strpos( $html, 'creating plans' ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
