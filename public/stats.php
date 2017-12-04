<?php
//
// Description
// ===========
// This method will list the art catalog pieces sorted by category.
//
// Arguments
// ---------
// user_id:         The user making the request
// 
// Returns
// -------
//
function ciniki_filedepot_stats($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'checkAccess');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['tnid'], 'ciniki.filedepot.stats'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }

    $rsp = array('stat'=>'ok', 'stats'=>array());
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbCount');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    
    //
    // Get the category stats
    //
    $strsql = "SELECT IF(category='', 'Uncategorized', category) AS name, COUNT(*) AS count "
        . "FROM ciniki_filedepot_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND parent_id = 0 "
        . "GROUP BY category "
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
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
