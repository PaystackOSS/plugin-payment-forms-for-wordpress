<?php
// Run: php tests/test-api-key-resolution.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options']  = array();
$GLOBALS['pff_updates']  = array();
$GLOBALS['pff_requests'] = array();
$GLOBALS['pff_secret_calls'] = array();

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
		$GLOBALS['pff_requests'][] = array( $url, $args );
		return array( 'code' => 403, 'body' => json_encode( array( 'message' => \paystack\payment_forms\Restricted_Key::SCOPE_DENIED_MESSAGE ) ) );
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

eval(
	'namespace paystack\\payment_forms;
	class Helpers {
		public static function get_secret_key( $mode ) {
			$GLOBALS["pff_secret_calls"][] = $mode;
			return "secret_for_" . $mode;
		}
	}
	class Test_API extends API {
		public function __construct( $module ) {
			parent::__construct();
			$this->set_module( $module );
		}
		public function public_key() {
			return $this->public;
		}
	}'
);

use paystack\payment_forms\Restricted_Key as RK;
use paystack\payment_forms\Test_API;

define( 'EXPECTED', 22 );

function run( $mode ) {
	$GLOBALS['pff_options']      = array( 'tpk' => 'pk_test_x', 'lpk' => 'pk_live_x' );
	if ( null !== $mode ) {
		$GLOBALS['pff_options']['mode'] = $mode;
	}
	$GLOBALS['pff_secret_calls'] = array();
	$GLOBALS['pff_requests']     = array();
	$GLOBALS['pff_updates']      = array();
	$api = new Test_API( 'plan' );
	$api->get_request();
	return $api;
}

$api = run( 'test' );
check( 'test: secret resolved for test', array( 'test' ), $GLOBALS['pff_secret_calls'] );
check( 'test: Authorization uses resolved secret', 'Bearer secret_for_test', $GLOBALS['pff_requests'][0][1]['headers']['Authorization'] );
check( 'test: public key is tpk', 'pk_test_x', $api->public_key() );
check( 'test: url targets the API base', 'https://api.paystack.co/plan/', $GLOBALS['pff_requests'][0][0] );
check( 'test: denial recorded under test', array( 'test' ), array_keys( RK::get_denied() ) );

$api = run( 'live' );
check( 'live: secret resolved for live', array( 'live' ), $GLOBALS['pff_secret_calls'] );
check( 'live: Authorization uses resolved secret', 'Bearer secret_for_live', $GLOBALS['pff_requests'][0][1]['headers']['Authorization'] );
check( 'live: public key is lpk', 'pk_live_x', $api->public_key() );
check( 'live: denial recorded under live', array( 'live' ), array_keys( RK::get_denied() ) );

$api = run( null );
check( 'unset mode resolves live secret', array( 'live' ), $GLOBALS['pff_secret_calls'] );
check( 'unset mode uses lpk', 'pk_live_x', $api->public_key() );
check( 'unset mode records under live', array( 'live' ), array_keys( RK::get_denied() ) );

$api = run( 'bogus' );
check( 'unknown mode resolves live secret', array( 'live' ), $GLOBALS['pff_secret_calls'] );
check( 'unknown mode uses lpk', 'pk_live_x', $api->public_key() );
check( 'unknown mode records under live', array( 'live' ), array_keys( RK::get_denied() ) );

$api = run( 'TEST' );
check( 'mode comparison is exact: TEST is live', array( 'live' ), $GLOBALS['pff_secret_calls'] );

$api = run( 'test' );
check( 'one request sent', 1, count( $GLOBALS['pff_requests'] ) );
check( 'timeout kept', 60, $GLOBALS['pff_requests'][0][1]['timeout'] );
check( 'secret never in the recorded denial', false, false !== strpos( json_encode( RK::get_denied() ), 'secret_for_' ) );
check( 'BASE_URL constant', 'https://api.paystack.co/', \paystack\payment_forms\API::BASE_URL );
check( 'api_ready is callable', true, method_exists( $api, 'api_ready' ) );
check( 'denial write is one option update', 1, count( $GLOBALS['pff_updates'] ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
