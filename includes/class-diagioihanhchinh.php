<?php
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Ramphor\Logger\Logger;

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
		$this->init_hooks();
	}

	public function setup_logger() {
		$log           = new Monolog( 'DIAGIOIHANHCHINH' );
		$stream_handle = new StreamHandler(
			apply_filters( 'diagioihanhchinh_logs_path', sprintf( '%s/diagioihanhchinh.log', WP_CONTENT_DIR ) ),
			Monolog::DEBUG
		);

		// Push stream handler to logger
		$log->pushHandler( $stream_handle );

		// Register diagioihanhchinh log to Ramphor Logger
		$logger = Logger::instance();
		$logger->registerLogger( 'diagioihanhchinh', $log );
	}

	protected function includes() {
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-common.php';
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-taxonomies.php';

		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-install.php';
	}

	protected function init_hooks() {
		register_activation_hook( DIAGIOIHANHCHINH_PLUGIN_FILE, array( Diagioihanhchinh_Install::class, 'active' ) );

		add_action( 'plugins_loaded', array( $this, 'setup_logger' ) );
	}
}
