<?php
//
// Description
// -----------
// This function will return a list of links for the website.
//
// Returns
// -------
// <events>
// 	<event id="" name="" />
// </events>
//
function ciniki_filedepot_web_list($ciniki, $business_id) {

	$strsql = "SELECT ciniki_filedepot_files.id, category AS cname, name, version, description, permalink "
		. "FROM ciniki_filedepot_files "
		. "WHERE ciniki_filedepot_files.business_id = '" . ciniki_core_dbQuote($ciniki, $business_id) . "' "
		. "AND (ciniki_filedepot_files.sharing_flags&0x01) = 0x01 "
		. "AND status = 1 "
		. "ORDER BY category, name ASC "
		. "";

	
    require_once($ciniki['config']['core']['modules_dir'] . '/core/private/dbHashQueryTree.php');
	return ciniki_core_dbHashQueryTree($ciniki, $strsql, 'links', array(
		array('container'=>'categories', 'fname'=>'cname', 'name'=>'category',
			'fields'=>array('name'=>'cname')),
		array('container'=>'files', 'fname'=>'name', 'name'=>'file',
			'fields'=>array('id', 'name', 'permalink', 'description')),
		));
}
?>
