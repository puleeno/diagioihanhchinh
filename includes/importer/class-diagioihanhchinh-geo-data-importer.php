<?php
class Diagioihanhchinh_Geo_Data_Importer {
	protected $data_dir;
	protected $kml_cache_dir;

	public function __construct() {
		$plugin_dir          = dirname( DIAGIOIHANHCHINH_PLUGIN_FILE );
		$this->data_dir      = sprintf( '%s/data/', $plugin_dir );
		$this->kml_cache_dir = sprintf( '%s/output/kml', $plugin_dir );
	}

	protected function fix_location_name( $name ) {
		return str_replace(
			array(
				'Cần Thơn',
			),
			array(
				'Cần Thơ',
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
					$term = Diagioihanhchinh_Data::get_term_from_clean_name( $city_name, $taxonomy );
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

	protected function import_district_geodata( $city_taxonomies ) {
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

				$geom = geoPHP::load( $city->asXML(), 'kml' );

				foreach ( $support_geodata_taxonomies as $taxonomy ) {
					do_action(
						"diagioihanhchinh_insert_{$taxonomy}_term_geodata",
						$geom,
						$term,
						$city->asXML()
					);
				}
			}
		}

		$parent_tt = term_exists( $city_name, $taxonomy );
		if ( ! $parent_tt ) {
			error_log( sprintf( 'Không tìm thấy tên thành phố "%s" trong CSDL', $city_name ) );
			return;
		}

		$taxonomy_info = Diagioihanhchinh::get_registered_locations( $taxonomy );
		if ( ! isset( $taxonomy_info['childs'] ) ) {
			error_log( sprintf( 'Not found child location of "%s" taxonomy', $taxonomy ) );
			return;
		}

		foreach ( $grouped_district_geodatas as $district_geodata ) {
			$wards_kml     = implode( "\n", $district_geodata['wards_kml'] );
			$district_name = $district_geodata['name'];

			$parser       = new KML();
			$multipolygon = $parser->read( $kml_content, true );

			foreach ( $taxonomy_info['childs'] as $district_taxonomy ) {
				$district_name = Diagioihanhchinh_Data::clean_location_name( $district_name );
				$district_tt   = term_exists( $district_name, $district_taxonomy, $parent_tt['term_id'] );

				if ( ! $district_tt ) {
					error_log( sprintf( 'Không tìm thấy huyện "%s" trong CSDL', $district_name ) );
					continue;
				}

				do_action(
					"diagioihanhchinh_insert_{$district_taxonomy}_term_geodata",
					$multipolygon,
					get_term( $district_tt['term_id'] ),
					$kml_content
				);

				$this->read_ward_geodata( $district_geodata['wards_kml'], $district_taxonomy, $district_tt['term_id'] );
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

		foreach ( $ward_geodatas as $ward_name => $ward_geodata ) {
			$ward_name   = Diagioihanhchinh_Data::clean_location_name( $ward_name );
			$kml_content = <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<kml xmlns="http://www.opengis.net/kml/2.2">
  {$ward_geodata}
</kml>
XML;
			$polygon     = geoPHP::load( $kml_content, 'kml' );

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
