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
	 * The plugin name.
	 *
	 * @var string
	 */
	public $plugin_name = 'pff-paystack';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_filter( 'page_row_actions', [ $this, 'quick_edit_links' ], 10, 2 );
		add_filter( 'manage_edit-paystack_form_columns', [ $this, 'register_columns' ], 10, 1 );
		add_action( 'manage_paystack_form_posts_custom_column', [ $this, 'column_data' ], 10, 2 );
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

	/**
	 * Registers our column names.
	 *
	 * @param array $columns
	 * @return array
	 */
	public function register_columns( $columns ) {
		$columns = array(
			'cb'        => '<input type="checkbox" />',
			'title'     => __( 'Name', 'paystack_forms' ),
			'shortcode' => __( 'Shortcode', 'paystack_forms' ),
			'payments'  => __( 'Payments', 'paystack_forms' ),
			'date'      => __( 'Date', 'paystack_forms' )
		);
		return $columns;
	}

	public function column_data( $column, $post_id ) {
		switch ( $column ) {
			case 'shortcode':
				echo wp_kses_post( '<span class="shortcode"><code>[pff-paystack id=&quot;' . $post_id . '&quot;]"</code></span>' );
				break;
			case 'payments':
				$num = $this->get_payments_count( $post_id );
				echo wp_kses_post( '<u><a href="' . admin_url( 'admin.php?page=submissions&form=' . $post_id ) . '">' . $num . '</a></u>' );
				break;
			default:
				break;
		}
	}

	/**
	 * Gets the payments count for the current form.
	 *
	 * @param int|string $form_id
	 * @return int
	 */
	private function get_payments_count( $form_id ) {
		global $wpdb;
		$table = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;
		$num   = wp_cache_get( 'form_payments_' . $form_id, 'pff_paystack' );
		if ( false === $num ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery
			$num = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT COUNT(*)
					FROM %i
					WHERE post_id = %d
					AND paid = '1'",
					$table,
					$form_id
				)
			);
			wp_cache_set( 'form_payments_' . $form_id, $num, 'pff_paystack', 60*5 );
		}
		return $num;
	}
}
