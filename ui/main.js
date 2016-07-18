//
// The filedepot app to manage an artists collection
//
function ciniki_filedepot_main() {
    this.sharingFlags = {
        '1':{'name':'Public'},
        '2':{'name':'Customers'},
        };
    this.init = function() {
        //
        // Setup the main panel to list the collection
        //
        this.menu = new M.panel('File Depot',
            'ciniki_filedepot_main', 'menu',
            'mc', 'medium', 'sectioned', 'ciniki.filedepot.main.menu');
        this.menu.data = {};
        this.menu.listby = 'recent';
        this.menu.category = '';
        this.menu.sections = {
            'search':{'label':'', 'type':'livesearchgrid', 'livesearchcols':1, 'hint':'search',
                'noData':'No files found',
                'headerValues':null,
                'cellClasses':['multiline'],
                },
            '_categories':{'label':'Categories', 'type':'simplelist'},
            '_list':{'label':'', 'visible':'yes',
                'num_cols':2, 'type':'simplegrid', 'headerValues':null,
                'cellClasses':['multiline', 'multiline'],
                'noData':'No Files found', },
        };
        this.menu.liveSearchCb = function(s, i, v) {
            M.api.getJSONBgCb('ciniki.filedepot.searchQuick', {'business_id':M.curBusinessID, 'start_needle':v, 'limit':'15'},
                function(rsp) {
                    M.ciniki_filedepot_main.menu.liveSearchShow(s, null, M.gE(M.ciniki_filedepot_main.menu.panelUID + '_' + s), rsp.files);
                });
            return true;
        };
        this.menu.liveSearchResultValue = function(s, f, i, j, d) {
            return this.cellValue(s, i, j, d);
        };
        this.menu.liveSearchResultRowFn = function(s, f, i, j, d) {
            return 'M.ciniki_filedepot_main.showFile(\'M.ciniki_filedepot_main.showMenu();\', \'' + d.file.id + '\');'; 
        };
        this.menu.liveSearchResultRowStyle = function(s, f, i, d) { return ''; };
//      Currently not allowing full search
//      this.menu.liveSearchSubmitFn = function(s, search_str) {
//          M.ciniki_filedepot_main.searchArtCatalog('M.ciniki_filedepot_main.showMenu();', search_str);
//      };
        this.menu.cellValue = function(s, i, j, d) {
            if( j == 0 ) { 
                var pname ='';
                if( d.file.project_id != null && d.file_project_id != '0' && d.file.project_name != null && d.file.project_name != '' ) {
                    pname = ' <span class="subdue">[' + d.file.project_name + ']</span>';
                }
                return '<span class="maintext">' + d.file.name + ' ' + d.file.version + pname + '</span><span class="subtext">' + d.file.org_filename + '</span>';
            }
            if( j == 1 ) {
                return '<span class="maintext">' + d.file.date_added + '</span><span class="subtext">' + d.file.shared + '</span>';
            }
        };
        this.menu.rowFn = function(s, i, d) {
            return 'M.ciniki_filedepot_main.showFile(\'M.ciniki_filedepot_main.showMenu();\', \'' + d.file.id + '\');'; 
        };
        this.menu.sectionData = function(s) { 
            return this.data[s];
        };
        this.menu.listValue = function(s, i, d) { 
            return d.section.name;
        };
        this.menu.listCount = function(s, i, d) {
            return d.section.count;
        };
        this.menu.listFn = function(s, i, d) {
            return 'M.ciniki_filedepot_main.showList(null,\'category\',\'' + encodeURIComponent(d.section.name) + '\');';
        };
        this.menu.noData = function(s, i, d) {
            return this.sections[s].noData;
        };
        this.menu.addButton('add', 'Add', 'M.ciniki_filedepot_main.showAdd(\'M.ciniki_filedepot_main.showMenu();\',\'' + M.ciniki_filedepot_main.menu.category + '\',0,\'\');');
        this.menu.addClose('Back');

        //
        // The panel to display the add form
        //
        this.add = new M.panel('Add File',
            'ciniki_filedepot_main', 'add',
            'mc', 'medium', 'sectioned', 'ciniki.filedepot.main.edit');
        this.add.default_data = {};
        this.add.child_id = 0;
        this.add.data = {}; 
// FIXME:       this.add.uploadDropFn = function() { return M.ciniki_filedepot_main.uploadDropImagesAdd; };
        this.add.sections = {
            '_file':{'label':'File', 'fields':{
                'uploadfile':{'label':'', 'type':'file', 'hidelabel':'yes'},
            }},
            'info':{'label':'Information', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'version':{'label':'Version', 'type':'text', 'size':'small'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'sharing_flags':{'label':'Sharing', 'type':'flags', 'toggle':'yes', 'join':'yes', 'flags':this.sharingFlags},
                'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_filedepot_main.addFile();'},
            }},
        };
        this.add.fieldValue = function(s, i, d) { 
            if( i == 'project_id_fkidstr' ) { return this.data['project_name']; }
            if( this.data[i] != null ) {
                return this.data[i]; 
            } 
            return ''; 
        };
        this.add.liveSearchCb = function(s, i, value) {
            if( i == 'category' ) {
                var rsp = M.api.getJSONBgCb('ciniki.filedepot.searchField', {'business_id':M.curBusinessID, 'field':i, 'start_needle':value, 'limit':15},
                    function(rsp) {
                        M.ciniki_filedepot_main.add.liveSearchShow(s, i, M.gE(M.ciniki_filedepot_main.add.panelUID + '_' + i), rsp.results);
                    });
            }
            if( i == 'project_id' ) {
                var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
                    function(rsp) {
                        M.ciniki_filedepot_main.add.liveSearchShow(s, i, M.gE(M.ciniki_filedepot_main.add.panelUID + '_' + i), rsp['projects']);
                    });
            }
        };
        this.add.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'category' && d.result != null ) { return d.result.name; }
            if( f == 'project_id' && d.project != null ) { return d.project.name; }
            return '';
        };
        this.add.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( (f == 'category') && d.result != null ) {
                return 'M.ciniki_filedepot_main.add.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
            }
            if( f == 'project_id' ) {
                return 'M.ciniki_filedepot_main.add.updateProject(\'' + s + '\',\'' + escape(d.project.name) + '\',\'' + d.project.id + '\');';
            }
        };
        this.add.updateField = function(s, fid, result) {
            M.gE(this.panelUID + '_' + fid).value = unescape(result);
            this.removeLiveSearch(s, fid);
        };
        this.add.updateProject = function(s, project_name, project_id) {
            M.gE(this.panelUID + '_project_id').value = project_id;
            M.gE(this.panelUID + '_project_id_fkidstr').value = unescape(project_name);
            this.removeLiveSearch(s, 'project_id');
        };
        this.add.listValue = function(s, i, d) { return d['label']; };
        this.add.listFn = function(s, i, d) { return d['fn']; };
        this.add.addButton('save', 'Save', 'M.ciniki_filedepot_main.addFile();');
        this.add.addClose('Cancel');

        //
        // Display information about a file
        //
        this.file = new M.panel('File',
            'ciniki_filedepot_main', 'file',
            'mc', 'medium', 'sectioned', 'ciniki.filedepot.main.edit');
        this.file_id = 0;
        this.file.data = null;
        this.file.sections = {
//          '_file':{'label':'File', 'fields':{
//              'org_filename':{'label':'', 'type':'noedit', 'hidelabel':'yes', 'history':'no'},
//          }},
            'info':{'label':'Details', 'list':{
                'name':{'label':'Title'},
                'version':{'label':'Version'},
                'category':{'label':'Category'},
                'shared':{'label':'Sharing'},
                'project_name':{'label':'Project', 'visible':'no'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{'description':{'label':'', 'type':'noedit', 'hidelabel':'yes'}}},
            'versions':{'label':'Versions', 'type':'simplegrid', 'num_cols':3,
                'headerValues':null,
                'cellClasses':['multiline', 'multiline','aligncenter'],
                'noData':'No versions found',
                'compact_split_at':2,
                },
            '_save':{'label':'', 'buttons':{
                'addversion':{'label':'New Version', 'fn':'M.ciniki_filedepot_main.showAddVersion(\'M.ciniki_filedepot_main.showFile();\');'},
//              'download':{'label':'Download', 'fn':'M.ciniki_filedepot_main.downloadFile(M.ciniki_filedepot_main.file.file_id);'},
//              'edit':{'label':'Edit', 'fn':'M.ciniki_filedepot_main.editFile(\'M.ciniki_filedepot_main.showFile();\',M.ciniki_filedepot_main.file.file_id);'},
//              'delete':{'label':'Delete', 'fn':'M.ciniki_filedepot_main.deleteFile(M.ciniki_filedepot_main.file.file_id);'},
            }},
            };
        this.file.sectionData = function(s) {
            if( s == 'description' ) { return {s:this.data[s]} }
            if( s == 'versions' ) { return this.data.versions; }
            return this.sections[s].list;
            };
        this.file.listLabel = function(s, i, d) {
            switch (s) {
                case 'info': return d.label;
            }
        };
        this.file.listValue = function(s, i, d) {
            if( i == 'description' ) {
                return this.data[i].replace(/\n/g, '<br/>');
            }
            return this.data[i];
        };
        this.file.fieldValue = function(s, i, d) {
            if( i == 'description' ) { 
                return this.data[i].replace(/\n/g, '<br/>');
            }
            return this.data[i];
        };
        this.file.noData = function(s) {
            if( s == 'versions' ) { return 'No other versions'; }
            return '';
        };
        this.file.cellValue = function(s, i, j, d) {
            if( j == 0 ) { 
                return '<span class="maintext">' + d.file.name + ' ' + d.file.version + '</span><span class="subtext">' + d.file.org_filename + '</span>';
            }
            if( j == 1 ) {
                return '<span class="maintext">' + d.file.date_added + '</span><span class="subtext">' + d.file.shared + '</span>';
            }
            if( j == 2 ) {
                return "<button onclick=\"event.stopPropagation(); M.ciniki_filedepot_main.downloadFile(\'" + d.file.id + "\'); return false;\">Download</button>"; 
            }
        };
        this.file.rowFn = function(s, i, d) {
            return 'M.ciniki_filedepot_main.editFile(\'M.ciniki_filedepot_main.showFile();\', \'' + d.file.id + '\');'; 
        };

//      this.file.addButton('edit', 'Edit', 'M.ciniki_filedepot_main.editFile(\'M.ciniki_filedepot_main.showFile();\',M.ciniki_filedepot_main.file.file_id);');
        this.file.addClose('Back');

        //
        // The panel to display the edit form
        //
        this.edit = new M.panel('File',
            'ciniki_filedepot_main', 'edit',
            'mc', 'medium', 'sectioned', 'ciniki.filedepot.main.edit');
        this.edit.file_id = 0;
        this.edit.data = null;
        this.edit.cb = null;
        this.edit.sections = {
//          '_file':{'label':'Image', 'fields':{
//              'image_id':{'label':'', 'type':'image_id', 'hidelabel':'yes', 'history':'no'},
//          }},
            'info':{'label':'Details', 'type':'simpleform', 'fields':{
                'name':{'label':'Title', 'type':'text'},
                'version':{'label':'Version', 'type':'text', 'size':'small'},
                'category':{'label':'Category', 'type':'text', 'livesearch':'yes', 'livesearchempty':'yes'},
                'sharing_flags':{'label':'Website', 'type':'flags', 'toggle':'yes', 'join':'yes', 'flags':this.sharingFlags},
                'project_id':{'label':'Project', 'active':'no', 'type':'fkid', 'livesearch':'yes', 'livesearchempty':'yes'},
            }},
            '_description':{'label':'Description', 'type':'simpleform', 'fields':{
                'description':{'label':'', 'type':'textarea', 'size':'small', 'hidelabel':'yes'},
            }},
            '_save':{'label':'', 'buttons':{
                'save':{'label':'Save', 'fn':'M.ciniki_filedepot_main.saveFile();'},
                'download':{'label':'Download', 'fn':'M.ciniki_filedepot_main.downloadFile(M.ciniki_filedepot_main.edit.file_id);'},
                'delete':{'label':'Delete', 'fn':'M.ciniki_filedepot_main.deleteFile(M.ciniki_filedepot_main.edit.file_id);'},
            }},
        };
        this.edit.fieldValue = function(s, i, d) { 
            if( i == 'project_id_fkidstr' ) { return this.data['project_name']; }
            return this.data[i]; 
        }
        this.edit.sectionData = function(s) {
            return this.data[s];
        };
        this.edit.liveSearchCb = function(s, i, value) {
            if( i == 'category' ) {
                var rsp = M.api.getJSONBgCb('ciniki.filedepot.searchField', {'business_id':M.curBusinessID, 'field':i, 'start_needle':value, 'limit':15},
                    function(rsp) {
                        M.ciniki_filedepot_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_filedepot_main.edit.panelUID + '_' + i), rsp.results);
                    });
            }
            if( i == 'project_id' ) {
                var rsp = M.api.getJSONBgCb('ciniki.projects.searchNames', {'business_id':M.curBusinessID, 'start_needle':value, 'limit':25},
                    function(rsp) {
                        M.ciniki_filedepot_main.edit.liveSearchShow(s, i, M.gE(M.ciniki_filedepot_main.edit.panelUID + '_' + i), rsp['projects']);
                    });
            }
        };
        this.edit.liveSearchResultValue = function(s, f, i, j, d) {
            if( f == 'category' && d.result != null ) { return d.result.name; }
            if( f == 'project_id' && d.project != null ) { return d.project.name; }
            return '';
        };
        this.edit.liveSearchResultRowFn = function(s, f, i, j, d) { 
            if( (f == 'category') && d.result != null ) {
                return 'M.ciniki_filedepot_main.edit.updateField(\'' + s + '\',\'' + f + '\',\'' + escape(d.result.name) + '\');';
            }
            if( f == 'project_id' ) {
                return 'M.ciniki_filedepot_main.edit.updateProject(\'' + s + '\',\'' + escape(d.project.name) + '\',\'' + d.project.id + '\');';
            }
        };
        this.edit.updateField = function(s, fid, result) {
            M.gE(this.panelUID + '_' + fid).value = unescape(result);
            this.removeLiveSearch(s, fid);
        };
        this.edit.updateProject = function(s, project_name, project_id) {
            M.gE(this.panelUID + '_project_id').value = project_id;
            M.gE(this.panelUID + '_project_id_fkidstr').value = unescape(project_name);
            this.removeLiveSearch(s, 'project_id');
        };
        this.edit.fieldHistoryArgs = function(s, i) {
            return {'method':'ciniki.filedepot.getHistory', 'args':{'business_id':M.curBusinessID, 
                'file_id':this.file_id, 'field':i}};
        };

        this.edit.addButton('save', 'Save', 'M.ciniki_filedepot_main.saveFile();');
        this.edit.addClose('Cancel');

        //
        // FIXME: Add search panel
        //
        this.search = new M.panel('Search Results',
            'ciniki_filedepot_main', 'search',
            'mc', 'medium', 'sectioned', 'ciniki.filedepot.main.search');
        this.search.addClose('Back');
    }

    this.start = function(cb, appPrefix, aG) {
        args = {};
        if( aG != null ) {
            args = eval(aG);
        }

        //
        // Create container
        //
        var appContainer = M.createContainer(appPrefix, 'ciniki_filedepot_main', 'yes');
        if( appContainer == null ) {
            alert('App Error');
            return false;
        }

        if( args.add != null && args.add == 'project' && args.project_id != null && args.project_id > 0 ) {
            this.showAdd(cb, null, args.project_id, args.project_name);
        } else if( args.file_id != null && args.file_id != '' ) {
            this.showFile(cb, args.file_id);
        } else {
            this.showMenu(cb, 'recent');
        }
    }

    this.showMenu = function(cb, listby, category) {
        this.menu.data = {};
        var rsp = M.api.getJSONCb('ciniki.filedepot.stats', 
            {'business_id':M.curBusinessID}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                M.ciniki_filedepot_main.menu.data._categories = rsp.stats.categories;   
                M.ciniki_filedepot_main.showList(cb, listby, category);
            });
    };

    this.showList = function(cb, listby, category) {
        if( listby != null ) {
            this.menu.listby = listby;
            if( listby == 'category' ) {
                this.menu.category = category;
            } else {
                this.menu.category = '';
            }
        }

        var args = {};
        if( this.menu.listby == 'category' ) {
            this.menu.sections._list.label = decodeURIComponent(this.menu.category);
            args = {'business_id':M.curBusinessID, 'category':this.menu.category};
        } else {
            this.menu.sections._list.label = 'Recent uploads';
            args = {'business_id':M.curBusinessID, 'sortby':'recent', 'limit':15};
        }
        var rsp = M.api.getJSONCb('ciniki.filedepot.list', args, function(rsp) {
            if( rsp.stat != 'ok' ) {
                M.api.err(rsp);
                return false;
            }
            var p = M.ciniki_filedepot_main.menu;
            p.data._list = rsp.files;
            p.refresh();
            p.show(cb);
        });
    };

    this.showAdd = function(cb, category, pid, pname) {
        this.add.reset();
        this.add.child_id = 0;
        this.add.data = {'category':''};
        if( category != null && category != '' ) {
            this.add.data.category = decodeURIComponent(category);
        }
        if( pid != null && pid > 0 ) {
            this.add.data.project_id = pid;
            this.add.data.project_name = unescape(pname);
        }
        if( M.curBusiness.modules['ciniki.projects'] != null ) {
            this.add.sections.info.fields.project_id.active = 'yes';
        } else {
            this.add.sections.info.fields.project_id.active = 'no';
        }
        this.add.refresh();
        this.add.show(cb);
    }

    this.showAddVersion = function(cb) {
        this.add.reset();
        this.add.child_id = this.file.file_id;
        if( M.curBusiness.modules['ciniki.projects'] != null ) {
            this.add.sections.info.fields.project_id.active = 'yes';
        } else {
            this.add.sections.info.fields.project_id.active = 'no';
        }
        this.add.data = this.file.data;
        this.add.refresh();
        this.add.show(cb);
    }

    this.addFile = function() {
        var f = this.add.formFieldValue(this.add.sections._file.fields.uploadfile, 'uploadfile');
        if( f == null || f == '' ) {
            alert("You must specify a file");
            return false;
        }
        var n = this.add.formFieldValue(this.add.sections.info.fields.name, 'name');
        var v = this.add.formFieldValue(this.add.sections.info.fields.version, 'version');
        if( n == this.add.data.name && v == this.add.data.version ) {
            alert("You must specify a new version or name");
            return false;
        }

        // Add the project if does not already exist
        if( M.curBusiness.modules['ciniki.projects'] != null && this.add.formValue('project_id') == 0 ) {
            var project_name = M.gE(this.add.panelUID + '_project_id_fkidstr').value;
            if( project_name != '' ) {
                var rsp = M.api.getJSONCb('ciniki.projects.add', 
                    {'business_id':M.curBusinessID, 'name':encodeURIComponent(project_name)}, function(rsp) {
                        if( rsp.stat != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        }
                        M.gE(M.ciniki_filedepot_main.add.panelUID + '_project_id').value = rsp.id;
                        M.ciniki_filedepot_main.addFileFinish();
                    });
            } else {
                M.ciniki_filedepot_main.addFileFinish();
            }
        } else {
            M.ciniki_filedepot_main.addFileFinish();
        }
    };

    this.addFileFinish = function() {
        var c = this.add.serializeFormData('yes');
        var rsp = M.api.postJSONFormData('ciniki.filedepot.add', 
            {'business_id':M.curBusinessID, 'child_id':this.add.child_id}, c,
            function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                } else {
                    M.ciniki_filedepot_main.file.file_id = rsp.id;
                    M.ciniki_filedepot_main.add.close();
                }
            });
    };

    this.showFile = function(cb, fid) {
        this.file.reset();
        if( fid != null ) {
            this.file.file_id = fid;
        }
        var rsp = M.api.getJSONCb('ciniki.filedepot.get', 
            {'business_id':M.curBusinessID, 'file_id':this.file.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_filedepot_main.file;

                p.data = rsp.file;
                if( rsp.file.description == null || rsp.file.description == '' ) {
                    p.sections._description.visible = 'no';
                } else {
                    p.sections._description.visible = 'yes';
                }
                if( M.curBusiness.modules['ciniki.projects'] != null ) {
                    p.sections.info.list.project_name.visible = 'yes';
                } else {
                    p.sections.info.list.project_name.visible = 'no';
                }

                p.refresh();
                p.show(cb);
            });
    };

    this.editFile = function(cb, fid) {
        if( fid != null ) {
            this.edit.file_id = fid;
        }
        var rsp = M.api.getJSONCb('ciniki.filedepot.get', 
            {'business_id':M.curBusinessID, 'file_id':this.edit.file_id}, function(rsp) {
                if( rsp.stat != 'ok' ) {
                    M.api.err(rsp);
                    return false;
                }
                var p = M.ciniki_filedepot_main.edit;
                if( M.curBusiness.modules['ciniki.projects'] != null ) {
                    p.sections.info.fields.project_id.active = 'yes';
                } else {
                    p.sections.info.fields.project_id.active = 'no';
                }
                p.data = rsp.file;
                p.refresh();
                p.show(cb);
            });
    };

    this.saveFile = function() {
        var c = this.edit.serializeFormData('no');

        if( c != '' ) {
            var rsp = M.api.postJSONFormData('ciniki.filedepot.update', 
                {'business_id':M.curBusinessID, 'file_id':this.edit.file_id}, c,
                    function(rsp) {
                        if( rsp['stat'] != 'ok' ) {
                            M.api.err(rsp);
                            return false;
                        } else {
                            M.ciniki_filedepot_main.edit.close();
                        }
                    });
        }
    };

    this.deleteFile = function(fid) {
        if( confirm('Are you sure you want to delete \'' + this.file.data.name + '\'?  All information about it will be removed and unrecoverable.') ) {
            var rsp = M.api.getJSONCb('ciniki.filedepot.delete', 
                {'business_id':M.curBusinessID, 'file_id':fid}, function(rsp) {
                    if( rsp.stat != 'ok' ) {
                        M.api.err(rsp);
                        return false;
                    }
                    if( rsp.parent_id > 0 ) {
                        // If deleted the current parent, and there is a new one, show it
                        M.ciniki_filedepot_main.showFile(null,rsp.parent_id);
                        M.ciniki_filedepot_main.edit.destroy();
                    } else if( M.ciniki_filedepot_main.file.file_id != fid ) {
                        // If you deleted an old version, return to current parent
                        M.ciniki_filedepot_main.edit.close();
                    } else {
                        // return to file list
                        M.ciniki_filedepot_main.file.close();
                        M.ciniki_filedepot_main.edit.destroy();
                    }
                });
        }
    };

    this.downloadFile = function(fid) {
        M.api.openFile('ciniki.filedepot.download', {'business_id':M.curBusinessID, 'file_id':fid});
    };
}
