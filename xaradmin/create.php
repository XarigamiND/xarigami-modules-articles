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
 * create item from xarModFunc('articles','admin','new')
 *
 * @param id     ptid       The publication Type ID for this new article
 * @param array  new_cids   An array with the category ids for this new article (OPTIONAL)
 * @param array  modify_cids   An array with the category ids for a cloned article (OPTIONAL)
 * @param string preview    Are we gonna see a preview? (OPTIONAL)
 * @param string save       Call the save action, form stays open (OPTIONAL)
 * @param string view       Call the view action, show newly created article (OPTIONAL)
 * @param string return_url The URL to return to (OPTIONAL)
 * @throws BAD_PARAM
 * @return  bool true on success, or mixed on failure
 */
function articles_admin_create()
{
    // Get parameters
    if (!xarVarFetch('ptid',     'id',    $ptid)) {return;}
    if (!xarVarFetch('itemtype', 'id', $itemtype,   $ptid, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('new_cids', 'array', $cids,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    //need modify_cids for clone
    if (!xarVarFetch('modify_cids', 'array', $cids,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('preview',  'str',   $preview, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('save',     'str',   $save, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('view',     'str',   $view,    NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('return_url', 'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}

    $antibotinvalid =0;//initialize
    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) {
            // Catch user session error and fall back to preview
            $msg = xarML('Article was <strong>NOT</strong> saved, please retry.');
            if (!isset($preview)) {
                xarSessionSetVar('statusmsg', $msg);
            }
            $preview = 1;
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    if (!isset($pubtypes[$ptid])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'publication type', 'admin', 'create',
                    'Articles');
         throw new BadParameterException(null,$msg);
        return;
    }

// TODO: switch to DD object style
    $article = array();
    $invalid = array();
    if (xarModIsHooked('uploads', 'articles', $ptid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    $properties = array();
    foreach ($pubtypes[$ptid]['config'] as $field => $value) {
        if (!empty($value['label']) && !empty($value['input'])) {
            if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
                $value['validation'] = unserialize($value['validation']);
            } else {
                $value['validation']= array();
            }
            $properties[$field] = xarModAPIFunc('dynamicdata','user','getproperty',
                                                 array('name' => $field,
                                                       'type' => $value['format'],
                                                       'label'=>$value['label'],
                                                       'validation' => $value['validation']));
            $check = $properties[$field]->checkInput($field);
            if (!$check) {
                $article[$field] = '';
                $invalid[$field] = $properties[$field]->invalid;
                $preview = 1;
            } else {
                $article[$field] = $properties[$field]->value;
            }
        }
        if (!isset($article[$field])) {
            $article[$field] = '';
        }
    }
    //what about hooked DD - we need to check now
    $checkargs = array('moduleid' => xarModGetIDFromName('articles'),
                            'module' => 'articles',
                           'itemtype' => $ptid,
                           'checkinfo' => TRUE, //this forces checkupdate later
                           'itemid'   => 0);
    //we need to check manually or pass $extrainfo['update'] = TRUE to the modify hook
    if (xarModIsHooked('dynamicdata','articles',$ptid)) {
        $myobject = Dynamic_Object_Master::getObject( $checkargs );
        if (isset($myobject)) {
            $properties = $myobject->getProperties();
            foreach ($properties as $property) {
                $check = $properties[$property->name]->checkInput();
                if (!$check) {
                    $invalid[$property->name] = $properties[$property->name]->invalid;
                    $myobject->invalid_object= TRUE;
                }
            }
        }
    }
    $article['ptid'] = $ptid;

    // check that we have a title when we need one, or fill in a dummy one
    if (empty($article['title'])) {
        if (empty($pubtypes[$ptid]['config']['title']['label'])) {
            $article['title'] = ' ';
        } elseif (empty($invalid['title'])) {
            // show this to the user
            $invalid['title'] = xarML('This field is required');
        }
    }
    if (empty($article['pubdate'])) {
        $article['pubdate'] = time();
    }

// TODO: make $status dependent on permissions ?
    if (empty($article['status'])) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
        if (isset($settings['defaultstatus'])) {
            $article['status'] = $settings['defaultstatus'];
        } elseif (empty($pubtypes[$ptid]['config']['status']['label'])) {
            $article['status'] = 2;
        } else {
            $article['status'] = 0;
        }
    }

    // Default the authorid to the current author if either not already set, or the authorid
    // field does not allow input when creating a new article.
    if (empty($article['authorid']) || empty($pubtypes[$ptid]['config']['authorid']['input'])) {
        $article['authorid'] = xarUserGetVar('uid');
    }
    if (empty($article['authorid'])) {
        $article['authorid'] = _XAR_ID_UNREGISTERED;
    }

    if (empty($article['language'])) {
        $article['language'] = xarMLSGetCurrentLocale();
    }

    if (!empty($cids) && count($cids) > 0) {
        $article['cids'] = array_values(preg_grep('/\d+/',$cids));
    } else {
        $article['cids'] = array();
    }

    // for preview
    $article['pubtypeid'] = $ptid;
    $article['aid'] = 0;

   $hookinfo = xarModCallHooks('item', 'submit', $ptid, array('itemtype'=>$ptid));
   $antibotinvalid = isset($hookinfo['antibotinvalid']) ? $hookinfo['antibotinvalid'] : 0;

    if ($preview || (count($invalid) > 0) || $antibotinvalid) {
        $data = xarModFunc('articles','admin','new',
                             array('preview' => true,
                                   'article' => $article,
                                   'checkinput' => TRUE,
                                   'return_url' => $return_url,
                                   'invalid' => $invalid,
                                    'antibotinvalid'=>$antibotinvalid));

        unset($article);

        if (is_array($data)) {
            return xarTplModule('articles','admin','new',$data);
        } else {
            return $data;
        }
    }

    // call transform input hooks
    $article['transform'] = array('summary','body','notes');
    $article = xarModCallHooks('item', 'transform-input', 0, $article,
                               'articles', $ptid);


    // Pass to API
    $aid = xarModAPIFunc('articles', 'admin', 'create', $article);

    if ($aid == false) {
        // database failure should already be handled in create
        // Handle any user error
        return xarTplModule('articles','user','errors',array('errortype' => 'creation_failed','var1'=>xarML('Database creation error')));
    }

    // Success
    $msg = xarML('The article was successfully created.');
    xarTplSetMessage($msg,'status');
    // Save and continue editing via feature request.
    if (isset($save)){
        if (xarSecurityCheck('EditArticles',0,'Article',$ptid.':All:All:All')) {
            xarResponseRedirect(xarModURL('articles', 'admin', 'modify',
                                          empty($return_url) ?  array('aid' => $aid) : array('aid' => $aid, 'return_url' => $return_url) ));
        } else {
            xarResponseRedirect(xarModURL('articles', 'user', 'view',
                                          empty($return_url) ? array('ptid' => $ptid) : array('ptid' => $ptid, 'return_url' => $return_url) ));
        }
    }
    // Save and view the new article
    if (isset($view)){
        xarResponseRedirect(xarModURL('articles', 'user', 'display',
                                      empty($return_url) ? array('ptid' => $ptid, 'aid' => $aid) : array('ptid' => $ptid, 'aid' => $aid, 'return_url' => $return_url) ));
    }

    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
        return true;
    }

    // if we can edit articles, go to admin view, otherwise go to user view
    if (xarSecurityCheck('EditArticles',0,'Article',$ptid.':All:All:All')) {
        xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                      array('ptid' => $ptid)));
    } else {
        xarResponseRedirect(xarModURL('articles', 'user', 'view',
                                      array('ptid' => $ptid)));
    }

    return true;
}

?>