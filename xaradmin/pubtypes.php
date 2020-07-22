<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * manage publication types (all-in-one function for now)
 */
function articles_admin_pubtypes()
{
    // Get parameters
    if (!xarVarFetch('ptid',   'isset', $ptid,   NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('action', 'isset', $action, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('name',   'isset', $name,   NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('descr',  'isset', $descr,  NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('label',  'isset', $label,  NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('format', 'isset', $format, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('input',  'isset', $input,  array(), XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('invalid', 'isset', $invalid,  array(), XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('validation',  'isset', $validation,  NULL, XARVAR_DONT_SET)) {return;}

    // Publication types can only be managed with ADMIN rights
    if (empty($ptid)) {
        $ptid = '';
        if (!xarSecurityCheck('AdminArticles')) return xarResponseForbidden();
    } else {
        if (!xarSecurityCheck('AdminArticles',1,'Article',"$ptid:All:All:All")) return xarResponseForbidden();
    }
    if (!isset($action)) {
        xarSessionSetVar('statusmsg', '');
    }
    // Initialise the template variables
    $data = array();
    $data['pubtypes'] = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    // Get current publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    // Verify the action
    if (!isset($action) || ($action != 'new' && $action != 'create' &&
                            (empty($ptid) || !isset($pubtypes[$ptid])))) {
        $action = 'view';
    }
    //set a list of DD property types that we allow validation editing for default fields
    //not all of these will be available until we add them in the getpubfieldformats AND check
    //for working validation.
    $data['valcheck'] = array('textbox','textarea_large','textarea_medium','textarea_small',
                                 'dropdown','imagelist','webpage','fileupload','textupload',
                                 'url','image','integerbox','integerlist',
                                 'floatbox','urlicon','date
                                 ','radio','imagelist','urltitle',
                                 'objectref','bltemplate','array','subform','email','userlist','grouplist',
                                 'password','xartinymce','checkbox');

    // Take action if necessary
    if ($action == 'create' || $action == 'update' || $action == 'confirm') {
        $invalid = array();
        // Confirm authorisation code
        if (!xarSecConfirmAuthKey()) return;

        if ($action == 'create') {
            if (!isset($name) || empty($name)) {
                $invalid['name'] =  xarML('Pubtype name must not be empty.');
            }
            if (!isset($descr) || empty($descr)) {
                $invalid['descr'] =  xarML('Pubtype description must not be empty.');
            }

            // check if we have any errors
            if (count($invalid) > 0) {
                xarResponseRedirect(xarModURL('articles', 'admin', 'pubtypes',
                                  array('action' => 'new',
                                        'name' => $name,
                                        'descr' => $descr,
                                        'format'=>$format,
                                        'validation'=>$validation,
                                        'invalid' => $invalid))
                                        );
                                        return;
            }

            $config = array();
            foreach ($label as $field => $value) {
                $config[$field]['label'] = $value;
            }
            foreach ($format as $field => $value) {
                $config[$field]['format'] = $value;

                // some default basedirs for now...
                if (isset($validation[$field])) {
                    $config[$field]['validation'] = $validation[$field];
                } elseif ($value == 'imagelist') {
                    $config[$field]['validation'] = '';
                } elseif ($value == 'webpage') {
                    $config[$field]['validation'] = '';
                }
            }
            foreach ($input as $field => $value) {
                $config[$field]['input'] = 1;
            }
            $ptid = xarModAPIFunc('articles', 'admin', 'createpubtype',
                                 array('name' => $name,
                                       'descr' => $descr,
                                       'config' => $config));
            if (empty($ptid)) {
                return; // throw back
            } else {
                if (empty($config['status']['label'])) {
                    $status = 2;
                } else {
                    $status = 0;
                }
                $settings = array('number_of_columns'    => 0,
                                  'itemsperpage'         => 20,
                                  'defaultview'          => 1,
                                  'showcategories'       => 1,
                                  'showcatcount'         => 0,
                                  'showprevnext'         => 0,
                                  'showcomments'         => 1,
                                  'showhitcounts'        => 1,
                                  'showratings'          => 0,
                                  'showmonthview'         => 1,
                                  'showmap'              => 1,
                                  'showpublinks'         => 0,
                                  'showpubcount'         => 0,
                                  'dotransform'          => 0,
                                  'titletransform'       => 0,
                                  'prevnextart'          => 0,
                                  'usealias'             => 0,
                                  'page_template'        => '',
                                  'usetitleforurl'       => 0,
                                  'defaultstatus'        => $status,
                                  'defaultsort'          => 'date');
                xarModSetVar('articles', 'settings.'.$ptid,serialize($settings));
                xarModSetVar('articles', 'number_of_categories.'.$ptid, 0);
                xarModSetVar('articles', 'mastercids.'.$ptid, '');

                // Redirect to the admin view page
                $msg = xarML('Publication type "#(1)"was successfully created.',$name);
                xarTplSetMessage($msg,'status');
                xarResponseRedirect(xarModURL('articles', 'admin', 'pubtypes',
                                              array('action' => 'view')));
                return true;
            }
        } elseif ($action == 'update') {
            $config = array();

            foreach ($label as $field => $value) {
                $config[$field]['label'] = $value;
            }

            foreach ($format as $field => $value) {
                $config[$field]['format'] = $value;
                // some default basedirs for now...
                 //fix for nasty bug that removes validation on update
                //if (isset($validation[$field]) && !empty($validation[$field])) {
                    //$config[$field]['validation'] = $validation[$field];
                if (isset($pubtypes[$ptid]['config'][$field]['validation'])) {

                    $config[$field]['validation'] =$pubtypes[$ptid]['config'][$field]['validation'];
                /*} elseif ($value == 'imagelist') {
                    $config[$field]['validation'] = 'modules/articles/xarimages';
                } elseif ($value == 'webpage') {
                    $config[$field]['validation'] = 'modules/articles';*/
                }
            }
            foreach ($input as $field => $value) {
                $config[$field]['input'] = 1;
            }

            if (!xarModAPIFunc('articles', 'admin', 'updatepubtype',
                              array('ptid' => $ptid,
                                    'name' => $name,
                                    'descr' => $descr,
                                    'config' => $config))) {
                return; // throw back
            } else {
                // Redirect back to the admin modify page to continue editing publication type
                 $msg = xarML('Publication type "#(1)" was successfully updated',$name);
                xarTplSetMessage($msg,'status');
                xarResponseRedirect(xarModURL('articles', 'admin', 'pubtypes',array('ptid'=>$ptid,'action' => 'modify')));
                return true;
            }
        } elseif ($action == 'confirm') {

            if (!xarModAPIFunc('articles', 'admin','deletepubtype',
                              array('ptid' => $ptid))) {
                $msg = xarML('There was a problem deleting publication type "#(1)". It was not deleted.',$name);
                xarTplSetMessage($msg,'status');
                return; // throw back
            } else {
                xarModDelVar('articles', 'settings.'.$ptid);
                xarModDelAlias($pubtypes[$ptid]['name'],'articles');
                xarModDelVar('articles', 'number_of_categories.'.$ptid);
                xarModDelVar('articles', 'mastercids.'.$ptid);
                $default = xarModGetVar('articles','defaultpubtype');
                if ($ptid == $default) {
                    xarModSetVar('articles','defaultpubtype','');
                }

                // Redirect to the admin view page
                 $msg = xarML('Publication type "#(1)" was deleted.',$name);
                xarTplSetMessage($msg,'status');
                xarResponseRedirect(xarModURL('articles', 'admin', 'pubtypes',
                                              array('action' => 'view')));
                return true;
            }
        }
    }

    // Create Edit/Delete/Modify Config links for each pubtype and
    // View/New links for articles of these pubtypes
    $authid = xarSecGenAuthKey();

    foreach ($pubtypes as $id => $pubtype) {
        if (!xarSecurityCheck('AdminArticles',0,'Article',"$id:All:All:All")) {
            $pubtypes[$id]['editurl'] = '';
            $pubtypes[$id]['deleteurl'] = '';
            $pubtypes[$id]['configurl'] = '';
            $pubtypes[$id]['viewurl'] = '';
            $pubtypes[$id]['addurl'] = '';
            continue;
        }
        $pubtypes[$id]['editurl']   = xarModURL('articles', 'admin', 'pubtypes',
                                             array('ptid' => $id,
                                                   'action' => 'modify'));
        $pubtypes[$id]['deleteurl'] = xarModURL('articles', 'admin', 'pubtypes',
                                               array('ptid' => $id,
                                                     'action' => 'delete',
                                                     'authid' => $authid));
        $pubtypes[$id]['configurl'] = xarModURL('articles', 'admin', 'modifyconfig',
                                               array('ptid' => $id));
        $pubtypes[$id]['viewurl']   = xarModURL('articles', 'admin', 'view',
                                               array('ptid' => $id));
        $pubtypes[$id]['addurl']    = xarModURL('articles', 'admin', 'new',
                                               array('ptid' => $id));
    }
    $data['pubtypes'] = $pubtypes;
    $data['newurl'] = xarModURL('articles', 'admin', 'pubtypes',
                               array('action' => 'new'));

/*
    // Get the list of defined field formats
    $pubfieldformats = xarModAPIFunc('articles','user','getpubfieldformats');
    $data['formats'] = array();
    foreach ($pubfieldformats as $fname => $flabel) {
        $data['formats'][] = array('fname' => $fname, 'flabel' => $flabel);
    }
*/
    // Fill in relevant variables
    if ($action == 'new') {
        $data['authid'] = xarSecGenAuthKey();
        $data['buttonlabel'] = xarML('Create Publication Type');
        $data['link'] = xarModURL('articles','admin','pubtypes',
                                 array('action' => 'create'));

        $data['fields'] = array();
        $pubfieldtypes = xarModAPIFunc('articles','user','getpubfieldtypes');
        // Fill in the *default* configuration fields
        $pubfields = xarModAPIFunc('articles','user','getpubfields');
        foreach ($pubfields as $field => $value) {
            $data['fields'][] = array('name'   => $field,
                                      'label'  => $value['label'],
                                      'format' => $value['format'],
                                      'validation' => !empty($value['validation']) ? $value['validation'] : '',
                                      'type'   => $pubfieldtypes[$field],
                                      'input'  => !empty($value['input']) ? 'checked="checked" ' : '',
                                      'checked' => !empty($value['input']) ? 'checked' : ''
                                      );
        }
    } elseif ($action == 'modify') {
        $data['item'] = $pubtypes[$ptid];
        $data['authid'] = xarSecGenAuthKey();
        $data['buttonlabel'] = xarML('Modify publication definition');
        $data['link'] = xarModURL('articles','admin','pubtypes',
                                 array('action' => 'update'));

        $data['fields'] = array();
        $pubfieldtypes = xarModAPIFunc('articles','user','getpubfieldtypes');
        // Fill in the *current* configuration fields
    // TODO: make order dependent on pubtype or not ?
    //    foreach ($pubtypes[$ptid]['config'] as $field => $value) {
        $pubfields = xarModAPIFunc('articles','user','getpubfields');
        foreach ($pubfields as $field => $dummy) {
           if (isset($pubtypes[$ptid]['config'][$field])) {
                $value = $pubtypes[$ptid]['config'][$field];
          //  } elseif (in_array($field,array('checkout','checkouttime','editor'))) {
         //       $value = array('label'=>'','format'=>'textbox','input'=>0);
            }

            $data['fields'][] = array('name'   => $field,
                                      'label'  => $value['label'],
                                      'format' => $value['format'],
                                      'validation' => !empty($value['validation']) ? $value['validation'] : '',
                                      'type'   => $pubfieldtypes[$field],
                                      'input'  => !empty($value['input']) ? 'checked="checked" ' : '', //backward compat
                                      'checked' => !empty($value['input']) ? 'checked' : '',

                                      );
        }
    } elseif ($action == 'delete') {
        $data['item'] = $pubtypes[$ptid];
        $data['authid'] = xarSecGenAuthKey();
        $data['buttonlabel'] = xarML('Delete');
        $data['numitems'] = xarModAPIFunc('articles','user','countitems',
                                          array('ptid' => $ptid));
        $data['link'] = xarModURL('articles','admin','pubtypes',
                                 array('action' => 'confirm'));
    }
    $data['invalid'] = isset($invalid)?$invalid:array();
    $data['name'] = isset($name)?$name:'';
    $data['descr'] = isset($descr)?$descr:'';
    $data['format'] = isset($format)?$format:array();
    $data['editlabel'] = xarML('Edit Pub Type');
    $data['deletelabel'] = xarML('Delete Pub Type');
    $data['viewlabel'] = xarML('View Articles');
    $data['addlabel'] = xarML('Add Article');
    $data['configlabel'] = xarML('Pub Type Config');
    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    $data['action'] = $action;
    $data['ptid'] = $ptid;

    // Return the template variables defined in this function
    return $data;
}

?>
