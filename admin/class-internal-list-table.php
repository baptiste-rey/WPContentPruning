<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Outbound_Links_Manager_Internal_List_Table extends WP_List_Table {

	/**
	 * Prepare the items for the table to process
	 *
	 * @return Void
	 */
	public function prepare_items() {
		$columns = $this->get_columns();
		$hidden = $this->get_hidden_columns();
		$sortable = $this->get_sortable_columns();

		$data = $this->table_data();
		usort( $data, array( &$this, 'sort_data' ) );

		// Pagination
		$per_page = 20;
		$current_page = $this->get_pagenum();
		$total_items = count( $data );

		$this->set_pagination_args( array(
			'total_items' => $total_items,
			'per_page'    => $per_page
		) );

		$this->items = array_slice( $data, ( ( $current_page - 1 ) * $per_page ), $per_page );
		
		$this->_column_headers = array( $columns, $hidden, $sortable );
	}

	/**
	 * Retrieve column headers
	 *
	 * @return Array
	 */
	public function get_columns() {
		$columns = array(
			'post_title'            => 'Titre de la page',
			'post_url'              => 'URL de la page',
			'post_date'             => 'Date de publication',
			'type'                  => 'Type',
			'internal_links_count'  => 'Liens internes'
		);
		return $columns;
	}

	/**
	 * Hidden columns
	 *
	 * @return Array
	 */
	public function get_hidden_columns() {
		return array();
	}

	/**
	 * Sortable columns
	 *
	 * @return Array
	 */
	public function get_sortable_columns() {
		return array(
			'post_title'           => array( 'post_title', false ),
			'post_date'            => array( 'post_date', false ),
			'internal_links_count' => array( 'internal_links_count', false )
		);
	}

	/**
	 * Display filter dropdowns above the table
	 *
	 * @param string $which Top or bottom
	 */
	protected function extra_tablenav( $which ) {
		if ( $which !== 'top' ) {
			return;
		}

		$filter_links = isset( $_GET['filter_links'] ) ? sanitize_text_field( $_GET['filter_links'] ) : '';
		?>
		<div class="alignleft actions">
			<select name="filter_links">
				<option value="">Liens internes : Tous</option>
				<option value="with" <?php selected( $filter_links, 'with' ); ?>>Avec liens internes (> 0)</option>
				<option value="without" <?php selected( $filter_links, 'without' ); ?>>Sans liens internes (= 0)</option>
			</select>
			<?php submit_button( 'Filtrer', '', 'filter_action', false ); ?>
		</div>
		<?php
	}

	/**
	 * Get the table data
	 *
	 * @return Array
	 */
	private function table_data() {
		global $wpdb;
		$table_name = $wpdb->prefix . 'internal_links_stats';
		$posts_table = $wpdb->prefix . 'posts';

		// Whitelist des colonnes autorisées pour le tri (protection SQL Injection)
		$allowed_orderby = array( 'post_title', 'post_date', 'internal_links_count' );
		$orderby = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) )
			? sanitize_sql_orderby( $_GET['orderby'] )
			: 'internal_links_count';

		$allowed_order = array( 'ASC', 'DESC' );
		$order = ( ! empty( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), $allowed_order ) )
			? strtoupper( $_GET['order'] )
			: 'DESC';

		$where_clauses = array();

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST['s'] ) ) . '%';
			$where_clauses[] = $wpdb->prepare( "(p.post_title LIKE %s OR p.post_name LIKE %s)", $search, $search );
		}

		// Filtre liens internes
		$filter_links = isset( $_GET['filter_links'] ) ? sanitize_text_field( $_GET['filter_links'] ) : '';
		if ( $filter_links === 'with' ) {
			$where_clauses[] = "s.internal_links_count > 0";
		} elseif ( $filter_links === 'without' ) {
			$where_clauses[] = "s.internal_links_count = 0";
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql = "SELECT s.*, p.post_title, p.post_date, p.post_type as p_type
				FROM $table_name s
				LEFT JOIN $posts_table p ON s.post_id = p.ID
				{$where_sql}
				ORDER BY $orderby $order";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		return $results;
	}

	/**
	 * Sort data
	 */
	private function sort_data( $a, $b ) {
		// Whitelist des colonnes autorisées
		$allowed_orderby = array( 'post_title', 'post_date', 'internal_links_count' );
		$orderby = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) )
			? $_GET['orderby']
			: 'internal_links_count';
		$order = ( ! empty( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), array( 'asc', 'desc' ) ) )
			? strtolower( $_GET['order'] )
			: 'desc';

		// Vérifier que la clé existe
		$val_a = isset( $a[$orderby] ) ? $a[$orderby] : '';
		$val_b = isset( $b[$orderby] ) ? $b[$orderby] : '';

		// Handle numeric vs string comparison
		if ( $orderby === 'internal_links_count' ) {
			$result = intval( $val_a ) - intval( $val_b );
		} else {
			$result = strnatcmp( $val_a, $val_b );
		}

		return ( $order === 'asc' ) ? $result : -$result;
	}

	/**
	 * Render columns
	 */
	public function column_default( $item, $column_name ) {
		switch ( $column_name ) {
			case 'post_title':
				return '<a href="' . get_edit_post_link( $item['post_id'] ) . '">' . esc_html( $item['post_title'] ) . '</a>';
			case 'post_url':
				$post_url = get_permalink( $item['post_id'] );
				return '<a href="' . esc_url( $post_url ) . '" target="_blank">' . esc_html( $post_url ) . '</a>';
			case 'post_date':
				return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['post_date'] ) ) );
			case 'type':
				return esc_html( $item['p_type'] );
			case 'internal_links_count':
				$count = intval( $item['internal_links_count'] );
				if ( $count > 0 ) {
					return '<button type="button" class="button button-small olm-view-links" data-post-id="' . esc_attr( $item['post_id'] ) . '" data-post-title="' . esc_attr( $item['post_title'] ) . '">' .
					       '<span class="dashicons dashicons-visibility" style="vertical-align:middle;margin-right:3px;"></span>' .
					       $count . ' lien(s)' .
					       '</button>';
				}
				return '<span style="color:#999;">0</span>';
			default:
				return print_r( $item, true );
		}
	}
}
