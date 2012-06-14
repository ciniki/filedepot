<?php
//
// Description
// ===========
// This method will return all the details for a file in the filedepot.
//
// Returns
// -------
//
function ciniki_filedepot_get($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No filedepot specified'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.get'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	$strsql = "SELECT ciniki_filedepot_files.id, uuid, type, status, name, version, "
		. "category, description, org_filename, sharing_flags, "
		. "CONCAT_WS('', "
			. "IF((ciniki_filedepot_files.sharing_flags=0), 'Private', ''), "
			. "IF((ciniki_filedepot_files.sharing_flags&0x01)=0x01, 'Public', ''), "
			. "IF((ciniki_filedepot_files.sharing_flags&0x02)=0x02, 'Customers', '')"
			. ") AS sharing , "
		. "ciniki_filedepot_files.date_added, ciniki_filedepot_files.last_updated "
		. "FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "";
	
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'filedepot', array(
		array('container'=>'files', 'fname'=>'id', 'name'=>'file',
			'fields'=>array('id', 'type', 'status', 'name', 'version', 'category', 'description', 'org_filename', 'sharing', 'sharing_flags'),
			),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['files']) ) {
		return array('stat'=>'ok', 'err'=>array('pkg'=>'ciniki', 'code'=>'702', 'msg'=>'Unable to find file'));
	}
	$file = $rc['files'][0]['file'];

	return array('stat'=>'ok', 'file'=>$file);
}
?>
