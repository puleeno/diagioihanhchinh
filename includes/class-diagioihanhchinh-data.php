<?php
class Diagioihanhchinh_Data {
	protected static $geo_mapping = array();

	public function __construct() {
		$this->init_hooks();

		$mapping_config_file = sprintf( '%s/data/geo/mapping.json', dirname( DIAGIOIHANHCHINH_PLUGIN_FILE ) );
		if ( file_exists( $mapping_config_file ) ) {
			static::$geo_mapping = json_decode( file_get_contents( $mapping_config_file ), true );
		}
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_cache_locations_post_type' ) );
	}

	public static function get_geo_mapping_fields() {
		return static::$geo_mapping;
	}

	public function register_cache_locations_post_type() {
		register_post_type(
			'dghc_cache_locations',
			array(
				'name'     => 'Diagioihanhchinh cache',
				'public'   => false,
				'_builtin' => true,
			)
		);
	}

	public static function create_location_key_from_name( $name ) {
		$name = remove_accents( $name );
		return preg_replace( array( '/[\s]/', '/_{2,}/' ), '_', $name );
	}


	/**
	 * Format lại tên của địa danh dựa theo format từ data của cục thống kê
	 *
	 * @link https://www.gso.gov.vn/dmhc2015/Default.aspx
	 */
	public static function clean_location_name( $name, $clean_unicode = false ) {
		/**
		 * Fix multi whitespace
		 */
		$name = preg_replace( '/(\s){2,}/', '$1', $name );

		/**
		 * Make name has - character must be [whitespace]-[whitespace]
		 *
		 * Eg. Bà Rịa -Vũng Tàu => Bà Rịa - Vũng Tàu
		 */
		$name = preg_replace(
			array(
				'/[^\s]-\s/',
				'/\s-[^\s]/',
				'/[^\s]-[^\s]/',
			),
			' - ',
			$name
		);

		/**
		 * Clean prefix name
		 * Eg. TP., thị xã, thị trấn, etc
		 */
		$name = str_replace(
			array(
				'Thị Trấn',
				'Thị trấn',
				'thị trấn',
				'Tỉnh',
				'tỉnh',
				'Huyện',
				'huyện',
				'Xã',
				'xã',
				'TP.',
				'TP',
				'Thành phố',
				'Thành Phố',
				'thị xã',
				'Thị xã',
				'Thị Xã',
			),
			'',
			$name
		);

		/**
		 * Fix district names and ward names
		 */
		$name = preg_replace(
			array(
				'/qu\ận {1,}([^d].+)$/',
				'/Qu\ận {1,}([^d].+)$/',
				'/ph\ư\ờng {1,}([^d].+)$/',
				'/Ph\ư\ờng {1,}([^d].+)$/',
			),
			'$1',
			trim( $name )
		);

		/**
		 * Fix name do not have whitespace
		 *
		 * LộcHà
		 * ChơnThành
		 */
		$name = preg_replace( '/(\w)([A-Z])/', '$1 $2', $name );

		/**
		 * Fix name has ' character
		 *
		 * Ea H' Leo
		 */
		$name = preg_replace( '/(\w\') /', '$1', $name );

		/**
		 * Fix special cases
		 */
		$name = str_replace(
			array(
				'Bà Rịa Vũng Tàu',
			),
			array(
				'Bà Rịa - Vũng Tàu',
			),
			$name
		);

		if ( $clean_unicode ) {
			return remove_accents( trim( $name ) );
		}
		return trim( $name );
	}

	public static function get_term_from_clean_name( $name, $taxonomy, $args = array( 'hide_empty' => false ) ) {
		global $wp_version;

		$args['taxonomy'] = $taxonomy;

		$filter_db = function( $terms_clauses ) use ( $name ) {
			global $wpdb;
			$clean_name = self::clean_location_name( $name );
			$clean_name = remove_accents( $name );

			$terms_clauses['join']  .= " INNER JOIN {$wpdb->prefix}wordland_locations l ON t.term_id = l.term_id";
			$terms_clauses['where'] .= " AND l.clean_name LIKE '%" . $wpdb->_real_escape( $clean_name ) . "%'";

			return $terms_clauses;
		};

		add_filter( 'terms_clauses', $filter_db );
		$terms = version_compare( $wp_version, '4.5.0' ) ? get_terms( $args ) : get_terms( $taxonomy, $args );
		remove_filter( 'terms_clauses', $filter_db );

		if ( empty( $terms ) ) {
			return false;
		}

		$term = array_shift( $terms );

		return get_term( $term, $taxonomy );
	}
}

new Diagioihanhchinh_Data();
