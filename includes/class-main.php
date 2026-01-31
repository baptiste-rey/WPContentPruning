<?php

/**
 * The file that defines the core plugin class
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Main {

	/**
	 * The loader that's responsible for maintaining and registering all hooks that power
	 * the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      Outbound_Links_Manager_Loader    $loader    Maintains and registers all hooks for the plugin.
	 */
	protected $loader;

	/**
	 * The unique identifier of this plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $plugin_name    The string used to uniquely identify this plugin.
	 */
	protected $plugin_name;

	/**
	 * The current version of the plugin.
	 *
	 * @since    1.0.0
	 * @access   protected
	 * @var      string    $version    The current version of the plugin.
	 */
	protected $version;

	/**
	 * Define the core functionality of the plugin.
	 *
	 * @since    1.0.0
	 */
	public function __construct() {
		if ( defined( 'OUTBOUND_LINKS_MANAGER_VERSION' ) ) {
			$this->version = OUTBOUND_LINKS_MANAGER_VERSION;
		} else {
			$this->version = '1.0.0';
		}
		$this->plugin_name = 'outbound-links-manager';

		$this->load_dependencies();
		$this->check_db_update();
		$this->define_admin_hooks();
		$this->define_public_hooks();
	}

	/**
	 * Vérifie si la base de données a besoin d'une mise à jour.
	 */
	private function check_db_update() {
		$installed_version = get_option( 'wpcp_db_version', '0' );
		if ( version_compare( $installed_version, $this->version, '<' ) ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-database.php';
			Outbound_Links_Manager_Database::create_tables();
			update_option( 'wpcp_db_version', $this->version );
		}
	}

	/**
	 * Load the required dependencies for this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function load_dependencies() {

		/**
		 * The class responsible for orchestrating the actions and filters of the
		 * core plugin.
		 */
		require_once plugin_dir_path( __FILE__ ) . 'class-loader.php';

		/**
		 * The class responsible for defining validation rules.
		 * (We'll create this or just skip if not critical yet, but let's stick to core)
		 */
		// require_once plugin_dir_path( __FILE__ ) . 'class-validator.php'; 

		/**
		 * The class responsible for defining all actions that occur in the admin area.
		 */
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'admin/class-admin.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-scanner.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-internal-scanner.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-link-manager.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-link-checker.php';
		require_once plugin_dir_path( __FILE__ ) . 'class-redirections.php';

		$this->loader = new Outbound_Links_Manager_Loader();

	}

	/**
	 * Register all of the hooks related to the admin area functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_admin_hooks() {

		$plugin_admin = new Outbound_Links_Manager_Admin( $this->get_plugin_name(), $this->get_version() );

		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_styles' );
		$this->loader->add_action( 'admin_enqueue_scripts', $plugin_admin, 'enqueue_scripts' );
		$this->loader->add_action( 'admin_menu', $plugin_admin, 'add_plugin_admin_menu' );
		
		// AJAX Hooks for Scanner
		$this->loader->add_action( 'wp_ajax_olm_start_scan', $plugin_admin, 'ajax_start_scan' );
		$this->loader->add_action( 'wp_ajax_olm_process_scan_batch', $plugin_admin, 'ajax_process_scan_batch' );
		$this->loader->add_action( 'wp_ajax_olm_purge_links', $plugin_admin, 'ajax_purge_links' );
		$this->loader->add_action( 'wp_ajax_olm_debug_post_scan', $plugin_admin, 'ajax_debug_post_scan' );

		// AJAX Hooks for Link Manager
		$this->loader->add_action( 'wp_ajax_olm_get_link', $plugin_admin, 'ajax_get_link' );
		$this->loader->add_action( 'wp_ajax_olm_update_link', $plugin_admin, 'ajax_update_link' );
		$this->loader->add_action( 'wp_ajax_olm_delete_link', $plugin_admin, 'ajax_delete_link' );

		// AJAX Hooks for Internal Links Scanner
		$this->loader->add_action( 'wp_ajax_olm_start_internal_scan', $plugin_admin, 'ajax_start_internal_scan' );
		$this->loader->add_action( 'wp_ajax_olm_process_internal_scan_batch', $plugin_admin, 'ajax_process_internal_scan_batch' );
		$this->loader->add_action( 'wp_ajax_olm_get_internal_links', $plugin_admin, 'ajax_get_internal_links' );
		$this->loader->add_action( 'wp_ajax_olm_get_incoming_links', $plugin_admin, 'ajax_get_incoming_links' );
		$this->loader->add_action( 'wp_ajax_olm_get_page_keywords', $plugin_admin, 'ajax_get_page_keywords' );

		// AJAX Hooks for Comments Management
		$this->loader->add_action( 'wp_ajax_olm_delete_all_comments', $plugin_admin, 'ajax_delete_all_comments' );

		// AJAX Hooks for Link Status Checking
		$this->loader->add_action( 'wp_ajax_olm_start_link_check', $plugin_admin, 'ajax_start_link_check' );
		$this->loader->add_action( 'wp_ajax_olm_process_link_check_batch', $plugin_admin, 'ajax_process_link_check_batch' );
		$this->loader->add_action( 'wp_ajax_olm_delete_links_by_status', $plugin_admin, 'ajax_delete_links_by_status' );

		// AJAX Hooks for Traffic Sync
		$this->loader->add_action( 'wp_ajax_olm_sync_traffic_data', $plugin_admin, 'ajax_sync_traffic_data' );
		$this->loader->add_action( 'wp_ajax_olm_delete_traffic_page', $plugin_admin, 'ajax_delete_traffic_page' );

		// AJAX Hooks for Google API
		$this->loader->add_action( 'wp_ajax_olm_google_disconnect', $plugin_admin, 'ajax_google_disconnect' );
		$this->loader->add_action( 'wp_ajax_olm_test_google_api', $plugin_admin, 'ajax_test_google_api' );

	}

	/**
	 * Register all of the hooks related to the public-facing functionality
	 * of the plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 */
	private function define_public_hooks() {
		// Redirections 301 sur le front-end
		$this->loader->add_action( 'template_redirect', 'Outbound_Links_Manager_Redirections', 'handle_redirect' );
	}

	/**
	 * Run the loader to execute all of the hooks with WordPress.
	 *
	 * @since    1.0.0
	 */
	public function run() {
		$this->loader->run();
	}

	/**
	 * The name of the plugin used to uniquely identify it within the context of
	 * WordPress and to define internationalization functionality.
	 *
	 * @since     1.0.0
	 * @return    string    The name of the plugin.
	 */
	public function get_plugin_name() {
		return $this->plugin_name;
	}

	/**
	 * The reference to the class that orchestrates the hooks with the plugin.
	 *
	 * @since     1.0.0
	 * @return    Outbound_Links_Manager_Loader    Orchestrates the hooks of the plugin.
	 */
	public function get_loader() {
		return $this->loader;
	}

	/**
	 * Retrieve the version number of the plugin.
	 *
	 * @since     1.0.0
	 * @return    string    The version number of the plugin.
	 */
	public function get_version() {
		return $this->version;
	}

}
