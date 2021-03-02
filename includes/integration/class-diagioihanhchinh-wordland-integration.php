<?php
class Diagioihanhchinh_WordLand_Integration {
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'change_location_labels' ) );
		add_action( 'after_setup_theme', array( $this, 'register_wordland_locations' ) );

		add_action( 'init', array( $this, 'create_data' ) );

		add_filter( 'diagioihanhchinh_pre_get_location_term', array( $this, 'override_default_get_term' ), 10, 4 );
		add_filter( 'wordland_clean_location_name', array(Diagioihanhchinh_Data::class, 'clean_location_name'), 10, 2);

		if (wp_is_request('cron')) {
			add_filter('wordland_spreadsheet_importer_mapping_city_name_field', function() {
				return 'area_level_1';
			});
			add_filter('wordland_spreadsheet_importer_mapping_district_name_field', function() {
				return 'area_level_2';
			});
			add_filter('wordland_spreadsheet_importer_mapping_ward_name_field', function() {
				return 'area_level_3';
			});
		}
	}

	protected function get_name_separator() {
		return apply_filters( 'diagioihanhchinh_name_separator', ', ' );
	}

	public static function get_term_from_clean_name( $name, $taxonomy, $args = array() ) {
		global $wp_version;

		$args['taxonomy'] = $taxonomy;

		$filter_db = function( $terms_clauses ) use ( $name ) {
			global $wpdb;
			$clean_name = Diagioihanhchinh_Data::clean_location_name( $name );
			$clean_name = remove_accents( $clean_name );

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

	public function change_location_labels() {
		add_filter(
			'wordland_taxonomy_administrative_area_level_1_args',
			function( $level_args ) {
				$labels = array(
					'name'        => 'Tỉnh/Thành phố',
					'plural_name' => 'Tỉnh/Thành phố',
				);

				$level_args['labels']  = $labels;
				$level_args['rewrite'] = array(
					'slug' => 'tinh-thanh',
				);
				return $level_args;
			}
		);

		add_filter(
			'wordland_taxonomy_administrative_area_level_2_args',
			function( $level_args ) {
				$labels = array(
					'name'        => 'Quận/huyện',
					'plural_name' => 'Quận/huyện',
				);

				$level_args['labels']  = $labels;
				$level_args['rewrite'] = array(
					'slug' => 'quan-huyen',
				);
				return $level_args;
			}
		);

		add_filter(
			'wordland_taxonomy_administrative_area_level_3_args',
			function( $level_args ) {
				$labels = array(
					'name'        => 'Phường/xã',
					'plural_name' => 'Phường/xã',
				);

				$level_args['labels']  = $labels;
				$level_args['rewrite'] = array(
					'slug' => 'phuong-xa',
				);
				return $level_args;
			}
		);
	}

	public function register_wordland_locations() {
		Diagioihanhchinh::register_location_taxonomy( 'administrative_area_level_1', 1 );
		Diagioihanhchinh::register_location_taxonomy(
			'administrative_area_level_2',
			2,
			'administrative_area_level_1'
		);
		Diagioihanhchinh::register_location_taxonomy(
			'administrative_area_level_3',
			3,
			'administrative_area_level_2'
		);

		add_filter(
			'diagioihanhchinh_administrative_area_level_1_support_geo_data',
			array( $this, 'add_city_locations_support_geodata' )
		);
	}

	public function add_city_locations_support_geodata( $taxonomies ) {
		if ( ! in_array( 'administrative_area_level_1', $taxonomies ) ) {
			array_push( $taxonomies, 'administrative_area_level_1' );
		}
		return $taxonomies;
	}

	public function create_data() {
		add_action( 'diagioihanhchinh_insert_term', array( $this, 'insert_data_to_wordland_location' ), 10, 4 );

		add_action(
			'diagioihanhchinh_insert_administrative_area_level_1_term_geodata',
			array( $this, 'update_term_geodata' ),
			10,
			4
		);
		add_action(
			'diagioihanhchinh_insert_administrative_area_level_2_term_geodata',
			array( $this, 'update_term_geodata' ),
			10,
			3
		);
		add_action(
			'diagioihanhchinh_insert_administrative_area_level_3_term_geodata',
			array( $this, 'update_term_geodata' ),
			10,
			3
		);
	}

	protected function check_geodata_exits( $term_id ) {
		global $wpdb;
		$sql = $wpdb->prepare( "SELECT term_id FROM {$wpdb->prefix}wordland_locations WHERE term_id=%d", $term_id );
		return intval( $wpdb->get_var( $sql ) ) > 0;
	}

	protected function create_geodata_sql( $multipolygon ) {
		global $wpdb;
		if ( is_a( $multipolygon, MultiPolygon::class ) ) {
			$insert_string = $wpdb->_real_escape( $multipolygon->out( 'ewkt' ) );
			return "ST_GeomFromText('$insert_string')";
		} elseif ( is_a( $multipolygon, Polygon::class ) ) {
			$insert_string = $wpdb->_real_escape( $multipolygon->out( 'ewkt' ) );

			return "ST_GeomFromText('$insert_string')";
		}
		return 'ST_GeomFromText(POINT(0 0)';
	}

	protected function get_parent_names( $parent_term_id, $clean_name = false, &$names = array() ) {
		$term = get_term( $parent_term_id );
		if ( $term && ! is_wp_error( $term ) ) {
			$term_name = $clean_name ? Diagioihanhchinh_Data::clean_location_name( $term->name ) : $term->name;
			array_push( $names, $term_name );
			if ( $term->parent > 0 ) {
				return $this->get_parent_names( $term->parent, $clean_name, $names );
			}
		}
		return $names;
	}

	public function update_term_geodata( $multipolygon, $term, $kml_content, $cached_kml_file = null ) {
		global $wpdb;
		$geodata_sql = $this->create_geodata_sql( $multipolygon );
		if ( $term->parent > 0 ) {
			$location_names = $this->get_parent_names( $term->parent );
			array_unshift( $location_names, $term->name );
			$location_names = implode( $this->get_name_separator(), $location_names );
		} else {
			$location_names = $term->name;
		}
		$acii_name = remove_accents( $location_names );

		if ( empty( $geodata_sql ) ) {
			if ( is_null( $cached_kml_file ) ) {
				$cached_kml_file = 'cache://district-' . Diagioihanhchinh_Data::create_location_key_from_name( $term->name );
			}
			error_log( sprintf( 'Geo data "%s" cho %s(%s) không hợp lệ', $cached_kml_file, $term->name, $term->taxonomy ) );
			return;
		}
		if ( $this->check_geodata_exits( $term->term_id ) ) {
			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}wordland_locations SET `location`={$geodata_sql} WHERE term_id=%d",
				$term->term_id
			);
		} else {
			$geo_mapping_fields = Diagioihanhchinh_Data::get_geo_mapping_fields();
			$geo_eng_name       = Diagioihanhchinh_Data::clean_location_name( $term->name );
			$geo_eng_name       = remove_accents( $geo_eng_name );
			if ( isset( $geo_mapping_fields[ $geo_eng_name ] ) ) {
				$geo_eng_name = $geo_mapping_fields[ $geo_eng_name ];
			}
			$sql = $wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wordland_locations(`term_id`, `created_at`, `location`, `location_name`, `ascii_name`, `geo_eng_name`) VALUES(%d, CURRENT_TIMESTAMP, {$geodata_sql}, %s, %s, %s, %s)",
				$term->term_id,
				$location_names,
				strtolower( $acii_name ),
				remove_accents( $geo_eng_name ),
			);
		}

		$result = $wpdb->query( $sql );
		return $result;

	}

	public function insert_data_to_wordland_location( $term_id, $name, $taxonomy, $parent_term_id ) {
		$location_name = implode( $this->get_name_separator(), $this->get_parent_names( $term_id ) );
		$clean_name    = implode( $this->get_name_separator(), $this->get_parent_names( $term_id, true ) );

		$geo_mapping_fields = Diagioihanhchinh_Data::get_geo_mapping_fields();

		$geo_eng_name = remove_accents( $clean_name );
		if ( isset( $geo_mapping_fields[ $geo_eng_name ] ) ) {
			$geo_eng_name = $geo_mapping_fields[ $geo_eng_name ];
		}

		$data = array(
			'location_name' => $location_name,
			'ascii_name'    => remove_accents( $location_name ),
			'clean_name'    => remove_accents( $clean_name ),
		);
		if ( $taxonomy === 'administrative_area_level_1' ) {
			$data['geo_eng_name'] = $geo_eng_name;
		}

		return Diagioihanhchinh_Query::insert_location( $term_id, $data );
	}

	public function override_default_get_term( $pre, $name, $taxonomy, $args ) {
		if ( in_array( $taxonomy, array( 'administrative_area_level_1', 'administrative_area_level_2', 'administrative_area_level_3' ) ) ) {
			$terms = static::get_term_from_clean_name( $name, $taxonomy, $args );
			return $terms;
		}
		return $pre;
	}
}
