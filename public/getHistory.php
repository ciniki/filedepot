<?php
//
// Description
// -----------
// This method will return the history for an element of a file in the filedepot.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:         The ID of the tenant the requested file belongs to.
// file_id:             The ID of the file to get the history for.
// field:               The element to get the history for.
//
// Returns
// -------
//
function ciniki_filedepot_getHistory($ciniki) {
    //
    // Find all the required and optional arguments
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        'field'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Field'), 
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $args = $rc['args'];
    
    //
    // Check access to tnid as owner, or sys admin
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'checkAccess');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['tnid'], 'ciniki.filedepot.getHistory');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbGetModuleHistory');
    return ciniki_core_dbGetModuleHistory($ciniki, 'ciniki.filedepot', 'ciniki_filedepot_history', $args['tnid'], 'ciniki_filedepot_files', $args['file_id'], $args['field']);
}
?>
