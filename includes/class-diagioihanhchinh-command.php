<?php
class Diagioihanhchinh_Command {
	/**
	 * Nhập thông tin địa giới hành chính Việt Nam vào WordPress
	 */
	public function import_data() {
		$importer = new Diagioihanhchinh_Data_Importer();
		$importer->import();
	}

	/**
	 * Nhập thông tin Geo data cho các địa giới hành chính đã được tạo
	 */
	public function import_geodata() {
		$importer = new Diagioihanhchinh_Geo_Data_Importer();
		$importer->import();
	}
}
