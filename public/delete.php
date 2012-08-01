<?php
//
// Description
// ===========
// This function will update an art catalog piece to the database.
//
// Arguments
// ---------
// user_id: 		The user making the request
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_filedepot_delete($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/prepareArgs.php');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No ID specified'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.delete'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
	$modules = $rc['modules'];

	//  
	// Turn off autocommit
	//  
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionStart.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionRollback.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbTransactionCommit.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbQuote.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbDelete.php');
	require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbAddModuleHistory.php');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.filedepot');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Get the uuid for the file
	//
	$strsql = "SELECT ciniki_businesses.uuid AS business_uuid, ciniki_filedepot_files.uuid AS file_uuid, parent_id "
		. "FROM ciniki_filedepot_files, ciniki_businesses "
		. "WHERE ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "AND ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND ciniki_filedepot_files.business_id = ciniki_businesses.id "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return $rc;
	}
	if( !isset($rc['file']) ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'710', 'msg'=>'Unable to delete file'));
	}
	$file_uuid = $rc['file']['file_uuid'];
	$business_uuid = $rc['file']['business_uuid'];
	$old_parent_id = $rc['file']['parent_id'];

	//
	// Check if there were childing for this file,
	// and reset parent to the most recent file.
	//
	$parent_id = 0;
	if( $old_parent_id == 0 ) {
		$strsql = "SELECT id, parent_id, version "
			. "FROM ciniki_filedepot_files "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			. "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
			. "ORDER BY id DESC "
			. "";
		$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
			return $rc;
		}
		if( $rc['num_rows'] > 0 && isset($rc['rows'][0]['id']) ) {
			// Update the most recent child to parent
			$parent_id = $rc['rows'][0]['id'];
			$strsql = "UPDATE ciniki_filedepot_files "
				. "SET parent_id = 0, last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND id = '" . ciniki_core_dbQuote($ciniki, $parent_id) . "' "
				. "";
			require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbUpdate.php');
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.filedepot');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
				return $rc;
			}
			$strsql = "UPDATE ciniki_filedepot_files "
				. "SET parent_id = '" . ciniki_core_dbQuote($ciniki, $parent_id) . "', "
				. "last_updated = UTC_TIMESTAMP() "
				. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
				. "AND parent_id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
				. "";
			$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.filedepot');
			if( $rc['stat'] != 'ok' ) {
				ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
				return $rc;
			}
		}
	}

	//
	// Move the file into storage
	//
	$storage_dirname = $ciniki['config']['core']['storage_dir'] . '/'
		. $business_uuid[0] . '/' . $business_uuid 
		. '/filedepot/'
		. $file_uuid[0];
	$storage_filename = $storage_dirname . '/' . $file_uuid;

	//
	// Start building the delete SQL
	//
	$strsql = "DELETE FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
		. "";

	$rc = ciniki_core_dbDelete($ciniki, $strsql, 'ciniki.filedepot');
	if( $rc['stat'] != 'ok' ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return $rc;
	}
	if( !isset($rc['num_affected_rows']) || $rc['num_affected_rows'] != 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'711', 'msg'=>'Unable to delete file'));
	}

	//
	// Remove from the filesystem
	//
	if( !unlink($storage_filename) ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'712', 'msg'=>'Unable to delete file'));
	}

	//
	// Log the delete in the history
	//
	$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.filedepot', 'ciniki_filedepot_history', $args['business_id'], 
		3, 'ciniki_filedepot_files', $args['file_id'], '', '');

	//
	// Commit the database changes
	//
    $rc = ciniki_core_dbTransactionCommit($ciniki, 'ciniki.filedepot');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}

	//
	// Update the last_change date in the business modules
	// Ignore the result, as we don't want to stop user updates if this fails.
	//
	ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
	ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'filedepot');

	// 
	// Update the web settings
	//
	if( isset($modules['ciniki.web']) ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'web', 'private', 'settingsUpdateDownloads');
		$rc = ciniki_web_settingsUpdateDownloads($ciniki, $modules, $args['business_id']);
		if( $rc['stat'] != 'ok' ) {
			// Don't return error code to user, they successfully updated the record
			error_log("ERR: " . $rc['code'] . ' - ' . $rc['msg']);
		}
	}

	return array('stat'=>'ok', 'parent_id'=>$parent_id);
}
?>
