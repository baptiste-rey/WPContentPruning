<?php
// Empêcher l'accès direct
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'WP_List_Table' ) ) {
	require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}

class Outbound_Links_Manager_Traffic_List_Table extends WP_List_Table {

	/**
	 * Constructor
	 */
	public function __construct() {
		parent::__construct( array(
			'singular' => 'page',
			'plural'   => 'pages',
			'ajax'     => false
		) );
	}

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
			'url'            => 'URL',
			'publish_date'   => 'Date de publication',
			'internal_links' => 'Liens internes',
			'outbound_links' => 'Liens sortants',
			'impressions'    => 'Total Impressions',
			'clicks'         => 'Total Clicks',
			'users'          => 'Total Users',
			'sessions'       => 'Total Sessions',
			'last_updated'   => 'Dernière mise à jour',
			'actions'        => 'Actions'
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
			'url'            => array( 'url', false ),
			'publish_date'   => array( 'publish_date', false ),
			'internal_links' => array( 'internal_links', false ),
			'outbound_links' => array( 'outbound_links', false ),
			'impressions'    => array( 'impressions', false ),
			'clicks'         => array( 'clicks', false ),
			'users'          => array( 'users', false ),
			'sessions'       => array( 'sessions', false ),
			'last_updated'   => array( 'last_updated', false )
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

		$filter_impressions  = isset( $_GET['filter_impressions'] ) ? sanitize_text_field( $_GET['filter_impressions'] ) : '';
		$filter_internal     = isset( $_GET['filter_internal'] ) ? sanitize_text_field( $_GET['filter_internal'] ) : '';
		$filter_outbound     = isset( $_GET['filter_outbound'] ) ? sanitize_text_field( $_GET['filter_outbound'] ) : '';
		?>
		<div class="alignleft actions">
			<select name="filter_impressions">
				<option value="">Impressions : Toutes</option>
				<option value="with" <?php selected( $filter_impressions, 'with' ); ?>>Avec impressions</option>
				<option value="without" <?php selected( $filter_impressions, 'without' ); ?>>Sans impressions</option>
			</select>

			<select name="filter_internal">
				<option value="">Liens internes : Tous</option>
				<option value="with" <?php selected( $filter_internal, 'with' ); ?>>Avec liens internes</option>
				<option value="without" <?php selected( $filter_internal, 'without' ); ?>>Sans liens internes</option>
			</select>

			<select name="filter_outbound">
				<option value="">Liens sortants : Tous</option>
				<option value="with" <?php selected( $filter_outbound, 'with' ); ?>>Avec liens sortants</option>
				<option value="without" <?php selected( $filter_outbound, 'without' ); ?>>Sans liens sortants</option>
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
		$table_name = $wpdb->prefix . 'page_traffic';
		$posts_table = $wpdb->prefix . 'posts';
		$internal_stats_table = $wpdb->prefix . 'internal_links_stats';
		$outbound_table = $wpdb->prefix . 'outbound_links';

