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
        . "AND status = 1 " // Active
        . "AND (sharing_flags&0x01) = 0x01 " // Public
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
            'class' => array('label'=>'Style', 'type'=>'toggle', 'toggles'=>array(
                'link' => 'Links',
                'button' => 'Buttons',
                )),
            'title' => array('label'=>'Title', 'type'=>'text'),
            'category' => array('label'=>'Category', 'type'=>'select', 'options'=>$categories, 
                'complex_options'=>array('value'=>'category', 'name'=>'category'),
                ),
            ),
        );

    //
    // Get the list of files
    //
    $strsql = "SELECT id, name "
        . "FROM ciniki_filedepot_files "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND parent_id = 0 "
        . "AND status = 1 " // Active
        . "AND (sharing_flags&0x01) = 0x01 " // Public
        . "ORDER BY name "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
    $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.filedepot', array(
        array('container'=>'files', 'fname'=>'id', 'fields'=>array('id', 'name')),
        ));
    if( $rc['stat'] != 'ok' ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.26', 'msg'=>'Unable to load categories', 'err'=>$rc['err']));
    }
    $files = isset($rc['files']) ? $rc['files'] : array(); 

    //
    // Image, Menu with no drop downs/submenus
    //
    $sections['ciniki.filedepot.filedownload'] = array(
        'name'=>'File Download',
        'module' => 'File Depot',
        'settings'=>array(
            'link-text' => array('label'=>'Link Text', 'type'=>'text'),
            'class' => array('label'=>'Style', 'type'=>'toggle', 'toggles'=>array(
                'link' => 'Link',
                'button' => 'Button',
                )),
            'file-id' => array('label'=>'File', 'type'=>'select', 'options'=>$files, 
                'complex_options'=>array('value'=>'id', 'name'=>'name'),
                ),
            ),
        );

    return array('stat'=>'ok', 'sections'=>$sections);
}
?>
