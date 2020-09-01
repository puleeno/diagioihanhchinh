<?php
class Diagioihanhchinh {
	protected static $instance;

	public static function get_instance() {
		if ( is_null( self::$instance ) ) {
			self::$instance = new static();
		}
		return static::$instance;
	}

	private function __construct() {
		$this->includes();
	}

	protected function includes() {
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-common.php';
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-install.php';
	}
}
