<?php
class Diagioihanhchinh_Geo_Data_Importer {
	protected $data_dir;
	protected $kml_cache_dir;

	protected static $cached_cities = array();

	public function __construct() {
		$plugin_dir          = dirname( DIAGIOIHANHCHINH_PLUGIN_FILE );
		$this->data_dir      = sprintf( '%s/data/', $plugin_dir );
		$this->kml_cache_dir = sprintf( '%s/output/kml', $plugin_dir );
	}

	protected function fix_location_name( $name ) {
		return str_replace(
			array(
				'Cần Thơn',
				'Bà Rịa -Vũng Tàu',
				'Quản Bình',
				'Hooc Môn',
				'Quậng'
			),
			array(
				'Cần Thơ',
				'Bà Rịa - Vũng Tàu',
				'Quảng Bình',
				'Hóc Môn',
				'Quận'
			),
			$name
		);
	}

	public function import() {
		$support_geodata_taxonomies = apply_filters(
			'diagioihanhchinh_administrative_area_level_1_support_geo_data',
			array()
		);
		if ( empty( $support_geodata_taxonomies ) ) {
			return;
		}
		$this->import_city_geodata( $support_geodata_taxonomies );
		$this->import_district_geodata( $support_geodata_taxonomies );
	}

	public function import_city_geodata( $support_geodata_taxonomies ) {
		$city_dat_file = sprintf( '%s/diaphantinhvn.kml', $this->data_dir );
		if ( ! file_exists( $city_dat_file ) ) {
			error_log(
				sprintf(
					'Lỗi tập tin data "%s" không tồn tại',
					$city_dat_file
				)
			);
		}
		$kml = simplexml_load_file( $city_dat_file );
		if ( ! isset( $kml->Document->Folder ) ) {
			return;
		}
		$cities = $kml->Document->Folder;
		foreach ( $cities->Placemark as $city ) {
			if ( ! isset( $city->ExtendedData->SchemaData->SimpleData[2] ) ) {
				continue;
			}
			$city_name = $city->ExtendedData->SchemaData->SimpleData[2];

			if ( (string) $city_name['name'] === 'ten_tinh' ) {
				$city_name = $this->fix_location_name( (string) $city_name );
				$geom      = geoPHP::load( $city->asXML(), 'kml' );

				foreach ( $support_geodata_taxonomies as $taxonomy ) {
					$term = Diagioihanhchinh_Data::get_location_term( $city_name, $taxonomy );
					if ( empty( $term ) ) {
						error_log( sprintf( 'Lỗi: không tìm thấy tỉnh/thành "%s" trong CSDL', $city_name ) );
						continue;
					}
					do_action(
						"diagioihanhchinh_insert_{$taxonomy}_term_geodata",
						$geom,
						$term,
						$city->asXML()
					);
				}
			}
		}
	}

	/**
	 * Get term ID is cached
	 */
	public static function get_cached_city( $city_name, $taxonomy ) {
		if ( ! isset( static::$cached_cities[ $taxonomy ][ $city_name ] ) ) {
			return false;
		}
		return static::$cached_cities[ $taxonomy ][ $city_name ];
	}

	protected function get_city_term( $city_name, $city_taxonomy ) {
		$cached_city = static::get_cached_city( $city_name, $city_taxonomy );
		if ( $cached_city !== false ) {
			return $cached_city;
		}
		$term = Diagioihanhchinh_Data::get_location_term( $city_name, $city_taxonomy );
		if ( $term ) {
			if ( ! isset( static::$cached_cities[ $city_taxonomy ] ) ) {
				static::$cached_cities[ $city_taxonomy ] = array();
			}
			static::$cached_cities[ $city_taxonomy ][ $city_name ] = $term->term_id;

			return $term->term_id;
		}

		return false;
	}

	protected function import_district_geodata( $city_taxonomies ) {
		$district_dat_file = sprintf( '%s/diaphanhuyen.kml', $this->data_dir );
		if ( ! file_exists( $district_dat_file ) ) {
			error_log(
				sprintf(
					'Lỗi tập tin data "%s" không tồn tại',
					$district_dat_file
				)
			);
		}
		$kml = simplexml_load_file( $district_dat_file );
		if ( ! isset( $kml->Document->Folder ) ) {
			return;
		}
		$districts = $kml->Document->Folder;
		foreach ( $districts->Placemark as $district ) {
			if ( ! isset( $district->ExtendedData->SchemaData->SimpleData[2] ) ) {
				continue;
			}
			$city_name     = $district->ExtendedData->SchemaData->SimpleData[2];
			$district_name = $district->ExtendedData->SchemaData->SimpleData[3];

			if ( (string) $city_name['name'] === 'Ten_Tinh' && (string) $district_name['name'] === 'Ten_Huyen' ) {
				$district_name = $this->fix_location_name( (string) $district_name );
				$geom          = geoPHP::load( $district->asXML(), 'kml' );

				foreach ( $city_taxonomies as $city_taxonomy ) {
					$city_taxonomy_info = Diagioihanhchinh::get_registered_locations( $city_taxonomy );
					$city_term_id       = $this->get_city_term( (string) $city_name, $city_taxonomy );

					if ( ! $city_term_id || empty( $city_taxonomy_info['childs'] ) ) {
						error_log( 'Không thể tìm thấy ID thành phố hoặc thông tin của thành phố' );
						continue;
					}

					$district_taxonomies = $city_taxonomy_info['childs'];

					foreach ( $district_taxonomies as $taxonomy ) {
						$term = Diagioihanhchinh_Data::get_location_term(
							$district_name,
							$taxonomy,
							array(
								'parent' => $city_term_id,
							)
						);

						if ( ! $term ) {
							error_log( sprintf( 'Không tìm thấy huyện "%s/%s" trong CSDL', $district_name, (string)$city_name ) );
							continue;
						}

						do_action(
							"diagioihanhchinh_insert_{$taxonomy}_term_geodata",
							$geom,
							$term,
							$district
						);
					}
				}
			}
		}
	}

	public function read_ward_geodata( $ward_geodatas, $district_taxonomy, $district_term_id ) {
		if ( empty( $ward_geodatas ) ) {
			return;
		}
		$district_taxonomy_info = Diagioihanhchinh::get_registered_locations( $district_taxonomy );
		if ( ! isset( $district_taxonomy_info['childs'] ) ) {
			error_log( sprintf( 'Not found child location of "%s" taxonomy', $district_taxonomy ) );
			return;
		}

		foreach ( $ward_geodatas as $ward_name => $kml_content ) {
			$ward_name = Diagioihanhchinh_Data::clean_location_name( $ward_name );
			$polygon   = geoPHP::load( $kml_content, 'kml' );

			foreach ( $district_taxonomy_info['childs'] as $ward_taxonomy ) {
				$ward_tt = term_exists( $ward_name, $ward_taxonomy, $district_term_id );
				if ( ! $ward_tt ) {
					error_log( sprintf( 'Không tìm thấy phường/xã "%s" trong CSDL', $ward_name ) );
					continue;
				}

				do_action(
					"diagioihanhchinh_insert_{$ward_taxonomy}_term_geodata",
					$polygon,
					get_term( $ward_tt['term_id'] ),
					$ward_geodata
				);
			}
		}
	}
}
