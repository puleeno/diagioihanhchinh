<?php
class Diagioihanhchinh_Wordland_Integration {
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'change_location_labels' ) );
		add_action( 'after_setup_theme', array( $this, 'register_wordland_locations' ) );

		add_action( 'init', array( $this, 'create_data' ) );
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

	protected function check_geodata_exits() {

	}

	protected function create_geodata_sql( $multipolygon ) {
		global $wpdb;
		if ( is_a( $multipolygon, MultiPolygon::class ) ) {
			$insert_string = $wpdb->_real_escape( $multipolygon->out( 'ewkb' ) );
			return "GeomFromWKB('$insert_string')";
		}
	}

	public function update_term_geodata( $multipolygon, $term, $kml_content, $cached_kml_file = null ) {
		global $wpdb;
		$geodata_sql = $this->create_geodata_sql( $multipolygon );
		if ( empty( $geodata_sql ) ) {
			if ( is_null( $cached_kml_file ) ) {
				$cached_kml_file = 'cache://district-' . Diagioihanhchinh_Data::create_location_key_from_name( $term->name );
			}
			error_log( sprintf( 'Geo data "%s" cho %s(%s) không hợp lệ', $cached_kml_file, $term->name, $term->taxonomy ) );
			return;
		}
		if ( $this->check_geodata_exits() ) {
			$sql = $wpdb->prepare(
				"UPDATE {$wpdb->prefix}wordland_locations SET `term_id`=%d, `location`={$geodata_sql}",
				$term->term_id
			);
		} else {
			$sql = $wpdb->prepare(
				"INSERT INTO {$wpdb->prefix}wordland_locations(`term_id`, `created_at`, `location` ) VALUES(%d, CURRENT_TIMESTAMP, {$geodata_sql})",
				$term->term_id
			);
		}

		return $wpdb->query( $sql );
	}
}
