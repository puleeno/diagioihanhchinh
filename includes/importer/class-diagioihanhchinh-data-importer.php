<?php
class Diagioihanhchinh_Data_Importer {
	const CACHED_META_KEY       = 'diagioihanhchinh_location_cached';
	const DEFAULT_PROCESS_ITEMS = 100;

	protected static $cached_locations = array();

	public function __construct() {
	}

	public function get_data_from_cache() {
		$cached_file = sprintf( '%s/outputs/all.json', dirname( DIAGIOIHANHCHINH_PLUGIN_FILE ) );
		if ( ! file_exists( $cached_file ) ) {
			return false;
		}
		$locations = json_decode( file_get_contents( $cached_file ), true );

		return $locations;
	}

	public function import() {
		$all_locations = $this->get_data_from_cache();

		if ( empty( $all_locations ) ) {
			return;
		}

		$this->import_from_cities( $all_locations );
	}

	protected function get_cached_location_id( $taxonomy, $level ) {
		$cached_option_key  = sprintf( '%s_level%d_cached_location_id', $taxonomy, 1 );
		$cached_location_id = get_option( $cached_option_key );

		if ( $cached_location_id > 0 && get_post_type( $cached_location_id ) !== 'dghc_cache_locations' ) {
			delete_option( $cached_option_key );
			$cached_location_id = false;
		}

		if ( ! $cached_location_id ) {
			$cached_location_id = wp_insert_post(
				array(
					'post_type'   => 'dghc_cache_locations',
					'post_title'  => $cached_option_key,
					'post_status' => 'public',
				)
			);

			if ( ! is_wp_error( $cached_location_id ) ) {
				update_option( $cached_option_key, $cached_location_id );
			} else {
				$cached_location_id = 0;
			}
		}

		return $cached_location_id;
	}

	protected function get_cached_location_data( $post_id ) {
		$cached = get_post_meta( $post_id, static::CACHED_META_KEY, true );
		return empty( $cached ) ? array() : $cached;
	}

	protected function add_new_term_to_cache( $post_id, $orgdata_id, $term_id, $cached_locations = null ) {
		if ( is_null( $cached_locations ) ) {
			$cached_locations = $this->get_cached_location_data( $post_id );
		}
		if ( ! is_array( $cached_locations ) ) {
			$cached_locations = array();
		}

		$cached_locations[ $orgdata_id ] = $term_id;
		update_post_meta( $post_id, static::CACHED_META_KEY, $cached_locations );
	}

	protected function insert_flag_orgid_taxomnomy_meta_to_reverse( $taxonomy, $orgid, $term_id ) {
		$reverse_key = sprintf( 'dghc_%s_%s', $taxonomy, $orgid );

		return update_term_meta(
			$term_id,
			$reverse_key,
			true
		);
	}

	protected function reverse_term_id_from_orgid_and_taxonomy( $taxonomy, $orgid ) {
		global $wpdb;

		$reverse_key = sprintf( 'dghc_%s_%s', $taxonomy, $orgid );

		$sql = $wpdb->prepare(
			"SELECT tm.term_id FROM {$wpdb->termmeta} tm
			WHERE tm.meta_key=%s",
			$reverse_key
		);

		return intval( $wpdb->get_var( $sql ) );
	}

	protected function insert_term( $name, $taxonomy, $parent_taxonomy_id = null ) {
		$term_id = term_exists( $name, $taxonomy, $parent_taxonomy_id );

		if ( empty( $term_id ) ) {
			$args = array();
			if ( $parent_taxonomy_id > 0 ) {
				$args['parent'] = $parent_taxonomy_id;
			}
			$term_taxonomy = wp_insert_term( $name, $taxonomy, $args );
			if ( ! is_wp_error( $term_taxonomy ) ) {
				return intval( $term_taxonomy['term_id'] );
			}
		} else {
			return intval( $term_id['term_id'] );
		}

		return false;
	}

