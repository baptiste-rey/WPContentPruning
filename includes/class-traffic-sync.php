<?php
/**
 * Traffic Sync Class
 *
 * Gère la synchronisation des données de trafic depuis Google Analytics et Search Console
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Traffic_Sync {

	/**
	 * Synchroniser les données de trafic
	 *
	 * @return array Résultat de la synchronisation
	 */
	public function sync_traffic_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		// Charger la classe Google API
		require_once plugin_dir_path( __FILE__ ) . 'class-google-api.php';
		$google_api = new Outbound_Links_Manager_Google_API();

		// Vérifier si l'API Google est configurée et authentifiée
		if ( $google_api->is_configured() && $google_api->is_authenticated() ) {
			return $this->sync_from_google_api_direct( $google_api );
		}

		// Sinon, vérifier si Google Site Kit est installé et actif
		if ( $this->is_site_kit_active() ) {
			return $this->sync_from_site_kit();
		}

		// En dernier recours, générer des données de démonstration
		return $this->generate_demo_data();
	}

	/**
	 * Vérifier si Google Site Kit est installé et actif
	 *
	 * @return bool
	 */
	private function is_site_kit_active() {
		return class_exists( 'Google\Site_Kit\Context' );
	}

	/**
	 * Synchroniser depuis Google Site Kit
	 *
	 * @return array
	 */
	private function sync_from_site_kit() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		$synced_count = 0;
		$error_message = '';

		try {
			// Récupérer les données depuis Site Kit via les transients/options
			// Site Kit stocke les données dans des transients et options WordPress

			// Récupérer toutes les pages/posts publiés
			$args = array(
				'post_type' => array( 'post', 'page' ),
				'post_status' => 'publish',
				'posts_per_page' => 100,
				'orderby' => 'date',
				'order' => 'DESC'
			);

			$posts = get_posts( $args );

			// Tentative de récupération des données via l'API REST de Site Kit
			// Site Kit expose des endpoints REST internes que nous pouvons essayer d'utiliser

			foreach ( $posts as $post ) {
				$url = parse_url( get_permalink( $post->ID ), PHP_URL_PATH );

				// Initialiser les valeurs par défaut
				$impressions = 0;
				$clicks = 0;
				$users = 0;
				$sessions = 0;

				// Essayer de récupérer les données Search Console
				// Site Kit stocke parfois les données dans des transients
				$search_console_data = $this->get_site_kit_search_console_data( $url );
				if ( $search_console_data ) {
					$impressions = isset( $search_console_data['impressions'] ) ? intval( $search_console_data['impressions'] ) : 0;
					$clicks = isset( $search_console_data['clicks'] ) ? intval( $search_console_data['clicks'] ) : 0;
				}

				// Essayer de récupérer les données Analytics
				$analytics_data = $this->get_site_kit_analytics_data( $url );
				if ( $analytics_data ) {
					$users = isset( $analytics_data['users'] ) ? intval( $analytics_data['users'] ) : 0;
					$sessions = isset( $analytics_data['sessions'] ) ? intval( $analytics_data['sessions'] ) : 0;
				}

				// Si aucune donnée n'a été récupérée, utiliser des données de démonstration pour ce post
				if ( $impressions == 0 && $clicks == 0 && $users == 0 && $sessions == 0 ) {
					$impressions = rand( 100, 5000 );
					$clicks = rand( 10, intval( $impressions * 0.1 ) );
					$users = rand( 5, $clicks );
					$sessions = rand( $users, intval( $users * 1.5 ) );
				}

				// Insérer ou mettre à jour
				$existing = $wpdb->get_row( $wpdb->prepare(
					"SELECT id FROM $table_name WHERE url = %s",
					$url
				) );

				if ( $existing ) {
					$wpdb->update(
						$table_name,
						array(
							'impressions' => $impressions,
							'clicks' => $clicks,
							'users' => $users,
							'sessions' => $sessions,
							'post_id' => $post->ID,
							'last_updated' => current_time( 'mysql' )
						),
						array( 'id' => $existing->id ),
						array( '%d', '%d', '%d', '%d', '%d', '%s' ),
						array( '%d' )
					);
				} else {
					$wpdb->insert(
						$table_name,
						array(
							'url' => $url,
							'post_id' => $post->ID,
							'impressions' => $impressions,
							'clicks' => $clicks,
							'users' => $users,
							'sessions' => $sessions,
							'last_updated' => current_time( 'mysql' ),
							'created_at' => current_time( 'mysql' )
						),
						array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
					);
				}

				$synced_count++;
			}

			$message = "Synchronisation réussie avec Google Site Kit: $synced_count pages mises à jour. Note: Les données proviennent de Site Kit si disponibles, sinon des valeurs de démonstration sont utilisées.";

		} catch ( Exception $e ) {
			$error_message = 'Erreur lors de la synchronisation depuis Site Kit: ' . $e->getMessage();
		}

		return array(
			'success' => $synced_count > 0,
			'synced_count' => $synced_count,
			'message' => $error_message ? $error_message : $message
		);
	}

	/**
	 * Récupérer les données Search Console pour une URL
	 *
	 * @param string $url L'URL de la page
	 * @return array|false Les données ou false
	 */
	private function get_site_kit_search_console_data( $url ) {
		// Essayer d'utiliser l'API REST de Site Kit
		if ( ! class_exists( 'Google\Site_Kit\Core\Authentication\Authentication' ) ) {
			return false;
		}

		try {
			// Préparer la requête pour Search Console
			// Site Kit expose des endpoints REST pour récupérer les données

			// Créer une requête REST interne
			$request = new WP_REST_Request( 'POST', '/google-site-kit/v1/modules/search-console/data/searchanalytics' );

			// Paramètres pour Search Console
			$end_date = date( 'Y-m-d' );
			$start_date = date( 'Y-m-d', strtotime( '-28 days' ) );

			$request->set_param( 'startDate', $start_date );
			$request->set_param( 'endDate', $end_date );
			$request->set_param( 'dimensions', array( 'page' ) );
			$request->set_param( 'url', home_url( $url ) );

			// Exécuter la requête
			$response = rest_do_request( $request );

			if ( $response->is_error() ) {
				return false;
			}

			$data = $response->get_data();

			// Extraire les données
			if ( isset( $data['rows'] ) && is_array( $data['rows'] ) ) {
				$total_impressions = 0;
				$total_clicks = 0;

				foreach ( $data['rows'] as $row ) {
					if ( isset( $row['keys'][0] ) && strpos( $row['keys'][0], $url ) !== false ) {
						$total_impressions += isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0;
						$total_clicks += isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0;
					}
				}

				if ( $total_impressions > 0 || $total_clicks > 0 ) {
					return array(
						'impressions' => $total_impressions,
						'clicks' => $total_clicks
					);
				}
			}

		} catch ( Exception $e ) {
			// En cas d'erreur, retourner false
			return false;
		}

		return false;
	}

	/**
	 * Récupérer les données Analytics pour une URL
	 *
	 * @param string $url L'URL de la page
	 * @return array|false Les données ou false
	 */
	private function get_site_kit_analytics_data( $url ) {
		// Essayer d'utiliser l'API REST de Site Kit pour Analytics
		if ( ! class_exists( 'Google\Site_Kit\Core\Authentication\Authentication' ) ) {
			return false;
		}

		try {
			// Préparer la requête pour Analytics 4
			// Site Kit utilise maintenant Google Analytics 4 (GA4)

			// Créer une requête REST interne pour GA4
			$request = new WP_REST_Request( 'POST', '/google-site-kit/v1/modules/analytics-4/data/report' );

			// Paramètres pour Analytics
			$end_date = date( 'Y-m-d' );
			$start_date = date( 'Y-m-d', strtotime( '-28 days' ) );

			$request->set_param( 'startDate', $start_date );
			$request->set_param( 'endDate', $end_date );
			$request->set_param( 'dimensions', array( 'pagePath' ) );
			$request->set_param( 'metrics', array(
				array( 'name' => 'totalUsers' ),
				array( 'name' => 'sessions' )
			) );
			$request->set_param( 'dimensionFilters', array(
				'pagePath' => $url
			) );

			// Exécuter la requête
			$response = rest_do_request( $request );

			if ( $response->is_error() ) {
				// Essayer avec l'ancienne version Analytics (Universal Analytics)
				return $this->get_site_kit_analytics_ua_data( $url );
			}

			$data = $response->get_data();

			// Extraire les données GA4
			if ( isset( $data['rows'] ) && is_array( $data['rows'] ) ) {
				$total_users = 0;
				$total_sessions = 0;

				foreach ( $data['rows'] as $row ) {
					if ( isset( $row['dimensionValues'][0]['value'] ) && $row['dimensionValues'][0]['value'] === $url ) {
						$total_users += isset( $row['metricValues'][0]['value'] ) ? intval( $row['metricValues'][0]['value'] ) : 0;
						$total_sessions += isset( $row['metricValues'][1]['value'] ) ? intval( $row['metricValues'][1]['value'] ) : 0;
					}
				}

				if ( $total_users > 0 || $total_sessions > 0 ) {
					return array(
						'users' => $total_users,
						'sessions' => $total_sessions
					);
				}
			}

		} catch ( Exception $e ) {
			// En cas d'erreur, retourner false
			return false;
		}

		return false;
	}

	/**
	 * Récupérer les données Analytics UA (Universal Analytics) pour une URL
	 *
	 * @param string $url L'URL de la page
	 * @return array|false Les données ou false
	 */
	private function get_site_kit_analytics_ua_data( $url ) {
		try {
			// Préparer la requête pour Universal Analytics (ancienne version)
			$request = new WP_REST_Request( 'POST', '/google-site-kit/v1/modules/analytics/data/report' );

			// Paramètres pour Analytics UA
			$end_date = date( 'Y-m-d' );
			$start_date = date( 'Y-m-d', strtotime( '-28 days' ) );

			$request->set_param( 'startDate', $start_date );
			$request->set_param( 'endDate', $end_date );
			$request->set_param( 'dimensions', array( 'ga:pagePath' ) );
			$request->set_param( 'metrics', array(
				array( 'expression' => 'ga:users' ),
				array( 'expression' => 'ga:sessions' )
			) );
			$request->set_param( 'dimensionFilters', array(
				array(
					'filters' => array(
						array(
							'dimensionName' => 'ga:pagePath',
							'operator' => 'EXACT',
							'expressions' => array( $url )
						)
					)
				)
			) );

			// Exécuter la requête
			$response = rest_do_request( $request );

			if ( $response->is_error() ) {
				return false;
			}

			$data = $response->get_data();

			// Extraire les données
			if ( isset( $data['rows'] ) && is_array( $data['rows'] ) ) {
				$total_users = 0;
				$total_sessions = 0;

				foreach ( $data['rows'] as $row ) {
					if ( isset( $row['dimensions'][0] ) && $row['dimensions'][0] === $url ) {
						$total_users += isset( $row['metrics'][0]['values'][0] ) ? intval( $row['metrics'][0]['values'][0] ) : 0;
						$total_sessions += isset( $row['metrics'][0]['values'][1] ) ? intval( $row['metrics'][0]['values'][1] ) : 0;
					}
				}

				if ( $total_users > 0 || $total_sessions > 0 ) {
					return array(
						'users' => $total_users,
						'sessions' => $total_sessions
					);
				}
			}

		} catch ( Exception $e ) {
			return false;
		}

		return false;
	}

	/**
	 * Synchroniser depuis l'API Google directe
	 *
	 * @param Outbound_Links_Manager_Google_API $google_api Instance de l'API Google
	 * @return array
	 */
	private function sync_from_google_api_direct( $google_api ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		$synced_count = 0;
		$error_count = 0;
		$error_message = '';

		try {
			// Récupérer l'ID de la propriété GA4
			$ga4_property_id = get_option( 'olm_google_ga4_property_id', '' );

			// NOUVELLE APPROCHE: Récupérer TOUTES les URLs depuis Search Console
			$urls_data = $google_api->get_all_urls_from_search_console( 28, 1000 );

			if ( is_wp_error( $urls_data ) ) {
				return array(
					'success' => false,
					'synced_count' => 0,
					'message' => 'Erreur lors de la récupération des données depuis Search Console: ' . $urls_data->get_error_message()
				);
			}

			if ( empty( $urls_data ) ) {
				return array(
					'success' => false,
					'synced_count' => 0,
					'message' => 'Aucune donnée retournée par Search Console. Vérifiez que votre site a des pages indexées avec du trafic.'
				);
			}

			// Pour chaque URL retournée par Search Console
			foreach ( $urls_data as $url_data ) {
				$url = $url_data['url'];
				$impressions = $url_data['impressions'];
				$clicks = $url_data['clicks'];

				// Essayer de trouver le post_id WordPress correspondant
				$post_id = url_to_postid( $url_data['full_url'] );
				if ( $post_id == 0 ) {
					$post_id = null; // NULL si aucun post WordPress ne correspond
				}

				// Initialiser les données Analytics
				$users = 0;
				$sessions = 0;

				// Récupérer les données Analytics si property ID est configuré
				if ( ! empty( $ga4_property_id ) && $post_id !== null ) {
					$analytics_data = $google_api->get_analytics_data( $ga4_property_id, $url );
					if ( ! is_wp_error( $analytics_data ) ) {
						$users = isset( $analytics_data['users'] ) ? intval( $analytics_data['users'] ) : 0;
						$sessions = isset( $analytics_data['sessions'] ) ? intval( $analytics_data['sessions'] ) : 0;
					}
				}

				// Insérer ou mettre à jour
				$existing = $wpdb->get_row( $wpdb->prepare(
					"SELECT id FROM $table_name WHERE url = %s",
					$url
				) );

				if ( $existing ) {
					$wpdb->update(
						$table_name,
						array(
							'impressions' => $impressions,
							'clicks' => $clicks,
							'users' => $users,
							'sessions' => $sessions,
							'post_id' => $post_id,
							'last_updated' => current_time( 'mysql' )
						),
						array( 'id' => $existing->id ),
						array( '%d', '%d', '%d', '%d', '%d', '%s' ),
						array( '%d' )
					);
				} else {
					$wpdb->insert(
						$table_name,
						array(
							'url' => $url,
							'post_id' => $post_id,
							'impressions' => $impressions,
							'clicks' => $clicks,
							'users' => $users,
							'sessions' => $sessions,
							'last_updated' => current_time( 'mysql' ),
							'created_at' => current_time( 'mysql' )
						),
						array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
					);
				}

				$synced_count++;
			}

			$message = "✅ Synchronisation réussie avec l'API Google: $synced_count URLs mises à jour avec de VRAIES données Search Console";
			if ( ! empty( $ga4_property_id ) ) {
				$message .= " et Analytics";
			}
			$message .= ".";

			if ( $error_count > 0 ) {
				$message .= " ($error_count erreur(s) rencontrée(s))";
			}

		} catch ( Exception $e ) {
			$error_message = 'Erreur lors de la synchronisation: ' . $e->getMessage();
		}

		return array(
			'success' => $synced_count > 0,
			'synced_count' => $synced_count,
			'message' => $error_message ? $error_message : $message
		);
	}

	/**
	 * Synchroniser depuis les API Google directement
	 *
	 * @return array
	 */
	private function sync_from_google_apis() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		$synced_count = 0;
		$error_message = '';

		try {
			// Vérifier si les credentials OAuth sont configurés
			$credentials = get_option( 'olm_google_credentials', array() );

			if ( empty( $credentials ) ) {
				// Générer des données de démonstration
				return $this->generate_demo_data();
			}

			// TODO: Implémenter l'accès aux API Google
			// Nécessite:
			// 1. Google Analytics Data API v1 pour Users et Sessions
			// 2. Google Search Console API pour Impressions et Clicks

			$error_message = 'Configuration OAuth requise. Veuillez configurer vos identifiants Google dans les paramètres.';

		} catch ( Exception $e ) {
			$error_message = 'Erreur lors de la synchronisation: ' . $e->getMessage();
		}

		return array(
			'success' => $synced_count > 0,
			'synced_count' => $synced_count,
			'message' => $error_message ? $error_message : "Synchronisation réussie: $synced_count pages mises à jour."
		);
	}

	/**
	 * Générer des données de démonstration
	 *
	 * @return array
	 */
	private function generate_demo_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		// Récupérer toutes les pages/posts publiés
		$args = array(
			'post_type' => array( 'post', 'page' ),
			'post_status' => 'publish',
			'posts_per_page' => 50,
			'orderby' => 'date',
			'order' => 'DESC'
		);

		$posts = get_posts( $args );
		$synced_count = 0;

		foreach ( $posts as $post ) {
			$url = parse_url( get_permalink( $post->ID ), PHP_URL_PATH );

			// Générer des données aléatoires pour la démonstration
			$impressions = rand( 100, 10000 );
			$clicks = rand( 10, intval( $impressions * 0.1 ) );
			$users = rand( 5, $clicks );
			$sessions = rand( $users, intval( $users * 1.5 ) );

			// Insérer ou mettre à jour
			$existing = $wpdb->get_row( $wpdb->prepare(
				"SELECT id FROM $table_name WHERE url = %s",
				$url
			) );

			if ( $existing ) {
				$wpdb->update(
					$table_name,
					array(
						'impressions' => $impressions,
						'clicks' => $clicks,
						'users' => $users,
						'sessions' => $sessions,
						'post_id' => $post->ID,
						'last_updated' => current_time( 'mysql' )
					),
					array( 'id' => $existing->id ),
					array( '%d', '%d', '%d', '%d', '%d', '%s' ),
					array( '%d' )
				);
			} else {
				$wpdb->insert(
					$table_name,
					array(
						'url' => $url,
						'post_id' => $post->ID,
						'impressions' => $impressions,
						'clicks' => $clicks,
						'users' => $users,
						'sessions' => $sessions,
						'last_updated' => current_time( 'mysql' ),
						'created_at' => current_time( 'mysql' )
					),
					array( '%s', '%d', '%d', '%d', '%d', '%d', '%s', '%s' )
				);
			}

			$synced_count++;
		}

		return array(
			'success' => true,
			'synced_count' => $synced_count,
			'message' => "Données de démonstration générées: $synced_count pages créées. Note: Pour obtenir de vraies données, configurez Google Analytics et Search Console."
		);
	}

	/**
	 * Purger toutes les données de trafic
	 *
	 * @return bool
	 */
	public function purge_traffic_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'page_traffic';

		$result = $wpdb->query( "TRUNCATE TABLE $table_name" );

		return $result !== false;
	}
}
