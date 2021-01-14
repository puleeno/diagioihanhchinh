<?php
class Diagioihanhchinh_Data_Importer {
	const CACHED_META_KEY = 'diagioihanhchinh_location_cached';
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

	protected function get_cached_location_id($taxonomy, $level) {
		$cached_option_key  = sprintf('%s_level%d_cached_location_id', $taxonomy, 1);
		$cached_location_id = get_option($cached_option_key);

		if ($cached_location_id > 0 && get_post_type($cached_location_id) !== 'dghc_cache_locations') {
			delete_option($cached_option_key);
			$cached_location_id = false;
		}

		if (!$cached_location_id) {
			$cached_location_id = wp_insert_post(array(
				'post_type' => 'dghc_cache_locations',
				'post_title' => $cached_option_key,
				'post_status' => 'public'
			));

			if (!is_wp_error($cached_location_id)) {
				update_option($cached_option_key, $cached_location_id);
			} else {
				$cached_location_id = 0;
			}
		}

		return $cached_location_id;
	}

	protected function get_cached_location_data($post_id) {
		$cached = get_post_meta($post_id, static::CACHED_META_KEY, true);
		return empty($cached) ? array() : $cached;
	}

	protected function add_new_term_to_cache($post_id, $orgdata_id, $term_id, $cached_locations = null) {
		if (is_null($cached_locations)) {
			$cached_locations = $this->get_cached_location_data($post_id);
		}
		if (!is_array($cached_locations)) {
			$cached_locations = array();
		}

		$cached_locations[$orgdata_id] = $term_id;
		update_post_meta($post_id, static::CACHED_META_KEY, $cached_locations);
	}

	protected function insert_term($name, $taxonomy, $parent_taxonomy_id = null) {
		$term_id = term_exists($name, $taxonomy, $parent_taxonomy_id);

		if (!$term_id) {
			$args = array();
			if ($parent_taxonomy_id > 0) {
				$args['parent'] = $parent_taxonomy_id;
			}
			$term_taxonomy = wp_insert_term($name, $taxonomy, $args);
			if (!is_wp_error($term_taxonomy)) {
				return $term_taxonomy['term_id'];
			}
		}

		return false;
	}

	public function import_from_cities($all_locations) {
		$city_taxonomies = Diagioihanhchinh::get_registered_locations(1);
		if (empty($city_taxonomies)) {
			return;
		}

		foreach($all_locations as $city_id => $city) {
			foreach($city_taxonomies as $taxonomy => $arg) {
				$cached_location_id = $this->get_cached_location_id($taxonomy, 1);
				if ($cached_location_id <= 0) {
					error_log(spintf('Không thể tạo data cache cho location `%s`', $cached_option_key));
					continue;
				}
				$cached_locations = $this->get_cached_location_data($cached_location_id);
				$term_id          = isset($cached_locations[$city_id]) ? $cached_locations[$city_id] : $this->insert_term($name, $taxonomy);

				if (!isset($cached_locations[$city_id])) {
					$cached_locations[$city_id] = $term_id;

					$this->add_new_term_to_cache($post_id, $city_id, $term_id, $cached_locations);
				}
			}
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
