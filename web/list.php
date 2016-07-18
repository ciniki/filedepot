<?php
//
// Description
// -----------
// This function will return a list of links for the website.
//
// Returns
// -------
// <events>
//  <event id="" name="" />
// </events>
//
function ciniki_filedepot_web_list($ciniki, $business_id) {

    $strsql = "SELECT ciniki_filedepot_files.id, category AS cname, name, extension, version, description, permalink "
        . "FROM ciniki_filedepot_files "
        . "WHERE ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' " 
        . "AND status = 1 "
        . "AND parent_id = 0 "
        . "AND ("
            . "((ciniki_filedepot_files.sharing_flags&0x01) = 0x01) ";
    if( isset($ciniki['session']['customer']['id']) && $ciniki['session']['customer']['id'] > 0 ) {
        $strsql .= "OR ((ciniki_filedepot_files.sharing_flags&0x02) = 0x02) ";
    }
    $strsql .= ") "
        . "ORDER BY category, name ASC "
        . "";

    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryTree');
    return ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'categories', 'fname'=>'cname', 'name'=>'category',
            'fields'=>array('name'=>'cname')),
        array('container'=>'files', 'fname'=>'name', 'name'=>'file',
            'fields'=>array('id', 'name', 'extension', 'permalink', 'description')),
        ));
}
?>