	public function import_from_cities( $all_locations ) {
		$city_taxonomies = Diagioihanhchinh::get_registered_locations( 1 );
		if ( empty( $city_taxonomies ) ) {
			return;
		}

		foreach ( $all_locations as $orgcity_id => $city ) {
			foreach ( array_keys( $city_taxonomies ) as $taxonomy ) {
				$cached_location_id = $this->get_cached_location_id( $taxonomy, $city['level'] );
				if ( $cached_location_id <= 0 ) {
					error_log( spintf( 'Không thể tạo data cache cho location `%s`', $cached_option_key ) );
					continue;
				}
				$cached_locations = $this->get_cached_location_data( $cached_location_id );
				if ( isset( $cached_locations[ $orgcity_id ] ) && term_exists( $cached_locations[ $orgcity_id ] ) ) {
					$term_id = $cached_locations[ $orgcity_id ];
				} else {
					$term_id = $this->insert_term( $city['name'], $taxonomy );
				}

				if ( ! isset( $cached_locations[ $orgcity_id ] ) ) {
					$cached_locations[ $orgcity_id ] = $term_id;

					$this->add_new_term_to_cache( $cached_location_id, $orgcity_id, $term_id, $cached_locations );
					$this->insert_flag_orgid_taxomnomy_meta_to_reverse( $taxonomy, $orgcity_id, $term_id );
				}
			}
			$this->import_from_districts( $city['districts'], $orgcity_id );
		}
	}

	public function import_from_districts( $districts, $orgcity_id ) {
		$district_taxonomies = Diagioihanhchinh::get_registered_locations( 2 );
		if ( empty( $district_taxonomies ) ) {
			return;
		}
		foreach ( $districts as $orgdistrict_id => $district ) {
			foreach ( $district_taxonomies as $taxonomy => $args ) {
				$cached_location_id = $this->get_cached_location_id( $taxonomy, $district['level'] );
				if ( $cached_location_id <= 0 ) {
					error_log( spintf( 'Không thể tạo data cache cho location `%s`', $cached_option_key ) );
					continue;
				}

				$cached_locations    = $this->get_cached_location_data( $cached_location_id );
				$parent_city_term_id = $this->reverse_term_id_from_orgid_and_taxonomy( $args['parent'], $orgcity_id );

				if ( isset( $cached_locations[ $orgdistrict_id ] ) && term_exists( $cached_locations[ $orgdistrict_id ] ) ) {
					$term_id = $cached_locations[ $orgdistrict_id ];
				} else {
					$term_id = $this->insert_term( $district['name'], $taxonomy, $parent_city_term_id );
				}

				if ( ! isset( $cached_locations[ $orgdistrict_id ] ) ) {
					$cached_locations[ $orgdistrict_id ] = $term_id;

					$this->add_new_term_to_cache( $cached_location_id, $orgdistrict_id, $term_id, $cached_locations );
				}
			}
			$this->import_from_wards( $district['wards'], $orgdistrict_id, $orgdistrict_id );
		}
	}

	public function import_from_wards( $wards, $orgcity_id, $orgdistrict_id ) {
		$ward_taxonomies = Diagioihanhchinh::get_registered_locations( 3 );
		if ( empty( $ward_taxonomies ) ) {
			return;
		}
		foreach ( $wards as $orgward_id => $ward ) {
			foreach ( $ward_taxonomies as $taxonomy => $args ) {
				$cached_location_id = $this->get_cached_location_id( $taxonomy, 3 );
				if ( $cached_location_id <= 0 ) {
					error_log( spintf( 'Không thể tạo data cache cho location `%s`', $cached_option_key ) );
					continue;
				}

				$cached_locations        = $this->get_cached_location_data( $cached_location_id );
				$parent_district_term_id = $this->reverse_term_id_from_orgid_and_taxonomy( $args['parent'], $orgdistrict_id );
				if ( isset( $cached_locations[ $orgward_id ] ) && term_exists( $cached_locations[ $orgward_id ] ) ) {
					$term_id = $cached_locations[ $orgward_id ];
				} else {
					$term_id = $this->insert_term( $ward['name'], $taxonomy, $parent_district_term_id );
				}

				if ( ! isset( $cached_locations[ $orgward_id ] ) ) {
					$cached_locations[ $orgward_id ] = $term_id;

					$this->add_new_term_to_cache( $cached_location_id, $orgward_id, $term_id, $cached_locations );
				}
			}
		}
	}
}
