<?php

/**
 * The plugin bootstrap file
 *
 * This file is read by WordPress to generate the plugin information in the plugin
 * admin area. This file also includes all of the dependencies used by the plugin,
 * registers the activation and deactivation functions, and defines a function
 * that starts the plugin.
 *
 * @link              kendyson.com
 * @since             1.0.0
 * @package           Paystack_Forms
 *
 * @wordpress-plugin
 *Plugin Name: Paystack Forms
 *Plugin URI: http://example.com
 *Description: Make Payment Forms for Paystack
 *Author: Douglas Kendyson
 *Author URI: http://kendyson.com
 * Version:           1.0.0
 * License:           GPL-2.0+
 * License URI:       http://www.gnu.org/licenses/gpl-2.0.txt
 * Text Domain:       paystack-forms
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}



/**
 * The code that runs during plugin activation.
 * This action is documented in includes/class-paystack-forms-activator.php
 */
function activate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-activator.php';
	Paystack_Forms_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 * This action is documented in includes/class-paystack-forms-deactivator.php
 */
function deactivate_paystack_forms() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms-deactivator.php';
	Paystack_Forms_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_paystack_forms' );
register_deactivation_hook( __FILE__, 'deactivate_paystack_forms' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-paystack-forms.php';

/**
 * Begins execution of the plugin.
 *
 * Since everything within the plugin is registered via hooks,
 * then kicking off the plugin from this point in the file does
 * not affect the page life cycle.
 *
 * @since    1.0.0
 */
function run_paystack_forms() {

	$plugin = new Paystack_Forms();
	$plugin->run();

}
run_paystack_forms();
