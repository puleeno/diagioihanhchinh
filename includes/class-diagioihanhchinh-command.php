<?php
use Commando\Command;
use Ramphor\Logger\Logger;
use PhpOffice\PhpSpreadsheet\Spreadsheet;

class Diagioihanhchinh_Command {
	protected $command;
	public function __construct() {
		$this->command = new Command();
		$this->parse_options();
	}

	protected function parse_options() {
		$this->command->option()
			->require()
			->description( 'Chọn lệnh cần action' );
		$this->command->option( 'f' )
			->default( 'json' )
			->alias( 'format' )
			->description( 'Tuỳ chọn định dạng cho đầu ra. Định dạng mặc định là json' );
	}

	public function execute() {
		$logger = Logger::instance()->get( 'diagioihanhchinh' );
		if ( ! isset( $this->command[0] ) ) {
			$logger->warning( sprintf( 'ERROR: Required argument `%s` must be specified', 'action' ) );
		}
		$support_actions = array( 'generate' );
		if ( ! in_array( $this->command[0], $support_actions ) ) {
			echo sprintf( 'Diagioihanhchinh support only commands: %s', implode( ', ', $support_actions ) );
			return;
		}
		$method = $this->command[0];

		// Call the method
		$this->$method();
	}

	protected function generate() {
		$format            = $this->command['format'];
		$supported_formats = array( 'json' );
		if ( ! in_array( $format, $supported_formats ) ) {
			echo sprintf( 'Diagioihanhchinh generator support only formats: %s', implode( ', ', $supported_formats ) );
			return;
		}

		$fetcher     = new Diagioihanhchinh_Fetcher();
		$spreadsheet = $fetcher->fetch();

		$method = sprintf( 'generate_%s', $format );
		// Generate with format
		$this->$method( $spreadsheet );
	}

	protected function generate_json( $spreadsheet ) {
		if (!is_a($spreadsheet, Spreadsheet::class)) {
			echo 'the data must be an instance of Spreadsheet';
			return;
		}
		$opened_sheet = $spreadsheet->getSheet(0);
		$rows = $opened_sheet->toArray();

		var_dump($rows[1]);die;
	}
}
