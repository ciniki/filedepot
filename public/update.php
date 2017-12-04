<?php
//
// Description
// ===========
// This method will update the details about an existing file in the depot.  If an new version 
// is to be uploaded, then it should be done with the ciniki.filedepot.add method.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the existing file is connected to.
// file_id:         The ID of the file to be updated.
// project_id:      (optional) The ID of the project the file is connected to.
// name:            (optional) The new name for the file.
// version:         (optional) The new version for the file.
// category:        (optional) The new category for the file.
// sharing_flags:   (optional) The flags specifing how the file is shared with customers on the website.
// description:     (optional) The description of the file.
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_filedepot_update($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
        'project_id'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Project'), 
        'name'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Name'), 
        'version'=>array('required'=>'no', 'blank'=>'no', 'name'=>'Version'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Category'), 
        'sharing_flags'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Sharing Options'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'name'=>'Description'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

    if( isset($args['name']) ) {
        $name = $args['name'];
        if( $args['version'] != '' ) {
            $name .= "-" . $args['version'];
        }
        $args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));

        //
        // Make sure the permalink is unique
        //
        $strsql = "SELECT id, name, permalink FROM ciniki_filedepot_files "
            . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
            . "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
            . "AND id <> '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "";
        $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
        if( $rc['stat'] != 'ok' ) {
            return $rc;
        }
        if( $rc['num_rows'] > 0 ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.17', 'msg'=>'You already have a file with this name, please choose another name'));
        }
    }

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this tenant
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'checkAccess');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['tnid'], 'ciniki.filedepot.update'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    //  
    // Turn off autocommit
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionStart');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionRollback');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbTransactionCommit');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
    $rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.filedepot');
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Start building the update SQL
    //
    $strsql = "UPDATE ciniki_filedepot_files SET last_updated = UTC_TIMESTAMP()";

    //
    // Add all the fields to the change log
    //
    $changelog_fields = array(
        'project_id',
        'name',
        'version',
        'permalink',
        'type',
        'category',
        'sharing_flags',
        'description',
        );
    foreach($changelog_fields as $field) {
        if( isset($args[$field]) ) {
            $strsql .= ", $field = '" . ciniki_core_dbQuote($ciniki, $args[$field]) . "' ";
            $rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.filedepot', 'ciniki_filedepot_history', $args['tnid'], 
                2, 'ciniki_filedepot_files', $args['file_id'], $field, $args[$field]);
        }
    }
    $strsql .= "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' ";
    $rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.filedepot');
    if( $rc['stat'] != 'ok' ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
        return $rc;
    }
    if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
        ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.18', 'msg'=>'Unable to update file'));
    }

    //
    // Commit the database changes
    //
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.filedepot');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }

    //
    // Update the last_change date in the tenant modules
    // Ignore the result, as we don't want to stop user updates if this fails.
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'updateModuleChangeDate');
    ciniki_tenants_updateModuleChangeDate($ciniki, $args['tnid'], 'ciniki', 'filedepot');

    // 
    // Update the web settings
    //
    if( isset($modules['ciniki.web']) ) {
        ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'settingsUpdateDownloads');
        $rc = ciniki_web_settingsUpdateDownloads($ciniki, $modules, $args['tnid']);
        if( $rc['stat'] != 'ok' ) {
            // Don't return error code to user, they successfully updated the record
            error_log("ERR: " . $rc['code'] . ' - ' . $rc['msg']);
        }
    }

    return array('stat'=>'ok');
}
?>
