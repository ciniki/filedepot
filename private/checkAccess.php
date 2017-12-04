<?php
//
// Description
// ===========
// This function will check the user has access to the atdo module, and 
// return a list of other modules enabled for the tenant.
//
// Arguments
// =========
// tnid:         The ID of the tenant the request is for.
// 
// Returns
// =======
//
function ciniki_filedepot_checkAccess($ciniki, $tnid, $method) {
    //
    // Check if the tenant is active and the module is enabled
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'private', 'checkModuleAccess');
    $rc = ciniki_tenants_checkModuleAccess($ciniki, $tnid, 'ciniki', 'filedepot');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    if( isset($rc['modules']) ) {
        $modules = $rc['modules'];
    } else {
        $modules = array();
    }

    if( !isset($rc['ruleset']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.1', 'msg'=>'No permissions granted'));
    }

    //
    // Sysadmins are allowed full access, except for deleting.
    //
    if( $method != 'ciniki.filedepot.delete' ) {
        if( ($ciniki['session']['user']['perms'] & 0x01) == 0x01 ) {
            return array('stat'=>'ok', 'modules'=>$modules);
        }
    }

    //
    // Users who are an owner or employee of a tenant can see the tenant atdo
    //
    $strsql = "SELECT tnid, user_id FROM ciniki_tenant_users "
        . "WHERE tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' "
        . "AND user_id = '" . ciniki_core_dbQuote($ciniki, $ciniki['session']['user']['id']) . "' "
        . "AND package = 'ciniki' "
        . "AND status = 10 "
        . "AND (permission_group = 'owners' OR permission_group = 'employees' OR permission_group = 'resellers' ) "
        . "";
    ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQuery');
    $rc = ciniki_core_dbHashQuery($ciniki, $strsql, 'ciniki.tenants', 'user');
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    //
    // If the user has permission, return ok
    //
    if( isset($rc['rows']) && isset($rc['rows'][0]) 
        && $rc['rows'][0]['user_id'] > 0 && $rc['rows'][0]['user_id'] == $ciniki['session']['user']['id'] ) {
        return array('stat'=>'ok', 'modules'=>$modules);
    }

    //
    // By default, fail
    //
    return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.2', 'msg'=>'Access denied.'));
}
?>
