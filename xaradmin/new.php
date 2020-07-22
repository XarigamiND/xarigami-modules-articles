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
 * add new article
 *
 * This function presents the template from which the article is created
 * @param int ptid The publication type id
 * @param string catid The category id this article will belong to
 * @param id itemtype the itemtype, if forced
 * @param string return_url The url to return to
 * @return mixed call to template with data array and name of template to use
 */
function articles_admin_new($args)
{
    extract($args);

    // Get parameters
    if (!xarVarFetch('ptid',        'id',    $ptid,       NULL,  XARVAR_NOT_REQUIRED)) {return;}
     if (!xarVarFetch('title',        'str',    $title,       NULL,  XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('catid',       'str',   $catid,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('itemtype',    'id',    $itemtype,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('return_url',  'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptidselect','isset', $ptidselect,     NULL, XARVAR_DONT_SET)) {return;}
     if (!xarVarFetch('checkinput','isset', $checkinput, NULL, XARVAR_DONT_SET)) {return;}
    if (isset($ptidselect)) $ptid = $ptidselect;
    //preview is boolean here
    if (!empty($preview) && isset($article)) {
        $ptid = $article['ptid'];
    } elseif (!isset($ptid) && !empty($itemtype) && is_numeric($itemtype)) {
        // when we use some categories filter
        $ptid = $itemtype;
    } elseif (!isset($ptid)) {
        $ptid = xarModGetVar('articles', 'defaultpubtype');
    }

    $data = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    $data['ptid'] = $ptid;
    $data['catid'] = $catid;
    if (isset($title) && !empty($title)) $data['title'] = $title;
    xarVarSetCached('Blocks.articles','ptid',$ptid);
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    if (!empty($article['cids'])) {
        $data['mycids'] = $article['cids'];
    }
    if (!isset($article)) {
        $article = array();
    }
    if (!isset($articles['cids']) && !empty($catid)) {
        $article['cids'] = preg_split('/[ +-]/',$catid);
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    // Security check
    if (empty($ptid)) {
        $ptid = '';

        if (!xarSecurityCheck('SubmitArticles')) {
               $msg = xarML('You have no permission to submit Articles');
                return xarResponseForbidden($msg);
        }
    } else {
        if (isset($article['cids']) && count($article['cids']) > 0) {
            foreach ($article['cids'] as $cid) {
                if (!xarSecurityCheck('SubmitArticles',1,'Article',"$ptid:$cid:All:All")) {
                    $catinfo = xarModAPIFunc('categories', 'user', 'getcatinfo',
                                             array('cid' => $cid));
                    if (empty($catinfo['name'])) {
                        $catinfo['name'] = $cid;
                    }
                    $msg = xarML('You have no permission to submit #(1) in category #(2)',
                                 $pubtypes[$ptid]['descr'],$catinfo['name']);
                    return xarResponseForbidden($msg);
                }
            }
        } else {
            if (!xarSecurityCheck('SubmitArticles',1,'Article',"$ptid:All:All:All")) {
                $msg = xarML('You have no permission to submit #(1)',
                             $pubtypes[$ptid]['descr']);
                return xarResponseForbidden($msg);
            }
        }
        if (xarModIsHooked('uploads', 'articles', $ptid)) {
            xarVarSetCached('Hooks.uploads','ishooked',1);
        }
    }
    $data['newcids']='';
    if (!empty($article['cids'])) {
       $data['newcids'] = $article['cids'];
    }
    if (!empty($preview)) {
        // Use articles user GUI function (not API) for preview
        if (!xarModLoad('articles','user')) return;
        $preview = xarModFunc('articles', 'user', 'display',
                             array('preview' => true, 'article' => $article));
    } else {
        $preview = '';
    }
    $data['preview'] = $preview;

    if (!empty($ptid)) {

        // preset some variables for hook modules
        $article['module'] = 'articles';
        $article['itemid'] = 0;
        $article['itemtype'] = $ptid;
         $article['checkinput'] = isset($checkinput)? $checkinput : FALSE;
        $article['antibotinvalid'] = isset($antibotinvalid)?$antibotinvalid:0;
        $hooks = xarModCallHooks('item','new','',$article);
    }
    if (empty($hooks)) {
        $hooks = '';
    }
    $data['hooks'] = $hooks;
    if (!empty($ptid)) {
        $formhooks = xarModAPIFunc('articles','user','formhooks', array('ptid' => $ptid));
        $data['formhooks'] = $formhooks;
    }
    // Array containing the different labels
    $labels = array();

    // Show publication type
     $publist = array();
    $pubfilters = array();
    foreach ($pubtypes as $id => $pubtype) {
        $pubitem = array();
        if ($id == $ptid) {
            $pubitem['plink'] = '';
        } else {
            if (!xarSecurityCheck('SubmitArticles',0,'Article',$id.':All:All:All')) {
                continue;
            }
            $pubitem['plink'] = xarModURL('articles','admin','new',
                                          array('ptid' => $id,
                                                'catid' => $catid));
        }
        $publist[$pubtype['ptid']] = ucfirst($pubtype['name']);
        $pubitem['ptitle'] = $pubtype['descr'];
        $pubfilters[] = $pubitem;
    }
    $data['pubfilters'] = $pubfilters;
    $data['publist'] = $publist;
    // Array containing the different values (except the article fields)
    $values = array();

    // TODO - language

// Note : this determines which fields are really shown in the template !!!
    // Show actual data fields
    $fields = array();
    $data['withupload'] = 0;
    if (!empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
        $data['defaultstatus'] = $settings['defaultstatus'];
    // TODO: make order dependent on pubtype or not ?
    //    foreach ($pubtypes[$ptid]['config'] as $field => $value) {}
        $pubfields = xarModAPIFunc('articles','user','getpubfields');
        foreach ($pubfields as $field => $dummy) {
              $value = $pubtypes[$ptid]['config'][$field];
              if (empty($value['label']) || empty($value['input'])) {
                continue;
            }
            $input = array();
            $input['name'] = $field;
             $input['label'] = isset($value['label'])? $value['label'] :'';
            $input['type'] = $value['format'];
            $input['id'] = $field;
            if (!empty($preview) && isset($article[$field])) {
                $input['value'] = $article[$field];
            } elseif ($field == 'pubdate') {
                // default publication time is now
                $input['value'] = time();
            } elseif ($field == 'status' && isset($settings['defaultstatus'])) {
                // default status (only if allowed on input)
                $input['value'] = $settings['defaultstatus'];
            }elseif (($field == 'title') && isset($data['title'])) {
                $input['value'] = $data['title'];
            } else {
                $input['value'] = '';
            }
            if (isset($value['validation'])) {
                $input['validation'] = $value['validation'];
            }

            if ($input['type'] == 'fileupload' || $input['type'] == 'textupload' || $input['type'] == 'image' ) {
                $data['withupload'] = 1;
            }
            if (!empty($preview) && isset($invalid) && !empty($invalid[$field])) {
                $input['invalid'] = $invalid[$field];
            }
            $fields[$field] = array('label' => $value['label'], 'id' => $field,
                                    'definition' => $input);
        }
    }
    $data['fields'] = $fields;

    if (!empty($ptid) && empty($data['withupload']) &&
        (xarVarIsCached('Hooks.dynamicdata','withupload') || xarModIsHooked('uploads', 'articles', $ptid)) ) {
        $data['withupload'] = 1;
         xarCoreCache::setCached('Hooks.uploads', 'ishooked', TRUE);
    }

    // Show allowable HTML
    $data['allowedhtml'] = '';
    foreach (xarConfigGetVar('Site.Core.AllowableHTML') as $k=>$v) {
        if ($v) {
            $data['allowedhtml'] .= '&lt;' . $k . '&gt; ';
        }
    }

    if (!empty($ptid)) {
        $formhooks = xarModAPIFunc('articles','user','formhooks',array('ptid'=>$ptid));
        $data['formhooks'] = $formhooks;
    }

    $data['previewlabel'] = xarVarPrepForDisplay(xarML('Preview'));
    $data['addlabel'] = xarVarPrepForDisplay(xarML('Add Article'));
    $data['authid'] = xarSecGenAuthKey('articles');
    $data['return_url'] = $return_url;
    $data['values'] = $values;

    if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
        xarTplSetPageTitle(xarML('New #(1)', $pubtypes[$ptid]['descr']));
    } else {
// TODO: allow templates per category ?
       $template = null;
       xarTplSetPageTitle(xarML('New'));
    }

    return xarTplModule('articles', 'admin', 'new', $data, $template);
}

?>
