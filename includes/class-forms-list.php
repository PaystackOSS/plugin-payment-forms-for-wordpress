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
 * Registers the additional functions for the WP Dashboard Forms List
 */
class Forms_List {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'page_row_actions', [ $this, 'quick_edit_links' ], 10, 2 );
	}

	/**
	 * Adds the "View Payments" link to the quick edit and disbles others.
	 *
	 * @param array $actions
	 * @param WP_Post $post
	 * @return array
	 */
	public function quick_edit_links( $actions, $post ) {
		if ( get_post_type( $post ) === 'paystack_form' ) {
			unset( $actions['view'] );
			unset( $actions['quick edit'] );
			$actions['export'] = '<a href="' . admin_url( 'admin.php?page=submissions&form=' . $post->ID ) . '" >' . __( 'View Payments', 'payment_forms' ) . '</a>';
		}
		return $actions;
	}
}
