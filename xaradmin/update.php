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
 * update item from articles_admin_modify
 *
 * @param id     ptid       The publication Type ID for this new article
 * @param array  new_cids   An array with the category ids for this new article (OPTIONAL)
 * @param string preview    Are we gonna see a preview? (OPTIONAL)
 * @param string save       Call the save action (OPTIONAL)
 * @param string return_url The URL to return to (OPTIONAL)
 * @return  bool true on success, or mixed on failure
 */
function articles_admin_update()
{
    // Get parameters
    if(!xarVarFetch('aid',          'isset',    $aid,       NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptid',         'isset',    $ptid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('modify_cids',  'isset',    $cids,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('preview',      'isset',    $preview,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('save',         'isset',    $save,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('return_url',  'str:1',    $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('checkin',      'checkbox', $checkin,   false, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('newpubtype',   'int',      $newpubtype,  $ptid, XARVAR_DONT_SET)) {return;}

    // Confirm authorisation code
  if (!xarSecConfirmAuthKey()) {
            // Catch exception and fall back to preview
            $msg = xarML('Article was <strong>NOT</strong> saved, please retry.');
            // Save the error message if we are not in preview
            if (!$preview) {
                xarTplSetMessage($msg,'error');
            }
            $preview = 1;
    }


    //get the editor
    if (xarUserIsLoggedIn()) {
        $editor=xarUserGetVar('uid');
    } else {
        $editor=_XAR_ID_UNREGISTERED;
    }

    if (empty($aid) || !is_numeric($aid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'item id', 'admin', 'update', 'Articles');
        throw new BadParameterException(null,$msg);
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    if (empty($ptid) || !isset($pubtypes[$ptid])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'publication type', 'admin', 'update', 'Articles');
        throw new BadParameterException(null,$msg);
    }

    // Get original article information
    $article = xarModAPIFunc('articles', 'user', 'get',
                            array('aid' => $aid,
                                  'withcids' => true));

    if (!isset($article)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                     'article', 'admin', 'update', 'Articles');
       throw new BadParameterException(null,$msg);
    }

// TODO: switch to DD object style
    $invalid = array();
    if (xarModIsHooked('uploads', 'articles', $ptid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    $modid = xarModGetIDFromName('articles');
    $properties = array();
    foreach ($pubtypes[$ptid]['config'] as $field => $value) {
        if (!empty($value['label'])) {

            if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
                $value['validation'] = unserialize($value['validation']);
            } else {
                $value['validation']= array();
            }
            $properties[$field] = xarModAPIFunc('dynamicdata','user','getproperty',
                                                 array('name' => $field,
                                                       'label'=>$value['label'],
                                                       'type' => $value['format'],
                                                       'validation' => $value['validation'],
                                                       'value' => $article[$field],
                                                       // fake DD property from articles (for now)
                                                       '_moduleid' => $modid,
                                                       '_itemtype' => $ptid,
                                                       '_itemid'   => $aid));
            $check = $properties[$field]->checkInput($field);

            if (!$check) {
                if ($field == 'authorid') {
                    // re-assign article to Anonymous
                    $article[$field] = _XAR_ID_UNREGISTERED;
                } else {
                    $article[$field] = '';
                    $invalid[$field] = $properties[$field]->invalid;
                    $preview = 1;
                }
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
                           'checkinput' => TRUE, //this forces checkupdate later
                           'itemid'   => $aid);
    //we need to check manually or pass $extrainfo['update'] = TRUE to the modify hook
    if (xarModIsHooked('dynamicdata','articles',$ptid)) {
        $myobject = Dynamic_Object_Master::getObject( $checkargs );
        if (isset($myobject)) {
            $properties = $myobject->getProperties();
            foreach ($properties as $property) {
                $check = $properties[$property->name]->checkInput();
                if ($check===FALSE) {
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
        $article['pubdate'] = 0;
    }

// TODO: make $status dependent on permissions ?
    if (empty($article['status'])) {
        if (empty($pubtypes[$ptid]['config']['status']['label'])) {
            $article['status'] = 2;
        } else {
            $article['status'] = 0;
        }
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
    $article['aid'] = $aid;

    //get our checkin/out info
    $article['editor'] = $editor;
        if (!empty($invalid) && count($invalid) > 0) {
                       $msg = xarML('The article cannot be saved due to input errors. Please check your input for errors.');
            xarTplSetMessage($msg,'error');
        }
    if ($preview || count($invalid) > 0) {

        $data = xarModFunc('articles','admin','modify',
                             array('preview' =>  TRUE,
                                   'article' => $article,
                                   'return_url' => $return_url,
                                   'checkinput' => TRUE,
                                   'invalid' => $invalid));

        unset($article);
        $invalid = array();
        if (is_array($data)) {
            return xarTplModule('articles','admin','modify',$data);
        } else {

            return $data;
        }
    }

    // call transform input hooks
    $article['transform'] = array('summary','body','notes');
    $article = xarModCallHooks('item', 'transform-input', $aid, $article,
                               'articles', $ptid);
    //checkout checkin review - if $checkin is set to true - then checkout must be active
    //let's update the values accordingly or else leave it as is
    if (TRUE == $checkin) {
       $article['checkout'] = 0;
       $article['checkouttime'] = 0;//being used as a flag which as 0 means 'leave time value as is - the original modify time'
    }

    //see if we need to change the pubtype
    $changepubtype = xarModGetVar('articles','changepubtype');
    $article['mask'] = 'ModerateArticles';
    $moderatelevel = xarModAPIFunc('articles','user','checksecurity',$article);
    $canchangepubtype = $changepubtype & $moderatelevel;
    if ($canchangepubtype && ($newpubtype != $ptid)) {
        $oldptid = $ptid;
        $article['ptid'] = $newpubtype;
        //here we go to change the pubtype links in various hook tables
        //get a list of hooked modules
        //we get all those with tables here but we will not update all hooks - some like DD can be updated when article is updated
        // we update only those where we want to retain data
        $updatelinks = xarModAPIFunc('articles','admin','updatelinks',array('aid'=>$article['aid'], 'ptid'=>$oldptid,'newptid'=>$newpubtype));
        //we don't want to update hooks here for DD
        xarVarSetCached('Hooks.all','noupdate',1);
   }


    // Pass to API and do the update
    if (!xarModAPIFunc('articles', 'admin', 'update', $article)) {
            $msg = xarML('There was a problem updating the article.');
            xarTplSetMessage($msg,'status');
        return;
    } else {
        // Success
        $msg = xarML('The article was successfully updated.');
        xarTplSetMessage($msg,'status');
    }
    if (isset($oldptid) && isset($newpubtype) && !empty($newpubtype)) {
        $ptid = $newpubtype; //for redirect
    }
    xarVarSetCached('Hooks.all','noupdate',0);
    unset($article);



    // Save and continue editing via feature request.
    if (isset($save) && xarSecurityCheck('EditArticles',0,'Article',$ptid.':All:All:All')) {
        xarResponseRedirect(xarModURL('articles', 'admin', 'modify',
                                      empty($return_url) ? array('aid' => $aid,'ptid'=>$ptid) : array('aid' => $aid,'ptid' => $ptid,'return_url' => $return_url) ));
        return true;
    }

    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
        return true;
    }

    // Return to the original admin view
    $lastview = xarSessionGetVar('Articles.LastView');
    if (isset($lastview)) {
        $lastviewarray = unserialize($lastview);
        if (!empty($lastviewarray['ptid']) && $lastviewarray['ptid'] == $ptid) {
            extract($lastviewarray);
            xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                          array('ptid' => $ptid,
                                                'catid' => $catid,
                                                'status' => $status,
                                                'startnum' => $startnum)));
            return true;
        }
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
