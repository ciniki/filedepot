<?php
//
// Description
// ===========
// This method will return the file
//
// Returns
// -------
//
function ciniki_filedepot_download($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No file specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/filedepot/private/checkAccess.php');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.download'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

//	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
//	$utc_offset = ciniki_users_timezoneOffset($ciniki);

//	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
//	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
//	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	//
	// Get the uuid for the file
	//
	$strsql = "SELECT ciniki_businesses.uuid AS business_uuid, ciniki_filedepot_files.uuid AS file_uuid, "
		. "ciniki_filedepot_files.name, ciniki_filedepot_files.extension "
		. "FROM ciniki_filedepot_files, ciniki_businesses "
		. "WHERE ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "AND ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_filedepot_files.business_id = ciniki_businesses.id "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'filedepot', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'718', 'msg'=>'Unable to find file'));
	}
	$filename = $rc['file']['name'] . '.' . $rc['file']['extension'];
	$file_uuid = $rc['file']['file_uuid'];
	$business_uuid = $rc['file']['business_uuid'];

	//
	// Move the file into storage
	//
	$storage_dirname = $ciniki['config']['core']['storage_dir'] . '/'
		. $business_uuid[0] . '/' . $business_uuid 
		. '/filedepot/'
		. $file_uuid[0];
	$storage_filename = $storage_dirname . '/' . $file_uuid;
	if( !is_file($storage_filename) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'719', 'msg'=>'Unable to find file'));
	}

	header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
	header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
	header('Cache-Control: no-cache, must-revalidate');
	header('Pragma: no-cache');
//	header('Content-Type: application/vnd.ms-excel');
	header('Content-Disposition: attachment;filename="' . $filename . '"');
	header('Content-Length: ' . filesize($storage_filename));
	header('Cache-Control: max-age=0');

	error_log('Downloading: ' . $storage_filename);
	$fp = fopen($storage_filename, 'rb');
	fpassthru($fp);

	return array('stat'=>'binary');
}
?>
