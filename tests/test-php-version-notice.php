<?php
// Run: php tests/test-php-version-notice.php
require __DIR__ . '/bootstrap.php';

require dirname( __DIR__ ) . '/includes/classes/class-php-version-notice.php';

$cases = array(
	array( '8.2.34', true ),
	array( '8.2.35', false ),
	array( '8.3.35', true ),
	array( '8.3.36', false ),
	array( '8.4.26', true ),
	array( '8.4.27', false ),
	array( '8.5.11', true ),
	array( '8.5.12', false ),
	array( '8.1.99', true ),
	array( '7.4.33', true ),
	array( '5.6.40', true ),
	array( '8.2', true ),
	array( '8.2.35RC1', false ),
	array( '8.6.0-dev', false ),
	array( '9.0.0', false ),
	array( '8.2.7-1ubuntu2', true ),
	array( '8.10.0', false ),
	array( 'garbage', false ),
	array( '', false ),
	array( '8.2.36', false ),
	array( '8.3.37', false ),
	array( '8.4.28', false ),
	array( '8.5.13', false ),
	array( '8.2.0', true ),
	array( '8.3.0', true ),
	array( '8.0.30', true ),
	array( '8.1.0', true ),
	array( '7.0.0', true ),
	array( '7.4', true ),
	array( '8.5', true ),
	array( '8.5.11-dev', true ),
	array( '8.2.34-1+deb12', true ),
	array( '8.6.1', false ),
	array( '8.7.0', false ),
	array( ' 8.2.1', false ),
);

define( 'EXPECTED', 37 );

$total    = 0;
$failures = 0;
foreach ( $cases as $case ) {
	$total++;
	$actual = \paystack\payment_forms\PHP_Version_Notice::is_affected( $case[0] );
	if ( $actual !== $case[1] ) {
		$failures++;
		echo 'FAIL: "' . $case[0] . '" expected ' . var_export( $case[1], true ) . ', got ' . var_export( $actual, true ) . "\n";
	}
}

$n = new \paystack\payment_forms\PHP_Version_Notice();
$tests = $n->add_site_health_test( array( 'direct' => array( 'existing' => 1 ) ) );
$total++;
if ( 1 !== $tests['direct']['existing'] || ! isset( $tests['direct']['pff_paystack_php_version']['test'] ) ) {
	$failures++;
	echo "FAIL: add_site_health_test registration\n";
}
$result = $n->site_health_result();
$total++;
if ( ! in_array( $result['status'], array( 'good', 'recommended', 'critical' ), true ) || 'pff_paystack_php_version' !== $result['test'] ) {
	$failures++;
	echo "FAIL: site_health_result shape\n";
}

echo "$total assertions, $failures failures\n";
$completed = true;
exit( ( $failures > 0 || EXPECTED !== $total ) ? 1 : 0 );
