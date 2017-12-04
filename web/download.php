<?php
//
// Description
// ===========
// This method will return the file
//
// Returns
// -------
//
function ciniki_filedepot_web_download($ciniki, $tnid, $file_uuid) {
    //
    // Get the uuid for the file
    //
    $strsql = "SELECT ciniki_tenants.uuid AS tenant_uuid, ciniki_filedepot_files.uuid AS file_uuid, "
        . "ciniki_filedepot_files.name, ciniki_filedepot_files.extension "
        . "FROM ciniki_filedepot_files, ciniki_tenants "
        . "WHERE CONCAT_WS('.', ciniki_filedepot_files.permalink, ciniki_filedepot_files.extension) = '" . ciniki_core_dbQuote($ciniki, $file_uuid) . "' "
        . "AND ciniki_filedepot_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND ciniki_filedepot_files.tnid = ciniki_tenants.id "
        . "AND ciniki_filedepot_files.status = 1 "
        // Verify requesting user has permission
        . "AND ("
            . "((ciniki_filedepot_files.sharing_flags&0x01) = 0x01) ";
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql .= "OR ((ciniki_filedepot_files.sharing_flags&0x02) = 0x02) ";
    }
    $strsql .= ") "
        . "";
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.filedepot', 'file');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['file']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.19', 'msg'=>'Unable to find file'));
    }
    $filename = $rc['file']['name'] . '.' . $rc['file']['extension'];
    $file_uuid = $rc['file']['file_uuid'];
    $tenant_uuid = $rc['file']['tenant_uuid'];

    //
    // Move the file into storage
    //
    $storage_dirname = $ciniki['config']['core']['storage_dir'] . '/'
        . $tenant_uuid[0] . '/' . $tenant_uuid 
        . '/filedepot/'
        . $file_uuid[0];
    $storage_filename = $storage_dirname . '/' . $file_uuid;
    if( !is_file($storage_filename) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.20', 'msg'=>'Unable to find file'));
    }

    return array('stat'=>'ok', 'storage_filename'=>$storage_filename, 'filename'=>$filename);

}
?>
