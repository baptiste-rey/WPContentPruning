<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class Outbound_Links_Manager_Redirections {

	/**
	 * Vérifie si l'URL courante a une redirection 301 et l'applique.
	 * Hooked sur template_redirect.
	 */
	public static function handle_redirect() {
		if ( is_admin() ) {
			return;
		}

		global $wpdb;
		$table_name = $wpdb->prefix . 'wpcp_redirections';

		// Vérifier que la table existe
		if ( $wpdb->get_var( "SHOW TABLES LIKE '$table_name'" ) !== $table_name ) {
			return;
		}

		// Récupérer le path de la requête actuelle
		$request_path = '/' . ltrim( $_SERVER['REQUEST_URI'], '/' );

		// Supprimer les query strings pour la comparaison
		$request_path = strtok( $request_path, '?' );

		// Normaliser : s'assurer qu'il y a un trailing slash
		$request_path_with_slash = trailingslashit( $request_path );
		$request_path_without_slash = untrailingslashit( $request_path );

		// Chercher une redirection correspondante (avec ou sans trailing slash)
		$redirect = $wpdb->get_row( $wpdb->prepare(
			"SELECT id, target_url, http_code FROM $table_name WHERE source_url = %s OR source_url = %s LIMIT 1",
			$request_path_with_slash,
			$request_path_without_slash
		) );

		if ( $redirect && ! empty( $redirect->target_url ) ) {
			// Incrémenter le compteur de hits
			$wpdb->update(
				$table_name,
				array( 'hits' => $wpdb->get_var( $wpdb->prepare(
					"SELECT hits FROM $table_name WHERE id = %d",
					$redirect->id
				) ) + 1 ),
				array( 'id' => $redirect->id ),
				array( '%d' ),
				array( '%d' )
			);

			// Effectuer la redirection
			wp_redirect( $redirect->target_url, $redirect->http_code );
			exit;
		}
	}
}
