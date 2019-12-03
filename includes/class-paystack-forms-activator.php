<?php

class Kkd_Pff_Paystack_Activator
{

	public static function activate()
	{
		global $wpdb;
		$version = get_option('kkd_db_version', '1.0');
		$table_name = $wpdb->prefix . KKD_PFF_PAYSTACK_TABLE;

		$charset_collate = $wpdb->get_charset_collate();

		$sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
			id int(11) NOT NULL AUTO_INCREMENT,
			post_id int(11) NOT NULL,
		  	user_id int(11) NOT NULL,
			email varchar(255) DEFAULT '' NOT NULL,
		  	metadata text,
		  	paid int(1) NOT NULL DEFAULT '0',
			plan varchar(255) DEFAULT '' NOT NULL,
			txn_code varchar(255) DEFAULT '' NOT NULL,
			txn_code_2 varchar(255) DEFAULT '' NOT NULL,
			amount varchar(255) DEFAULT '' NOT NULL,
		  	ip varchar(255) NOT NULL, 
			deleted_at varchar(255) DEFAULT '' NULL,
			created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
		  	modified timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
		  	UNIQUE KEY id (id),PRIMARY KEY  (id)
		) $charset_collate;";

		include_once ABSPATH . 'wp-admin/includes/upgrade.php';
		dbDelta($sql);

		if (version_compare($version, '2.0') < 0) {
			$sql = "CREATE TABLE IF NOT EXISTS `" . $table_name . "` (
				id int(11) NOT NULL AUTO_INCREMENT,
				post_id int(11) NOT NULL,
				user_id int(11) NOT NULL,
				email varchar(255) DEFAULT '' NOT NULL,
				metadata text,
				paid int(1) NOT NULL DEFAULT '0',
				plan varchar(255) DEFAULT '' NOT NULL,
				txn_code varchar(255) DEFAULT '' NOT NULL,
				txn_code_2 varchar(255) DEFAULT '' NOT NULL,
				amount varchar(255) DEFAULT '' NOT NULL,
				paid_at timestamp,
				ip varchar(255) NOT NULL,
				deleted_at varchar(255) DEFAULT '' NULL,
				created_at timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
				modified timestamp DEFAULT '0000-00-00 00:00:00' NOT NULL,
				UNIQUE KEY id (id),PRIMARY KEY  (id)
			) $charset_collate;";

			dbDelta($sql);

			update_option('kkd_db_version', '2.0');
		}


		$row = $wpdb->get_results(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = '" . $table_name . "' AND column_name = 'plan'"
		);
		if (empty($row)) {
			$wpdb->query("ALTER TABLE `" . $table_name . "` ADD `plan` VARCHAR(255) NOT NULL AFTER `paid`;");
		}

		$row1 = $wpdb->get_results(
			"SELECT COLUMN_NAME FROM INFORMATION_SCHEMA.COLUMNS
			WHERE table_name = '" . $table_name . "' AND column_name = 'txn_code_2'"
		);
		if (empty($row1)) {
			$wpdb->query("ALTER TABLE `" . $table_name . "` ADD `txn_code_2` VARCHAR(255) DEFAULT '' NULL AFTER `txn_code`;");
		}
	}
}
