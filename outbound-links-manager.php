<?php
/**
 * Plugin Name:       Content Pruning
 * Plugin URI:        https://rc2i.net/
 * Description:       Outil complet d'elagage et d'optimisation de contenu pour WordPress. Analysez le trafic de vos pages, identifiez les contenus sous-performants, gerez vos liens sortants et internes, verifiez les liens casses et prenez des decisions eclairees pour ameliorer votre SEO.
 * Version:           1.0.6
 * Author:            Baptiste REY - Rc2i.net
 * Author URI:        https://rc2i.net/
 * License:           GPLv2 or later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       content-pruning
 * Domain Path:       /languages
 */

// If this file is called directly, abort.
if ( ! defined( 'WPINC' ) ) {
	die;
}

/**
 * Current plugin version.
 */
define( 'OUTBOUND_LINKS_MANAGER_VERSION', '1.0.6' );

/**
 * The code that runs during plugin activation.
 */
function wpcp_activate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	Outbound_Links_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function wpcp_deactivate() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	Outbound_Links_Manager_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'wpcp_activate' );
register_deactivation_hook( __FILE__, 'wpcp_deactivate' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-main.php';

/**
 * Begins execution of the plugin.
 */
function wpcp_run() {
	$plugin = new Outbound_Links_Manager_Main();
	$plugin->run();
}

wpcp_run();
