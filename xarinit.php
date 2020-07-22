<?php
/**
 * Articles module
 *
 * @package Xaraya modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub, jojodee
 */
/**
 * initialise the articles module
 * @param void
 * @return bool true
 */
function articles_init()
{
    // Get database information
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

// TODO: Somewhere in the future, status should be managed by a workflow module

    // Create tables
    $articlestable = $xartable['articles'];
/*
    $query = "CREATE TABLE $articlestable (
            xar_aid INT(10) NOT NULL AUTO_INCREMENT,
            xar_title VARCHAR(255) NOT NULL DEFAULT '',
            xar_summary TEXT,
            xar_body TEXT,
            xar_notes TEXT,
            xar_status TINYINT(2) NOT NULL DEFAULT '0',
            xar_authorid INT(11) NOT NULL,
            xar_pubdate INT UNSIGNED NOT NULL,
            xar_pubtypeid INT(4) NOT NULL DEFAULT '1',
            xar_pages INT UNSIGNED NOT NULL,
            xar_language VARCHAR(30) NOT NULL DEFAULT '',
            PRIMARY KEY(xar_aid),
            KEY xar_authorid (xar_authorid),
            KEY xar_pubtypeid (xar_pubtypeid),
            KEY xar_pubdate (xar_pubdate),
            KEY xar_status (xar_status)
            )";
*/
    $fields = array(
        'xar_aid'=>array('type'=>'integer','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_title'=>array('type'=>'varchar','size'=>254,'null'=>FALSE,'default'=>''),
        'xar_summary'=>array('type'=>'text'),
        'xar_body'=>array('type'=>'text'),
        'xar_notes'=>array('type'=>'text'),
        'xar_status'=>array('type'=>'integer','size'=>'tiny','null'=>FALSE,'default'=>'0'),
        'xar_authorid'=>array('type'=>'integer','null'=>FALSE,'default'=>'0'),
        'xar_pubdate'=>array('type'=>'integer','unsigned'=>TRUE,'null'=>FALSE,'default'=>'0'),
        'xar_pubtypeid'=>array('type'=>'integer','size'=>'small','null'=>FALSE,'default'=>'1'),
        'xar_pages'=>array('type'=>'integer','unsigned'=>TRUE,'null'=>FALSE,'default'=>'1'),
        'xar_language'=>array('type'=>'varchar','size'=>30,'null'=>FALSE,'default'=>'')
    );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($articlestable,$fields);
    if (empty($query)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table and send exception if unsuccessful
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
        'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_authorid',
        'fields'    => array('xar_authorid'),
        'unique'    => false
    );
    $query = xarDBCreateIndex($articlestable,$index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
        'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_pubtypeid',
        'fields'    => array('xar_pubtypeid'),
        'unique'    => false
    );
    $query = xarDBCreateIndex($articlestable,$index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
        'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_pubdate',
        'fields'    => array('xar_pubdate'),
        'unique'    => false
    );
    $query = xarDBCreateIndex($articlestable,$index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
        'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_status',
        'fields'    => array('xar_status'),
        'unique'    => false
    );
    $query = xarDBCreateIndex($articlestable,$index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $index = array(
        'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_language',
        'fields'    => array('xar_language'),
        'unique'    => false
    );
    $query = xarDBCreateIndex($articlestable,$index);
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // Create tables
    $pubtypestable = $xartable['publication_types'];
/*
    $query = "CREATE TABLE $pubtypestable (
            xar_pubtypeid INT(4) NOT NULL AUTO_INCREMENT,
            xar_pubtypename VARCHAR(30) NOT NULL,
            xar_pubtypedescr VARCHAR(255) NOT NULL DEFAULT '',
            xar_pubtypeconfig TEXT,
            PRIMARY KEY(xar_pubtypeid))";
*/
    $fields = array(
        'xar_pubtypeid'=>array('type'=>'integer','size'=>'small','null'=>FALSE,'increment'=>TRUE,'primary_key'=>TRUE),
        'xar_pubtypename'=>array('type'=>'varchar','size'=>30,'null'=>FALSE,'default'=>''),
        'xar_pubtypedescr'=>array('type'=>'varchar','size'=>254,'null'=>FALSE,'default'=>''),
        'xar_pubtypeconfig'=>array('type'=>'text')
    );

    // Create the Table - the function will return the SQL is successful or
    // raise an exception if it fails, in this case $query is empty
    $query = xarDBCreateTable($pubtypestable,$fields);
    if (empty($query)) return; // throw back

    // Pass the Table Create DDL to adodb to create the table and send exception if unsuccessful
    $result = $dbconn->Execute($query);
    if (!$result) return;

// TODO: load configuration from file(s) ?

    // Load the initial setup of the publication types
    if (file_exists('modules/articles/xarsetup.php')) {
        include 'modules/articles/xarsetup.php';
    } else {
        // TODO: add some defaults here
        $pubtypes = array();
        $categories = array();
        $settings = array();
        $defaultpubtype = 0;
    }

    // Save publication types
    $pubid = array();
    foreach ($pubtypes as $pubtype) {
        list($id,$name,$descr,$config) = $pubtype;
        $nextId = $dbconn->GenId($pubtypestable);
        $query = "INSERT INTO $pubtypestable
                (xar_pubtypeid, xar_pubtypename, xar_pubtypedescr,
                 xar_pubtypeconfig)
                VALUES (?,?,?,?)";
        $bindvars = array($nextId, $name, $descr, $config);
        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) return;
        $ptid = $dbconn->PO_Insert_ID($pubtypestable, 'xar_pubtypeid');
        $pubid[$id] = $ptid;
    }

    // Create articles categories
    $cids = array();
    foreach ($categories as $category) {
        $cid[$category['name']] = xarModAPIFunc('categories',
                                               'admin',
                                               'create',
                        Array('name' => $category['name'],
                              'description' => $category['description'],
                              'parent_id' => 0));
        foreach ($category['children'] as $child) {
            $cid[$child] = xarModAPIFunc('categories',
                                        'admin',
                                        'create',
                        Array('name' => $child,
                              'description' => $child,
                              'parent_id' => $cid[$category['name']]));
        }
    }

    // Set up module variables
    xarModSetVar('articles', 'SupportShortURLs', 1);

    // Save articles settings for each publication type
    foreach ($settings as $id => $values) {
        if (isset($pubid[$id])) {
            $id = $pubid[$id];
        }
        // replace category names with cids
        if (isset($values['categories'])) {
            $cidlist = array();
            foreach ($values['categories'] as $catname) {
                if (isset($cid[$catname])) {
                    $cidlist[] = $cid[$catname];
                }
            }
            unset($values['categories']);
            if (!empty($id)) {
                xarModSetVar('articles', 'number_of_categories.'.$id, count($cidlist));
                xarModSetVar('articles', 'mastercids.'.$id, join(';',$cidlist));
            } else {
                xarModSetVar('articles', 'number_of_categories', count($cidlist));
                xarModSetVar('articles', 'mastercids', join(';',$cidlist));
            }
        } elseif (!empty($id)) {
            xarModSetVar('articles', 'number_of_categories.'.$id, 0);
            xarModSetVar('articles', 'mastercids.'.$id, '');
        } else {
            xarModSetVar('articles', 'number_of_categories', 0);
            xarModSetVar('articles', 'mastercids', '');
        }
        if (isset($values['defaultview']) && !is_numeric($values['defaultview'])) {
            if (isset($cid[$values['defaultview']])) {
                $values['defaultview'] = 'c' . $cid[$values['defaultview']];
            } else {
                $values['defaultview'] = 1;
            }
        }
        if (!empty($id)) {
            xarModSetVar('articles', 'settings.'.$id,serialize($values));
        } else {
            xarModSetVar('articles', 'settings',serialize($values));
        }
    }

    // Set default publication type
    xarModSetVar('articles', 'defaultpubtype', $defaultpubtype);

    // Enable/disable full-text search with MySQL (for all pubtypes and all text fields)
    xarModSetVar('articles', 'fulltextsearch', '');

    // Register blocks
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'related'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'topitems'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'featureditems'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'random'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'register_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'glossary'))) return;

    if (!xarModRegisterHook('item', 'search', 'GUI',
                           'articles', 'user', 'search')) {
        return false;
    }

    if (!xarModRegisterHook('item', 'waitingcontent', 'GUI',
                           'articles', 'admin', 'waitingcontent')) {
        return false;
    }

// TODO: move this to some common place in Xaraya (base module ?)
    // Register BL tags
    xarTplRegisterTag('articles', 'articles-field',
                      //array(new xarTemplateAttribute('bid', XAR_TPL_STRING|XAR_TPL_REQUIRED)),
                      array(),
                      'articles_userapi_handleFieldTag');

    // Enable articles hooks for search
    if (xarModIsAvailable('search')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'search', 'hookModName' => 'articles'));
    }

    // Enable categories hooks for articles
    xarModAPIFunc('modules','admin','enablehooks',
                  array('callerModName' => 'articles', 'hookModName' => 'categories'));

    // Enable comments hooks for articles
    if (xarModIsAvailable('comments')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'hookModName' => 'comments'));
    }
    // Enable hitcount hooks for articles
    if (xarModIsAvailable('hitcount')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'hookModName' => 'hitcount'));
    }
    // Enable ratings hooks for articles
    if (xarModIsAvailable('ratings')) {
        xarModAPIFunc('modules','admin','enablehooks',
                      array('callerModName' => 'articles', 'hookModName' => 'ratings'));
    }

    /*********************************************************************
    * Define instances for the core modules
    * Format is
    * xarDefineInstance(Module,Component,Querystring,ApplicationVar,LevelTable,ChildIDField,ParentIDField)
    *********************************************************************/
    $xartable = xarDBGetTables();
    $instances = array(
                       array('header' => 'external', // this keyword indicates an external "wizard"
                             'query'  => xarModURL('articles', 'admin', 'privileges'),
                             'limit'  => 0
                            )
                    );
    xarDefineInstance('articles', 'Article', $instances);

    $query = "SELECT DISTINCT instances.xar_title FROM $xartable[block_instances] as instances LEFT JOIN $xartable[block_types] as btypes ON btypes.xar_id = instances.xar_type_id WHERE xar_module = 'articles'";
    $instances = array(
                        array('header' => 'Article Block Title:',
                                'query' => $query,
                                'limit' => 20
                            )
                    );
    xarDefineInstance('articles','Block',$instances);

