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
		if ( ! is_a( $spreadsheet, Spreadsheet::class ) ) {
			echo 'the data must be an instance of Spreadsheet';
			return;
		}
		$opened_sheet   = $spreadsheet->getSheet( 0 );
		$rows           = $opened_sheet->toArray();
		$mapping_header = array(
			'Tỉnh Thành Phố' => 'city_name',
			'Mã TP'          => 'city_code',
			'Quận Huyện'     => 'district_name',
			'Mã QH'          => 'district_code',
			'Phường Xã'      => 'ward_name',
			'Mã PX'          => 'ward_code',
			'Cấp'            => 'level',
			'Tên Tiếng Anh'  => 'english_name',
		);
		$header         = array_values( $mapping_header );
		if ( $header ) {
			unset( $rows[0] );
		}
		$json_arr = array();

		foreach ( $rows as $row ) {
			$row = array_combine( $header, $row );
			if ( ! isset( $json_arr[ $row['city_code'] ] ) ) {
				$json_arr[ $row['city_code'] ] = array(
					'name' => $row['city_name'],
				);
			}

			if ( ! isset( $json_arr[ $row['city_code'] ]['districts'][ $row['district_code'] ] ) ) {
				$json_arr[ $row['city_code'] ]['districts'][ $row['district_code'] ] = array(
					'name' => $row['district_name'],
				);
			}

			if ( ! isset( $json_arr[ $row['city_code'] ]['districts'][ $row['district_code'] ]['wards'][ $row['ward_code'] ] ) ) {
				$json_arr[ $row['city_code'] ]['districts'][ $row['district_code'] ]['wards'][ $row['ward_code'] ] = array(
					'name' => $row['ward_name'],
				);
			}
		}

		$json_writer = fopen( sprintf( '%s/outputs/all.json', dirname( DIAGIOIHANHCHINH_PLUGIN_FILE ) ), 'w' );
		fwrite( $json_writer, json_encode( $json_arr ) );
		fclose( $json_writer );
	}
}
