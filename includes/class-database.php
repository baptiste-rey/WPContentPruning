<?php

/**
 * Handles database operations
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Database {

	/**
	 * Create or update the plugin tables.
	 *
	 * @since 1.0.0
	 */
	public static function create_tables() {
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		$table_links = $wpdb->prefix . 'outbound_links';
		$table_history = $wpdb->prefix . 'outbound_links_history';
		$table_meta = $wpdb->prefix . 'outbound_links_meta';

		// SQL for Links Table
		$sql_links = "CREATE TABLE $table_links (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url varchar(2083) NOT NULL,
			anchor_text text,
			post_id bigint(20) NOT NULL,
			post_type varchar(20) NOT NULL,
			link_attributes text,
			occurrence_count int(11) DEFAULT 1,
			http_status int(3) DEFAULT NULL,
			last_checked datetime DEFAULT '0000-00-00 00:00:00',
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY url (url(191)),
			KEY post_id (post_id),
			KEY http_status (http_status)
		) $charset_collate;";

		// SQL for History Table
		$sql_history = "CREATE TABLE $table_history (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			link_id bigint(20) NOT NULL,
			action_type varchar(50) NOT NULL,
			old_value text,
			new_value text,
			user_id bigint(20) NOT NULL,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY link_id (link_id)
		) $charset_collate;";

		// SQL for Meta Table (Optional/Future proofing)
		$sql_meta = "CREATE TABLE $table_meta (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			link_id bigint(20) NOT NULL,
			meta_key varchar(255) DEFAULT '',
			meta_value longtext,
			PRIMARY KEY  (id),
			KEY link_id (link_id),
			KEY meta_key (meta_key(191))
		) $charset_collate;";

		// SQL for Internal Links Stats Table
		$table_internal = $wpdb->prefix . 'internal_links_stats';
		$sql_internal = "CREATE TABLE $table_internal (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			post_type varchar(20) NOT NULL,
			internal_links_count int(11) DEFAULT 0,
			last_scanned datetime DEFAULT CURRENT_TIMESTAMP,
			updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY post_id (post_id),
			KEY internal_links_count (internal_links_count)
		) $charset_collate;";

		// SQL for Internal Links Details Table
		$table_internal_details = $wpdb->prefix . 'internal_links_details';
		$sql_internal_details = "CREATE TABLE $table_internal_details (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			post_id bigint(20) NOT NULL,
			target_url varchar(2083) NOT NULL,
			target_post_id bigint(20) DEFAULT NULL,
			anchor_text text,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			KEY post_id (post_id),
			KEY target_post_id (target_post_id),
			KEY target_url (target_url(191))
		) $charset_collate;";

		// SQL for Page Traffic Table
		$table_page_traffic = $wpdb->prefix . 'page_traffic';
		$sql_page_traffic = "CREATE TABLE $table_page_traffic (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			url varchar(2083) NOT NULL,
			post_id bigint(20) DEFAULT NULL,
			impressions bigint(20) DEFAULT 0,
			clicks bigint(20) DEFAULT 0,
			users bigint(20) DEFAULT 0,
			sessions bigint(20) DEFAULT 0,
			last_updated datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY url (url(191)),
			KEY post_id (post_id)
		) $charset_collate;";

		// SQL for Redirections Table (301)
		$table_redirections = $wpdb->prefix . 'wpcp_redirections';
		$sql_redirections = "CREATE TABLE $table_redirections (
			id bigint(20) NOT NULL AUTO_INCREMENT,
			source_url varchar(2083) NOT NULL,
			target_url varchar(2083) NOT NULL,
			http_code int(3) DEFAULT 301,
			hits bigint(20) DEFAULT 0,
			created_at datetime DEFAULT CURRENT_TIMESTAMP,
			PRIMARY KEY  (id),
			UNIQUE KEY source_url (source_url(191))
		) $charset_collate;";

		require_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
		dbDelta( $sql_links );
		dbDelta( $sql_history );
		dbDelta( $sql_meta );
		dbDelta( $sql_internal );
		dbDelta( $sql_internal_details );
		dbDelta( $sql_page_traffic );
		dbDelta( $sql_redirections );
	}
}