// TODO: pubtype ?

    /*********************************************************************
    * Register the module components that are privileges objects
    * Format is
    * xarregisterMask(Name,Realm,Module,Component,Instance,Level,Description)
    *********************************************************************/

    xarRegisterMask('ViewArticles','All','articles','Article','All','ACCESS_OVERVIEW');
    xarRegisterMask('ReadArticles','All','articles','Article','All','ACCESS_READ');
    xarRegisterMask('SubmitArticles','All','articles','Article','All','ACCESS_COMMENT');

    xarRegisterMask('EditArticles','All','articles','Article','All','ACCESS_EDIT');
    // Submitting articles only requires COMMENT privileges, not ADD privileges
    // xarRegisterMask('AddArticles','All','articles','Article','All','ACCESS_ADD');
    xarRegisterMask('DeleteArticles','All','articles','Article','All','ACCESS_DELETE');
    xarRegisterMask('AdminArticles','All','articles','Article','All','ACCESS_ADMIN');
    xarRegisterMask('ReadArticlesBlock','All','articles','Block','All','ACCESS_READ');

    // Initialisation successful and complete to version 1.5.1 now continue at 1.5.1 for any new or upgraded code
    return articles_upgrade('1.5.1');
}

/**
 * upgrade the articles module from an old version
 * @param string $oldversion The former version number to upgrade from
 * @return bool
 */
