<?php
// Run: php tests/test-request-plan.php
require __DIR__ . '/bootstrap.php';

$GLOBALS['pff_options'] = array( 'mode' => 'test' );
$GLOBALS['pff_reply']   = null;
$GLOBALS['pff_calls']   = array();

if ( ! function_exists( 'get_option' ) ) {
	function get_option( $name, $default = false ) {
		return array_key_exists( $name, $GLOBALS['pff_options'] ) ? $GLOBALS['pff_options'][ $name ] : $default;
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
foreach ( array( 'wp_remote_get', 'wp_remote_post' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		eval( "function $fn( \$url, \$args ) { \$GLOBALS['pff_calls'][] = array( '$fn', \$url, \$args ); return \$GLOBALS['pff_reply']; }" );
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
require dirname( __DIR__ ) . '/includes/classes/class-request-plan.php';

eval(
	'namespace paystack\\payment_forms;
	class Helpers {
		public static function get_secret_key( $mode ) {
			return "rk_test_x";
		}
	}'
);

use paystack\payment_forms\Request_Plan;

define( 'EXPECTED', 21 );

function reply( $code, $body ) {
	$GLOBALS['pff_reply'] = array( 'code' => $code, 'body' => json_encode( $body ) );
	$GLOBALS['pff_calls'] = array();
}
function plan( $code, $currency = 'ZAR', $archived = false, $deleted = false, $extra = array() ) {
	return array_merge( array( 'plan_code' => $code, 'currency' => $currency, 'amount' => 10000, 'interval' => 'monthly', 'invoice_limit' => 0, 'is_archived' => $archived, 'is_deleted' => $deleted ), $extra );
}

$rp = new Request_Plan();

// list_plans skips archived, deleted and other-currency plans.
reply( 200, array( 'status' => true, 'meta' => array( 'total' => 4 ), 'data' => array( plan( 'PLN_arch', 'ZAR', true ), plan( 'PLN_del', 'ZAR', false, true ), plan( 'PLN_ngn', 'NGN' ), plan( 'PLN_ok' ) ) ) );
$found = $rp->list_plans( '?amount=10000&interval=monthly', 'ZAR' );
check( 'list skips archived/deleted/other currency', 'PLN_ok', $found ? $found->plan_code : false );
check( 'list url', 'https://api.paystack.co/plan/?amount=10000&interval=monthly', $GLOBALS['pff_calls'][0][1] );

reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_arch', 'ZAR', true ) ) ) );
check( 'list only archived returns false', false, $rp->list_plans( '?amount=1', 'ZAR' ) );

reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_ngn', 'NGN' ) ) ) );
check( 'list other currency returns false', false, $rp->list_plans( '?amount=1', 'ZAR' ) );
$any = $rp->list_plans( '?amount=1' );
check( 'list without currency accepts any', 'PLN_ngn', $any ? $any->plan_code : false );

reply( 200, array( 'status' => true, 'data' => array() ) );
check( 'list empty returns false', false, $rp->list_plans( '?amount=1', 'ZAR' ) );

reply( 403, array( 'message' => 'denied' ) );
check( 'list error returns false', false, $rp->list_plans( '?amount=1', 'ZAR' ) );

reply( 200, array( 'status' => true, 'data' => array( array( 'name' => 'no code' ) ) ) );
check( 'list plan without code skipped', false, $rp->list_plans( '?amount=1' ) );

check( 'list empty args no request', false, $rp->list_plans( '' ) );

// list_plans checks the terms itself rather than trusting the API filter.
reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_amt', 'ZAR', false, false, array( 'amount' => 100 ) ), plan( 'PLN_wk', 'ZAR', false, false, array( 'interval' => 'weekly' ) ), plan( 'PLN_lim', 'ZAR', false, false, array( 'invoice_limit' => 3 ) ), plan( 'PLN_match' ) ) ) );
$m = $rp->list_plans( '?x', 'ZAR', 10000, 'monthly' );
check( 'list skips wrong amount, interval and limited plans', 'PLN_match', $m ? $m->plan_code : false );
reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_amt', 'ZAR', false, false, array( 'amount' => 100 ) ) ) ) );
check( 'list wrong amount only returns false', false, $rp->list_plans( '?x', 'ZAR', 10000, 'monthly' ) );
reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_wk', 'ZAR', false, false, array( 'interval' => 'weekly' ) ) ) ) );
check( 'list wrong interval only returns false', false, $rp->list_plans( '?x', 'ZAR', 10000, 'monthly' ) );
reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_lim', 'ZAR', false, false, array( 'invoice_limit' => 3 ) ) ) ) );
check( 'list limited plan only returns false', false, $rp->list_plans( '?x', 'ZAR', 10000, 'monthly' ) );
reply( 200, array( 'status' => true, 'data' => array( plan( 'PLN_str', 'ZAR', false, false, array( 'amount' => '10000' ) ) ) ) );
$m = $rp->list_plans( '?x', 'ZAR', 10000, 'monthly' );
check( 'list numeric-string amount matches', 'PLN_str', $m ? $m->plan_code : false );

// create_plan reads data.plan_code from a real create response.
$body = array( 'name' => 'x', 'amount' => 12000, 'interval' => 'monthly', 'currency' => 'ZAR' );
foreach ( array( 200, 201 ) as $status ) {
	reply( $status, array( 'status' => true, 'message' => 'Plan created', 'data' => array( 'plan_code' => 'PLN_new', 'interval' => 'monthly', 'currency' => 'ZAR' ) ) );
	$created = $rp->create_plan( $body );
	check( "create $status returns response", 'PLN_new', $created ? $created->data->plan_code : false );
}
check( 'create posts body', $body, $GLOBALS['pff_calls'][0][2]['body'] );

reply( 201, array( 'status' => true, 'plan_code' => 'PLN_top' ) );
check( 'create without data.plan_code false', false, $rp->create_plan( $body ) );

reply( 400, array( 'status' => false, 'message' => 'bad' ) );
check( 'create 400 false', false, $rp->create_plan( $body ) );

$GLOBALS['pff_reply'] = new WP_Error();
check( 'create WP_Error false', false, $rp->create_plan( $body ) );

check( 'create empty body false', false, $rp->create_plan( array() ) );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
