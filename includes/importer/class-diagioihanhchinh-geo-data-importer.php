<?php
class Diagioihanhchinh_Geo_Data_Importer {
	public function import() {
		$support_geodata_taxonomies = apply_filters(
			'diagioihanhchinh_administrative_area_level_1_support_geo_data',
			array()
		);
		if ( empty( $support_geodata_taxonomies ) ) {
			return;
		}

		global $wp_version;
		foreach ( $support_geodata_taxonomies as $support_geodata_taxonomy ) {
			$args  = array(
				'taxonomy'   => $support_geodata_taxonomy,
				'hide_empty' => false,
				'orderby'    => 'ID',
			);
			$terms = version_compare( $wp_version, '4.5.0' ) ? get_terms( $args ) : get_terms( $args['taxonomy'], $args );
			$this->import_city_geodata( $terms );
		}
	}

	public function import_city_geodata( $terms ) {
		if ( empty( $terms ) ) {
			return;
		}
		foreach ( $terms as $term ) {
			$city_name = remove_accents( str_replace( 'TP ', '', $term->name ) );
			$this->read_geo_data_from_city_name( $city_name, $term );
		}
	}

	public function read_geo_data_from_city_name( $name, $term ) {
		$plugin_dir      = dirname( DIAGIOIHANHCHINH_PLUGIN_FILE );
		$kml_dir         = sprintf( '%s/outputs/kml', $plugin_dir );
		$cached_kml_file = sprintf( '%s/%s.kml', $kml_dir, $name );
		if ( ! file_exists( $cached_kml_file ) ) {
			$data_file = sprintf( '%s/data/kml/%s.kmz', $plugin_dir, $name );
			$zip       = new ZipArchive();
			$res       = $zip->open( $data_file );
			if ( $res === true ) {
				$zip->extractTo( $kml_dir );
				$zip->close();
				rename(
					sprintf( '%s/doc.kml', $kml_dir ),
					$cached_kml_file
				);
			} else {
				error_log( sprintf( 'Không thể mở tập tin "%s"', $data_file ) );
				return false;
			}
		}

		$kml_content = file_get_contents( $cached_kml_file );

		$parser       = new KML();
		$multipolygon = $parser->read( $kml_content, true );

		do_action(
			"diagioihanhchinh_insert_{$term->taxonomy}_term_geodata",
			$multipolygon,
			$term,
			$kml_content,
			$cached_kml_file
		);
	}
}
