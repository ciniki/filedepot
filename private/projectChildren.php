<?php
//
// Description
// ===========
// This function will return a list of files associated with a project.
//
// Arguments
// ---------
// ciniki:
// business_id:     The ID of the business to get the ATDO's for.
// project_id:      The ID of the project to get the ATDO's for.
// 
// Returns
// -------
// <project>
//      <files>
//          <file id="45" name="Filename" version="1" org_filename="file.jpg" sharing_flags="0" shared="Private" date_added="Sep 22, 2012 15:21 pm" />
//      </files>
// </project>
//
function ciniki_filedepot_projectChildren($ciniki, $business_id, $project_id) {
    
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
    $utc_offset = ciniki_users_timezoneOffset($ciniki);

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
    ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
    $datetime_format = ciniki_users_datetimeFormat($ciniki);

    $project = array();
    // 
    // Build the SQL string to grab the list of files
    //
    $strsql = "SELECT ciniki_filedepot_files.id, name, version, org_filename, sharing_flags, "
        . "IF((sharing_flags&0x01)=0x01, 'Public', IF((sharing_flags&0x02)=0x02, 'Customers', 'Private')) AS shared, "
        . "DATE_FORMAT(CONVERT_TZ(date_added, '+00:00', '" . ciniki_core_dbQuote($ciniki, $utc_offset) . "'), '" . ciniki_core_dbQuote($ciniki, $datetime_format) . "') AS date_added "
        . "FROM ciniki_filedepot_files "
        . "WHERE business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
        . "AND project_id = '" . ciniki_core_dbQuote($ciniki, $project_id) . "' "
        . "AND parent_id = 0 "
        . "AND status = 1 ";
    if( isset($args['category']) ) {
        $strsql .= "AND (category = '" . ciniki_core_dbQuote($ciniki, $args['category']) . "'";
        if( $args['category'] == 'Uncategorized' ) {
            $strsql .= " OR category = ''";
        }
        $strsql .= ") ";
    }
    $strsql .= "ORDER BY name ";
    $rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'files', 'fname'=>'id', 'name'=>'file',
            'fields'=>array('id', 'name', 'version', 'org_filename', 'sharing_flags', 'shared', 'date_added')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( !isset($rc['files']) ) {
        $rc['files'] = array();
    }
    return array('stat'=>'ok', 'project'=>array('files'=>$rc['files']));
}
?>
