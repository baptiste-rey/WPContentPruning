<?php
/**
 * Google API Integration Class
 *
 * Gère la connexion directe aux API Google (Search Console et Analytics)
 *
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Google_API {

	/**
	 * URL de l'API Search Console
	 */
	const SEARCH_CONSOLE_API_URL = 'https://searchconsole.googleapis.com/v1/urlTestingTools/mobileFriendlyTest:run';
	const SEARCH_CONSOLE_WEBMASTERS_URL = 'https://www.googleapis.com/webmasters/v3/sites';

	/**
	 * URL de l'API Analytics
	 */
	const ANALYTICS_API_URL = 'https://analyticsdata.googleapis.com/v1beta';

	/**
	 * Access Token
	 */
	private $access_token;

	/**
	 * Site URL
	 */
	private $site_url;

	/**
	 * Constructor
	 */
	public function __construct() {
		$this->site_url = get_site_url();
		$this->access_token = get_option( 'olm_google_access_token', '' );
	}

	/**
	 * Vérifier si l'API est configurée
	 *
	 * @return bool
	 */
	public function is_configured() {
		$client_id = get_option( 'olm_google_client_id', '' );
		$client_secret = get_option( 'olm_google_client_secret', '' );

		return ! empty( $client_id ) && ! empty( $client_secret );
	}

	/**
	 * Vérifier si l'utilisateur est authentifié
	 *
	 * @return bool
	 */
	public function is_authenticated() {
		return ! empty( $this->access_token );
	}

	/**
	 * Obtenir l'URL d'authentification OAuth
	 *
	 * @return string
	 */
	public function get_auth_url() {
		$client_id = get_option( 'olm_google_client_id', '' );
		$redirect_uri = $this->get_redirect_uri();

		$params = array(
			'client_id' => $client_id,
			'redirect_uri' => $redirect_uri,
			'response_type' => 'code',
			'scope' => implode( ' ', array(
				'https://www.googleapis.com/auth/webmasters.readonly',
				'https://www.googleapis.com/auth/analytics.readonly'
			) ),
			'access_type' => 'offline',
			'prompt' => 'consent'
		);

		return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query( $params );
	}

	/**
	 * Obtenir l'URI de redirection OAuth (normalisée)
	 *
	 * @return string
	 */
	private function get_redirect_uri() {
		// Construire l'URI de redirection en évitant les doubles slashes
		$redirect_uri = admin_url( 'admin.php?page=outbound-links-manager&tab=settings&google_auth=callback' );

		// Normaliser l'URI pour éviter les doubles slashes
		$redirect_uri = preg_replace( '#(?<!:)//+#', '/', $redirect_uri );

		return $redirect_uri;
	}

	/**
	 * Échanger le code d'autorisation contre un access token
	 *
	 * @param string $code Le code d'autorisation
	 * @return bool|WP_Error
	 */
	public function exchange_code_for_token( $code ) {
		$client_id = get_option( 'olm_google_client_id', '' );
		$client_secret = get_option( 'olm_google_client_secret', '' );
		$redirect_uri = $this->get_redirect_uri();

		$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'body' => array(
				'code' => $code,
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'redirect_uri' => $redirect_uri,
				'grant_type' => 'authorization_code'
			)
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			update_option( 'olm_google_access_token', $body['access_token'] );

			if ( isset( $body['refresh_token'] ) ) {
				update_option( 'olm_google_refresh_token', $body['refresh_token'] );
			}

			$this->access_token = $body['access_token'];
			return true;
		}

		return new WP_Error( 'token_error', 'Impossible d\'obtenir le token d\'accès' );
	}

	/**
	 * Rafraîchir le token d'accès
	 *
	 * @return bool|WP_Error
	 */
	public function refresh_access_token() {
		$client_id = get_option( 'olm_google_client_id', '' );
		$client_secret = get_option( 'olm_google_client_secret', '' );
		$refresh_token = get_option( 'olm_google_refresh_token', '' );

		if ( empty( $refresh_token ) ) {
			return new WP_Error( 'no_refresh_token', 'Pas de refresh token disponible' );
		}

		$response = wp_remote_post( 'https://oauth2.googleapis.com/token', array(
			'body' => array(
				'client_id' => $client_id,
				'client_secret' => $client_secret,
				'refresh_token' => $refresh_token,
				'grant_type' => 'refresh_token'
			)
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['access_token'] ) ) {
			update_option( 'olm_google_access_token', $body['access_token'] );
			$this->access_token = $body['access_token'];
			return true;
		}

		return new WP_Error( 'refresh_error', 'Impossible de rafraîchir le token' );
	}

	/**
	 * Récupérer les données Search Console pour une URL
	 *
	 * @param string $url L'URL relative de la page
	 * @param int $days Nombre de jours à récupérer (par défaut 28)
	 * @return array|WP_Error
	 */
	public function get_search_console_data( $url, $days = 28 ) {
		if ( ! $this->is_authenticated() ) {
			return new WP_Error( 'not_authenticated', 'Non authentifié' );
		}

		// Utiliser l'URL configurée dans les paramètres, ou fallback sur get_site_url()
		$site_url = get_option( 'olm_google_search_console_url', '' );
		if ( empty( $site_url ) ) {
			$site_url = get_site_url();
		}

		$end_date = date( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Préparer la requête
		$api_url = self::SEARCH_CONSOLE_WEBMASTERS_URL . '/' . urlencode( $site_url ) . '/searchAnalytics/query';

		$request_body = array(
			'startDate' => $start_date,
			'endDate' => $end_date,
			'dimensions' => array( 'page' ),
			'dimensionFilterGroups' => array(
				array(
					'filters' => array(
						array(
							'dimension' => 'page',
							'expression' => $site_url . $url,
							'operator' => 'equals'
						)
					)
				)
			)
		);

		// Debug: Logger la requête
		error_log( 'Search Console API Request:' );
		error_log( 'URL: ' . $api_url );
		error_log( 'Site URL: ' . $site_url );
		error_log( 'Page URL: ' . $url );
		error_log( 'Full URL recherché: ' . $site_url . $url );
		error_log( 'Date range: ' . $start_date . ' to ' . $end_date );

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $request_body ),
			'timeout' => 30
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'Search Console API Error: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body = wp_remote_retrieve_body( $response );

		// Debug: Logger la réponse
		error_log( 'Search Console API Response Status: ' . $status_code );
		error_log( 'Search Console API Response Body: ' . substr( $body, 0, 500 ) );

		// Si le token a expiré, essayer de le rafraîchir
		if ( $status_code === 401 ) {
			$refresh_result = $this->refresh_access_token();
			if ( ! is_wp_error( $refresh_result ) ) {
				// Réessayer la requête avec le nouveau token
				return $this->get_search_console_data( $url, $days );
			}
			return new WP_Error( 'auth_expired', 'Token d\'authentification expiré' );
		}

		$body_decoded = json_decode( $body, true );

		if ( isset( $body_decoded['rows'] ) && is_array( $body_decoded['rows'] ) ) {
			$total_impressions = 0;
			$total_clicks = 0;

			foreach ( $body_decoded['rows'] as $row ) {
				$total_impressions += isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0;
				$total_clicks += isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0;
			}

			error_log( 'Data found: ' . $total_impressions . ' impressions, ' . $total_clicks . ' clicks' );

			return array(
				'impressions' => $total_impressions,
				'clicks' => $total_clicks
			);
		}

		error_log( 'No data returned from Search Console API' );

		return array(
			'impressions' => 0,
			'clicks' => 0
		);
	}

	/**
	 * Récupérer les données Analytics pour une URL
	 *
	 * @param string $property_id L'ID de la propriété GA4
	 * @param string $url L'URL relative de la page
	 * @param int $days Nombre de jours à récupérer
	 * @return array|WP_Error
	 */
	public function get_analytics_data( $property_id, $url, $days = 28 ) {
		if ( ! $this->is_authenticated() ) {
			return new WP_Error( 'not_authenticated', 'Non authentifié' );
		}

		$end_date = date( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		// API Analytics Data (GA4)
		$api_url = self::ANALYTICS_API_URL . '/properties/' . $property_id . ':runReport';

		$request_body = array(
			'dateRanges' => array(
				array(
					'startDate' => $start_date,
					'endDate' => $end_date
				)
			),
			'dimensions' => array(
				array( 'name' => 'pagePath' )
			),
			'metrics' => array(
				array( 'name' => 'totalUsers' ),
				array( 'name' => 'sessions' )
			),
			'dimensionFilter' => array(
				'filter' => array(
					'fieldName' => 'pagePath',
					'stringFilter' => array(
						'matchType' => 'EXACT',
						'value' => $url
					)
				)
			)
		);

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $request_body ),
			'timeout' => 30
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 401 ) {
			$refresh_result = $this->refresh_access_token();
			if ( ! is_wp_error( $refresh_result ) ) {
				return $this->get_analytics_data( $property_id, $url, $days );
			}
			return new WP_Error( 'auth_expired', 'Token d\'authentification expiré' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $body['rows'] ) && is_array( $body['rows'] ) ) {
			$total_users = 0;
			$total_sessions = 0;

			foreach ( $body['rows'] as $row ) {
				if ( isset( $row['metricValues'] ) ) {
					$total_users += isset( $row['metricValues'][0]['value'] ) ? intval( $row['metricValues'][0]['value'] ) : 0;
					$total_sessions += isset( $row['metricValues'][1]['value'] ) ? intval( $row['metricValues'][1]['value'] ) : 0;
				}
			}

			return array(
				'users' => $total_users,
				'sessions' => $total_sessions
			);
		}

		return array(
			'users' => 0,
			'sessions' => 0
		);
	}

	/**
	 * Récupérer TOUTES les URLs avec du trafic depuis Search Console
	 *
	 * @param int $days Nombre de jours à récupérer (par défaut 28)
	 * @param int $row_limit Nombre maximum d'URLs à récupérer (par défaut 1000)
	 * @return array|WP_Error Array of URLs with their data or WP_Error
	 */
	public function get_all_urls_from_search_console( $days = 28, $row_limit = 1000 ) {
		if ( ! $this->is_authenticated() ) {
			return new WP_Error( 'not_authenticated', 'Non authentifié' );
		}

		// Utiliser l'URL configurée dans les paramètres, ou fallback sur get_site_url()
		$site_url = get_option( 'olm_google_search_console_url', '' );
		if ( empty( $site_url ) ) {
			$site_url = get_site_url();
		}

		$end_date = date( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		// Préparer la requête pour récupérer TOUTES les pages
		$api_url = self::SEARCH_CONSOLE_WEBMASTERS_URL . '/' . urlencode( $site_url ) . '/searchAnalytics/query';

		$request_body = array(
			'startDate' => $start_date,
			'endDate' => $end_date,
			'dimensions' => array( 'page' ),
			'rowLimit' => $row_limit
		);

		// Debug: Logger la requête
		error_log( '=== GET ALL URLS FROM SEARCH CONSOLE ===' );
		error_log( 'API URL: ' . $api_url );
		error_log( 'Site URL: ' . $site_url );
		error_log( 'Request body: ' . json_encode( $request_body ) );

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type' => 'application/json'
			),
			'body' => json_encode( $request_body ),
			'timeout' => 30
		) );

		if ( is_wp_error( $response ) ) {
			error_log( 'WP_Error: ' . $response->get_error_message() );
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );
		$body_raw = wp_remote_retrieve_body( $response );

		// Debug: Logger la réponse
		error_log( 'Response status: ' . $status_code );
		error_log( 'Response body: ' . substr( $body_raw, 0, 1000 ) );

		// Si le token a expiré, essayer de le rafraîchir
		if ( $status_code === 401 ) {
			error_log( 'Token expired, refreshing...' );
			$refresh_result = $this->refresh_access_token();
			if ( ! is_wp_error( $refresh_result ) ) {
				// Réessayer la requête avec le nouveau token
				return $this->get_all_urls_from_search_console( $days, $row_limit );
			}
			return new WP_Error( 'auth_expired', 'Token d\'authentification expiré' );
		}

		$body = json_decode( $body_raw, true );

		$urls_data = array();

		if ( isset( $body['rows'] ) && is_array( $body['rows'] ) ) {
			error_log( 'Found ' . count( $body['rows'] ) . ' rows in response' );
			foreach ( $body['rows'] as $row ) {
				if ( isset( $row['keys'][0] ) ) {
					$full_url = $row['keys'][0];

					// Extraire le path de l'URL complète
					$parsed_url = parse_url( $full_url );
					$url_path = isset( $parsed_url['path'] ) ? $parsed_url['path'] : '/';

					$urls_data[] = array(
						'url' => $url_path,
						'full_url' => $full_url,
						'impressions' => isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0,
						'clicks' => isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0,
						'ctr' => isset( $row['ctr'] ) ? floatval( $row['ctr'] ) : 0,
						'position' => isset( $row['position'] ) ? floatval( $row['position'] ) : 0
					);
				}
			}
			error_log( 'Processed ' . count( $urls_data ) . ' URLs' );
		} else {
			error_log( 'No rows found in response body' );
			if ( isset( $body['error'] ) ) {
				error_log( 'API Error: ' . json_encode( $body['error'] ) );
			}
		}

		return $urls_data;
	}

	/**
	 * Récupérer les mots-clés (requêtes) pour une URL spécifique depuis Search Console
	 *
	 * @param string $page_url L'URL complète de la page
	 * @param int $days Nombre de jours à récupérer (par défaut 28)
	 * @param int $row_limit Nombre maximum de mots-clés (par défaut 50)
	 * @return array|WP_Error
	 */
	public function get_keywords_for_url( $page_url, $days = 28, $row_limit = 50 ) {
		if ( ! $this->is_authenticated() ) {
			return new WP_Error( 'not_authenticated', 'Non authentifié' );
		}

		$site_url = get_option( 'olm_google_search_console_url', '' );
		if ( empty( $site_url ) ) {
			$site_url = get_site_url();
		}

		$end_date = date( 'Y-m-d' );
		$start_date = date( 'Y-m-d', strtotime( "-{$days} days" ) );

		$api_url = self::SEARCH_CONSOLE_WEBMASTERS_URL . '/' . urlencode( $site_url ) . '/searchAnalytics/query';

		$request_body = array(
			'startDate'  => $start_date,
			'endDate'    => $end_date,
			'dimensions' => array( 'query' ),
			'dimensionFilterGroups' => array(
				array(
					'filters' => array(
						array(
							'dimension'  => 'page',
							'expression' => $page_url,
							'operator'   => 'equals'
						)
					)
				)
			),
			'rowLimit' => $row_limit
		);

		$response = wp_remote_post( $api_url, array(
			'headers' => array(
				'Authorization' => 'Bearer ' . $this->access_token,
				'Content-Type'  => 'application/json'
			),
			'body'    => json_encode( $request_body ),
			'timeout' => 30
		) );

		if ( is_wp_error( $response ) ) {
			return $response;
		}

		$status_code = wp_remote_retrieve_response_code( $response );

		if ( $status_code === 401 ) {
			$refresh_result = $this->refresh_access_token();
			if ( ! is_wp_error( $refresh_result ) ) {
				return $this->get_keywords_for_url( $page_url, $days, $row_limit );
			}
			return new WP_Error( 'auth_expired', 'Token d\'authentification expiré' );
		}

		$body = json_decode( wp_remote_retrieve_body( $response ), true );

		$keywords = array();

		if ( isset( $body['rows'] ) && is_array( $body['rows'] ) ) {
			foreach ( $body['rows'] as $row ) {
				$keywords[] = array(
					'query'       => isset( $row['keys'][0] ) ? $row['keys'][0] : '',
					'clicks'      => isset( $row['clicks'] ) ? intval( $row['clicks'] ) : 0,
					'impressions' => isset( $row['impressions'] ) ? intval( $row['impressions'] ) : 0,
					'ctr'         => isset( $row['ctr'] ) ? round( floatval( $row['ctr'] ) * 100, 2 ) : 0,
					'position'    => isset( $row['position'] ) ? round( floatval( $row['position'] ), 1 ) : 0,
				);
			}
		}

		return $keywords;
	}

	/**
	 * Déconnecter (supprimer les tokens)
	 */
	public function disconnect() {
		delete_option( 'olm_google_access_token' );
		delete_option( 'olm_google_refresh_token' );
		$this->access_token = '';
	}
}
