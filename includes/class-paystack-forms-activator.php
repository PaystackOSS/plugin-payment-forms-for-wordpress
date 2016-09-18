<?php
class Kkd_Pff_Paystack_Activator {

	public static function activate() {
		global $wpdb;
		$table_name = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;

		$sql = "CREATE TABLE IF NOT EXISTS  `".$table_name."` (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_id int(11) NOT NULL,
		  user_id int(11) NOT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
		  metadata text,
		  paid int(1) NOT NULL DEFAULT '0',
			plan varchar(255) DEFAULT '' NOT NULL,
			txn_code varchar(255) DEFAULT '' NOT NULL,
		  amount varchar(255) DEFAULT '' NOT NULL,
		  ip varchar(255) NOT NULL,
			deleted_at varchar(255) DEFAULT '' NULL,
			created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  modified timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  UNIQUE KEY id (id),PRIMARY KEY  (id)
		);";
		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta($sql);
		$row = $wpdb->get_results(  "SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = '".$table_name."' AND column_name = 'plan'"  );

		if(empty($row)){
			$wpdb->query("ALTER TABLE `".$table_name."` ADD `plan` VARCHAR(255) NOT NULL AFTER `paid`;");
		}

	}



}
