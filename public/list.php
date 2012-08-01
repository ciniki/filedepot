<?php
//
// Description
// ===========
// This method will list the art catalog pieces sorted by category.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
//
function ciniki_filedepot_list($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No status specified'), 
        'sortby'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No sort type specified'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'errmsg'=>'No limit specified'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.list'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/timezoneOffset.php');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	require_once($ciniki['config']['core']['modules_dir'] . '/users/private/datetimeFormat.php');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	
	// 
	// Build the SQL string to grab the list of files
	//
	$strsql = "SELECT ciniki_filedepot_files.id, name, version, org_filename, sharing_flags, "
		. "IF((sharing_flags&0x01)=0x01, 'Public', IF((sharing_flags&0x02)=0x02, 'Customers', 'Private')) AS shared, "
		. "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
		. "FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND parent_id = 0 "
		. "AND status = 1 ";
	if( isset($args['category']) ) {
		$strsql .= "AND (category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "'";
		if( $args['category'] == 'Uncategorized' ) {
			$strsql .= " OR category = ''";
		}
		$strsql .= ") ";
	}
	if( isset($args['sortby']) && $args['sortby'] == 'recent' ) {
		$strsql .= "ORDER BY date_added DESC ";
	} else {
		$strsql .= "ORDER BY name ";
	}
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} elseif( !isset($args['category']) ) {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
		array('container'=>'files', 'fname'=>'id', 'name'=>'file',
			'fields'=>array('id', 'name', 'version', 'org_filename', 'sharing_flags', 'shared', 'date_added')),
		));
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['files']) ) {
		return array('stat'=>'ok', 'files'=>array());
	}
	return array('stat'=>'ok', 'files'=>$rc['files']);
}
?>
