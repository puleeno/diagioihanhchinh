<?php
class Diagioihanhchinh_Taxonomies {
	public function __construct() {
		add_action( 'init', array( $this, 'register_locations' ) );
	}

	public function register_locations() {
	}
}

new Diagioihanhchinh_Taxonomies();
