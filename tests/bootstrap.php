<?php
// Shared WordPress stubs and helpers; required by each tests/test-*.php.
if ( PHP_SAPI !== 'cli' ) {
	exit;
}

define( 'ABSPATH', __DIR__ . '/' );

foreach ( array( 'add_action', 'add_filter' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		eval( "function $fn() {}" );
	}
}
if ( ! function_exists( 'apply_filters' ) ) {
	function apply_filters( $tag, $value ) {
		global $pff_filter_overrides;
		return isset( $pff_filter_overrides[ $tag ] ) ? $pff_filter_overrides[ $tag ] : $value;
	}
}
foreach ( array( '__', 'esc_html__', 'esc_html', 'esc_url', 'wp_strip_all_tags', 'wp_kses_post' ) as $fn ) {
	if ( ! function_exists( $fn ) ) {
		eval( "function $fn( \$text ) { return \$text; }" );
	}
}
if ( ! function_exists( 'wp_json_encode' ) ) {
	function wp_json_encode( $data ) {
		return json_encode( $data );
	}
}

// Tests set $completed = true at the end; an early exit or fatal fails the run.
$completed = false;
register_shutdown_function(
	function () {
		global $completed;
		if ( ! $completed ) {
			echo "did not complete\n";
			exit( 1 );
		}
	}
);

$total    = 0;
$failures = 0;
function check( $label, $expected, $actual ) {
	global $total, $failures;
	$total++;
	if ( $expected !== $actual ) {
		$failures++;
		echo "FAIL: $label expected " . var_export( $expected, true ) . ', got ' . var_export( $actual, true ) . "\n";
	}
}
