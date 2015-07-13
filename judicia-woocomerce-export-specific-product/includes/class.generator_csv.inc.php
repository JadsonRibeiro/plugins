<?php

/**
 * Manage CSV files
 *
 * Manages the criation of .csv files.
 * Generate the file, write the data, ... 
 */
class ManageCSV {


	/**
	 * Generates .csv file in the '/exports/' path and make downloadable
	 *
	 * @param array $emails     - list with emails
	 *
	 * @return boolean | string - False if occur error, else, the filename
	 */
	static function generate_csv($emails) {
	
		$response = array('error' => false, 'message' => '#001 - Arquivo gerado com sucesso', 'rendered_button' => '');

		if(empty($emails))
			return array('error' => true, 'message' => '#002 - Nenhum email encontrado.');

		// render file name
		$filename = self::render_file_name();

		ob_start();

		if(!$handle = fopen($filename, 'w+'))
			return array('error' => true, 'message' => '#003 - Erro ao Gerar arquivo.');
		
		// Built header of csv file
		fputcsv($handle, array('Customer Email'));
		
		// Fill File with data
		foreach ($emails as $email) {
			fputcsv($handle, array($email));
		}
		fclose($handle);

		$buffer = ob_get_clean();

		chmod($filename, 0755);


		//  ***  Ajax isnt for downloading files  ** //
		// Download Forced
		// $download_result = self::download_csv($filename);
		// if($download_result['error'])
		// 	return $response = array('error' => true, 'message' => $download_result['message']);

		// link to Download
		$response['rendered_button'] = self::render_button_to_download($filename);

		return $response;
	}

	/**
	 * Render file name
	 *
	 * @param string $product_name - The name of the product
	 *
	 * @return string - the file name according todays date
	 */
	static function render_file_name(){
		$date = getdate();
		$file_name = "customers_".$date['year']."_".$date['mon']."_".$date['mday']."_".$date['hours']."_".$date['minutes']."_".$date['seconds'].".csv";
		$file_name = EXP_EXPORTS_PATH.''.$file_name;
		return $file_name;
	}

	/**
	 * Force Download file
	 *
	 * @param string $filename - Path to file
	 *
	 * @return array           - log of process
	 */
	static function download_csv($filename){

		$response = array('error' => false);

		$link = EXP_INCLUDES_URL.'/'.basename($filename);

		if(!file_exists($filename))
			return array('error' => true, 'message' => '#005 - Arquivo não foi gerado.');

		if(!$handle = fopen($link, 'r'))
			return array('error' => true, 'message' => '#006 - Erro ao abrir aquivo para Download.');
		$content = fread($handle, filesize($filename));
		fclose($handle);

		//Delete file
		// unlink($filename);

		header("Cache-Control: must-revalidate, post-check=0, pre-check=0");
        header('Content-Description: File Transfer');
        header("Content-type: text/csv");
        header("Content-Disposition: attachment; filename=".basename($filename)."");
        header("Expires: 0");
        header("Pragma: public");

        if(!$output_handle = fopen("php://output", 'w'))
        	return array('error' => true, 'message' => '#007 - Erro ao abrir stream para Download.');
        if(!fwrite($output_handle, $content))
        	return array('error' => true, 'message' => '#008 - Erro ao abrir escrever arquivo para Download.');
        fclose($output_handle);
       
        return $response;
	}

	/**
	 * Render an html code to download the generated file
	 * 
	 * @param string $filename - path to file
	 *
	 * @return string - html code to download file
	 */
	static public function render_button_to_download($filename){
		$link = EXP_INCLUDES_URL.'/'.basename($filename);
		return "<a href=".$link." download> <button> Baixar ".basename($filename)."</button> </a>";
	}
}