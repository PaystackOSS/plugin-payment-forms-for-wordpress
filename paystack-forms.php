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
function register_cpt_paystack_form() {

    $labels = array(
        'name' => _x( 'Paystack Forms', 'paystack_form' ),
        'singular_name' => _x( 'Paystack Form', 'paystack_form' ),
        'add_new' => _x( 'Add New', 'paystack_form' ),
        'add_new_item' => _x( 'Add Paystack Form', 'paystack_form' ),
        'edit_item' => _x( 'Edit Paystack Form', 'paystack_form' ),
        'new_item' => _x( 'Paystack Form', 'paystack_form' ),
        'view_item' => _x( 'View Paystack Form', 'paystack_form' ),
        'search_items' => _x( 'Search Paystack Forms', 'paystack_form' ),
        'not_found' => _x( 'No Paystack Forms found', 'paystack_form' ),
        'not_found_in_trash' => _x( 'No Paystack Forms found in Trash', 'paystack_form' ),
        'parent_item_colon' => _x( 'Parent Paystack Form:', 'paystack_form' ),
        'menu_name' => _x( 'Paystack Forms', 'paystack_form' ),
    );

    $args = array(
        'labels' => $labels,
        'hierarchical' => true,
        'description' => 'Paystack Forms filterable by genre',
        'supports' => array( 'title', 'editor', 'author', 'thumbnail', 'trackbacks', 'custom-fields', 'comments', 'revisions', 'page-attributes' ),
        'taxonomies' => array( 'genres' ),
        'public' => true,
        'show_ui' => true,
        'show_in_menu' => true,
        'menu_position' => 5,
        'menu_icon' => plugins_url('images/logo.png', __FILE__),
        'show_in_nav_menus' => true,
        'publicly_queryable' => true,
        'exclude_from_search' => false,
        'has_archive' => true,
        'query_var' => true,
        'can_export' => true,
        'rewrite' => true,
        'capability_type' => 'post'
    );

    register_post_type( 'paystack_form', $args );
}

add_action( 'init', 'register_cpt_paystack_form' );

function genres_taxonomy() {
    register_taxonomy(
        'genres',
        'paystack_form',
        array(
            'hierarchical' => true,
            'label' => 'Genres',
            'query_var' => true,
            'rewrite' => array(
                'slug' => 'genre',
                'with_front' => false
            )
        )
    );
}
add_action( 'init', 'genres_taxonomy');
// Function used to automatically create Paystack Forms page.
function create_paystack_form_pages()
  {
   //post status and options
    $post = array(
          'comment_status' => 'closed',
          'ping_status' =>  'closed' ,
          'post_date' => date('Y-m-d H:i:s'),
          'post_name' => 'paystack_form',
          'post_status' => 'publish' ,
          'post_title' => 'Paystack Forms',
          'post_type' => 'page',
    );
    //insert page and save the id
    $newvalue = wp_insert_post( $post, false );
    //save the id in the database
    update_option( 'mrpage', $newvalue );
  }
  register_activation_hook( __FILE__, 'create_paystack_form_pages');
