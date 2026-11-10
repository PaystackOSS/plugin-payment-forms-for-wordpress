<?php
// Run: php tests/test-enqueue-scripts.php
require __DIR__ . '/bootstrap.php';

define( 'PFF_PLUGIN_NAME', 'pff-paystack' );
define( 'PFF_PAYSTACK_PLUGIN_URL', 'https://example.test/pff' );
define( 'PFF_PAYSTACK_VERSION', 'test' );
define( 'PFF_PAYSTACK_PERCENTAGE', 1.5 );
define( 'PFF_PAYSTACK_CROSSOVER_TOTAL', 2500 );
define( 'PFF_PAYSTACK_ADDITIONAL_CHARGE', 100 );
define( 'PFF_PAYSTACK_LOCAL_CAP', 2000 );

$registered = array();
$enqueued   = array();
$localized  = array();
$current    = null;
$printed    = array();
$footer_done = 0;

$deps = array();
function wp_register_script( $handle, $src = '', $d = array() ) { global $registered, $deps; $registered[] = $handle; $deps[ $handle ] = $d; }
function wp_print_scripts( $handle ) { global $printed; $printed[] = $handle; }
function did_action( $hook ) { global $footer_done; return 'wp_print_footer_scripts' === $hook ? $footer_done : 0; }
function wp_enqueue_script( $handle ) { global $enqueued; $enqueued[ $handle ] = true; }
function wp_localize_script( $handle, $name ) { global $localized; $localized[ $handle ] = $name; }
function get_post() { global $current; return $current; }
function has_shortcode( $content, $tag ) { return false !== strpos( $content, '[' . $tag ); }
function get_option( $name, $default = '' ) { return $default; }
function esc_attr( $v ) { return $v; }

require dirname( __DIR__ ) . '/includes/classes/class-helpers.php';
require dirname( __DIR__ ) . '/includes/classes/class-setup.php';

use paystack\payment_forms\Setup;

$handles = array( 'blockUI', 'Paystack', 'pff-paystack-public' );
$reset   = function () { global $registered, $enqueued, $localized, $printed; $registered = $enqueued = $localized = $printed = array(); };
$public  = array( 'pff-paystack-public' );
$setup   = ( new ReflectionClass( Setup::class ) )->newInstanceWithoutConstructor();

// Shortcode lives outside post_content (Bricks, widgets, templates): scripts are only registered up front.
$current = (object) array( 'post_content' => '' );
$setup->enqueue_scripts();
check( 'no shortcode registers all handles', $handles, $registered );
check( 'no shortcode enqueues nothing', array(), $enqueued );
check( 'settings localized on public script', 'pffSettings', $localized['pff-paystack-public'] ?? null );
check( 'public script pulls in blockUI and inline.js', array( 'jquery', 'blockUI', 'Paystack' ), $deps['pff-paystack-public'] );

// ...and rendering the shortcode enqueues them.
Setup::enqueue_public_scripts();
check( 'render enqueues public script', $public, array_keys( $enqueued ) );

// Rendered after footer scripts printed: print directly instead.
$reset();
$footer_done = 1;
Setup::enqueue_public_scripts();
check( 'late render prints public script', $public, $printed );
check( 'late render does not just enqueue', array(), $enqueued );
$footer_done = 0;

foreach ( array( 'pff-paystack', 'paystack_form' ) as $tag ) {
	$reset();
	$current = (object) array( 'post_content' => "[$tag id=\"5\"]" );
	$setup->enqueue_scripts();
	check( "[$tag] in content enqueues early", $public, array_keys( $enqueued ) );
}

$reset();
$current = null;
$setup->enqueue_scripts();
check( 'no global post does not enqueue', array(), $enqueued );
check( 'no global post still registers', $handles, $registered );

$src = file_get_contents( dirname( __DIR__ ) . '/includes/classes/class-form-shortcode.php' );
check( 'form shortcode enqueues scripts', 1, substr_count( $src, 'Setup::enqueue_public_scripts();' ) );

echo "$total checks, $failures failures\n";
$completed = true;
exit( $failures ? 1 : 0 );