		// Whitelist des colonnes autorisées pour le tri (protection SQL Injection)
		$allowed_orderby = array( 'url', 'impressions', 'clicks', 'users', 'sessions', 'last_updated', 'publish_date', 'internal_links', 'outbound_links' );
		$orderby_input = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) )
			? $_GET['orderby']
			: 'impressions';

		// Mapper les colonnes virtuelles vers les vraies colonnes SQL
		$orderby_map = array(
			'publish_date'   => 'p.post_date',
			'internal_links' => 'internal_links_count',
			'outbound_links' => 'outbound_links_count',
		);
		$orderby = isset( $orderby_map[ $orderby_input ] ) ? $orderby_map[ $orderby_input ] : "t.$orderby_input";

		$allowed_order = array( 'ASC', 'DESC' );
		$order = ( ! empty( $_GET['order'] ) && in_array( strtoupper( $_GET['order'] ), $allowed_order ) )
			? strtoupper( $_GET['order'] )
			: 'DESC';

		$where_clauses = array();

		if ( ! empty( $_REQUEST['s'] ) ) {
			$search = '%' . $wpdb->esc_like( sanitize_text_field( $_REQUEST['s'] ) ) . '%';
			$where_clauses[] = $wpdb->prepare( "t.url LIKE %s", $search );
		}

		// Filtre impressions
		$filter_impressions = isset( $_GET['filter_impressions'] ) ? sanitize_text_field( $_GET['filter_impressions'] ) : '';
		if ( $filter_impressions === 'with' ) {
			$where_clauses[] = "t.impressions > 0";
		} elseif ( $filter_impressions === 'without' ) {
			$where_clauses[] = "t.impressions = 0";
		}

		$where_sql = '';
		if ( ! empty( $where_clauses ) ) {
			$where_sql = ' WHERE ' . implode( ' AND ', $where_clauses );
		}

		$sql = "SELECT t.*, p.post_date AS publish_date,
					COALESCE(ils.internal_links_count, 0) AS internal_links_count,
					COALESCE(ol_count.outbound_count, 0) AS outbound_links_count
				FROM $table_name AS t
				LEFT JOIN $posts_table AS p ON t.post_id = p.ID
				LEFT JOIN $internal_stats_table AS ils ON t.post_id = ils.post_id
				LEFT JOIN (
					SELECT post_id, COUNT(*) AS outbound_count
					FROM $outbound_table
					GROUP BY post_id
				) AS ol_count ON t.post_id = ol_count.post_id
				{$where_sql}
				ORDER BY $orderby $order";

		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Filtres PHP pour les colonnes calculées via JOIN
		$filter_internal = isset( $_GET['filter_internal'] ) ? sanitize_text_field( $_GET['filter_internal'] ) : '';
		if ( $filter_internal === 'with' ) {
			$results = array_filter( $results, function( $row ) {
				return intval( $row['internal_links_count'] ) > 0;
			} );
		} elseif ( $filter_internal === 'without' ) {
			$results = array_filter( $results, function( $row ) {
				return intval( $row['internal_links_count'] ) === 0;
			} );
		}

		$filter_outbound = isset( $_GET['filter_outbound'] ) ? sanitize_text_field( $_GET['filter_outbound'] ) : '';
		if ( $filter_outbound === 'with' ) {
			$results = array_filter( $results, function( $row ) {
				return intval( $row['outbound_links_count'] ) > 0;
			} );
		} elseif ( $filter_outbound === 'without' ) {
			$results = array_filter( $results, function( $row ) {
				return intval( $row['outbound_links_count'] ) === 0;
			} );
		}

		return array_values( $results );
	}

	/**
	 * Sort data
	 */
	private function sort_data( $a, $b ) {
		// Set defaults avec whitelist
		$allowed_orderby = array( 'url', 'impressions', 'clicks', 'users', 'sessions', 'last_updated', 'publish_date', 'internal_links' );
		$orderby = ( ! empty( $_GET['orderby'] ) && in_array( $_GET['orderby'], $allowed_orderby ) )
			? $_GET['orderby']
			: 'impressions';
		$order = ( ! empty( $_GET['order'] ) && in_array( strtolower( $_GET['order'] ), array( 'asc', 'desc' ) ) )
			? strtolower( $_GET['order'] )
			: 'desc';

		// Mapper les noms de colonnes vers les clés du tableau de données
		$key_map = array(
			'publish_date'   => 'publish_date',
			'internal_links' => 'internal_links_count',
			'outbound_links' => 'outbound_links_count',
		);
		$data_key = isset( $key_map[ $orderby ] ) ? $key_map[ $orderby ] : $orderby;

		// Vérifier que la clé existe
		$val_a = isset( $a[$data_key] ) ? $a[$data_key] : '';
		$val_b = isset( $b[$data_key] ) ? $b[$data_key] : '';

		// Pour les valeurs numériques
		if ( in_array( $orderby, array( 'impressions', 'clicks', 'users', 'sessions', 'internal_links', 'outbound_links' ) ) ) {
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
			case 'url':
				// Si l'URL est relative, la rendre absolue
				$url = $item['url'];
				if ( strpos( $url, 'http' ) !== 0 ) {
					$url = home_url( $url );
				}
				return '<a href="' . esc_url( $url ) . '" target="_blank">' . esc_html( $item['url'] ) . '</a>';
			case 'publish_date':
				if ( ! empty( $item['publish_date'] ) && $item['publish_date'] != '0000-00-00 00:00:00' ) {
					return esc_html( date_i18n( get_option( 'date_format' ), strtotime( $item['publish_date'] ) ) );
				}
				return '<span style="color: #999;">—</span>';
			case 'internal_links':
				$count = intval( $item['internal_links_count'] );
				if ( $count > 0 ) {
					return '<span style="font-weight: 600; color: #2271b1;">' . number_format_i18n( $count ) . '</span>';
				}
				return '<span style="color: #d63638; font-weight: 600;">0</span>';
			case 'outbound_links':
				$count = intval( $item['outbound_links_count'] );
				if ( $count > 0 ) {
					return '<span style="font-weight: 600; color: #b32d2e;">' . number_format_i18n( $count ) . '</span>';
				}
				return '<span style="color: #999;">0</span>';
			case 'impressions':
				return number_format_i18n( intval( $item['impressions'] ) );
			case 'clicks':
				return number_format_i18n( intval( $item['clicks'] ) );
			case 'users':
				return number_format_i18n( intval( $item['users'] ) );
			case 'sessions':
				return number_format_i18n( intval( $item['sessions'] ) );
			case 'last_updated':
				if ( $item['last_updated'] && $item['last_updated'] != '0000-00-00 00:00:00' ) {
					return esc_html( date_i18n( get_option( 'date_format' ) . ' ' . get_option( 'time_format' ), strtotime( $item['last_updated'] ) ) );
				}
				return '<span style="color: #999;">Jamais</span>';
			case 'actions':
				return '<button class="button button-small olm-delete-traffic" data-id="' . esc_attr( $item['id'] ) . '" style="color: #b32d2e; border-color: #b32d2e;">'
					. '<span class="dashicons dashicons-trash" style="font-size: 14px; line-height: 1.8;"></span> Supprimer'
					. '</button>';
			default:
				return print_r( $item, true );
		}
	}
}
