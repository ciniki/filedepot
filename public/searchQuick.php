<?php
//
// Description
// ===========
// This method will search the file depot for any file names or descriptions that match the search string.
//
// Returns
// -------
//
function ciniki_filedepot_searchQuick($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Business'), 
        'start_needle'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Search String'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.searchQuick'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$date_format = ciniki_users_dateFormat($ciniki);

	$strsql = "SELECT ciniki_filedepot_files.id, name, version, org_filename "
		. "FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND parent_id = 0 "
		. "AND status = 1 "
		. "AND (name LIKE '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR name like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR org_filename like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR org_filename like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR description like '" . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. "OR description like '% " . ciniki_core_dbQuote($ciniki, $args['start_needle']) . "%' "
			. ") "
		. "ORDER BY name "
		. "";
	if( isset($args['limit']) && $args['limit'] != '' && $args['limit'] > 0 ) {
		$strsql .= "LIMIT " . ciniki_core_dbQuote($ciniki, $args['limit']) . " ";
	} else {
		$strsql .= "LIMIT 25 ";
	}
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
		array('container'=>'files', 'fname'=>'id', 'name'=>'file',
			'fields'=>array('id', 'name', 'version', 'org_filename')),
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
