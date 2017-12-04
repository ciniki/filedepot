<?php
//
// Description
// ===========
// This method will return the file in it's binary form.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the requested file belongs to.
// file_id:         The ID of the file to be downloaded.
//
// Returns
// -------
// Binary file.
//
function ciniki_filedepot_download($ciniki) {
    //  
    // Find all the required and optional arguments
    //  
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'prepareArgs');
    $rc = ciniki_core_prepareArgs($ciniki, 'no', array(
        'tnid'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'Tenant'), 
        'file_id'=>array('required'=>'yes', 'blank'=>'no', 'name'=>'File'), 
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['tnid'], 'ciniki.filedepot.download'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }   

    //
    // Get the uuid for the file
    //
    $strsql = "SELECT ciniki_tenants.uuid AS tenant_uuid, ciniki_filedepot_files.uuid AS file_uuid, "
        . "ciniki_filedepot_files.name, ciniki_filedepot_files.extension "
        . "FROM ciniki_filedepot_files, ciniki_tenants "
        . "WHERE ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
        . "AND ciniki_filedepot_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND ciniki_filedepot_files.tnid = ciniki_tenants.id "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.13', 'msg'=>'Unable to find file'));
    }
    $filename = $rc['file']['name'] . '.' . $rc['file']['extension'];
    $file_uuid = $rc['file']['file_uuid'];
    $tenant_uuid = $rc['file']['tenant_uuid'];

    //
    // Move the file into storage
    //
    $storage_dirname = $ciniki['config']['ciniki.core']['storage_dir'] . '/'
        . $tenant_uuid[0] . '/' . $tenant_uuid 
        . '/filedepot/'
        . $file_uuid[0];
    $storage_filename = $storage_dirname . '/' . $file_uuid;
    if( !is_file($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.14', 'msg'=>'Unable to find file'));
    }

    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT"); 
    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT"); 
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    // Set mime header
    $finfo = finfo_open(FILEINFO_MIME);
    if( $finfo ) { header('Content-Type: ' . finfo_file($finfo, $storage_filename)); }
    // Specify Filename
    header('Content-Disposition: attachment;filename="' . $filename . '"');
    header('Content-Length: ' . filesize($storage_filename));
    header('Cache-Control: max-age=0');

    error_log('Downloading: ' . $storage_filename);
    $fp = fopen($storage_filename, 'rb');
    fpassthru($fp);

    return array('stat'=>'binary');
}
?>
