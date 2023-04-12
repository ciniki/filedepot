<?php
//
// Description
// -----------
// This function will return the b
//
// Arguments
// ---------
// ciniki:
// tnid:            The ID of the tenant.
// args:            The possible arguments for.
//
//
// Returns
// -------
//
function ciniki_filedepot_wng_process(&$ciniki, $tnid, &$request, $section) {

    $blocks = array();
    $s = isset($section['settings']) ? $section['settings'] : array();

    //
    // Check to make sure the module is enabled
    //
    if( !isset($ciniki['tenant']['modules']['ciniki.filedepot']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.wng.22', 'msg'=>"Content not available."));
    }

    //
    // Check to make sure the report is specified
    //
    if( !isset($section['ref']) || !isset($section['settings']) ) {
        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.wng.23', 'msg'=>"No category specified."));
    }

    //
    // Get the tenant storage directory
    //
    ciniki_core_loadMethod($ciniki, 'ciniki', 'tenants', 'hooks', 'storageDir');
    $rc = ciniki_tenants_hooks_storageDir($ciniki, $tnid, array());
    if( $rc['stat'] != 'ok' ) {
        return $rc;
    }
    $tenant_storage_dir = $rc['storage_dir'];
    
    //
    // Return the individual file to download
    //
    if( $section['ref'] == 'ciniki.filedepot.filedownload' 
        && isset($section['settings']['file-id']) 
        && $section['settings']['file-id'] > 0
        ) {
        $strsql = "SELECT id, "
            . "uuid, "
            . "name, "
            . "CONCAT_WS('.', permalink, extension) AS permalink, "
            . "version, "
            . "description "
            . "FROM ciniki_filedepot_files "
            . "WHERE ciniki_filedepot_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
            . "AND status = 1 "
            . "AND parent_id = 0 "
            . "AND id = '" . ciniki_core_dbQuote($ciniki, $section['settings']['file-id']) . "' "
            . "AND ("
                . "((ciniki_filedepot_files.sharing_flags&0x01) = 0x01) ";
        if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
            $strsql .= "OR ((ciniki_filedepot_files.sharing_flags&0x02) = 0x02) ";
        }
        $strsql .= ") "
            . "ORDER BY name, version ASC "
            . "";

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.filedepot', array(
            array('container'=>'files', 'fname'=>'permalink', 
                'fields'=>array('id', 'uuid', 'name', 'permalink', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.24', 'msg'=>'Unable to get file list', 'err'=>$rc['err']));
        }
        if( isset($rc['files']) && count($rc['files']) > 0 
            && isset($request['cur_uri_pos']) 
            && isset($request['uri_split'][($request['cur_uri_pos']+2)])
            && $request['uri_split'][($request['cur_uri_pos']+1)] == 'download'
            && $request['uri_split'][($request['cur_uri_pos']+2)] != '' 
            ) {
            foreach($rc['files'] as $file) {
                if( $request['uri_split'][($request['cur_uri_pos']+2)] == $file['permalink'] ) {

                    $storage_dirname = $tenant_storage_dir . '/filedepot/' . $file['uuid'][0];
                    $storage_filename = $storage_dirname . '/' . $file['uuid'];
                    if( !is_file($storage_filename) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.25', 'msg'=>'Unable to find file'));
                    }

                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    $finfo = finfo_open(FILEINFO_MIME);
                    if( $finfo ) {
                        header('Content-Type: ' . finfo_file($finfo, $storage_filename));
                    }
        //          header('Content-Disposition: attachment;filename="' . $rc['filename'] . '"');
                    header('Content-Length: ' . filesize($storage_filename));
                    header('Cache-Control: max-age=0');

                    $fp = fopen($storage_filename, 'rb');
                    fpassthru($fp);
                    return array('stat'=>'exit');
                }
            }
        } elseif( isset($rc['files']) && count($rc['files']) > 0 ) {
            $files = $rc['files'];
            foreach($files as $fid => $file) {
                if( $request['page']['path'] == '/' ) {
                    $files[$fid]['url'] = $request['page']['path'] . 'download/' . $file['permalink'];
                } else {
                    $files[$fid]['url'] = $request['page']['path'] . '/download/' . $file['permalink'];
                }
                if( isset($s['link-text']) && $s['link-text'] != '' ) {
                    $files[$fid]['name'] = $s['link-text'];
                }
            }
            $blocks[] = array(
                'type' => 'filelist',
                'link-class' => isset($s['class']) ? $s['class'] : 'link',
                'class' => 'section-' . ciniki_core_makePermalink($ciniki, $section['label']),
                'items' => $files,
                );
        }
    }

    //
    // Return the list of reports for the tenant
    //
    elseif( $section['ref'] == 'ciniki.filedepot.filelist' 
        && isset($section['settings']['category']) 
        && $section['settings']['category'] != ''
        ) {
        $strsql = "SELECT id, "
            . "uuid, "
            . "name, "
            . "CONCAT_WS('.', permalink, extension) AS permalink, "
            . "version, "
            . "description "
            . "FROM ciniki_filedepot_files "
            . "WHERE ciniki_filedepot_files.tnid = '" . ciniki_core_dbQuote($ciniki, $tnid) . "' " 
            . "AND status = 1 "
            . "AND parent_id = 0 "
            . "AND category = '" . ciniki_core_dbQuote($ciniki, $section['settings']['category']) . "' "
            . "AND ("
                . "((ciniki_filedepot_files.sharing_flags&0x01) = 0x01) ";
        if( isset($request['session']['customer']['id']) && $request['session']['customer']['id'] > 0 ) {
            $strsql .= "OR ((ciniki_filedepot_files.sharing_flags&0x02) = 0x02) ";
        }
        $strsql .= ") "
            . "ORDER BY name, version ASC "
            . "";

        ciniki_core_loadMethod($ciniki, 'ciniki', 'core', 'private', 'dbHashQueryArrayTree');
        $rc = ciniki_core_dbHashQueryArrayTree($ciniki, $strsql, 'ciniki.filedepot', array(
            array('container'=>'files', 'fname'=>'permalink', 
                'fields'=>array('id', 'uuid', 'name', 'permalink', 'description')),
            ));
        if( $rc['stat'] != 'ok' ) {
            return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.24', 'msg'=>'Unable to get file list', 'err'=>$rc['err']));
        }
        if( isset($rc['files']) && count($rc['files']) > 0 
            && isset($request['cur_uri_pos']) 
            && isset($request['uri_split'][($request['cur_uri_pos']+2)])
            && $request['uri_split'][($request['cur_uri_pos']+1)] == 'download'
            && $request['uri_split'][($request['cur_uri_pos']+2)] != '' 
            ) {
            foreach($rc['files'] as $file) {
                if( $request['uri_split'][($request['cur_uri_pos']+2)] == $file['permalink'] ) {

                    $storage_dirname = $tenant_storage_dir . '/filedepot/' . $file['uuid'][0];
                    $storage_filename = $storage_dirname . '/' . $file['uuid'];
                    if( !is_file($storage_filename) ) {
                        return array('stat'=>'fail', 'err'=>array('code'=>'ciniki.filedepot.25', 'msg'=>'Unable to find file'));
                    }

                    header("Expires: Mon, 26 Jul 1997 05:00:00 GMT");
                    header("Last-Modified: " . gmdate("D,d M YH:i:s") . " GMT");
                    header('Cache-Control: no-cache, must-revalidate');
                    header('Pragma: no-cache');
                    $finfo = finfo_open(FILEINFO_MIME);
                    if( $finfo ) {
                        header('Content-Type: ' . finfo_file($finfo, $storage_filename));
                    }
        //          header('Content-Disposition: attachment;filename="' . $rc['filename'] . '"');
                    header('Content-Length: ' . filesize($storage_filename));
                    header('Cache-Control: max-age=0');

                    $fp = fopen($storage_filename, 'rb');
                    fpassthru($fp);
                    return array('stat'=>'exit');
                }
            }
        } elseif( isset($rc['files']) && count($rc['files']) > 0 ) {
            $files = $rc['files'];
            foreach($files as $fid => $file) {
                $files[$fid]['url'] = $request['page']['path'] . '/download/' . $file['permalink'];
            }
            $blocks[] = array(
                'type' => 'filelist',
                'class' => 'section-' . ciniki_core_makePermalink($ciniki, $section['label']),
                'link-class' => isset($s['class']) ? $s['class'] : 'link',
                'items' => $files,
                );
        }
    }

    return array('stat'=>'ok', 'blocks'=>$blocks);
}
?>
