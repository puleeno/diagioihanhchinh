<?php
use Monolog\Logger as Monolog;
use Monolog\Handler\StreamHandler;
use Ramphor\Logger\Logger;

class Diagioihanhchinh {
	protected static $instance;
	protected static $location_taxonomies = array();

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
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-data.php';
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-fetcher.php';

		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-install.php';

		require_once dirname( __FILE__ ) . '/integration/class-diagioihanhchinh-wordland-integration.php';

		// Create WP CLI commands
		require_once dirname( __FILE__ ) . '/importer/class-diagioihanhchinh-data-importer.php';
		require_once dirname( __FILE__ ) . '/importer/class-diagioihanhchinh-geo-data-importer.php';
		require_once dirname( __FILE__ ) . '/class-diagioihanhchinh-command.php';
	}

	protected function init_hooks() {
		register_activation_hook( DIAGIOIHANHCHINH_PLUGIN_FILE, array( Diagioihanhchinh_Install::class, 'active' ) );

		add_action( 'plugins_loaded', array( $this, 'setup_logger' ) );
		add_action( 'plugins_loaded', array( $this, 'load_integrations' ) );

		add_filter( 'wp_unique_term_slug_is_bad_slug', array( $this, 'allow_duplicate_slug' ), 10, 3 );

		if ( class_exists( WP_CLI::class ) ) {
			add_action( 'cli_init', array( $this, 'register_commands' ) );
		}
	}

	public function load_integrations() {
		$active_plugins = get_option( 'active_plugins' );
		if ( in_array( 'wordland/wordland.php', $active_plugins ) ) {
			new Diagioihanhchinh_Wordland_Integration();
		}
	}

	public function register_commands() {
		WP_CLI::add_command( 'dghc', Diagioihanhchinh_Command::class );
	}

	public static function register_location_taxonomy( $tax_name, $level = 1, $parent_taxonomy = null ) {
		if ( $level < 0 && $level > 3 ) {
			// The level is invalid
			return;
		}
		if ( ! isset( static::$location_taxonomies[ $level ] ) ) {
			static::$location_taxonomies[ $level ] = array();
		}

		$taxs = &static::$location_taxonomies[ $level ];
		if ( $level > 1 ) {
			if ( ! $parent_taxonomy ) {
				error_log( 'Địa danh có level cấp huyện, xã bắt buộc phải set `parent_taxonomy`' );
				return;
			}
			$parent_level = $level - 1;

			if ( ! isset( static::$location_taxonomies[ $parent_level ][ $parent_taxonomy ] ) ) {
				error_log( '`parent_taxonomy` của ' . $tax_name . ' không hợp lệ' );
				return;
			}
		}

		$taxs[ $tax_name ]                         = array( 'parent' => $parent_taxonomy );
		static::$location_taxonomies['registed'][] = $tax_name;
		static::$location_taxonomies[ $tax_name ]  = array(
			'level'  => $level,
			'parent' => $parent_taxonomy,
		);
	}

	public static function get_registered_locations( $level = null ) {
		if ( is_null( $level ) ) {
			return static::$location_taxonomies;
		}
		if ( isset( static::$location_taxonomies[ $level ] ) ) {
			return static::$location_taxonomies[ $level ];
		}
		return false;
	}

	public function allow_duplicate_slug( $needs_suffix, $slug, $term ) {
		if ( in_array( $term->taxonomy, array( 'administrative_area_level_2', 'administrative_area_level_3' ) ) ) {
			return '';
		}
		return $needs_suffix;
	}
}
