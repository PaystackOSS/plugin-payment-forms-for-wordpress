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
}
