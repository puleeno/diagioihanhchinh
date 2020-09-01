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
	}
}

new Diagioihanhchinh_Taxonomies();