function articles_upgrade($oldversion)
{
    $dbconn = xarDBGetConn();
    $xarTables = xarDBGetTables();
    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();
    // Upgrade dependent on old version number
    switch($oldversion) {
        case '1.4':
            // Get current publication types
            $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
            // Get configurable fields for articles
            $pubfields = xarModAPIFunc('articles','user','getpubfields');
            // Update the configuration of each publication type
            foreach ($pubtypes as $ptid => $pubtype) {
                // Map the (bodytext + bodyfile) fields to a single body field
                // + use the textupload format if relevant
                $pubtype['config']['body'] = $pubtype['config']['bodytext'];
                if (!empty($pubtype['config']['bodyfile']['label'])) {
                    $pubtype['config']['body']['format'] = 'textupload';
                    if (empty($pubtype['config']['body']['label'])) {
                        $pubtype['config']['body']['label'] = $pubtype['config']['bodyfile']['label'];
                    }
                }
                $config = array();
                foreach (array_keys($pubfields) as $field) {
                    $config[$field] = $pubtype['config'][$field];
                }
                if (!xarModAPIFunc('articles', 'admin', 'updatepubtype',
                                   array('ptid' => $ptid,
                                   //      'name' => $name, /* not allowed here */
                                         'descr' => $pubtype['descr'],
                                         'config' => $config))) {
                    return false;
                }
            }

        // no upgrade for random block here - you can register it via blocks admin
        case '1.5':
        case '1.5.0':
            // Upgrade the glossary block - we'll be kind :-)
            if (!xarModAPIFunc(
                'blocks', 'admin', 'register_block_type',
                array(
                    'modName'  => 'articles',
                    'blockType'=> 'glossary'
                )
            )) {return;}

        case '1.5.1':
            // Code to upgrade from version 1.5.1 goes here

            // Enable/disable full-text search with MySQL (for all pubtypes and all text fields)
            xarModSetVar('articles', 'fulltextsearch', '');

            if (!xarModRegisterHook('item','create','API','articles','admin','createhook')) {
                return false;
            }

/* skip for now...
            // Get database information
            $dbconn = xarDBGetConn();
            $xartable = xarDBGetTables();

            //Load Table Maintainance API
            xarDBLoadTableMaintenanceAPI();

            $articlestable = $xartable['articles'];

            $index = array(
                'name'      => 'i_' . xarDBGetSiteTablePrefix() . '_articles_language',
                'fields'    => array('xar_language'),
                'unique'    => false
            );
            $query = xarDBCreateIndex($articlestable,$index);
            $result = $dbconn->Execute($query);
            if (!$result) return;
*/

        case '1.5.2':

            $articlesTable = $xarTables['articles'];
             $query = xarDBAlterTable( $articlesTable,
                              array('command' => 'add',
                                    'field'   => 'xar_checkout',
                                    'type'    => 'integer',
                                    'size'    => 'tiny',
                                    'null'    => TRUE,
                                    'default' => '0'));
            // Pass to ADODB, and send exception if the result isn't valid.
            $result = $dbconn->Execute($query);
            //next ...
                $query2 = xarDBAlterTable( $articlesTable,
                              array('command' => 'add',
                                    'field'   => 'xar_checkouttime',
                                    'type'    => 'integer',
                                    'unsigned' => TRUE,
                                    'null'    => TRUE,
                                    'default' => '0'));
                 $result = $dbconn->Execute($query2);
             //next ...
                 $query3 = xarDBAlterTable( $articlesTable,
                              array('command' => 'add',
                                    'field'   => 'xar_editor',
                                    'type'    => 'integer',
                                    'unsigned' => TRUE,
                                    'null'    => TRUE,
                                    'default' => '0'));
                 $result = $dbconn->Execute($query3);


            /* Get a data dictionary object with all the item create methods in it */
            /* jojo - we can't use this it will fail on some dbtypes
            $datadict = xarDBNewDataDict($dbconn, 'ALTERTABLE');

            // Add a few more fields
            $fields= "xar_checkout        I1     NotNull    DEFAULT 0,
                      xar_checkouttime    I      NotNull    DEFAULT 0,
                      xar_editor          I      NotNull    DEFAULT 0
                     ";
            $result = $datadict->changeTable($articlesTable , $fields);
            if (!$result) {return;}
            */

        case '1.5.3':

    //now update the settings
            $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
            $newfields = array(
                'checkout'     => array('label'  => '',
                                         'format' => 'textbox',
                                         'input'  => 0),
                'checkouttime' => array('label'  => '',
                                        'format' => 'calendar',
                                        'input'  => 0),
                'editor'       => array('label'  => '',
                                        'format' => 'username',
                                        'input'  => 0)

                       );
            $newconfigs = array();
            foreach ($pubtypes as $pubtype=>$fields) {
                $config = $fields['config'];
                $config = array_merge($config,$newfields);
                $newconfigs[]= array('ptid'=>$pubtype,'config'=>serialize($config));
            }

            $dbconn = xarDBGetConn();
            $xartable = xarDBGetTables();
            $pubtypestable = $xartable['publication_types'];
            foreach ($newconfigs as $pubconfig) {
                // Update the publication type (don't allow updates on name)
                $query = "UPDATE $pubtypestable
                SET xar_pubtypeconfig = ?
                WHERE xar_pubtypeid = ?";
                $bindvars = array($pubconfig['config'], $pubconfig['ptid']);
                $result = $dbconn->Execute($query,$bindvars);
            if (!$result) return;
            }

        case '1.5.4':
           //get rid of the createhook - wrong accidentally added from another scenario
           xarModUnRegisterHook('item','create','API','articles','admin','createhook');
        case '1.5.5':
           xarRemoveInstances('articles','Block');
           xarModSetVar('articles', 'ptypenamechange', '0');
        case '1.5.6':
        //allows editing and approval of articles
        xarRegisterMask('ModerateArticles','All','articles','Article','All','ACCESS_MODERATE');
        case '1.5.7':
            xarModSetVar('articles','changepubtype',false);
        case '1.5.8':
            //update 3rd point to signify addition of changepubtype
        case '1.5.9':

        case '1.6.0':
            xarModSetVar('articles','archiveaccess',4);
            xarModSetVar('articles','autoarchive',serialize(array(-1)));
            xarModSetVar('articles','usearchiving',false);
            xarModSetVar('articles','archiveage',0);
        case '1.6.1':
            //register mask which for some reason was commented out in the original install
            xarRegisterMask('AddArticles','All','articles','Article','All','ACCESS_ADD');
        case '1.6.2':
        case '1.6.3':
            //update to signify code changes to cater for new property validations
            //get the existing validations
            $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
            foreach ($pubtypes as $ptid=>$pubtype)
            {

                //now get get each field
                foreach ($pubtype['config'] as $field => $typeinfo)
                {
                    //get validation and check if serialized
                    $validation = isset($typeinfo['validation'])?$typeinfo['validation']:'';
                    $fieldformat = $typeinfo['format'];
                    if (!empty($validation)) {
                       //check to see if it's serialized
                        try {
                            $check = @unserialize($validation);
                        } catch (Exception $e) {
                            //do nothing
                        }
                        $serialized =  ($check===false && $validation  != serialize(false)) ? false : true;
                        $newvalidation= '';
                        if ($serialized === FALSE)
                        { //then we need to process it

                            switch ($fieldformat)
                            {

                                 case 'grouplist':
                                     $newvalidation = updateFileGroupValidations('grouplist', $validation);
                                    break;
                                case 'userlist':
                                     $newvalidation = updateFileGroupValidations('userlist', $validation);
                                    break;
                                case 'status':
                                case 'calendar':
                                     $newvalidation = updateFileGroupValidations('calendar', $validation);
                                    break;
                                case 'textarea':
                                case 'textarea_small':
                                case 'textarea_medium':
                                case 'textarea_large':
                                      $newvalidation = updateFileGroupValidations('textarea', $validation);
                                    break;
                                case 'urlicon':
                                    $newvalidation = updateFileGroupValidations('urlicon', $validation);
                                    break;
                                case 'url':
                                case 'urltitle':
                                case 'image':
                                case 'textbox':
                                    $newvalidation = updateFileGroupValidations('textbox', $validation);
                                    break;
                                case 'floatbox':
                                case 'numberbox':
                                    $newvalidation = updateFileGroupValidations('numberbox', $validation);
                                    break;
                                case 'webpage':
                                case 'imagelist':
                                case 'filelist':
                                    //eg stripsd|stripsd;(gif|jpg|jpeg|png)
                                    $newvalidation = updateFileGroupValidations('imagelist', $validation);
                                    break;
                                case 'textupload':
                                case 'fileupload':
                                      $newvalidation = updateFileGroupValidations('fileupload', $validation);
                                    break;
                                case 'email':
                                    break;
                                case 'checkboxlist':
                                case 'dropdown':
                                     $newvalidation = updateFileGroupValidations('dropdown', $validation);
                                    break;
                                default:
                                    break;
                            }
                            if (!empty($newvalidation))
                            {
                                //the new field configuration for $field of $pubtype
                                $pubtype['config'][$field]['validation'] = $newvalidation;
                            }
                        }
                    } //end validation fix for pubconfig field
                    //now update the pubtype
                    $updatepub = xarModAPIFunc('articles','admin','updatepubtype',
                                                array('ptid'=>$ptid,
                                                    'name'=>$pubtype['name'],
                                                    'descr'=>$pubtype['descr'],
                                                    'config'=>$pubtype['config']
                                                    )
                                                );

                } //end pubtype check
            }
         case '1.7.1': 
         case '1.7.3' : 
          /* Register hooks that we are providing to other modules. */
            if (!xarModRegisterHook('item', 'usermenu', 'GUI','articles', 'user', 'usermenu')) {
                return false;
            }
        case '1.7.5' : //current

    }
    return true;
}
//convert validations for imagelist, file upload, filelist core 1.3.5 -> core 1.4.0
function updateFileGroupValidations($fieldtype, $validation)
{

    $defaultval = array ('xv_allowempty' => 1,
                         'xv_display_layout' => 'default');
    //no uploads hooked
    //let's fix it
    switch ($fieldtype)
    {
        case 'dropdown':
                if (is_array($validation)) {
                    $optionlist = '';
                    foreach($validation as $id => $name) {
                         $optionlist .= $id.','.$name.';';
                    }
                    $defaultval['xv_optionlist'] = $optionlist;

                // if the validation field starts with xarModAPIFunc
                } elseif (preg_match('/^xarModAPIFunc/i',$validation)) {
                      $defaultval['xv_func'] = $validation;

                } elseif (strchr($validation,';') || strchr($validation,',')) {
                    $defaultval['xv_optionlist'] = $validation;

                // or if it contains a data file path
                } elseif (preg_match('/^{file:(.*)}/',$validation, $fileMatch)) {
                     $defaultval['xv_file'] =  $fileMatch[1];

                } elseif (!empty($validation)) {
                    $defaultval['xv_other'] = $validation;
                }
            break;
        case 'email':
            $oldval = explode(';',$validation);
            if (isset($oldval[0])) {
                $min = explode(':',$oldval[0]);
                if (isset($min[1])) $defaultval['xv_min_length']   = $min[1];
            }
            if (isset($oldval[1])) {
                $max = explode(':',$oldval[1]);
                if (isset($max[1])) $defaultval['xv_max_length']   = $max[1];
            }
            if (isset($oldval[2])) {
                $reg = explode(':',$oldval[2]);
                if (isset($reg[1])) $defaultval['xv_pattern']   = $reg[1];
            }
            if (isset($oldval[3])) {
                $ob = explode(':',$oldval[3]);
                if (isset($ob[1])) $defaultval['xv_obfuscate']   = $ob[1];
            }
            if (isset($oldval[4])) {
                $txt = explode(':',$oldval[4]);
                if (isset($txt[1])) $defaultval['xv_linktext']   = $txt[1];
            }
            if (isset($oldval[5])) {
                $img = explode(':',$oldval[5]);
                if (isset($img[1])) $defaultval['xv_useimage']   = $img[1];
            }

            break;
        case 'calendar':
         $defaultval['xv_dbformat'] = isset($validation) && !empty($validation) ? $validation : 'timestamp';
            break;
        case 'urlicon':
              $defaultval['xv_size']          = 10;
             if (isset($validation)) $defaultval['xv_icon_url']   = $validation;
             break;
        case 'numberbox':
            $defaultval['xv_size']          = 10;
             $oldval = explode(':',$validation);
                if (isset($oldval[0])) $defaultval['xv_min']   = $oldval[0];
                if (isset($oldval[1])) $defaultval['xv_max']   = $oldval[1];
                if (isset($oldval[2])) $defaultval['xv_other'] = $oldval[2];
                break;
        case 'textarea':
            $oldval = explode(':',$validation);
            if (isset($oldval[0])) $defaultval['xv_rows']   = $oldval[0];
            if (isset($oldval[1])) $defaultval['xv_cols']       = $oldval[1];
            if (isset($oldval[2])) $defaultval['xv_classname'] = $oldval[2];

            if (isset($oldval[3])) {
                if (substr($oldval[3],0,8) == 'maxlength') { //special case
                    $defaultval['xv_max_length']  = $oldval[3];
                } else {
                    $defaultval['xv_other']     = $oldval[3];
                }
            }
            break;
        case 'textbox':
             $defaultval['xv_size']          = 50;
            $oldval = explode(':',$validation);
                if (isset($oldval[0])) $defaultval['xv_min_length'] = $oldval[0];
                if (isset($oldval[1])) $defaultval['xv_max_length'] = $oldval[1];
                if (isset($oldval[2])) $defaultval['xv_pattern'] = $oldval[2];
                if (isset($oldval[3])) $defaultval['xv_other'] = $oldval[3];
            break;
        case 'imagelist':
            $validations = explode(';',$validation);
            $dirvalidations = isset($validations[0]) ?$validations[0]:'';
            $filevalidations = isset($validations[1]) ?$validations[1]:'';

            if (strpos($dirvalidations,'|') !== false) {
                $parts = explode('|',$dirvalidations);
                $defaultval['xv_basedir'] = isset($parts[0])? $parts[0] :'';
            } else {
                $defaultval['xv_basedir']  = $dirvalidations;
            }
            if (isset( $filevalidations) && !empty( $filevalidations) && substr( $filevalidations,0,1) == '(' )
            {
                 $filevalidations = trim( $filevalidations,'()');
                  $filevalidations = explode('|',  $filevalidations);

                $defaultval['xv_file_ext'] = implode(',', $filevalidations);
            } else {
                $defaultval['xv_file_ext']  = '';
            }
            if (isset($validations[2]))  $defaultval['xv_max_file_size'] = trim($validations[2]);
            $display = isset($validations[3]) ?$validations[3]:false;
            $defaultval['xv_display'] = isset($display) ? $display : FALSE;
            $defaultval['xv_display_width'] =100;
            break;
        case 'userlist':
            if (preg_match('/^xarModAPIFunc/i',$validation)) {
                     $defaultval['xv_func'] = $validation;
            } else {
                foreach(preg_split('/(?<!\\\);/', $validation) as $option) {
                    // Semi-colons can be escaped with a '\' prefix.
                    $option = str_replace('\;', ';', $option);
                    // An option comes in two parts: option-type:option-value
                    if (strchr($option, ':')) {
                        list($option_type, $option_value) = explode(':', $option, 2);
                        if ($option_type == 'state' && is_numeric($option_value)) {
                           $defaultval['xv_userstate']  = $option_value;
                        }

                        if ($option_type == 'group') {
                             $defaultval['xv_grouplist'] =  explode(',', $option_value);
                        }
                        if ($option_type == 'show') {
                            $showlist = explode(',', $option_value);
                            // Remove invalid elements (fields that are not valid).
                            $showfilter = create_function(
                                '$a', 'return preg_match(\'/^[-]?(name|uname|email|uid|state|date_reg)$/\', $a);'
                            );
                             $defaultval['xv_showfields'] = array_filter($showlist, $showfilter);
                        }
                        if ($option_type == 'order') {
                             $defaultval['xv_orderlist'] = explode(',', $option_value);
                        }
                    }
                }
            }
            break;
        case 'grouplist':
            $defaultval['xv_ancestorgrouplist'] = '';
            $defaultval['xv_parentgrouplist'] ='';
            $defaultval['xv_grouplist'] = '';
            //let's fix it
            if (!empty($validation)) {
                foreach(preg_split('/(?<!\\\);/', $validation) as $option) {
                    // Semi-colons can be escaped with a '\' prefix.
                    $option = str_replace('\;', ';', $option);
                    // An option comes in two parts: option-type:option-value
                    if (strchr($option, ':')) {
                        list($option_type, $option_value) = explode(':', $option, 2);
                        $option_type = trim($option_type);
                        if ($option_type == 'ancestor') {
                            $defaultval['xv_ancestorgrouplist'] = array_merge($defaultval['xv_ancestorgrouplist'], explode(',', $option_value));
                        }
                        if ($option_type == 'parent') {
                             $defaultval['xv_parentgrouplist'] = array_merge($defaultval['xv_parentgrouplist'], explode(',', $option_value));
                        }
                        if ($option_type == 'group') {
                             $defaultval['xv_grouplist'] = array_merge( $defaultval['xv_grouplist'], explode(',', $option_value));
                        }
                    }
                }
            }
            break;
        case 'fileupload':
            if (!empty($validation)) {
                $fields = explode(';', $validation);
                if (isset($fields[0]) && trim($fields[0]) != '') {
                    $prop_path = rtrim(trim($fields[0]), '/');
                   $defaultval['xv_basedir'] = $prop_path;
                } else {
                    // No base directory supplied, so default to '{var}/uploads', with no basedir.
                    $defaultval['xv_basedir'] = '{var}/uploads';
                }

                // TODO: allow descendant class to override filetype.
                if (isset($fields[1]) && !empty($fields[1]) && substr($fields[1],0,1) == '(' )
                {
                    $fields[1] = trim( $fields[1],'()');
                     $fields[1] = explode('|', $fields[1]);

                    $defaultval['xv_file_ext'] = implode(',',$fields[1]);
                } else {
                    $defaultval['xv_file_ext']  = '';
                }
                if (isset($fields[2]))  $defaultval['xv_max_file_size'] = trim($fields[2]);
                $display = isset($fields[3]) ?$fields[3]:false;
                $defaultval['xv_display'] = isset($display) ? $display : FALSE;
                $defaultval['xv_display_width'] =100;
            }
            break;
        default:
            break;
    }

        $newvalidation = serialize($defaultval);
    return $newvalidation;
}


/**
 * delete the articles module
 */
function articles_delete()
{
    // Get database information
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();

    //Load Table Maintainance API
    xarDBLoadTableMaintenanceAPI();

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['articles']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);
    if (!$result) return;

    // Generate the SQL to drop the table using the API
    $query = xarDBDropTable($xartable['publication_types']);
    if (empty($query)) return; // throw back

    // Drop the table and send exception if returns false.
    $result = $dbconn->Execute($query);
    if (!$result) return;

  // UnRegister blocks
    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'related'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'topitems'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'featureditems'))) return;

    if (!xarModAPIFunc('blocks',
                       'admin',
                       'unregister_block_type',
                       array('modName'  => 'articles',
                             'blockType'=> 'glossary'))) return;


   // TODO: remove entries from categories_linkage !

    // Delete module variables

    //FIXME: This is breaking the removal of the module...

    xarModDelAllVars('articles');

    // Unregister BL tags
    xarTplUnregisterTag('articles-field');

    /**
     * Remove instances
     */

    // Remove Masks and Instances
    xarRemoveMasks('articles');
    xarRemoveInstances('articles');


    // Deletion successful
    return true;
}

?>