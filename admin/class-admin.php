<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * The admin-specific functionality of the plugin.
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/admin
 */

class Outbound_Links_Manager_Admin {

	/**
	 * The ID of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $plugin_name    The ID of this plugin.
	 */
	private $plugin_name;

	/**
	 * The version of this plugin.
	 *
	 * @since    1.0.0
	 * @access   private
	 * @var      string    $version    The current version of this plugin.
	 */
	private $version;

	/**
	 * Initialize the class and set its properties.
	 *
	 * @since    1.0.0
	 * @param    string    $plugin_name    The name of this plugin.
	 * @param    string    $version        The version of this plugin.
	 */
	public function __construct( $plugin_name, $version ) {
		$this->plugin_name = $plugin_name;
		$this->version = $version;
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles() {
		wp_enqueue_style( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'css/admin.css', array(), $this->version, 'all' );
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts() {
		wp_enqueue_script( $this->plugin_name, plugin_dir_url( __FILE__ ) . 'js/admin.js', array( 'jquery' ), $this->version, true );

		// Localize script for AJAX
		wp_localize_script( $this->plugin_name, 'olm_ajax', array(
			'ajax_url' => admin_url( 'admin-ajax.php' ),
			'nonce'    => wp_create_nonce( 'olm_ajax_nonce' )
		));
	}

	/**
	 * Register the administration menu for this plugin into the WordPress Dashboard menu.
	 *
	 * @since    1.0.0
	 */
	public function add_plugin_admin_menu() {
		add_menu_page(
			'WP Content Pruning',
			'Content Pruning',
			'manage_options',
			$this->plugin_name,
			array( $this, 'display_plugin_admin_page' ),
			'dashicons-editor-unlink',
			30
		);
	}

	/**
	 * Render the generic administration page for this plugin.
	 *
	 * @since    1.0.0
	 */
	public function display_plugin_admin_page() {
		$active_tab = isset( $_GET['tab'] ) ? $_GET['tab'] : 'outbound';

		// Handle Google OAuth callback
		if ( $active_tab == 'settings' && isset( $_GET['google_auth'] ) && $_GET['google_auth'] == 'callback' ) {
			$this->handle_google_oauth_callback();
		}

		// Save settings
		if ( $active_tab == 'settings' && isset( $_POST['olm_save_settings'] ) ) {
			$this->save_settings();
		}

		// Traiter les actions en masse
		$this->process_bulk_action();

		// Get statistics
		$stats = $this->get_scan_statistics();

		if ( $active_tab == 'outbound' ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-list-table.php';
			$olm_list_table = new Outbound_Links_Manager_List_Table();
			$olm_list_table->prepare_items();
		} elseif ( $active_tab == 'internal' ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-internal-list-table.php';
			$olm_internal_list_table = new Outbound_Links_Manager_Internal_List_Table();
			$olm_internal_list_table->prepare_items();
		} elseif ( $active_tab == 'incoming' ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-incoming-list-table.php';
			$olm_incoming_list_table = new Outbound_Links_Manager_Incoming_List_Table();
			$olm_incoming_list_table->prepare_items();
		} elseif ( $active_tab == 'traffic' ) {
			require_once plugin_dir_path( __FILE__ ) . 'class-traffic-list-table.php';
			$olm_traffic_list_table = new Outbound_Links_Manager_Traffic_List_Table();
			$olm_traffic_list_table->prepare_items();
		}

		include_once 'partials/admin-display.php';
	}

	/**
	 * Save plugin settings
	 *
	 * @since    1.0.0
	 */
	private function save_settings() {
		// Verify nonce
		if ( ! isset( $_POST['olm_settings_nonce'] ) || ! wp_verify_nonce( $_POST['olm_settings_nonce'], 'olm_save_settings' ) ) {
			wp_die( 'Vérification de sécurité échouée' );
		}

		// Check permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission refusée' );
		}

		// Save excluded domains
		if ( isset( $_POST['olm_excluded_domains'] ) ) {
			$excluded_domains = sanitize_textarea_field( $_POST['olm_excluded_domains'] );
			update_option( 'olm_excluded_domains', $excluded_domains );
		}

		// Save Google API credentials
		if ( isset( $_POST['olm_google_client_id'] ) ) {
			$client_id = sanitize_text_field( $_POST['olm_google_client_id'] );
			update_option( 'olm_google_client_id', $client_id );
		}

		if ( isset( $_POST['olm_google_client_secret'] ) ) {
			$client_secret = sanitize_text_field( $_POST['olm_google_client_secret'] );
			update_option( 'olm_google_client_secret', $client_secret );
		}

		if ( isset( $_POST['olm_google_ga4_property_id'] ) ) {
			$ga4_property_id = sanitize_text_field( $_POST['olm_google_ga4_property_id'] );
			update_option( 'olm_google_ga4_property_id', $ga4_property_id );
		}

		if ( isset( $_POST['olm_google_search_console_url'] ) ) {
			$search_console_url = esc_url_raw( $_POST['olm_google_search_console_url'] );
			update_option( 'olm_google_search_console_url', $search_console_url );
		}

		// Redirect with success message
		wp_redirect( add_query_arg(
			array(
				'page' => $this->plugin_name,
				'tab' => 'settings',
				'settings-updated' => 'true'
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * Get scan statistics
	 *
	 * @since    1.0.0
	 * @return   array  Statistics data
	 */
	private function get_scan_statistics() {
		global $wpdb;

		$stats = array();

		// Outbound links statistics
		$outbound_table = $wpdb->prefix . 'outbound_links';
		$stats['total_outbound_links'] = $wpdb->get_var( "SELECT COUNT(*) FROM $outbound_table" );
		$stats['posts_with_outbound'] = $wpdb->get_var( "SELECT COUNT(DISTINCT post_id) FROM $outbound_table" );
		$stats['unique_outbound_urls'] = $wpdb->get_var( "SELECT COUNT(DISTINCT url) FROM $outbound_table" );

		// Internal links statistics
		$internal_table = $wpdb->prefix . 'internal_links_stats';
		$stats['posts_scanned_internal'] = $wpdb->get_var( "SELECT COUNT(*) FROM $internal_table" );
		$stats['total_internal_links'] = $wpdb->get_var( "SELECT SUM(internal_links_count) FROM $internal_table" );
		$stats['posts_without_internal'] = $wpdb->get_var( "SELECT COUNT(*) FROM $internal_table WHERE internal_links_count = 0" );

		// Total posts on site
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		$post_types_sql = "'" . implode( "','", array_map( 'esc_sql', $post_types ) ) . "'";
		$stats['total_posts_site'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM {$wpdb->prefix}posts WHERE post_status = 'publish' AND post_type IN ($post_types_sql)"
		);

		// Traffic statistics
		$traffic_table = $wpdb->prefix . 'page_traffic';
		$stats['traffic_total_pages'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $traffic_table" );
		$stats['traffic_pages_with_impressions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $traffic_table WHERE impressions > 0" );
		$stats['traffic_pages_without_impressions'] = (int) $wpdb->get_var( "SELECT COUNT(*) FROM $traffic_table WHERE impressions = 0" );
		$stats['traffic_total_clicks'] = (int) $wpdb->get_var( "SELECT COALESCE(SUM(clicks), 0) FROM $traffic_table" );
		$stats['traffic_total_impressions'] = (int) $wpdb->get_var( "SELECT COALESCE(SUM(impressions), 0) FROM $traffic_table" );

		// Incoming links statistics
		$details_table = $wpdb->prefix . 'internal_links_details';
		$stats['incoming_total_pages'] = (int) $stats['posts_scanned_internal'];
		$stats['incoming_pages_with'] = (int) $wpdb->get_var(
			"SELECT COUNT(DISTINCT s.post_id) FROM $internal_table s
			 INNER JOIN $details_table d ON s.post_id = d.target_post_id
			 WHERE d.target_post_id IS NOT NULL AND d.target_post_id > 0"
		);
		$stats['incoming_pages_without'] = $stats['incoming_total_pages'] - $stats['incoming_pages_with'];
		$stats['incoming_total_links'] = (int) $wpdb->get_var(
			"SELECT COUNT(*) FROM $details_table WHERE target_post_id IS NOT NULL AND target_post_id > 0"
		);

		return $stats;
	}

	/**
	 * Process bulk actions
	 *
	 * @since    1.0.0
	 */
	public function process_bulk_action() {
		// Vérifier si une action en masse est déclenchée
		if ( isset( $_POST['action'] ) && $_POST['action'] == 'bulk-delete' ) {
			$this->handle_bulk_delete();
		}
		if ( isset( $_POST['action2'] ) && $_POST['action2'] == 'bulk-delete' ) {
			$this->handle_bulk_delete();
		}
	}

	/**
	 * Handle bulk delete action
	 *
	 * @since    1.0.0
	 */
	private function handle_bulk_delete() {
		// Vérifier les permissions
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission refusée' );
		}

		// Vérifier le nonce
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( $_POST['_wpnonce'], 'bulk-liens' ) ) {
			wp_die( 'Vérification de sécurité échouée' );
		}

		// Récupérer les IDs sélectionnés
		$link_ids = isset( $_POST['bulk-delete'] ) ? array_map( 'intval', $_POST['bulk-delete'] ) : array();

		if ( empty( $link_ids ) ) {
			return;
		}

		// Supprimer chaque lien
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-link-manager.php';
		$manager = new Outbound_Links_Manager_Link_Manager();
		
		$success_count = 0;
		$error_count = 0;

		foreach ( $link_ids as $link_id ) {
			$result = $manager->delete_link( $link_id );
			if ( is_wp_error( $result ) ) {
				$error_count++;
			} else {
				$success_count++;
			}
		}

		// Afficher un message de résultat
		$message = sprintf(
			'%d lien(s) supprimé(s) avec succès.',
			$success_count
		);

		if ( $error_count > 0 ) {
			$message .= sprintf(
				' %d erreur(s) rencontrée(s).',
				$error_count
			);
		}

		// Rediriger avec un message
		wp_redirect( add_query_arg(
			array(
				'page' => $this->plugin_name,
				'bulk_delete_success' => $success_count,
				'bulk_delete_error' => $error_count
			),
			admin_url( 'admin.php' )
		) );
		exit;
	}

	/**
	 * AJAX: Start Scan - Calculate total batches
	 */
	public function ajax_start_scan() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$scanner = new Outbound_Links_Manager_Scanner();
		$total_posts = $scanner->get_total_posts_count();
		$batch_size = Outbound_Links_Manager_Scanner::BATCH_SIZE;
		$total_batches = ceil( $total_posts / $batch_size );

		wp_send_json_success( array( 
			'total_posts' => $total_posts,
			'total_batches' => $total_batches 
		) );
	}

	/**
	 * AJAX: Process Batch
	 */
	public function ajax_process_scan_batch() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$batch = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 1;
		$scanner = new Outbound_Links_Manager_Scanner();
		$links_found = $scanner->scan_batch( $batch );
		wp_send_json_success( array( 'links_found' => $links_found ) );
	}

	/**
	 * AJAX: Get Link Details
	 */
	public function ajax_get_link() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}
		
		$id = isset( $_GET['id'] ) ? intval( $_GET['id'] ) : 0;
		$manager = new Outbound_Links_Manager_Link_Manager();
		$link = $manager->get_link( $id );
		
		if ( $link ) {
			wp_send_json_success( $link );
		} else {
			wp_send_json_error( 'Lien introuvable' );
		}
	}

	/**
	 * AJAX: Update Link
	 */
	public function ajax_update_link() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$url = isset( $_POST['url'] ) ? esc_url_raw( $_POST['url'] ) : '';
		$anchor = isset( $_POST['anchor'] ) ? sanitize_text_field( $_POST['anchor'] ) : '';
		$target = isset( $_POST['target'] ) ? sanitize_text_field( $_POST['target'] ) : '';
		$rel = isset( $_POST['rel'] ) ? sanitize_text_field( $_POST['rel'] ) : ''; // array? 
		// Simple implementation: single string for rel or specific handling needed? 
		// Spec says checkboxes: sponsored, ugc. Admin UI will send array or comma separated.
		// Let's assume frontend sends a string 'nofollow sponsored' or array.
		// For simplicity, let's treat it as a string that we pass to 'rel' attribute.
		
		$nofollow = isset( $_POST['nofollow'] ) && $_POST['nofollow'] === 'true' ? true : false;
		
		// Construct attributes
		$rels = array();
		if ( $nofollow ) $rels[] = 'nofollow';
		if ( ! empty( $rel ) ) $rels[] = $rel; // add other rels if passed
		
		$new_data = array(
			'url' => $url,
			'anchor' => $anchor,
			'attributes' => array(
				'target' => $target,
				'rel'    => implode( ' ', $rels )
			)
		);

		$manager = new Outbound_Links_Manager_Link_Manager();
		$result = $manager->update_link( $id, $new_data );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( 'Lien mis à jour.' );
		}
	}

	/**
	 * AJAX: Delete Link (remove <a> tag but keep anchor text)
	 */
	public function ajax_delete_link() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;

		$manager = new Outbound_Links_Manager_Link_Manager();
		$result = $manager->delete_link( $id );

		if ( is_wp_error( $result ) ) {
			wp_send_json_error( $result->get_error_message() );
		} else {
			wp_send_json_success( 'Lien supprimé avec succès. Le texte a été conservé.' );
		}
	}

	/**
	 * AJAX: Start Internal Links Scan - Calculate total batches
	 */
	public function ajax_start_internal_scan() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );
		
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$scanner = new Outbound_Links_Manager_Internal_Scanner();
		$total_posts = $scanner->get_total_posts_count();
		$batch_size = Outbound_Links_Manager_Internal_Scanner::BATCH_SIZE;
		$total_batches = ceil( $total_posts / $batch_size );

		wp_send_json_success( array( 
			'total_posts' => $total_posts,
			'total_batches' => $total_batches 
		) );
	}

	/**
	 * AJAX: Process Internal Links Batch
	 */
	public function ajax_process_internal_scan_batch() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$batch = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 1;
		$scanner = new Outbound_Links_Manager_Internal_Scanner();
		$posts_processed = $scanner->scan_batch( $batch );
		wp_send_json_success( array( 'posts_processed' => $posts_processed ) );
	}

	/**
	 * AJAX: Get Internal Links Details for a post
	 */
	public function ajax_get_internal_links() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'ID de post invalide' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'internal_links_details';

		$links = $wpdb->get_results( $wpdb->prepare(
			"SELECT target_url, anchor_text FROM $table_name WHERE post_id = %d ORDER BY id ASC",
			$post_id
		), ARRAY_A );

		wp_send_json_success( array( 'links' => $links ) );
	}

	/**
	 * AJAX: Get Incoming Links Details for a post (pages that link TO this post)
	 */
	public function ajax_get_incoming_links() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'ID de post invalide' );
		}

		global $wpdb;
		$details_table = $wpdb->prefix . 'internal_links_details';
		$posts_table = $wpdb->prefix . 'posts';

		$links = $wpdb->get_results( $wpdb->prepare(
			"SELECT d.post_id AS source_post_id, d.anchor_text, p.post_title AS source_title
			 FROM $details_table d
			 LEFT JOIN $posts_table p ON d.post_id = p.ID
			 WHERE d.target_post_id = %d
			 ORDER BY p.post_title ASC",
			$post_id
		), ARRAY_A );

		wp_send_json_success( array( 'links' => $links ) );
	}

	/**
	 * AJAX: Purge all links
	 */
	public function ajax_purge_links() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		global $wpdb;
		$scanner = new Outbound_Links_Manager_Scanner();
		$result = $scanner->purge_all_links();

		// Log any database errors
		if ( $wpdb->last_error ) {
			wp_send_json_error( 'Erreur SQL: ' . $wpdb->last_error );
		}

		if ( $result !== false ) {
			// Get count of deleted rows
			$table_name = $wpdb->prefix . 'outbound_links';
			$remaining = $wpdb->get_var( "SELECT COUNT(*) FROM $table_name" );
			wp_send_json_success( array(
				'message' => 'Base de données purgée avec succès.',
				'remaining' => $remaining
			) );
		} else {
			wp_send_json_error( 'Erreur lors de la purge de la base de données.' );
		}
	}

	/**
	 * AJAX: Debug single post scan
	 */
	public function ajax_debug_post_scan() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'ID de post invalide' );
		}

		$post = get_post( $post_id );
		if ( ! $post ) {
			wp_send_json_error( 'Post introuvable' );
		}

		// Get scanner instance with debug method
		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-scanner.php';
		$scanner = new Outbound_Links_Manager_Scanner();
		$debug_info = $scanner->debug_scan_post( $post_id );

		wp_send_json_success( $debug_info );
	}

	/**
	 * AJAX: Delete all comments
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_all_comments() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		global $wpdb;

		// Compter tous les commentaires avant suppression
		$total_comments = $wpdb->get_var( "SELECT COUNT(*) FROM $wpdb->comments" );

		if ( $total_comments == 0 ) {
			wp_send_json_success( array(
				'message' => 'Aucun commentaire à supprimer.',
				'deleted' => 0
			) );
		}

		// Supprimer tous les commentaires et leurs métadonnées
		$wpdb->query( "DELETE FROM $wpdb->commentmeta" );
		$wpdb->query( "DELETE FROM $wpdb->comments" );

		// Réinitialiser le compteur auto-increment
		$wpdb->query( "ALTER TABLE $wpdb->comments AUTO_INCREMENT = 1" );

		// Mettre à jour le compteur de commentaires pour tous les posts
		$wpdb->query( "UPDATE $wpdb->posts SET comment_count = 0" );

		wp_send_json_success( array(
			'message' => sprintf( '%d commentaire(s) supprimé(s) avec succès.', $total_comments ),
			'deleted' => $total_comments
		) );
	}

	/**
	 * AJAX: Start link status check
	 *
	 * @since    1.0.0
	 */
	public function ajax_start_link_check() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-link-checker.php';
		$checker = new Outbound_Links_Manager_Link_Checker();
		$total_links = $checker->get_total_links_count();
		$batch_size = Outbound_Links_Manager_Link_Checker::BATCH_SIZE;
		$total_batches = ceil( $total_links / $batch_size );

		wp_send_json_success( array(
			'total_links' => $total_links,
			'total_batches' => $total_batches
		) );
	}

	/**
	 * AJAX: Process link check batch
	 *
	 * @since    1.0.0
	 */
	public function ajax_process_link_check_batch() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$batch = isset( $_POST['batch'] ) ? intval( $_POST['batch'] ) : 1;

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-link-checker.php';
		$checker = new Outbound_Links_Manager_Link_Checker();
		$result = $checker->check_batch( $batch );

		wp_send_json_success( $result );
	}

	/**
	 * AJAX: Delete links by HTTP status
	 *
	 * @since    1.0.0
	 */
	public function ajax_delete_links_by_status() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		$statuses = isset( $_POST['statuses'] ) ? array_map( 'intval', $_POST['statuses'] ) : array();

		if ( empty( $statuses ) ) {
			wp_send_json_error( 'Aucun statut spécifié' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';

		// Créer les placeholders pour la requête préparée
		$placeholders = implode( ', ', array_fill( 0, count( $statuses ), '%d' ) );

		// Compter les liens à supprimer
		$count = $wpdb->get_var( $wpdb->prepare(
			"SELECT COUNT(*) FROM $table_name WHERE http_status IN ($placeholders)",
			$statuses
		) );

		if ( $count == 0 ) {
			wp_send_json_success( array(
				'message' => 'Aucun lien à supprimer avec ces statuts.',
				'deleted' => 0
			) );
		}

		// Supprimer les liens
		$deleted = $wpdb->query( $wpdb->prepare(
			"DELETE FROM $table_name WHERE http_status IN ($placeholders)",
			$statuses
		) );

		wp_send_json_success( array(
			'message' => sprintf( '%d lien(s) supprimé(s) avec succès.', $deleted ),
			'deleted' => $deleted
		) );
	}

	/**
	 * AJAX: Synchronize traffic data
	 *
	 * @since    1.0.0
	 */
	public function ajax_sync_traffic_data() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-traffic-sync.php';
		$sync = new Outbound_Links_Manager_Traffic_Sync();
		$result = $sync->sync_traffic_data();

		if ( $result['success'] ) {
			wp_send_json_success( $result );
		} else {
			wp_send_json_error( $result['message'] );
		}
	}

	/**
	 * AJAX: Delete a traffic page entry
	 *
	 * @since    1.0.2
	 */
	public function ajax_delete_traffic_page() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		$id = isset( $_POST['id'] ) ? intval( $_POST['id'] ) : 0;
		$redirect_url = isset( $_POST['redirect_url'] ) ? esc_url_raw( $_POST['redirect_url'] ) : home_url( '/' );

		if ( ! $id ) {
			wp_send_json_error( 'ID invalide' );
		}

		if ( empty( $redirect_url ) ) {
			$redirect_url = home_url( '/' );
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';
		$redirections_table = $wpdb->prefix . 'wpcp_redirections';

		// Récupérer les infos de la page avant suppression
		$row = $wpdb->get_row( $wpdb->prepare(
			"SELECT post_id, url FROM $table_name WHERE id = %d",
			$id
		) );

		if ( ! $row ) {
			wp_send_json_error( 'Entrée de trafic introuvable.' );
		}

		// Construire l'URL source pour la redirection
		$source_url = $row->url;
		if ( strpos( $source_url, 'http' ) !== 0 ) {
			// URL relative, on la garde telle quelle (le hook front comparera les paths)
			$source_url = '/' . ltrim( $source_url, '/' );
		} else {
			// URL absolue, extraire le path
			$parsed = wp_parse_url( $source_url );
			$source_url = isset( $parsed['path'] ) ? $parsed['path'] : '/';
		}

		// Enregistrer la redirection 301
		$wpdb->replace(
			$redirections_table,
			array(
				'source_url' => $source_url,
				'target_url' => $redirect_url,
				'http_code'  => 301,
				'hits'       => 0,
			),
			array( '%s', '%s', '%d', '%d' )
		);

		// Supprimer la page WordPress (mise à la corbeille)
		$post_trashed = false;
		if ( $row->post_id ) {
			$post = get_post( $row->post_id );
			if ( $post ) {
				$post_trashed = wp_trash_post( $row->post_id );
			}
		}

		// Supprimer l'entrée de la table de trafic
		$wpdb->delete( $table_name, array( 'id' => $id ), array( '%d' ) );

		$message = $post_trashed
			? 'Page mise à la corbeille avec redirection 301 vers ' . $redirect_url
			: 'Entrée supprimée avec redirection 301 vers ' . $redirect_url;

		wp_send_json_success( $message );
	}

	/**
	 * AJAX: Get Search Console keywords for a specific page
	 */
	public function ajax_get_page_keywords() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission denied' );
		}

		$post_id = isset( $_GET['post_id'] ) ? intval( $_GET['post_id'] ) : 0;

		if ( ! $post_id ) {
			wp_send_json_error( 'ID de post invalide' );
		}

		$post_url = get_permalink( $post_id );
		if ( ! $post_url ) {
			wp_send_json_error( 'URL introuvable pour ce post' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-google-api.php';
		$google_api = new Outbound_Links_Manager_Google_API();

		if ( ! $google_api->is_authenticated() ) {
			wp_send_json_error( 'API Google non connectée. Configurez vos identifiants dans les Paramètres.' );
		}

		$keywords = $google_api->get_keywords_for_url( $post_url );

		if ( is_wp_error( $keywords ) ) {
			wp_send_json_error( $keywords->get_error_message() );
		}

		wp_send_json_success( array( 'keywords' => $keywords, 'url' => $post_url ) );
	}

	/**
	 * Handle Google OAuth callback
	 *
	 * @since    1.0.0
	 */
	private function handle_google_oauth_callback() {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Permission refusée' );
		}

		if ( ! isset( $_GET['code'] ) ) {
			wp_redirect( add_query_arg(
				array(
					'page' => $this->plugin_name,
					'tab' => 'settings',
					'google_auth' => 'error'
				),
				admin_url( 'admin.php' )
			) );
			exit;
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-google-api.php';
		$google_api = new Outbound_Links_Manager_Google_API();

		$code = sanitize_text_field( $_GET['code'] );
		$result = $google_api->exchange_code_for_token( $code );

		if ( is_wp_error( $result ) ) {
			wp_redirect( add_query_arg(
				array(
					'page' => $this->plugin_name,
					'tab' => 'settings',
					'google_auth' => 'error',
					'error_message' => urlencode( $result->get_error_message() )
				),
				admin_url( 'admin.php' )
			) );
		} else {
			wp_redirect( add_query_arg(
				array(
					'page' => $this->plugin_name,
					'tab' => 'settings',
					'google_auth' => 'success'
				),
				admin_url( 'admin.php' )
			) );
		}
		exit;
	}

	/**
	 * AJAX: Disconnect Google API
	 *
	 * @since    1.0.0
	 */
	public function ajax_google_disconnect() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-google-api.php';
		$google_api = new Outbound_Links_Manager_Google_API();
		$google_api->disconnect();

		wp_send_json_success( 'Déconnecté de Google avec succès' );
	}

	/**
	 * AJAX: Test Google API Connection
	 *
	 * @since    1.0.0
	 */
	public function ajax_test_google_api() {
		check_ajax_referer( 'olm_ajax_nonce', 'security' );

		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Permission refusée' );
		}

		require_once plugin_dir_path( dirname( __FILE__ ) ) . 'includes/class-google-api.php';
		$google_api = new Outbound_Links_Manager_Google_API();

		$wp_site_url = get_site_url();
		$configured_sc_url = get_option( 'olm_google_search_console_url', '' );
		$sc_url = ! empty( $configured_sc_url ) ? $configured_sc_url : $wp_site_url;

		// NOUVELLE APPROCHE: Récupérer les URLs directement depuis Search Console
		$urls_data = $google_api->get_all_urls_from_search_console( 28, 10 );

		if ( is_wp_error( $urls_data ) ) {
			wp_send_json_error( 'Erreur API: ' . $urls_data->get_error_message() );
			return;
		}

		if ( empty( $urls_data ) ) {
			$debug_info = array(
				'wordpress_site_url' => $wp_site_url,
				'search_console_url_configured' => ! empty( $configured_sc_url ) ? $configured_sc_url : 'Non configurée (utilise l\'URL WordPress)',
				'erreur' => 'Aucune URL retournée par Search Console. Vérifiez l\'URL configurée ci-dessus.',
				'impressions' => 0,
				'clicks' => 0,
				'periode' => 'Derniers 28 jours',
				'date_debut' => date( 'Y-m-d', strtotime( '-28 days' ) ),
				'date_fin' => date( 'Y-m-d' ),
			);
			wp_send_json_success( $debug_info );
			return;
		}

		// Prendre la première URL avec du trafic
		$test_url_data = $urls_data[0];
		$url = $test_url_data['url'];
		$full_url = $test_url_data['full_url'];

		// Essayer de trouver le titre de la page WordPress correspondante
		$post_id = url_to_postid( $full_url );
		$page_title = $post_id ? get_the_title( $post_id ) : 'Page non associée à WordPress (mais présente dans GSC)';

		$debug_info = array(
			'wordpress_site_url' => $wp_site_url,
			'search_console_url_configured' => ! empty( $configured_sc_url ) ? $configured_sc_url : 'Non configurée (utilise l\'URL WordPress)',
			'page_testee' => $page_title,
			'url_relative' => $url,
			'url_complete' => $full_url,
			'impressions' => $test_url_data['impressions'],
			'clicks' => $test_url_data['clicks'],
			'periode' => 'Derniers 28 jours',
			'date_debut' => date( 'Y-m-d', strtotime( '-28 days' ) ),
			'date_fin' => date( 'Y-m-d' ),
			'total_urls_gsc' => count( $urls_data )
		);

		wp_send_json_success( $debug_info );
	}
}
