<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 * @author xarigami articles dev team
 */
/**
 * modify article
 * @param int aid The ID of the article
 * @param string return_url
 * @param int preview
 */
function articles_admin_modify($args)
{
    extract($args);

    // Get parameters
    if (!xarVarFetch('aid','isset', $aid, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('return_url', 'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('checkinput','isset', $checkinput, NULL, XARVAR_DONT_SET)) {return;}
    if (isset($aid) && !isset($preview) && empty($preview)) {
       // $preview = 0;
        // Get article information
        $article = xarModAPIFunc('articles', 'user','get', array('aid' => $aid, 'withcids' => true));
    }
    if (!isset($article) || $article == false) {
        $msg = xarML('Unable to find #(1) item #(2)',
                    'Article', xarVarPrepForDisplay($aid));
         throw new BadParameterException(null,$msg);
    }

    $ptid = $article['pubtypeid'];
    if (!isset($ptid)) {
       $ptid = '';
    }
    $data = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    $data['ptid'] = $ptid;
    $data['aid'] = $aid;
    xarVarSetCached('Blocks.articles','ptid',$ptid);
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    //sends back the selected cids so theyre not lost during preview
    if (!empty($article['cids'])) {
       $data['mycids'] = $article['cids'];
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    //variable to hold calculated edit ability for this author
    $canedit = FALSE;
    // Security check
    $input = array();
    $input['article'] = $article;
    $input['mask'] = 'EditArticles';
    if (!xarModAPIFunc('articles','user','checksecurity',$input)) {
        $msg = xarML('You have no permission to modify #(1) item #(2)',
                     $pubtypes[$ptid]['descr'], xarVarPrepForDisplay($aid));
        return xarResponseForbidden($msg);
    } else {
        $canedit = TRUE;
    }
    unset($input);

    //get the editor
    if (xarUserIsLoggedIn()) {
        $editor=xarUserGetVar('uid');
    } else {
        $editor=_XAR_ID_UNREGISTERED;
    }
    $data['itemid'] = $aid;
    $changepubtype = xarModGetVar('articles','changepubtype');
    $input['mask'] = 'ModerateArticles';
    $moderatelevel = xarModAPIFunc('articles','user','checksecurity',$input);
    $data['canchangepubtype'] = $changepubtype & $moderatelevel;
    $dropdowns = array();
    if ($data['canchangepubtype']) { //prepare some info for the template and GUI for changing pubtype
        foreach($pubtypes as $k=>$v) {
            $dropdowns[$k] = $v['name'];
        }
    }

    $data['dropdowns'] =  $dropdowns;


    if (!empty($ptid)) {
         $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
    }

    $usecheckout = isset($settings['usecheckoutin'])?$settings['usecheckoutin']:0;
    $data['usecheckout']=$usecheckout;
    if ($usecheckout ==1) {
        if ($article['checkout'] !=1) { //not checked out
            $article['checkouttime'] = time(); //now
            $article['editor'] = $editor;
            $article['checkout'] =1;
            //update the item and mark as checked out before continuing
            $checkoutarticle = xarModAPIFunc('articles','admin','checkout',$article);
        } else {
            $article['checkouttime'] = 0; //This now acts as a toggle  - leave time as it was as it's already checked out.
            if ($article['editor'] !=$editor) {
                $msg = xarML('The article \'#(1)\' is currently checked out for editing',$article['title']);
                return xarResponseForbidden($msg);
            }
        }
    }
    if (xarModIsHooked('uploads', 'articles', $ptid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }

    // Use articles user GUI function (not API) for preview
    if (!xarModLoad('articles','user')) return;
    $data['preview'] = xarModFunc('articles', 'user', 'display',
                                  array('preview' => true, 'article' => $article));

    // preset some variables for hook modules
    $article['module'] = 'articles';
    $article['itemid'] = $aid;
    $article['itemtype'] = $ptid;
    $article['checkinput'] = isset($checkinput)? $checkinput : FALSE;
    $hooks = xarModCallHooks('item','modify',$aid,$article);
    if (empty($hooks)) {
        $hooks = array();
    }
    $data['hooks'] = $hooks;

    $formhooks = xarModAPIFunc('articles','user','formhooks', array('ptid' => $ptid));
    $data['formhooks'] = $formhooks;

    // Array containing the different values (except the article fields)
    $values = array();

    // Show publication type
    $data['pubtype'] = $pubtypes[$ptid]['name'];
    //backward compat
    $values['pubtype'] = $pubtypes[$ptid]['descr'];
    $data['values'] = $values;
    // TODO - language

// Note : this determines which fields are really shown in the template !!!
    // Show actual data fields
    $fields = array();
    $data['withupload'] = 0;
    // Get the labels from the pubtype configuration
// TODO: make order dependent on pubtype or not ?
//    foreach ($pubtypes[$ptid]['config'] as $field => $value) {}
    $pubfields = xarModAPIFunc('articles','user','getpubfields');
    foreach ($pubfields as $field => $dummy) {
        $value =isset($pubtypes[$ptid]['config'][$field])?$pubtypes[$ptid]['config'][$field]:null; // for the addition of checkout/checkin else errors

       if (empty($value['label'])) {
            continue;
        }
        $input = array();
        $input['name'] = $field;
        $input['id'] = $field;
        $input['label'] = $value['label'];
        $input['type'] = $value['format'];
        $input['value'] = $article[$field];
        if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')){
            if ($input['type'] != 'dropdown') {
                $input['validation'] = unserialize($value['validation']);
            } else {
                  $input['validation'] = $value['validation'];
            }
        } else {
             $input['validation']= array();
        }

        if ($input['type'] == 'fileupload' || $input['type'] == 'textupload' || $input['type'] == 'image' ) {
            $input['withupload'] = 1;
             $data['withupload'] = 1; //set the general withuploads as well
        }

        if (!empty($preview) && isset($invalid) && !empty($invalid[$field])) {
            $input['invalid'] = $invalid[$field];
        }
        // using new field tags here
        $fields[$field] = array('label' => $value['label'], 'id' => $field,
                                'definition' => $input);

    }
    unset($article);
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

    $formhooks = xarModAPIFunc('articles','user','formhooks',array('ptid'=>$ptid));
    $data['formhooks'] = $formhooks;

    $data['previewlabel'] = xarML('Preview');
    $data['updatelabel'] = xarML('Update Article');
    $data['authid'] = xarSecGenAuthKey('articles');
    $data['return_url'] = $return_url;
    $data['canedit'] = $canedit;
    if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
    } else {
// TODO: allow templates per category ?
       $template = null;
    }
    return xarTplModule('articles', 'admin', 'modify', $data, $template);
}

?>