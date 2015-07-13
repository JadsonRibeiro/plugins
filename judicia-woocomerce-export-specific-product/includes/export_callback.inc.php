<?php

/**
 * Callback function 
 *
 * Use to handle with ajax requests made by js
 * Primarily used to export emails to cvs according params passed by ajax
 */

/**
 * Ajax Callback
 *
 * @return void
 */
function export_callback(){

	define('EXP_STATUS_SUCCESSFULLY', 0);
	define('EXP_STATUS_NO_ORDER', 1);
	define('EXP_STATUS_GENERATE_ERROR', 2);
	
	$response = array();

	switch ($_POST['mode']) {
		case 'export':
			$response = ManageCSV::generate_csv(ExportEmailSearch::getEmailByProduct($_POST['field_product'], array($_POST['field_status'])));
			break;
		
		default:
			$response['error'] = true;
			$response['message'] = 'MODE DEFAULT';
			break;
	}
	
	header("Content-Type: application/json");
	echo json_encode($response);
	exit;
}