<?php
class Diagioihanhchinh_Import_Data {
	const DEFAULT_PROCESS_ITEMS = 100;

	public function __construct() {
	}

	public function get_data_from_cache() {
		$cached_file = sprintf( '%s/output/all.json', dirname( DIAGIOIHANHCHINH_PLUGIN_FILE ) );
	}

	public function import() {
		$all_locations = $this->get_data_from_cache();

		if ( empty( $all_locations ) ) {
			return;
		}
	}
}
