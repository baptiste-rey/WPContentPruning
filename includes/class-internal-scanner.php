<?php

/**
 * Handles the scanning of content for internal links.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Internal_Scanner {

	/**
	 * limits for batch processing
	 */
	const BATCH_SIZE = 20;

	/**
	 * Count total posts to scan.
	 *
	 * @since 1.0.0
	 * @return int Total number of posts to scan.
	 */
	public function get_total_posts_count() {
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		// Exclure les attachments (médias) qui n'ont pas de contenu à scanner
		$post_types = array_diff( $post_types, array( 'attachment' ) );

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
		);
		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Process a batch of posts to count internal links.
	 *
	 * @since 1.0.0
	 * @param int $batch The batch number (1-based).
	 * @return int Number of posts processed in this batch.
	 */
	public function scan_batch( $batch ) {
		global $wpdb;

		$offset = ( $batch - 1 ) * self::BATCH_SIZE;
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		// Exclure les attachments (médias) qui n'ont pas de contenu à scanner
		$post_types = array_diff( $post_types, array( 'attachment' ) );

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => 'publish',
			'posts_per_page' => self::BATCH_SIZE,
			'offset'         => $offset,
		);
		
		$posts = get_posts( $args );
		$home_url = home_url();
		$parsed_home = parse_url($home_url);
		$home_host = isset($parsed_home['host']) ? $parsed_home['host'] : '';

		foreach ( $posts as $post ) {
			// Count internal links only in post_content (not meta)
			$content = $post->post_content;
			
			if ( empty( trim( $content ) ) ) {
				$this->save_stats( $post->ID, $post->post_type, 0 );
				continue;
			}

			$internal_links = $this->extract_internal_links( $content, $home_host );
			$internal_count = count( $internal_links );
			
			$this->save_stats( $post->ID, $post->post_type, $internal_count );
			$this->save_internal_links_details( $post->ID, $internal_links );
		}

		return count( $posts );
	}

	/**
	 * Extract internal links with details from content.
	 *
	 * @param string $content The content to scan.
	 * @param string $home_host The site's host for comparison.
	 * @return array Array of internal links with url and anchor text.
	 */
	private function extract_internal_links( $content, $home_host ) {
		$internal_links = array();
		
		// Use regex to extract links with their anchor text
		// Pattern matches: <a ... href="URL" ...>ANCHOR TEXT</a>
		$pattern = '/<a\s[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is';
		
		if ( preg_match_all( $pattern, $content, $matches, PREG_SET_ORDER ) ) {
			foreach ( $matches as $match ) {
				$url = trim( $match[1] );
				// Strip HTML tags from anchor but keep the text
				$anchor_text = trim( strip_tags( $match[2] ) );
				
				// Skip empty, anchors, mailto, tel, javascript
				if ( empty( $url ) || $url === '#' || 
					 strpos( $url, 'mailto:' ) === 0 || 
					 strpos( $url, 'tel:' ) === 0 || 
					 strpos( $url, 'javascript:' ) === 0 ) {
					continue;
				}

				// Check if it's internal
				if ( $this->is_internal( $url, $home_host ) ) {
					// Convert relative URLs to absolute
					if ( strpos( $url, '/' ) === 0 && strpos( $url, '//' ) !== 0 ) {
						$url = home_url( $url );
					}
					
					$internal_links[] = array(
						'url' => $url,
						'anchor' => $anchor_text
					);
				}
			}
		}

		return $internal_links;
	}

	/**
	 * Check if URL is internal (belongs to this site).
	 *
	 * @param string $url The URL to check.
	 * @param string $home_host The site's host.
	 * @return bool True if internal, false otherwise.
	 */
	private function is_internal( $url, $home_host ) {
		// Relative URLs are internal
		if ( strpos( $url, '/' ) === 0 && strpos( $url, '//' ) !== 0 ) {
			return true;
		}

		// Handle Protocol-relative URLs (//example.com)
		if ( strpos( $url, '//' ) === 0 ) {
			$url = 'http:' . $url;
		}

		// Must start with http/https to be absolute
		if ( strpos( $url, 'http' ) !== 0 ) {
			return false;
		}

		$parsed = parse_url( $url );
		if ( ! is_array( $parsed ) || ! isset( $parsed['host'] ) ) {
			return false;
		}

		$link_host = $parsed['host'];
		
		// Remove www. for comparison
		$clean_link_host = str_ireplace( 'www.', '', $link_host );
		$clean_home_host = str_ireplace( 'www.', '', $home_host );

		// If hosts match, it's internal
		return ( strcasecmp( $clean_link_host, $clean_home_host ) === 0 );
	}

	/**
	 * Save internal links statistics to database.
	 *
	 * @param int $post_id The post ID.
	 * @param string $post_type The post type.
	 * @param int $count Number of internal links.
	 */
	private function save_stats( $post_id, $post_type, $count ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'internal_links_stats';
		
		// Insert or update
		$existing = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM $table_name WHERE post_id = %d",
			$post_id
		) );

		if ( $existing ) {
			$wpdb->update(
				$table_name,
				array(
					'internal_links_count' => $count,
					'last_scanned'         => current_time( 'mysql' ),
				),
				array( 'post_id' => $post_id ),
				array( '%d', '%s' ),
				array( '%d' )
			);
		} else {
			$wpdb->insert(
				$table_name,
				array(
					'post_id'              => $post_id,
					'post_type'            => $post_type,
					'internal_links_count' => $count,
					'last_scanned'         => current_time( 'mysql' ),
				),
				array( '%d', '%s', '%d', '%s' )
			);
		}
	}

	/**
	 * Save internal links details to database.
	 *
	 * @param int $post_id The post ID.
	 * @param array $links Array of internal links with url and anchor.
	 */
	private function save_internal_links_details( $post_id, $links ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'internal_links_details';

		// Delete existing links for this post
		$wpdb->delete(
			$table_name,
			array( 'post_id' => $post_id ),
			array( '%d' )
		);

		// Insert new links
		foreach ( $links as $link ) {
			$target_post_id = $this->resolve_url_to_post_id( $link['url'] );

			if ( $target_post_id ) {
				$wpdb->insert(
					$table_name,
					array(
						'post_id'        => $post_id,
						'target_url'     => $link['url'],
						'target_post_id' => $target_post_id,
						'anchor_text'    => $link['anchor'],
					),
					array( '%d', '%s', '%d', '%s' )
				);
			} else {
				$wpdb->insert(
					$table_name,
					array(
						'post_id'        => $post_id,
						'target_url'     => $link['url'],
						'anchor_text'    => $link['anchor'],
					),
					array( '%d', '%s', '%s' )
				);
			}
		}
	}

	/**
	 * Resolve a URL to a WordPress post ID with multiple fallback methods.
	 *
	 * @param string $url The URL to resolve.
	 * @return int|null Post ID or null if not found.
	 */
	private function resolve_url_to_post_id( $url ) {
		// Méthode 1 : url_to_postid() natif WordPress
		$post_id = url_to_postid( $url );
		if ( $post_id > 0 ) {
			return $post_id;
		}

		// Méthode 2 : Essayer avec/sans trailing slash
		$url_with_slash = trailingslashit( $url );
		$url_without_slash = untrailingslashit( $url );

		$post_id = url_to_postid( $url_with_slash );
		if ( $post_id > 0 ) {
			return $post_id;
		}

		$post_id = url_to_postid( $url_without_slash );
		if ( $post_id > 0 ) {
			return $post_id;
		}

		// Méthode 3 : Extraire le path et chercher par post_name (slug)
		$parsed = wp_parse_url( $url );
		if ( ! isset( $parsed['path'] ) || $parsed['path'] === '/' ) {
			return null;
		}

		$path = trim( $parsed['path'], '/' );
		// Prendre le dernier segment du path comme slug potentiel
		$segments = explode( '/', $path );
		$slug = end( $segments );

		if ( empty( $slug ) ) {
			return null;
		}

		global $wpdb;
		$found = $wpdb->get_var( $wpdb->prepare(
			"SELECT ID FROM {$wpdb->posts} WHERE post_name = %s AND post_status = 'publish' LIMIT 1",
			$slug
		) );

		if ( $found ) {
			return (int) $found;
		}

		return null;
	}
}
