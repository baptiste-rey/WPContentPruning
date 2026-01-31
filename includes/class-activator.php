<?php

/**
 * Fired during plugin activation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

/**
 * Fired during plugin activation.
 *
 * This class defines all code necessary to run during the plugin's activation.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 * @author     Your Name <email@example.com>
 */
class Outbound_Links_Manager_Activator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function activate() {
		require_once plugin_dir_path( __FILE__ ) . 'class-database.php';
		Outbound_Links_Manager_Database::create_tables();
	}

}
