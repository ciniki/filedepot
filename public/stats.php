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
function ciniki_filedepot_stats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.stats'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

	$rsp = array('stat'=>'ok', 'stats'=>array());
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	
	//
	// Get the category stats
	//
	$strsql = "SELECT IF(category='', 'Uncategorized', category) AS name, COUNT(*) AS count "
		. "FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "GROUP BY category "
		. "ORDER BY name "
		. "";
	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
		array('container'=>'sections', 'fname'=>'name', 'name'=>'section',
			'fields'=>array('name', 'count')),
		));
	// error_log($strsql);
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( !isset($rc['sections']) ) {
		return array('stat'=>'ok', 'stats'=>array('categories'=>array(array('section'=>array('name'=>'Uncategorized', 'count'=>'0')))));
	}
	$rsp['stats']['categories'] = $rc['sections'];
	
	return $rsp;
}
?>
