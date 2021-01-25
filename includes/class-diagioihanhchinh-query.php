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
			$raw_sql                  = "INSERT INTO {$wpdb->prefix}wordland_locations(";

			foreach ( array_keys( $location_data ) as $key ) {
				$raw_sql .= sprintf( '%s, ', $key );
			}
			if ( ! isset( $location_data['location'] ) ) {
				$raw_sql .= 'location';
			} else {
				$raw_sql = rtrim( $raw_sql, ', ' );
			}
			$raw_sql .= ') VALUES(';
			foreach ( $location_data as $value ) {
				switch ( gettype( $value ) ) {
					case 'boolean':
					case 'integer':
						$raw_sql .= '%d, ';
						break;
					case 'double':
						$raw_sql .= '%f, ';
						break;
					default:
						$raw_sql .= '%s, ';
						break;
				}
			}

			if ( ! isset( $location_data['location'] ) ) {
				$raw_sql .= "ST_GeomFromText('POINT(0 0)')";
			} else {
				$raw_sql = rtrim( $raw_sql, ', ' );
			}
			$raw_sql .= ')';
			$values   = array_values( $location_data );
			array_unshift( $values, $raw_sql );
			$sql = call_user_func_array( array( $wpdb, 'prepare' ), $values );

			return $wpdb->query( $sql );
		}
	}
}
