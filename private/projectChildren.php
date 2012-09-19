<?php
//
// Description
// ===========
// This function will return a list of files associated with a project.
//
// Arguments
// ---------
// ciniki:
// business_id:		The ID of the business to get the ATDO's for.
// project_id:		The ID of the project to get the ATDO's for.
// 
// Returns
// -------
//
function ciniki_filedepot_projectChildren($ciniki, $business_id, $project_id) {
    
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'timezoneOffset');
	$utc_offset = ciniki_users_timezoneOffset($ciniki);

	ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbQuote');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'datetimeFormat');
	ciniki_core_loadMethod($ciniki, 'ciniki', 'users', 'private', 'dateFormat');
	$datetime_format = ciniki_users_datetimeFormat($ciniki);
	$date_format = ciniki_users_dateFormat($ciniki);

	$project = array();
//	$rc = ciniki_core_dbHashQueryTree($ciniki, $strsql, 'ciniki.atdo', array(
//		array('container'=>'childtypes', 'fname'=>'type', 'name'=>'tchild',
//			'fields'=>array('type')),
//		array('container'=>'children', 'fname'=>'id', 'name'=>'child',
//			'fields'=>array('id', 'subject', 'allday', 'status', 'priority', 'private', 'assigned', 'assigned_user_ids', 'assigned_users', 'due_date', 'due_time',
//				'start_ts', 'start_date', 
//				'last_followup_age'=>'age_followup', 'last_followup_user'=>'followup_user'), 
//			'idlists'=>array('assigned_user_ids'), 'lists'=>array('assigned_users')),
//		));

	return array('stat'=>'ok', 'project'=>$project);
}
?>
