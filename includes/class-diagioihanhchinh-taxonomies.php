<?php
class Diagioihanhchinh_Taxonomies {
	public function __construct() {
		add_action( 'init', array( $this, 'register_locations' ) );
	}

	public function register_locations() {
		$applied_post_types = apply_filters( 'diagioihanhchinh_post_types', array() );
		if ( empty( $applied_post_types ) ) {
			Logger::get( 'diagioihanhchinh' )->debug(
				'You must set post types to register locations',
				$applied_post_types
			);
			return;
		}

		$this->register_first_tier( $applied_post_types );
		$this->register_second_tier( $applied_post_types );
		$this->register_third_tier( $applied_post_types );
	}

	public function register_first_tier( $applied_post_types ) {
		$labels        = array(
			'name' => __( 'City', 'diagioihanhchinh' ),
		);
		$tinhthanhargs = apply_filters(
			'diagioihanhchinh_tinhthanh_args',
			array(
				'labels' => $labels,
				'public' => true,
			)
		);

		register_taxonomy( 'tinhthanh', $applied_post_types, $tinhthanhargs );
	}

	public function register_second_tier( $applied_post_types ) {
	}

	public function register_third_tier( $applied_post_types ) {
	}
}

new Diagioihanhchinh_Taxonomies();
