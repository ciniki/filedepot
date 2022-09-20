<?php
//
// Description
// -----------
//
// Arguments
// ---------
//
// Returns
// -------
//
function ciniki_filedepot_objects($ciniki) {
    
    $objects = array();
    $objects['file'] = array(
        'name'=>'File',
        'sync'=>'yes',
        'table'=>'ciniki_filedepot_files',
        'fields'=>array(
            'parent_id'=>array('name' => 'Parent', 'ref'=>'ciniki.filedepot.file', 'default'=>'0'),
            'project_id'=>array('name' => 'Project', 'ref'=>'ciniki.projects.project', 'default'=>'0'),
            'type'=>array('name' => 'Type', 'default'=>''),
            'extension'=>array('name' => 'File Extension', 'default'=>''),
            'status'=>array('name' => 'Status', 'default'=>'1'),
            'name'=>array('name' => 'Name'),
            'version'=>array('name' => 'Version', 'default'=>''),
            'permalink'=>array('name' => 'Permalink', 'default'=>''),
            'category'=>array('name' => 'Category', 'default'=>''),
            'parent_category'=>array('name' => 'Parent Category', 'default'=>''),
            'description'=>array('name' => 'Description', 'default'=>''),
            'org_filename'=>array('name' => 'Original Filename', 'default'=>''),
            'sharing_flags'=>array('name' => 'Sharing Options', 'default'=>'0'),
            'user_id'=>array('name' => 'User', 'default'=>''),
            ),
        'history_table'=>'ciniki_filedepot_history',
        );
    
    return array('stat'=>'ok', 'objects'=>$objects);
}
?>
