<?php
class Diagioihanhchinh_Data_Importer {
	const DEFAULT_PROCESS_ITEMS = 100;

	protected static $cached_locations = array();

	public function __construct() {
	}

	public function get_data_from_cache() {
		$cached_file = sprintf( '%s/outputs/all.json', dirname( DIAGIOIHANHCHINH_PLUGIN_FILE ) );
		if (!file_exists($cached_file)) {
			return false;
		}
		$locations = json_decode(file_get_contents($cached_file), true);

		return $locations;
	}

	public function import() {
		$all_locations = $this->get_data_from_cache();

		if ( empty( $all_locations ) ) {
			return;
		}

		$this->import_from_cities($all_locations);
	}

	public function import_from_cities($all_locations) {
		foreach($all_locations as $city_id => $city) {
			$this->import_from_districts($city['districts']);
		}
	}

	public function import_from_districts($districts) {
		foreach($districts as $district_id => $district) {
			$this->import_from_wards($district['wards']);
		}
	}

	public function import_from_wards($wards) {
		foreach($wards as $ward_id => $ward) {
		}
	}
}
