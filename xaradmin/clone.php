<?php
/**
 * Articles module
 *
 * @package modules
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * Clone item
 *
 * @param id     ptid       The publication Type ID for this new article
 * @param string return_url The URL to return to (OPTIONAL)
 * @throws BAD_PARAM
 * @return  bool true on success, or mixed on failure
 */
function articles_admin_clone()
{
    // Get parameters
    if (!xarVarFetch('aid','isset', $aid, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('new_cids', 'array', $cids,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('preview',  'str',   $preview, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('save',     'str',   $save, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('view',     'str',   $view,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('return_url', 'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptidselect','isset', $ptidselect,     NULL, XARVAR_DONT_SET)) {return;}
    if (isset($ptidselect)) $ptid = $ptidselect;
    //get the article and all values
    if (isset($aid)) {
        // Get article information
        $article = xarModAPIFunc('articles', 'user','get', array('aid' => $aid, 'withcids' => true));
    }
    if (!isset($article) || $article == false) {
        $msg = xarML('Unable to find #(1) item #(2)',
                    'Article', xarVarPrepForDisplay($aid));
        throw new IDNotFoundException(null,$msg);
    }
    $ptid = $article['pubtypeid'];
    if (!isset($ptid)) {
       $ptid = '';
    }
    $data = array();
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    $data['ptid'] = $ptid;
    $data['aid'] = $aid;

    //categories
    if (!empty($article['cids'])) {
       $data['mycids'] = $article['cids'];
    }
    xarVarSetCached('Blocks.articles','ptid',$ptid);
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    //get the pubtype info
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    $hasstatus =  empty($pubtypes[$ptid]['config']['status']['label'])?FALSE:TRUE;
    //we can't clone an article that doesnt' have status
    if (!$hasstatus) {
    $msg = xarML('This article #(1) with pubtype name #(2) has no status field and cannot be cloned',
                     xarVarPrepForDisplay($aid),  $pubtypes[$ptid]['name']);
        throw new BadParameterException(null,$msg);
    }
    $input = array();
    $input['article'] = $article;
    $input['mask'] = 'EditArticles';

    if (!xarModAPIFunc('articles','user','checksecurity',$input)) {
        $msg = xarML('You have no permission to clone article AID #(1)',
                     xarVarPrepForDisplay($aid));
         return xarResponseForbidden($msg);
    }
    unset($input);
    //get the editor
    if (xarUserIsLoggedIn()) {
        $editor=xarUserGetVar('uid');
    } else {
        $editor=_XAR_ID_UNREGISTERED;
    }

    $data['itemid'] = $aid;

    if (!empty($ptid)) {
         $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
    }

    if (xarModIsHooked('uploads', 'articles', $ptid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    // preset some variables for hook modules
    $article['module'] = 'articles';
    $article['itemid'] = $aid;
    $article['itemtype'] = $ptid;

    $hooks = xarModCallHooks('item','modify',$aid,$article);
    if (empty($hooks)) {
        $hooks = array();
    }
    $data['hooks'] = $hooks;

    $formhooks = xarModAPIFunc('articles','user','formhooks', array('ptid' => $ptid));
    $data['formhooks'] = $formhooks;
    $data['defaultstatus'] = 4; //draft

    // Array containing the different values (except the article fields)
    $values = array();

    // Show publication type
    $values['pubtype'] = $pubtypes[$ptid]['descr'];
    $data['values'] = $values;

    $fields = array();
    $data['withupload'] = 0;

    $pubfields = xarModAPIFunc('articles','user','getpubfields');
    foreach ($pubfields as $field => $dummy) {
        $value =isset($pubtypes[$ptid]['config'][$field])?$pubtypes[$ptid]['config'][$field]:null; // for the addition of checkout/checkin else errors
        if (empty($value['label'])) {
            continue;
        }
        $input = array();
        $input['name'] = $field;
        $input['id'] = $field;
        $input['type'] = $value['format'];
        $input['value'] = $article[$field];
        if (isset($value['validation'])) {
            $input['validation'] = $value['validation'];
        }

        if ($input['type'] == 'fileupload' || $input['type'] == 'textupload' ) {
            $data['withupload'] = 1;
        }
        if (!empty($preview) && isset($invalid) && !empty($invalid[$field])) {
            $input['invalid'] = $invalid[$field];
        }
        // using new field tags here
        $fields[$field] = array('label' => $value['label'], 'id' => $field,
                                'definition' => $input);
    }
    unset($article);
    //special case for status - must be draft
    $fields['status']['definition']['value'] = 4;//must be draft
    //special case for title
    $fields['title']['definition']['value'] =  $fields['title']['definition']['value'].' '.xarML('CLONED');//must be draft

    $data['fields'] = $fields;

    if (!empty($ptid) && empty($data['withupload']) &&
        (xarVarIsCached('Hooks.dynamicdata','withupload') || xarModIsHooked('uploads', 'articles', $ptid)) ) {
        $data['withupload'] = 1;
    }

    // Show allowable HTML
    $data['allowedhtml'] = '';
    foreach (xarConfigGetVar('Site.Core.AllowableHTML') as $k=>$v) {
        if ($v) {
            $data['allowedhtml'] .= '&lt;' . $k . '&gt; ';
        }
    }
    $data['preview'] = '';
    $data['previewlabel'] = xarML('Preview');
    $data['addlabel'] = xarVarPrepForDisplay(xarML('Add Article'));
    $data['authid'] = xarSecGenAuthKey('articles');
    $data['return_url'] = $return_url;
    $data['values'] = $values;
     if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
        xarTplSetPageTitle(xarML('New #(1)', $pubtypes[$ptid]['descr']));
    } else {
       $template = null;
       xarTplSetPageTitle(xarML('New'));
    }

    return xarTplModule('articles', 'admin', 'clone', $data, $template);

}

?>
