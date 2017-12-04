<?php
//
// Description
// ===========
// This method will return all the details for a file in the filedepot.
//
// Arguments
// ---------
// api_key:
// auth_token:
// tnid:     The ID of the tenant the file belongs to.
// file_id:         The ID of the file to get the details for.
//
// Returns
// -------
// <rsp stat="ok">
//      <file id="23" type="0" status="1" name="July Report" version="1.0" 
//          parent_id="0" category="Reports" description="The monthly report for July 2012"
//          org_filename="july_12.pdf" shared="Customers" sharing_flags="2" date_added="July 12, 2012 7:45 AM" />
// </rsp>
function ciniki_filedepot_get($ciniki) {
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
    $rc = ciniki_filedepot_checkAccess($ciniki, $args['tnid'], 'ciniki.filedepot.get'); 
    if( $rc['stat'] != 'ok' ) { 
        return $rc;
    }
    $modules = $rc['modules'];

    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    $strsql = "SELECT ciniki_filedepot_files.id, ciniki_filedepot_files.uuid, ciniki_filedepot_files.type, "
        . "ciniki_filedepot_files.status, ciniki_filedepot_files.name, ciniki_filedepot_files.version, ciniki_filedepot_files.parent_id, "
        . "ciniki_filedepot_files.category, ciniki_filedepot_files.description, ciniki_filedepot_files.org_filename, ciniki_filedepot_files.sharing_flags, "
        . "CONCAT_WS('', "
            . "IF((ciniki_filedepot_files.sharing_flags=0), 'Private', ''), "
            . "IF((ciniki_filedepot_files.sharing_flags&0x01)=0x01, 'Public', ''), "
            . "IF((ciniki_filedepot_files.sharing_flags&0x02)=0x02, 'Customers', '')"
            . ") AS shared , "
        . "DATE_FORMAT(CONVERT_TZ(ciniki_filedepot_files.date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
        . ", ciniki_filedepot_files.last_updated ";
    if( isset($modules['ciniki.projects']) ) {
        $strsql .= ", ciniki_filedepot_files.project_id, ciniki_projects.name AS project_name ";
    }
    $strsql .= "FROM ciniki_filedepot_files ";
    if( isset($modules['ciniki.projects']) ) {
        $strsql .= "LEFT JOIN ciniki_projects ON (ciniki_filedepot_files.project_id = ciniki_projects.id AND ciniki_projects.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "') ";
    }
    $strsql .= "WHERE ciniki_filedepot_files.tnid = '" . ciniki_core_dbQuote($ciniki, $args['tnid']) . "' "
        . "AND (ciniki_filedepot_files.id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "' "
            . "OR ciniki_filedepot_files.parent_id = '" . ciniki_core_dbQuote($ciniki, $args['file_id']) . "') "
        . "ORDER BY ciniki_filedepot_files.parent_id, version DESC "
        . "";
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    if( isset($modules['ciniki.projects']) ) {
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'files', 'fname'=>'id', 'name'=>'file',
            'fields'=>array('id', 'type', 'status', 'name', 'version', 'parent_id', 'category', 'description', 'org_filename', 'shared', 'sharing_flags', 'date_added', 
                'project_id', 'project_name'),
            ),
        ));
    } else {
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'files', 'fname'=>'id', 'name'=>'file',
            'fields'=>array('id', 'type', 'status', 'name', 'version', 'parent_id', 'category', 'description', 'org_filename', 'shared', 'sharing_flags', 'date_added'),
            ),
        ));
    }
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['files']) ) {
        return array('stat'=>'ok', 'err'=>array('code'=>'ciniki.filedepot.15', 'msg'=>'Unable to find file'));
    }
    $file = $rc['files'][0]['file'];
    if( isset($rc['files'][0]) ) {
        $file['versions'] = $rc['files'];
    } else {
        $file['versions'] = array();
    }

    return array('stat'=>'ok', 'file'=>$file);
}
?>
