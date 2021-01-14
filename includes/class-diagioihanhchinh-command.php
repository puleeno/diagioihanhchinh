<?php
class Diagioihanhchinh_Command {
	/**
	 * Nhập thông tin địa giới hành chính Việt Nam vào WordPress
	 */
	public function import_data() {
		$importer = new Diagioihanhchinh_Data_Importer();
		$importer->import();
	}
}
