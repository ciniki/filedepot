<?php
//
// Description
// ===========
// This method will add a new file to the filedepot module.
//
// Arguments
// ---------
// api_key:
// auth_token:
// business_id:			The ID of the business to add the file to.
// project_id:			(optional) The ID of the project in ciniki.projects module the file is connected to.
// child_id:			(optional) The ID of the file this one is to replace with a new version.
//						The version argument must be specified along with child_id.
// type:				(optional) The type of file.  **Not yet implemented**
// name:				The name of the file.
// version:				(optional) The version of the file.  Must be specified if this
//						file is to be a new version of another specified by child_id.
// category:			(optional) The category for the file.
// description:			(optional) The extended description of the file, can be much longer than the name.
// sharing_flags:		(optional) How the file is shared with the public and customers.  If the
//						value is zero (0) then the file is private to the business owners and employees.
//
//						0x01 - Public, available to general public via the business website.
//						0x02 - Customers, available only to customers who have logged into the site.
//						0x04 - Available to a specified list of customers **future**
// 
// Returns
// -------
// <rsp stat='ok' id='34' />
//
function ciniki_filedepot_add($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'business_id'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No business specified'), 
		'project_id'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'errmsg'=>'No project specified'),
		'child_id'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'errmsg'=>'No file specified'),
		'type'=>array('required'=>'no', 'blank'=>'no', 'default'=>'0', 'errmsg'=>'No type specified'),
        'name'=>array('required'=>'yes', 'blank'=>'no', 'errmsg'=>'No name specified'), 
        'version'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'', 'errmsg'=>'No version specified'), 
        'category'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No category specified'), 
        'description'=>array('required'=>'no', 'blank'=>'yes', 'errmsg'=>'No description specified'), 
        'sharing_flags'=>array('required'=>'no', 'blank'=>'yes', 'default'=>'0', 'errmsg'=>'No location specified'), 
        )); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   
    $args = $rc['args'];

	//
	// Check that the project id is a number
	//
	if( isset($args['project_id']) && $args['project_id'] == '' ) {
		$args['project_id'] = 0;
	}

	//
	// Check the project does exist, and the user has permission to it
	//
	if( isset($args['project_id']) && $args['project_id'] > 0 ) {
		ciniki_core_loadMethod($ciniki, 'ciniki', 'projects', 'private', 'checkAccess');
		$rc = ciniki_projects_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.add', $args['project_id']);
		if( $rc['stat'] != 'ok' ) {
			return $rc;
		}
	}

	$name = $args['name'];
	if( $args['version'] != '' ) {
		$name .= "-" . $args['version'];
	}
	$args['permalink'] = preg_replace('/ /', '-', preg_replace('/[^a-z0-9 ]/', '', strtolower($name)));

    //  
    // Make sure this module is activated, and
    // check permission to run this function for this business
    //  
	ciniki_core_loadMethod($ciniki, 'ciniki', 'filedepot', 'private', 'checkAccess');
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['business_id'], 'ciniki.filedepot.add'); 
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
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbInsert');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbAddModuleHistory');
	$rc = ciniki_core_dbTransactionStart($ciniki, 'ciniki.filedepot');
	if( $rc['stat'] != 'ok' ) { 
		return $rc;
	}   

	//
	// Check the permalink doesn't already exist
	//
	$strsql = "SELECT id, name, permalink FROM ciniki_filedepot_files "
		. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
		. "AND permalink = '" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "' "
		. "";
	$rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
	if( $rc['stat'] != 'ok' ) {
		return $rc;
	}
	if( $rc['num_rows'] > 0 ) {
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'713', 'msg'=>'You already have a file with this name, please choose another name'));
	}

    //
    // Check to see if an image was uploaded
    //
    if( isset($_FILES['uploadfile']['error']) && $_FILES['uploadfile']['error'] == UPLOAD_ERR_INI_SIZE ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'704', 'msg'=>'Upload failed, file too large.'));
    }
    // FIXME: Add other checkes for $_FILES['uploadfile']['error']

	//
	// Make sure a file was submitted
	//
	if( !isset($_FILES) || !isset($_FILES['uploadfile']) || $_FILES['uploadfile']['tmp_name'] == '' ) {
        return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'706', 'msg'=>'No file specified.'));
	}

	$args['org_filename'] = $_FILES['uploadfile']['name'];
	$args['extension'] = preg_replace('/^.*\.([a-zA-Z]+)$/', '$1', $args['org_filename']);

	//
	// Add the filedepot to the database
	//
	$strsql = "INSERT INTO ciniki_filedepot_files (uuid, project_id, business_id, type, extension, status, name, version, permalink, "
		. "category, description, org_filename, sharing_flags, user_id, "
		. "date_added, last_updated) VALUES ("
		. "UUID(), "
		. "'" . ciniki_core_dbQuote($ciniki, $args['project_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['type']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['extension']) . "', "
		. "'1', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['name']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['version']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['permalink']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['category']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['description']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['org_filename']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $args['sharing_flags']) . "', "
		. "'" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "', "
		. "UTC_TIMESTAMP(), UTC_TIMESTAMP())"
		. "";
	$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.filedepot');
	if( $rc['stat'] != 'ok' ) { 
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return $rc;
	}
	if( !isset($rc['insert_id']) || $rc['insert_id'] < 1 ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'701', 'msg'=>'Unable to add file'));
	}
	$file_id = $rc['insert_id'];

	//
	// Add all the fields to the change log
	//
	$changelog_fields = array(
		'project_id',
		'type',
		'name',
		'version',
		'category',
		'description',
		'org_filename',
		'sharing_flags',
		);
	foreach($changelog_fields as $field) {
		$insert_name = $field;
		if( isset($ciniki['request']['args'][$field]) && $ciniki['request']['args'][$field] != '' ) {
			$rc = ciniki_core_dbAddModuleHistory($ciniki, 'ciniki.filedepot', 'ciniki_filedepot_history', $args['business_id'], 
				1, 'ciniki_filedepot_files', $file_id, $insert_name, $ciniki['request']['args'][$field]);
		}
	}

	//
	// If a child_id was specified, then update all children
	//
	if( $args['child_id'] > 0 ) {
		$strsql = "UPDATE ciniki_filedepot_files "
			. "SET parent_id = '" . ciniki_core_dbQuote($ciniki, $file_id) . "' "
			. ", last_updated = UTC_TIMESTAMP() "
			. "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "' "
			// Move all children of the old parent
			. "AND (parent_id = '" . ciniki_core_dbQuote($ciniki, $args['child_id']) . "' "
				// and the original parent should now become a child
				. "OR id = '" . ciniki_core_dbQuote($ciniki, $args['child_id']) . "' "
				. ") "
			. "";
		ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbUpdate');
		$rc = ciniki_core_dbUpdate($ciniki, $strsql, 'ciniki.filedepot');
		if( $rc['stat'] != 'ok' ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
			return $rc;
		}
	}

	//
	// Get the uuid for the file
	//
	$strsql = "SELECT ciniki_businesses.uuid AS business_uuid, ciniki_filedepot_files.uuid AS file_uuid "
		. "FROM ciniki_filedepot_files, ciniki_businesses "
		. "WHERE ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $file_id) . "' "
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
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'705', 'msg'=>'Unable to add file'));
	}
	$file_uuid = $rc['file']['file_uuid'];
	$business_uuid = $rc['file']['business_uuid'];

	//
	// Move the file into storage
	//
	$storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
		. $business_uuid[0] . '/' . $business_uuid 
		. '/filedepot/'
		. $file_uuid[0];
	$storage_filename = $storage_dirname . '/' . $file_uuid;
	if( !is_dir($storage_dirname) ) {
		if( !mkdir($storage_dirname, 0700, true) ) {
			ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
			return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'707', 'msg'=>'Unable to add file'));
		}
	}
	if( !rename($_FILES['uploadfile']['tmp_name'], $storage_filename) ) {
		ciniki_core_dbTransactionRollback($ciniki, 'ciniki.filedepot');
		return array('stat'=>'fail', 'err'=>array('pkg'=>'ciniki', 'code'=>'708', 'msg'=>'Unable to add file'));
	}
	
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
	// Check if the web module settings should be updated to allow 
	// downloads for public or customer only
	//
	if( isset($modules['ciniki.web']) ) {
		//
		// If the file is available to the public, make sure the setting allows it in the web module
		//
		$updated = 0;
		if( isset($args['sharing_flags']) && ($args['sharing_flags']&0x01) == 0x01 ) {
			$strsql = "INSERT INTO ciniki_web_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, 'page-downloads-public') . "', 'yes' "
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = 'yes' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
			if( $rc['stat'] != 'ok' ) {
				// Don't return the error to the user, it's only an internal error
				error_log('Unable to set page-downloads-public setting');
			}
			$updated = 1;
		}
		//
		// If the file is available to customers, make sure the setting allows it in the web module.
		// This will enable the customer sign in button.
		//
		if( isset($args['sharing_flags']) && ($args['sharing_flags']&0x02) == 0x02 ) {
			$strsql = "INSERT INTO ciniki_web_settings (business_id, detail_key, detail_value, date_added, last_updated) "
				. "VALUES ('" . ciniki_core_dbQuote($ciniki, $args['business_id']) . "'"
				. ", '" . ciniki_core_dbQuote($ciniki, 'page-downloads-customers') . "', 'yes' "
				. ", UTC_TIMESTAMP(), UTC_TIMESTAMP()) "
				. "ON DUPLICATE KEY UPDATE detail_value = 'yes' "
				. ", last_updated = UTC_TIMESTAMP() "
				. "";
			$rc = ciniki_core_dbInsert($ciniki, $strsql, 'ciniki.web');
			if( $rc['stat'] != 'ok' ) {
				// Don't return the error to the user, it's only an internal error
				error_log('Unable to set page-downloads-public setting');
			}
			$updated = 1;
		}

		//
		// Update the last_change date in the business modules
		// Ignore the result, as we don't want to stop user updates if this fails.
		//
		ciniki_core_loadMethod($ciniki, 'ciniki', 'businesses', 'private', 'updateModuleChangeDate');
		ciniki_businesses_updateModuleChangeDate($ciniki, $args['business_id'], 'ciniki', 'web');

	}

	return array('stat'=>'ok', 'id'=>$file_id);
}
?>
