<?php
class Diagioihanhchinh_Data {
	public function __construct() {
		$this->init_hooks();
	}

	public function init_hooks() {
		add_action( 'init', array( $this, 'register_cache_locations_post_type' ) );
	}

	public function register_cache_locations_post_type() {
		register_post_type(
			'dghc_cache_locations',
			array(
				'name'     => 'Diagioihanhchinh cache',
				'public'   => false,
				'_builtin' => true,
			)
		);
	}

	public static function create_location_key_from_name( $name ) {
		$name = remove_accents( $name );
		return preg_replace( array( '/[\s]/', '/_{2,}/' ), '_', $name );
	}


	/**
	 * Format lại tên của địa danh dựa theo format từ data của cục thống kê
	 *
	 * @link https://www.gso.gov.vn/dmhc2015/Default.aspx
	 */
	public static function clean_location_name( $name ) {
		/**
		 * Fix name do not have whitespace
		 *
		 * LộcHà
		 * ChơnThành
		 */
		$name = preg_replace( '/(\w)([A-Z])/', '$1 $2', $name );

		/**
		 * Fix name has ' character
		 *
		 * Ea H' Leo
		 */
		$name = preg_replace( '/(\w\') /', '$1', $name );

		/**
		 * Fix name has - character
		 *
		 * Bà Rịa – Vũng Tàu
		 */
		$name = str_replace(
			array(
				'Bà Rịa Vũng Tàu',
			),
			array(
				'Bà Rịa – Vũng Tàu',
			),
			$name
		);

		$replaced_name = str_replace(
			array(
				'Thành phố ',
				'Thị trấn ',
				'Tỉnh ',
				'Huyện ',
				'Xã ',
				'Thị xã ',
				'',
			),
			'',
			$name
		);

		return trim( $replaced_name );
	}
}

new Diagioihanhchinh_Data();
