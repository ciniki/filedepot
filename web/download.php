<?php
//
// Description
// ===========
// This method will return the file
//
// Returns
// -------
//
function ciniki_filedepot_web_download($ciniki, $business_id, $file_uuid) {
	//
	// Get the uuid for the file
	//
	$strsql = "SELECT ciniki_businesses.uuid AS business_uuid, ciniki_filedepot_files.uuid AS file_uuid, "
		. "ciniki_filedepot_files.name, ciniki_filedepot_files.extension "
		. "FROM ciniki_filedepot_files, ciniki_businesses "
		. "WHERE ciniki_filedepot_files.permalink = '" . ciniki_core_dbQuote($ciniki, $file_uuid) . "' "
		. "AND ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND ciniki_filedepot_files.business_id = ciniki_businesses.id "
		. "AND ciniki_filedepot_files.status = 1 "
		// Verify requesting user has permission
		. "AND ("
			. "((ciniki_filedepot_files.sharing_flags&0x01) = 0x01) ";
	if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
		$strsql .= "OR ((ciniki_filedepot_files.sharing_flags&0x02) = 0x02) ";
	}
	$strsql .= ") "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'filedepot', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['file']) ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'715', 'msg'=>'Unable to find file'));
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'716', 'msg'=>'Unable to find file'));
	}

	return array('stat'=>'ok', 'storage_filename'=>$storage_filename, 'filename'=>$filename);

}
?>
