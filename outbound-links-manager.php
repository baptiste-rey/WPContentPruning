<?php
/**
 * Plugin Name:       WP Content Pruning (WPCP)
 * Plugin URI:        https://rc2i.net/
 * Description:       Outil complet d'élagage et d'optimisation de contenu pour WordPress. Analysez le trafic de vos pages, identifiez les contenus sous-performants, gérez vos liens sortants et internes, vérifiez les liens cassés et prenez des décisions éclairées pour améliorer votre SEO.
 * Version:           1.0.6
 * Author:            Baptiste REY - Rc2i.net
 * Author URI:        https://rc2i.net/
 * Text Domain:       wp-content-pruning
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
function activate_outbound_links_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-activator.php';
	Outbound_Links_Manager_Activator::activate();
}

/**
 * The code that runs during plugin deactivation.
 */
function deactivate_outbound_links_manager() {
	require_once plugin_dir_path( __FILE__ ) . 'includes/class-deactivator.php';
	Outbound_Links_Manager_Deactivator::deactivate();
}

register_activation_hook( __FILE__, 'activate_outbound_links_manager' );
register_deactivation_hook( __FILE__, 'deactivate_outbound_links_manager' );

/**
 * The core plugin class that is used to define internationalization,
 * admin-specific hooks, and public-facing site hooks.
 */
require plugin_dir_path( __FILE__ ) . 'includes/class-main.php';

/**
 * Begins execution of the plugin.
 */
function run_outbound_links_manager() {
	$plugin = new Outbound_Links_Manager_Main();
	$plugin->run();
}

run_outbound_links_manager();
