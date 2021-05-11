<?php
//
// Description
// -----------
// This function will return the list of available sections to the ciniki.wng module.
//
// Arguments
// ---------
// ciniki:
// tnid:     
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_filedepot_wng_sections(&$ciniki, $tnid, $args) {

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.filedepot']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.21', 'msg'=>"I'm sorry, the section you requested does not exist."));
    }

    $sections = array();

    //
    // Get the categories
    //
    $strsql = "SELECT DISTINCT category "
        . "FROM ciniki_filedepot_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND parent_id = 0 "
        . "ORDER BY category "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'categories', 'fname'=>'category', 'fields'=>array('category')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.23', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $categories = isset($rc['categories']) ? $rc['categories'] : array(); 

    //
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.filedepot.filelist'] = array(
        'name'=>'Files',
        'module' => 'File Depot',
        'settings'=>array(
            'category' => array('label'=>'Category', 'type'=>'select', 'options'=>$categories, 
                'complex_options'=>array('value'=>'category', 'name'=>'category'),
                ),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
