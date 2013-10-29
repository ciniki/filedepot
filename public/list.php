<?php
//
// Description
// ===========
// This method will return a list of files in the file depot.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id: 		The ID of the business to get the list of files from.
// category:			(optional) Only get files from this category.
// sortby:				(optional) How the results should be sorted.  If not
//						specified, they are sorted by name.
//					
//						- name - Sort the results by the name of the files.
//						- recent - Sort the results by the most recent files first.
// 
// limit:				The maximum number of results to return.
//
// Returns
// -------
//
function ciniki_filedepot_list($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'sortby'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sort Order'), 
        'limit'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Limit'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];
    
    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'checkAccess');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.list'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
	$modules = $rc['modules'];

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);

	
	// 
	// Build the SQL string to grab the list of files
	//
	$strsql = "SELECT ciniki_filedepot_files.id, ciniki_filedepot_files.name, ciniki_filedepot_files.version, ciniki_filedepot_files.org_filename, ciniki_filedepot_files.sharing_flags, "
		. "IF((ciniki_filedepot_files.sharing_flags&0x01)=0x01, 'Public', IF((ciniki_filedepot_files.sharing_flags&0x02)=0x02, 'Customers', 'Private')) AS shared, "
		. "DATE_FORMAT(CONVERT_TZ(ciniki_filedepot_files.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added ";
	if( isset($modules['ciniki.projects']) ) {
		$strsql .= ", ciniki_filedepot_files.project_id, ciniki_projects.name AS project_name ";
	}
	$strsql .= "FROM ciniki_filedepot_files ";
	if( isset($modules['ciniki.projects']) ) {
		$strsql .= "LEFT JOIN ciniki_projects ON (ciniki_filedepot_files.project_id = ciniki_projects.id AND ciniki_projects.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "') ";
	}
	$strsql .= "WHERE ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_filedepot_files.parent_id = 0 "
		. "AND ciniki_filedepot_files.status = 1 ";
	if( isset($args['category']) ) {
		$strsql .= "AND (ciniki_filedepot_files.category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "'";
		if( $args['category'] == 'Uncategorized' ) {
			$strsql .= " OR ciniki_filedepot_files.category = ''";
		}
		$strsql .= ") ";
	}
	if( isset($args['sortby']) && $args['sortby'] == 'recent' ) {
		$strsql .= "ORDER BY ciniki_filedepot_files.date_added DESC ";
	} else {
		$strsql .= "ORDER BY ciniki_filedepot_files.name ";
	}
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} elseif( !isset($args['category']) ) {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	if( isset($modules['ciniki.projects']) ) {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'version', 'org_filename', 'sharing_flags', 'shared', 'date_added', 'project_id', 'project_name')),
			));
	} else {
		$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
			array('container'=>'files', 'fname'=>'id', 'name'=>'file',
				'fields'=>array('id', 'name', 'version', 'org_filename', 'sharing_flags', 'shared', 'date_added')),
			));
	}
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['files']) ) {
		return array('stat'=>'ok', 'files'=>array());
	}
	return array('stat'=>'ok', 'files'=>$rc['files']);
}
?>
