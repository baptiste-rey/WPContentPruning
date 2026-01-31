<?php

/**
 * Handles the scanning of content for external links.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Scanner {

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
			'post_status'    => array( 'publish', 'draft', 'inherit' ),
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'suppress_filters' => true, // Désactiver les filtres pour compter TOUS les posts
		);
		$query = new WP_Query( $args );
		return $query->found_posts;
	}

	/**
	 * Process a batch of posts.
	 *
	 * @since 1.0.0
	 * @param int $batch The batch number (1-based).
	 * @return int Number of links found in this batch.
	 */
	public function scan_batch( $batch ) {
		global $wpdb;

		$offset = ( $batch - 1 ) * self::BATCH_SIZE;
		$post_types = get_post_types( array( 'public' => true ), 'names' );
		// Exclure les attachments (médias) qui n'ont pas de contenu à scanner
		$post_types = array_diff( $post_types, array( 'attachment' ) );

		$args = array(
			'post_type'      => $post_types,
			'post_status'    => array( 'publish', 'draft', 'inherit' ),
			'posts_per_page' => self::BATCH_SIZE,
			'offset'         => $offset,
			'orderby'        => 'ID',
			'order'          => 'ASC',
			'no_found_rows'  => true,
			'suppress_filters' => true, // Désactiver les filtres pour récupérer TOUS les posts
		);

		$posts = get_posts( $args );
		$new_links_count = 0;
		$home_url = home_url();
		$parsed_home = parse_url($home_url);
		$home_host = isset($parsed_home['host']) ? $parsed_home['host'] : '';

		foreach ( $posts as $post ) {
			$this->clear_post_links( $post->ID );

			$content_blob = $post->post_content;

			// Scan ALL Meta Data, including hidden (_elementor_data, etc.)
			$metas = get_post_meta( $post->ID );
			if ( $metas ) {
				foreach ( $metas as $key => $values ) {
					// recursive flatten
					array_walk_recursive($values, function($item) use (&$content_blob) {
						if (is_string($item) || is_numeric($item)) {
							$content_blob .= "\n" . $item;
						}
					});
				}
			}

			if ( empty( trim( $content_blob ) ) ) {
				continue;
			}

			$links = $this->extract_links( $content_blob );

			foreach ( $links as $link ) {
				if ( $this->is_external( $link['url'], $home_host ) && ! $this->is_domain_excluded( $link['url'] ) ) {
					$this->save_link( $post, $link );
					$new_links_count++;
				}
			}
		}

		return $new_links_count;
	}

	/**
	 * Clear links for a specific post.
	 */
	private function clear_post_links( $post_id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';
		$wpdb->delete( $table_name, array( 'post_id' => $post_id ), array( '%d' ) );
	}

	/**
	 * Purge all links from the database.
	 *
	 * @since 1.0.0
	 * @return bool True on success, false on failure.
	 */
	public function purge_all_links() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';
		// Use DELETE instead of TRUNCATE for better WordPress compatibility
		$result = $wpdb->query( "DELETE FROM $table_name" );
		return $result !== false;
	}

	/**
	 * Debug scan for a single post - returns detailed information
	 *
	 * @since 1.0.0
	 * @param int $post_id Post ID to debug
	 * @return array Debug information
	 */
	public function debug_scan_post( $post_id ) {
		$post = get_post( $post_id );
		if ( ! $post ) {
			return array( 'error' => 'Post not found' );
		}

		$home_url = home_url();
		$parsed_home = parse_url($home_url);
		$home_host = isset($parsed_home['host']) ? $parsed_home['host'] : '';

		$debug = array(
			'post_id' => $post_id,
			'post_title' => $post->post_title,
			'post_type' => $post->post_type,
			'post_status' => $post->post_status,
			'post_date' => $post->post_date,
			'home_url' => $home_url,
			'home_host' => $home_host,
			'content_length' => strlen( $post->post_content ),
			'meta_fields' => array(),
			'all_links_found' => array(),
			'external_links' => array(),
			'excluded_links' => array(),
			'internal_links' => array(),
			'excluded_domains' => get_option( 'olm_excluded_domains', "youtube.com\nyoutu.be" ),
		);

		// Get all meta
		$metas = get_post_meta( $post_id );
		$debug['meta_count'] = count( $metas );
		foreach ( $metas as $key => $values ) {
			$debug['meta_fields'][] = $key;
		}

		// Build content blob
		$content_blob = $post->post_content;
		if ( $metas ) {
			foreach ( $metas as $key => $values ) {
				array_walk_recursive($values, function($item) use (&$content_blob) {
					if (is_string($item) || is_numeric($item)) {
						$content_blob .= "\n" . $item;
					}
				});
			}
		}

		$debug['total_content_length'] = strlen( $content_blob );

		// Extract all links
		$links = $this->extract_links( $content_blob );
		$debug['all_links_found'] = $links;
		$debug['total_links_found'] = count( $links );

		// Test each link
		foreach ( $links as $link ) {
			$is_ext = $this->is_external( $link['url'], $home_host );
			if ( $is_ext ) {
				// Check if excluded
				if ( $this->is_domain_excluded( $link['url'] ) ) {
					$debug['excluded_links'][] = $link;
				} else {
					$debug['external_links'][] = $link;
				}
			} else {
				$debug['internal_links'][] = $link;
			}
		}

		$debug['external_count'] = count( $debug['external_links'] );
		$debug['excluded_count'] = count( $debug['excluded_links'] );
		$debug['internal_count'] = count( $debug['internal_links'] );

		return $debug;
	}

	/**
	 * Extract links using multiple strategies.
	 */
	private function extract_links( $content ) {
		$links = array();

		// Prepare variations of content to scan to ensure we catch escaped/json-encoded links
		$variations = array(
			$content,
			html_entity_decode( $content, ENT_QUOTES | ENT_HTML5 ), // Catch &lt;a href...
			stripslashes( $content ),        // Catch \"http...\"
			html_entity_decode( stripslashes( $content ), ENT_QUOTES | ENT_HTML5 ) // Combined
		);
		$variations = array_unique( $variations );

		foreach ( $variations as $html_variant ) {
			// 1. Direct Regex for <a> tags (most reliable for catching all href attributes)
			// This catches: <a href="URL" ...>ANCHOR</a> and all variations
			$pattern_anchor = '/<a\s+[^>]*href\s*=\s*["\']([^"\']+)["\'][^>]*>(.*?)<\/a>/is';
			if ( preg_match_all( $pattern_anchor, $html_variant, $matches_anchor, PREG_SET_ORDER ) ) {
				foreach ( $matches_anchor as $m ) {
					$url = trim( $m[1] );
					$anchor = trim( strip_tags( $m[2] ) );

					if ( empty( $url ) || $url === '#' ) {
						continue;
					}

					// Extract attributes from the full <a> tag
					$attributes = array();
					$full_tag = $m[0];

					// Extract target attribute
					if ( preg_match( '/target\s*=\s*["\']([^"\']+)["\']/i', $full_tag, $target_match ) ) {
						$attributes['target'] = $target_match[1];
					}

					// Extract rel attribute
					if ( preg_match( '/rel\s*=\s*["\']([^"\']+)["\']/i', $full_tag, $rel_match ) ) {
						$attributes['rel'] = $rel_match[1];
					}

					$links[] = array(
						'url'        => $url,
						'anchor'     => $anchor ?: '(Sans texte)',
						'attributes' => $attributes,
					);
				}
			}

			// 2. DOMDocument Scan (backup method)
			if ( class_exists( 'DOMDocument' ) ) {
				$dom = new DOMDocument();
				libxml_use_internal_errors( true );

				// Force UTF-8 and suppress warnings
				$content_encoded = '<?xml encoding="UTF-8">' . $html_variant;

				// Loose parsing options
				@$dom->loadHTML( $content_encoded, LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );

				foreach ( $dom->getElementsByTagName( 'a' ) as $node ) {
					$url = $node->getAttribute( 'href' );
					$url = trim( $url );

					if ( empty( $url ) || $url === '#' ) {
						continue;
					}

					$anchor = $node->textContent;

					$attributes = array();
					if ( $node->hasAttribute( 'target' ) ) {
						$attributes['target'] = $node->getAttribute( 'target' );
					}
					if ( $node->hasAttribute( 'rel' ) ) {
						$attributes['rel'] = $node->getAttribute( 'rel' );
					}

					$links[] = array(
						'url'        => $url,
						'anchor'     => $anchor,
						'attributes' => $attributes,
					);
				}
				libxml_clear_errors();
			}

			// 3. Raw Regex Scan (for URLs in JSON/JS that DOM doesn't see)
			// More permissive: captures URLs with single or double quotes
			$pattern_raw = '/["\']?(https?:\/\/[^\s"\'<>]+)["\']?/i';
			if ( preg_match_all( $pattern_raw, $html_variant, $matches_raw, PREG_SET_ORDER ) ) {
				foreach ( $matches_raw as $m ) {
					$url = $m[1];
					// Clean trailing punctuation that might be caught
					$url = rtrim( $url, '.,;:!?)' );

					if ( empty( $url ) ) {
						continue;
					}

					$links[] = array(
						'url'        => $url,
						'anchor'     => '(Lien brut / Méta)',
						'attributes' => array()
					);
				}
			}
		}

		// Deduplicate links based on URL
		$unique_links = array();
		foreach ( $links as $link ) {
			$key = md5( $link['url'] ); // Dedup mainly by URL.
			// Note: If same URL has different anchor in different variants, we keep the first one found.
			// Priority: Anchor tag with text > DOM parsed > Raw URL

			if ( ! isset( $unique_links[ $key ] ) ) {
				$unique_links[ $key ] = $link;
			} else {
				// If we have a "Raw" link but now found a "Real" link (with anchor text), replace it.
				if ( $unique_links[ $key ]['anchor'] === '(Lien brut / Méta)' && $link['anchor'] !== '(Lien brut / Méta)' ) {
					$unique_links[ $key ] = $link;
				}
			}
		}

		return array_values( $unique_links );
	}

	/**
	 * Check if URL is external.
	 */
	private function is_external( $url, $home_host ) {
		// Ignore anchors (#), mailto:, tel:, javascript:
		if ( strpos( $url, '#' ) === 0 || 
			 strpos( $url, 'mailto:' ) === 0 || 
			 strpos( $url, 'tel:' ) === 0 || 
			 strpos( $url, 'javascript:' ) === 0 ) {
			return false;
		}
		
		// Handle Protocol-relative URLs (//example.com)
		if ( strpos( $url, '//' ) === 0 ) {
			$url = 'http:' . $url; // assume http for parsing
		}
		// Handle relative URLs (/about) -> Internal
		elseif ( strpos( $url, 'http' ) !== 0 ) {
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

		// Strict compare
		if ( strcasecmp( $clean_link_host, $clean_home_host ) === 0 ) {
			return false;
		}

		return true;
	}

	/**
	 * Check if domain is excluded from scanning.
	 *
	 * @param string $url The URL to check.
	 * @return bool True if domain is excluded, false otherwise.
	 */
	private function is_domain_excluded( $url ) {
		// Get excluded domains from options
		$excluded_domains_text = get_option( 'olm_excluded_domains', "youtube.com\nyoutu.be" );

		if ( empty( trim( $excluded_domains_text ) ) ) {
			return false;
		}

		// Parse URL to get host
		$parsed = parse_url( $url );
		if ( ! is_array( $parsed ) || ! isset( $parsed['host'] ) ) {
			return false;
		}

		$url_host = strtolower( $parsed['host'] );
		// Remove www. for comparison
		$url_host_clean = str_ireplace( 'www.', '', $url_host );

		// Parse excluded domains list (one per line)
		$excluded_domains = array_map( 'trim', explode( "\n", $excluded_domains_text ) );
		$excluded_domains = array_filter( $excluded_domains ); // Remove empty lines

		foreach ( $excluded_domains as $excluded_domain ) {
			$excluded_domain = strtolower( trim( $excluded_domain ) );
			if ( empty( $excluded_domain ) ) {
				continue;
			}

			// Remove www. from excluded domain
			$excluded_domain_clean = str_ireplace( 'www.', '', $excluded_domain );

			// Check if URL host contains the excluded domain
			// This allows matching both "youtube.com" and "www.youtube.com"
			// Also matches subdomains like "m.youtube.com"
			if ( strpos( $url_host_clean, $excluded_domain_clean ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Save link to DB.
	 */
	private function save_link( $post, $link_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';
		
		// Check duplicates if needed? 
		// For now we just insert. The Scanner clears post links before this loop.
		
		$wpdb->insert(
			$table_name,
			array(
				'url'             => $link_data['url'],
				'anchor_text'     => $link_data['anchor'],
				'post_id'         => $post->ID,
				'post_type'       => $post->post_type,
				'link_attributes' => json_encode( $link_data['attributes'] ),
				'occurrence_count'=> 1, 
				'created_at'      => current_time( 'mysql' ),
			),
			array(
				'%s',
				'%s',
				'%d',
				'%s',
				'%s',
				'%d',
				'%s'
			)
		);
	}
}
