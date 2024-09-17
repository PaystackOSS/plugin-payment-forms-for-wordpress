<?php
/**
 * The setup plugin class, this will return register the post type and other needed items.
 *
 * @package paystack\payment_forms
 */

namespace paystack\payment_forms;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Plugin Settings class.
 */
class Setup {

	/**
	 * Constructor: Registers the custom post type on WordPress 'init' action.
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_type' ] );
		add_action( 'plugins_loaded', [ $this, 'load_plugin_textdomain' ] );
		add_action( 'plugin_action_links_' . KKD_PFF_PLUGIN_BASENAME, [ $this, 'add_action_links' ] );
	}

    /**
     * Registers the custom post type 'paystack_form'.
     */
    public function register_post_type() {
        $labels = [
            'name'                  => __( 'Paystack Forms', 'paystack_form' ),
            'singular_name'         => __( 'Paystack Form', 'paystack_form' ),
            'add_new'               => __( 'Add New', 'paystack_form' ),
            'add_new_item'          => __( 'Add Paystack Form', 'paystack_form' ),
            'edit_item'             => __( 'Edit Paystack Form', 'paystack_form' ),
            'new_item'              => __( 'Paystack Form', 'paystack_form' ),
            'view_item'             => __( 'View Paystack Form', 'paystack_form' ),
            'all_items'             => __( 'All Forms', 'paystack_form' ),
            'search_items'          => __( 'Search Paystack Forms', 'paystack_form' ),
            'not_found'             => __( 'No Paystack Forms found', 'paystack_form' ),
            'not_found_in_trash'    => __( 'No Paystack Forms found in Trash', 'paystack_form' ),
            'parent_item_colon'     => __( 'Parent Paystack Form:', 'paystack_form' ),
            'menu_name'             => __( 'Paystack Forms', 'paystack_form' ),
		];

        $args = [
            'labels'                => $labels,
            'hierarchical'          => true,
            'description'           => __( 'Paystack Forms filterable by genre', 'paystack_form' ),
            'supports'              => array( 'title', 'editor' ),
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => true,
			'show_in_rest'          => false,
            'menu_position'         => 5,
            'menu_icon'             => KKD_PFF_PAYSTACK_PLUGIN_URL . '/assets/images/logo.png',
            'show_in_nav_menus'     => true,
            'publicly_queryable'    => true,
            'exclude_from_search'   => false,
            'has_archive'           => false,
            'query_var'             => true,
            'can_export'            => true,
            'rewrite'               => false,
            'comments'              => false,
            'capability_type'       => 'post',
		];
        register_post_type( 'paystack_form', $args );
    }

	/**
	 * Load the plugin text domain for translation.
	 */
	public function load_plugin_textdomain() {
		load_plugin_textdomain( 'pff-paystack', false, KKD_PFF_PAYSTACK_PLUGIN_PATH . '/languages/' );
	}

	/**
	 * Add a link to our settings page in the plugin action links.
	 */
	public function add_action_links( $links ) {
		$settings_link = array(
			'<a href="' . admin_url( 'edit.php?post_type=paystack_form&page=settings') . '">' . __( 'Settings', 'paystack_forms' ) . '</a>',
		);
		return array_merge( $settings_link, $links );
	}
}
