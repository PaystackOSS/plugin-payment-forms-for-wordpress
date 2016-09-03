<?php

/**
 * Fired during plugin activation
 *
 * @link       kendyson.com
 * @since      1.0.0
 *
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Paystack_Forms
 * @subpackage Paystack_Forms/includes
 * @author     kendysond <kendyson@kendyson.com>
 */
class Paystack_Forms_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		global $wpdb;
		$charset_collate = $wpdb->get_charset_collate();
		$table_name = $wpdb->prefix . 'paystack_forms_payments';

		$sql = "CREATE TABLE $table_name (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_id int(11) NOT NULL,
		  user_id int(11) NOT NULL,
		  email varchar(255) DEFAULT '' NOT NULL,
		  metadata text,
		  paid int(1) NOT NULL DEFAULT '0',
			txn_code varchar(255) DEFAULT '' NOT NULL,
		  amount varchar(255) DEFAULT '' NOT NULL,
		  views smallint(5) NOT NULL,
			created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  modified datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY id (id)
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql );
	}



}
