<?php

/**
 * Handles link modification and deletion.
 *
 * @since      1.0.0
 * @package    Outbound_Links_Manager
 * @subpackage Outbound_Links_Manager/includes
 */

class Outbound_Links_Manager_Link_Manager {

	/**
	 * Get link by ID.
	 *
	 * @param int $id Link ID.
	 * @return object|null Link object.
	 */
	public function get_link( $id ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'outbound_links';
		return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM $table_name WHERE id = %d", $id ) );
	}

	/**
	 * Update a link.
	 *
	 * @param int $link_id Link ID.
	 * @param array $new_data New data [url, anchor, attributes, etc].
	 * @return bool|WP_Error True on success.
	 */
	public function update_link( $link_id, $new_data ) {
		global $wpdb;
		$link = $this->get_link( $link_id );

		if ( ! $link ) {
			return new WP_Error( 'not_found', 'Lien introuvable.' );
		}

		$post = get_post( $link->post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', 'Contenu introuvable.' );
		}

		// Ne modifier le contenu que pour les types de posts standards (pas les attachments)
		if ( $post->post_type !== 'attachment' && ! empty( $post->post_content ) ) {
			// Update Content
			$updated_content = $this->replace_link_in_content(
				$post->post_content,
				$link,
				$new_data
			);

			// Save Post seulement si le contenu a changé
			if ( $updated_content !== $post->post_content ) {
				$post_update = array(
					'ID'           => $post->ID,
					'post_content' => $updated_content,
				);
				wp_update_post( $post_update );
			}
		}

		// Save History
		$table_history = $wpdb->prefix . 'outbound_links_history';
		$wpdb->insert(
			$table_history,
			array(
				'link_id' => $link_id,
				'action_type' => 'update',
				'old_value' => json_encode( $link ), // simple dump of old obj
				'new_value' => json_encode( $new_data ),
				'user_id' => get_current_user_id()
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);

		// Update DB Table
		$table_name = $wpdb->prefix . 'outbound_links';
		$wpdb->update(
			$table_name,
			array(
				'url'             => $new_data['url'],
				'anchor_text'     => $new_data['anchor'],
				'link_attributes' => json_encode( $new_data['attributes'] ),
				'updated_at'      => current_time( 'mysql' )
			),
			array( 'id' => $link_id ),
			array( '%s', '%s', '%s', '%s' ),
			array( '%d' )
		);
		
		return true;
	}

	/**
	 * Replace link in content using DOMDocument.
	 */
	private function replace_link_in_content( $content, $old_link, $new_data ) {
		// Use DOMDocument to robustly find and replace
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		
		// Load with encoding hack
		$dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		
		$found = false;
		
		foreach ( $dom->getElementsByTagName('a') as $node ) {
			$href = $node->getAttribute('href');
			$text = $node->nodeValue;
			
			// Match heuristics: URL match is primary.
			// If URL matches old URL, we update it.
			// Checks for anchor text ?
			
			if ( $href === $old_link->url ) {
				// Update HREF
				if ( isset( $new_data['url'] ) ) {
					$node->setAttribute('href', $new_data['url']);
				}
				
				// Update Anchor
				if ( isset( $new_data['anchor'] ) ) {
					$node->nodeValue = $new_data['anchor'];
				}
				
				// Update Attributes (Target, Nofollow)
				if ( isset( $new_data['attributes'] ) && is_array( $new_data['attributes'] ) ) {
					// Handle specific attributes like rel and target
					if ( isset( $new_data['attributes']['target'] ) ) {
						if ( empty($new_data['attributes']['target']) ) {
							$node->removeAttribute('target');
						} else {
							$node->setAttribute('target', $new_data['attributes']['target']);
						}
					}
					
					if ( isset( $new_data['attributes']['rel'] ) ) {
						 if ( empty($new_data['attributes']['rel']) ) {
							$node->removeAttribute('rel');
						} else {
							$node->setAttribute('rel', $new_data['attributes']['rel']);
						}
					}
				}
				
				$found = true;
			}
		}
		
		if ( $found ) {
			return $dom->saveHTML();
		}
		
		return $content;
	}

	/**
	 * Delete a link (remove the <a> tag but keep anchor text).
	 *
	 * @param int $link_id Link ID.
	 * @return bool|WP_Error True on success.
	 */
	public function delete_link( $link_id ) {
		global $wpdb;
		$link = $this->get_link( $link_id );

		if ( ! $link ) {
			return new WP_Error( 'not_found', 'Lien introuvable.' );
		}

		$post = get_post( $link->post_id );
		if ( ! $post ) {
			return new WP_Error( 'post_not_found', 'Contenu introuvable.' );
		}

		// Ne modifier le contenu que pour les types de posts standards (pas les attachments)
		if ( $post->post_type !== 'attachment' && ! empty( $post->post_content ) ) {
			// Remove link from content but keep anchor text
			$updated_content = $this->remove_link_from_content(
				$post->post_content,
				$link
			);

			// Save Post seulement si le contenu a changé
			if ( $updated_content !== $post->post_content ) {
				// Utilisation directe de $wpdb pour éviter les hooks WordPress lors de suppressions en masse
				$wpdb->update(
					$wpdb->posts,
					array(
						'post_content' => $updated_content,
						'post_modified' => current_time( 'mysql' ),
						'post_modified_gmt' => current_time( 'mysql', 1 )
					),
					array( 'ID' => $post->ID ),
					array( '%s', '%s', '%s' ),
					array( '%d' )
				);

				// Nettoyer le cache WordPress pour ce post
				clean_post_cache( $post->ID );
			}
		}

		// Save History
		$table_history = $wpdb->prefix . 'outbound_links_history';
		$wpdb->insert(
			$table_history,
			array(
				'link_id' => $link_id,
				'action_type' => 'delete',
				'old_value' => json_encode( $link ),
				'new_value' => '',
				'user_id' => get_current_user_id()
			),
			array( '%d', '%s', '%s', '%s', '%d' )
		);

		// Delete from DB Table
		$table_name = $wpdb->prefix . 'outbound_links';
		$wpdb->delete(
			$table_name,
			array( 'id' => $link_id ),
			array( '%d' )
		);
		
		return true;
	}

	/**
	 * Remove link from content but keep anchor text.
	 */
	private function remove_link_from_content( $content, $old_link ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		
		// Load with encoding hack
		$dom->loadHTML( mb_convert_encoding( $content, 'HTML-ENTITIES', 'UTF-8' ), LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD );
		
		$found = false;
		$nodes_to_replace = array();
		
		foreach ( $dom->getElementsByTagName('a') as $node ) {
			$href = $node->getAttribute('href');
			
			if ( $href === $old_link->url ) {
				// Store node and its text content for replacement
				$nodes_to_replace[] = array(
					'node' => $node,
					'text' => $node->nodeValue
				);
				$found = true;
			}
		}
		
		// Replace nodes with text nodes
		foreach ( $nodes_to_replace as $item ) {
			$textNode = $dom->createTextNode( $item['text'] );
			$item['node']->parentNode->replaceChild( $textNode, $item['node'] );
		}
		
		if ( $found ) {
			return $dom->saveHTML();
		}
		
		return $content;
	}
}
