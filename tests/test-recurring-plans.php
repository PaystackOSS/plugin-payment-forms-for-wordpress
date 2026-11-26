<?php
// Run: php tests/test-recurring-plans.php
require __DIR__ . '/bootstrap.php';

if ( ! function_exists( 'sanitize_text_field' ) ) {
	function sanitize_text_field( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'wp_unslash' ) ) {
	function wp_unslash( $text ) {
		return $text;
	}
}
if ( ! function_exists( 'get_the_title' ) ) {
	function get_the_title( $id ) {
		return 'Form ' . $id;
	}
}

require dirname( __DIR__ ) . '/includes/classes/class-form-submit.php';

eval(
	'namespace paystack\\payment_forms;
	class Fake_Plans {
		public $list = false;
		public $create = false;
		public $calls = array();
		public function list_plans( $args, $currency = "", $amount = null, $interval = null ) {
			$this->calls[] = array( "list", $args, $currency, $amount, $interval );
			return $this->list;
		}
		public function create_plan( $body ) {
			$this->calls[] = array( "create", $body );
			return $this->create;
		}
	}
	class Recur_Failed extends \Exception {}
	class Test_Submit extends Form_Submit {
		public function setup( $meta, $form_data ) {
			$this->meta = $meta;
			$this->form_data = $form_data;
			$this->metadata = array( "pf-plancode" => isset( $form_data["pf-plancode"] ) ? $form_data["pf-plancode"] : "" );
			$this->fixed_metadata = array();
		}
		public function plancode() {
			return $this->meta["plancode"];
		}
		public function fixed() {
			return $this->fixed_metadata;
		}
		protected function fail_recurring() {
			throw new Recur_Failed();
		}
	}
	function pff_paystack() {
		return $GLOBALS["pff_app"];
	}'
);

use paystack\payment_forms\Fake_Plans;
use paystack\payment_forms\Test_Submit;
use paystack\payment_forms\Recur_Failed;

define( 'EXPECTED', 20 );

$plans = new Fake_Plans();
$GLOBALS['pff_app'] = (object) array( 'classes' => array( 'request-plan' => $plans ) );

function meta( $recur, $recurplan = '' ) {
	return array( 'recur' => $recur, 'recurplan' => $recurplan, 'currency' => 'ZAR' );
}
function submit( $meta, $form_data, $amount ) {
	global $plans;
	$plans->calls = array();
	$s = new Test_Submit();
	$s->setup( $meta, $form_data );
	try {
		$s->process_recurring_plans( $amount );
	} catch ( Recur_Failed $e ) {
		return 'failed';
	}
	return $s;
}

// Fixed plan forms use the form's own plan, never the submitted one.
$s = submit( meta( 'plan', 'PLN_owner' ), array( 'pf-plancode' => 'PLN_cheap' ), 100 );
check( 'fixed plan ignores submitted code', 'PLN_owner', $s->plancode() );
check( 'fixed plan makes no API calls', array(), $plans->calls );
$s = submit( meta( 'plan', '' ), array( 'pf-plancode' => 'PLN_cheap' ), 100 );
check( 'fixed plan without owner plan stays empty', '', $s->plancode() );

// Optional: an existing matching plan is reused with the exact terms requested.
$plans->list   = (object) array( 'plan_code' => 'PLN_found', 'interval' => 'monthly' );
$plans->create = false;
$s = submit( meta( 'optional' ), array( 'pf-interval' => 'monthly' ), 100 );
check( 'optional reuses found plan', 'PLN_found', $s->plancode() );
check( 'optional list call terms', array( 'list', '?amount=10000&interval=monthly', 'ZAR', 10000, 'monthly' ), $plans->calls[0] );
check( 'optional records interval', 'monthly', $s->fixed()[1]['value'] );

// Optional: no match, so a plan is created in the form currency and used.
$plans->list   = false;
$plans->create = json_decode( '{"status":true,"data":{"plan_code":"PLN_new"}}' );
$s = submit( meta( 'optional' ), array( 'pf-interval' => 'weekly' ), 120.5 );
check( 'optional uses created plan', 'PLN_new', $s->plancode() );
check( 'create body', array( 'name' => 'Form 0 [ZAR121] - weekly', 'amount' => 12050, 'interval' => 'weekly', 'currency' => 'ZAR' ), $plans->calls[1][1] );
check( 'created plan interval falls back to requested', 'weekly', $s->fixed()[1]['value'] );

// Optional: plan creation failing stops the payment instead of charging once.
$plans->create = false;
check( 'optional create failure fails', 'failed', submit( meta( 'optional' ), array( 'pf-interval' => 'monthly' ), 100 ) );

// Optional: tampered or invalid input never reaches Paystack.
foreach ( array( 'monthly&amount=1', 'hourly', '' ) as $bad ) {
	check( "interval '$bad' fails", 'failed', submit( meta( 'optional' ), array( 'pf-interval' => $bad ), 100 ) );
	check( "interval '$bad' no API call", array(), $plans->calls );
}
check( 'zero amount fails', 'failed', submit( meta( 'optional' ), array( 'pf-interval' => 'monthly' ), 0 ) );
check( 'zero amount no API call', array(), $plans->calls );

// Optional: no interval chosen, or none submitted, is a one-off payment.
$s = submit( meta( 'optional' ), array( 'pf-interval' => 'no' ), 100 );
check( 'interval none is one-off', '', $s->plancode() );
$s = submit( meta( 'optional' ), array(), 100 );
check( 'missing interval is one-off', '', $s->plancode() );

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
