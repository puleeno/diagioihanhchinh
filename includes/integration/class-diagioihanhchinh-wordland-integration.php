<?php
class Diagioihanhchinh_Wordland_Integration {
	public function __construct() {
		add_action( 'after_setup_theme', array( $this, 'change_location_labels' ) );
		add_action( 'after_setup_theme', array( $this, 'register_wordland_locations' ) );
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
	}
}
