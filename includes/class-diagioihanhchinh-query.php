<?php
class Diagioihanhchinh_Query {
	public static function insert_location( $term_id, $location_data ) {
		global $wpdb;
		$sql        = $wpdb->prepare( "SELECT term_id FROM {$wpdb->prefix}wordland_locations WHERE term_id=%d", $term_id );
		$table_name = sprintf( '%swordland_locations', $wpdb->prefix );

		if ( intval( $wpdb->get_var( $sql ) ) > 0 ) {
			return $wpdb->update(
				$table_name,
				$location_data,
				array(
					'term_id' => $term_id,
				)
			);
		} else {
			$location_data['term_id'] = $term_id;
			return $wpdb->insert( $table_name, $location_data );
		}
	}
}
