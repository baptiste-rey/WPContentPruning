<?php

/**
 * Fired during plugin deactivation
 *
 * @link       https://example.com
 * @since      1.0.0
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

/**
 * Fired during plugin deactivation.
 *
 * This class defines all code necessary to run during the plugin's deactivation.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 * @author     Your Name <email@example.com>
 */
class Outbound_Links_Manager_Deactivator {

	/**
	 * Short Description. (use period)
	 *
	 * Long Description.
	 *
	 * @since    1.0.0
	 */
	public static function deactivate() {
		// Clean up rules or transient data if necessary.
		// Usually we don't drop tables on simple deactivation to preserve data,
		// unless it's a specific 'uninstall' event.
	}

}
