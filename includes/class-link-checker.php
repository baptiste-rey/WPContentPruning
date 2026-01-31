<?php

/**
 * Handles HTTP status checking for outbound links.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Link_Checker {

	const BATCH_SIZE = 10; // Nombre de liens à vérifier par batch
	const TIMEOUT = 10; // Timeout en secondes pour chaque requête

	/**
	 * Get total number of links to check.
	 *
	 * @since 1.0.0
	 * @return int Total number of links.
	 */
	public function get_total_links_count() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';
		return $wpdb->get_var( "SELECT COUNT(DISTINCT url) FROM $table_name" );
	}

	/**
	 * Process a batch of links to check their HTTP status.
	 *
	 * @since 1.0.0
	 * @param int $batch The batch number (1-based).
	 * @return array Results with checked count and errors.
	 */
	public function check_batch( $batch ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';

		$offset = ( $batch - 1 ) * self::BATCH_SIZE;

		// Récupérer les URLs uniques à vérifier
		$links = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT DISTINCT url FROM $table_name ORDER BY url LIMIT %d OFFSET %d",
				self::BATCH_SIZE,
				$offset
			)
		);

		$checked_count = 0;
		$error_count = 0;

		foreach ( $links as $link ) {
			$status = $this->check_url_status( $link->url );

			if ( $status !== false ) {
				// Mettre à jour tous les liens avec cette URL
				$wpdb->update(
					$table_name,
					array(
						'http_status' => $status,
						'last_checked' => current_time( 'mysql' )
					),
					array( 'url' => $link->url ),
					array( '%d', '%s' ),
					array( '%s' )
				);
				$checked_count++;
			} else {
				$error_count++;
			}
		}

		return array(
			'checked' => $checked_count,
			'errors' => $error_count
		);
	}

	/**
	 * Check the HTTP status of a URL.
	 *
	 * @since 1.0.0
	 * @param string $url The URL to check.
	 * @return int|false HTTP status code or false on failure.
	 */
	private function check_url_status( $url ) {
		// Utiliser wp_remote_head pour une requête HEAD (plus rapide)
		$response = wp_remote_head( $url, array(
			'timeout'     => self::TIMEOUT,
			'redirection' => 5,
			'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
			'sslverify'   => false // Pour éviter les erreurs SSL sur certains sites
		) );

		// Si HEAD échoue, essayer avec GET
		if ( is_wp_error( $response ) ) {
			$response = wp_remote_get( $url, array(
				'timeout'     => self::TIMEOUT,
				'redirection' => 5,
				'user-agent'  => 'WordPress/' . get_bloginfo( 'version' ) . '; ' . get_bloginfo( 'url' ),
				'sslverify'   => false
			) );
		}

		if ( is_wp_error( $response ) ) {
			// En cas d'erreur, retourner 0 pour indiquer une erreur de connexion
			return 0;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		return $status_code ? $status_code : false;
	}

	/**
	 * Get statistics about link statuses.
	 *
	 * @since 1.0.0
	 * @return array Statistics.
	 */
	public function get_status_statistics() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';

		$stats = array();

		// Total de liens vérifiés
		$stats['checked'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status IS NOT NULL"
		);

		// Liens OK (2xx)
		$stats['ok'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status >= 200 AND http_status < 300"
		);

		// Redirections (3xx)
		$stats['redirects'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status >= 300 AND http_status < 400"
		);

		// Erreurs client (4xx)
		$stats['client_errors'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status >= 400 AND http_status < 500"
		);

		// Erreurs serveur (5xx)
		$stats['server_errors'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status >= 500"
		);

		// Erreurs de connexion (0)
		$stats['connection_errors'] = $wpdb->get_var(
			"SELECT COUNT(*) FROM $table_name WHERE http_status = 0"
		);

		return $stats;
	}
}
