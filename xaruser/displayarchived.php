<?php
/**
 * Articles module
 *
 * @package modules
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author Xarigami Team
 */
/**
 * Display an archived article
 *
 * @param int aid
 * @param int page
 * @param int ptid The publication Type ID
 * @return array with template information
 */
function articles_user_displayarchived($args)
{

    // Get parameters from user
    if(!xarVarFetch('aid',  'id',    $aid,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    // page within the article for multipaged articles
    //- allow int or string in case we want to have nicer url
    // int always will be used for inbuilt multipaged articles using <!--pagebreak-->
    if(!xarVarFetch('page', 'isset', $page,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    // this is used to determine whether we come from a pubtype-based view or a
    // categories-based navigation
    if(!xarVarFetch('ptid', 'id',    $ptid,  NULL, XARVAR_NOT_REQUIRED)) {return;}


    // if passed in via URL we can override - not in this case
    // extract ($args);

    // Defaults
    if (!isset($page)) {
        $page = 1;
    }


    if (!isset($aid) || !is_numeric($aid) || $aid < 1) {
        return xarML('Invalid article ID');
    }

    // Load API
    if (!xarModAPILoad('articles', 'user')) return;


        $article = xarModAPIFunc('articles', 'user', 'get',
                                array('aid' => $aid,
                                      'withcids' => true));

    if (!is_array($article)) {
        if (!empty($article) && (int)$article == $aid || ($article == FALSE)) { //the article is there but there is a view privilege issue
            $msg = xarML('You do not have the required access level to read this article', 'user', 'get', 'articles');
            return xarResponseForbidden($msg);

        } else {
            $msg = xarML('Failed to retrieve article in #(3)_#(1)_#(2).php', 'user', 'get', 'articles');
            return xarResponseNotFound($msg);
        }
    }

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    // Check that the publication type is valid, otherwise use the article's pubtype
    if (!empty($ptid) && !isset($pubtypes[$ptid])) {
        $ptid = $article['pubtypeid'];
    }

// keep original ptid (if any)
//    $ptid = $article['pubtypeid'];
    $pubtypeid = $article['pubtypeid'];
    $authorid = $article['authorid'];
    if (!isset($article['cids'])) {
        $article['cids'] = array();
    }
    $cids = $article['cids'];

    // Get the article settings for this publication type
    if (empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings'));
    } else {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
    }

    // Initialize the data array
    $data = array();
    $data['archivenotice'] =false;
    //we got this far so they have access per se to the doc
    //now check access to archived documents
    $hasaccess = xarModGetVar('articles','archiveaccess');
    $displayarchived = xarModAPIFunc('roles','user','checkgroup',array('gid'=>$hasaccess,'uid'=>xarUserGetVar('uid'),'isancestor'=>true));
    //Administration level access?
    $isadmin = xarSecurityCheck('AdminArticles', 0, 'Article', "{$article['pubtypeid']}:All:All:All");
    $data['displayarchived'] = $isadmin || $displayarchived;

    $usearchiving = xarModGetVar('articles','usearchiving');

    $data['archived'] = false;
    if ($usearchiving && ($article['status'] == 5 ))   { //archived
        $data['archived'] = true;
    }

    //checkout functionality is off by default
    if (!isset($settings['usecheckoutin'])) $settings['usecheckoutin'] = 0;

    $usecheckoutin = $settings['usecheckoutin'];
    $data['usecheckoutin'] = $usecheckoutin; //pass through to template


    // show the number of articles for each publication type
    if (!isset($showpubcount)) {
        if (!isset($settings['showpubcount']) || !empty($settings['showpubcount'])) {
            $showpubcount = 1; // default yes
        } else {
            $showpubcount = 0;
        }
    }

    // show the number of articles for each category
    if (!isset($showcatcount)) {
        if (empty($settings['showcatcount'])) {
            $showcatcount = 0; // default no
        } else {
            $showcatcount = 1;
        }
    }

 // DO Navigation links
    $data['publabel'] = xarML('Publication');
    $data['publinks'] = xarModAPIFunc('articles','user','getpublinks',
                                     array('status' => array(3,2),
                                           'count' => $showpubcount));
    if (isset($showmap)) {
        $settings['showmap'] = $showmap;
    }
    if (!empty($settings['showmap'])) {
        $data['maplabel'] = xarML('View Article Map');
        $data['maplink'] = xarModURL('articles','user','viewmap',
                                    array('ptid' => $ptid));
    }
    if (isset($showmonthview)) {
        $settings['showmonthview'] = $showmonthview;
    }
    if (!empty($settings['showmonthview'])) {
        $data['monthviewlabel'] = xarML('View by Month');
        $data['monthviewlink'] = xarModURL('articles','user','monthview',
                                        array('ptid' => $ptid));
    }
    if (isset($showpublinks)) {
        $settings['showpublinks'] = $showpublinks;
    }
    if (!empty($settings['showpublinks'])) {
        $data['showpublinks'] = 1;
    } else {
        $data['showpublinks'] = 0;
    }
    $data['showcatcount'] = $showcatcount;


    //now in case of checkin check out and user is
    //get the current user
    if (xarUserIsLoggedIn()) {
        $thisuser=xarUserGetVar('uid');
    } else {
         $thisuser=_XAR_ID_UNREGISTERED;
    }

    $data['ptid'] = $ptid; // navigation pubtype
    $data['pubtypeid'] = $pubtypeid; // article pubtype

    $viewstatus = array(2,3,5); //only show published and front page to those without at least edit level privs

    // Security check for EDIT access
    $input = array();
    $input['article'] = $article;
    $input['mask'] = 'EditArticles';
    $input['archivedisplay'] = true; //signal checksecurity this is a special case
    $data['aid'] = $article['aid'];
    $data['ptid'] = $article['pubtypeid'];

    $data['cids'] = $article['cids'];
    $ret = '';
    //first check if the article is archived and setup the template to be shown
    if ($data['archived']) {
      //  $data['title']= $article['title'];
      //  $ret= xarTplModule('articles','user','archived',$data);
    }
    //now check for edit and access rights
    if (xarModAPIFunc('articles','user','checksecurity',$input)) {
        if (isset($usecheckoutin) && $usecheckoutin == 1 && $article['checkout']==1 && ($thisuser != $article['editor'])) {
            $data['editurl'] = '';
        } else {
            $return_url = xarServerGetCurrentURL(array(), false);
            $data['editurl'] = xarModURL('articles', 'admin', 'modify',
                                     array('aid' => $article['aid'],'ptid'=>$ptid,
                                           'return_url' => $return_url));
        }
    // don't show unapproved articles to non-editors
    } elseif (!in_array($article['status'],$viewstatus)) {

        $status = xarModAPIFunc('articles', 'user', 'getstatusname',
                                array('status' => $article['status']));
        $ret = xarML('You have no permission to view this item [Status: #(1)]', $status);
    }
    if (!empty($ret)) return $ret;

    $data['edittitle'] = xarML('Edit');


    $data['topic_icons'] = '';
    $data['topic_images'] = array();
    $data['topic_urls'] = array();
    $data['topic_names'] = array();
    if (count($cids) > 0) {
        if (!xarModAPILoad('categories', 'user')) return;
        $catlist = xarModAPIFunc('categories',  'user', 'getcatinfo',
                                array('cids' => $cids));
        foreach ($catlist as $cat) {
            $link = xarModURL('articles','user','view',
                             array(//'status' => array(3,2),
                                   'ptid' => $ptid,
                                   'catid' => $cat['cid']));
            $name = xarVarPrepForDisplay($cat['name']);

            $data['topic_urls'][] = $link;
            $data['topic_names'][] = $name;

            if (!empty($cat['image'])) {
                $image = xarTplGetImage($cat['image'],'categories');
                $data['topic_icons'] .= '<a href="'. $link .'">'.
                                        '<img src="'. $image .
                                        '" alt="'. $name .'" />'.
                                        '</a>';
                $data['topic_images'][] = $image;

                break;
            }
        }
    }
        //initialize some vars
    $data['header'] = '';
    $data['sections'] = array();
    $data['sectioncount'] = 0;
    $data['sectiontitles'] = array();
    $data['tab'] = 0;
    // multi-page output for 'body' field (mostly for sections at the moment)
    $themeName = xarVarGetCached('Themes.name','CurrentTheme');
       $sectioncount = preg_match_all('/<!-- section: (.+) -->/', $article['body'], $matches);

    if ($themeName != 'print'){
        if (strstr($article['body'],'<!--pagebreak-->')) {
            if (!empty($page)) $page = (int)$page;

            $pages = explode('<!--pagebreak-->',$article['body']);

            // For documents with many pages, the pages can be
            // arranged in blocks.
            $pageBlockSize = 10;

            // Get pager information: one item per page.
            $pagerinfo = xarTplPagerInfo((empty($page) ? 1 : ($page)), count($pages), 1, $pageBlockSize);

            // Retrieve current page and total pages from the pager info.
            // These will have been normalised to ensure they are in range.
            $page = $pagerinfo['currentpage'];
            $numpages = $pagerinfo['totalpages'];

            // Discard everything but the current page.
            $article['body'] = $pages[$page - 1];
            unset($pages);

            if ($page > 1) {
                // Don't count page hits after the first page.
                xarVarSetCached('Hooks.hitcount','nocount',1);
            }

            // Pass in the pager info so a complete custom pager
            // can be created in the template if required.
            $data['pagerinfo'] = $pagerinfo;

            // Get the rendered pager.
            // The pager template (last parameter) could be an
            // option for the publication type.
            $urlmask = xarModURL(
                'articles','user','display',
                array('ptid' => $ptid, 'aid' => $aid, 'page' => '%%')
            );
            $data['pager'] = xarTplGetPager(
                $page, $numpages, $urlmask,
                1, $pageBlockSize, 'multipage'
            );

            // Next two assignments for legacy templates.
            // TODO: deprecate them?
            $data['next'] = xarTplGetPager(
                $page, $numpages, $urlmask,
                1, $pageBlockSize, 'multipagenext'
            );
            $data['previous'] = xarTplGetPager(
                $page, $numpages, $urlmask,
                1, $pageBlockSize, 'multipageprev'
            );
        }elseif (!empty($sectioncount)) {
            if ($preview) {
                $article['body'] = preg_replace('/<!-- section: (.+) -->/',
                                                '<hr/><div style="text-align: center;">'.xarML('Tab Page').'</div><hr/>',
                                                $article['body']);
            } else {
                if ($tab  > 1) {
                    // Don't count page hits after the first page.
                    xarVarSetCached('Hooks.hitcount','nocount',1);
                }
                if ($tab > $sectioncount) {
                    $tab = $sectioncount;
                }
                //headings
                $sectiontitles = $matches[1];
                // Split the body up into an array of sections.
                $sections = preg_split('/<!-- section: (.+) -->/', $article['body']);
                // Remove the first section if there are too many.
                // We don't want anything before the first section marker
                if (count($sections) > $sectioncount) {
                    $header = trim(array_shift($sections));
                }
                $article['body'] = $sections[$tab-1];
                $data['header'] = $header;
                $data['sections'] = $sections;
                $data['sectioncount'] = $sectioncount;
                 $data['sectiontitles'] = $sectiontitles;
                 $data['tab'] = $tab;
            }
        } elseif (!isset($page) && $page !=1 ) {
            $data['previous'] = '';
            $data['next'] = '';
            $data['page'] = $page;
        } else {
            $data['previous'] = '';
            $data['next'] = '';
        }
    } else {
        $article['body'] = preg_replace('/<!--pagebreak-->/',
                                        '',
                                        $article['body']);
    }

    // TEST

        $data['prevart'] = '';
        $data['nextart'] = '';


    // Display article
 //make sure we set the uploads is hooked before we do displays
    if (xarModIsHooked('uploads', 'articles', $pubtypeid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    // Fill in the fields based on the pubtype configuration
    foreach ($pubtypes[$pubtypeid]['config'] as $field => $value) {
        if (empty($value['label'])) {
            $data[$field] = '';
            continue;
        }
        $data[$field.'_value'] = $article[$field];
        if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
            $data['validation'][$field] = unserialize($value['validation']);
        } else {
             $data['validation'][$field] = array();
        }
        switch ($value['format']) {
            case 'username':
            case 'userlist':
                $data['value'][$field]=$article[$field];
                $data[$field] = $article[$field];
        // TODO: replace by authorid and sync with templates
                $data['author'] = xarUserGetVar('name', $article[$field]);
                if (!isset($data['author'])) {
                    $data['author'] = '';
                } elseif (empty($data['author'])) {
                    $data['author'] = xarUserGetVar('uname', $article[$field]);
                }
                if ($article[$field] > _XAR_ID_UNREGISTERED) {
                    $data['profile'] = xarModURL('roles','user','display',
                                                array('uid' => $article[$field]));
                }

                break;
            case 'status':
                $data['value'][$field]=$article[$field];
                $data[$field] = $article[$field];
                break;
            case 'calendar':
                $data['value'][$field]=$article[$field];
                // Make sure there is a value date
                if (!empty($article[$field])) {
                    // all calendar fields are passed "as is" now, so you can format them in the templates
                    $data[$field] = $article[$field];
                    // legacy support for $date variable in templates
                    if ($field == 'pubdate') {
                        // the date for this field is represented in the user's timezone for display
                        $data['date'] = xarLocaleFormatDate('%a, %d %B %Y %H:%M:%S %Z', $article[$field]);
                    }
                } else {
                    $data[$field] = '';
                    // legacy support for $date variable in templates
                    if ($field == 'pubdate') {
                        $data['date'] = '';
                    }
                }
                break;
            case 'url':
                $data['value'][$field]=$article[$field];
                $data[$field] = xarVarPrepHTMLDisplay($article[$field]);
                if (!empty($article[$field]) && $article[$field] != 'http://') {
                    $data['redirect'] = xarModURL('articles','user','redirect',
                                                  array('ptid' => $ptid,
                                                        'aid' => $aid));
                } else {
                    $data['redirect'] = '';
                }
                break;
            case 'textbox':
            case 'textarea':
                $data[$field] = xarVarPrepHTMLDisplay($article[$field]);
            case 'urltitle':
                // fall through
        // TEST ONLY
            case 'webpage':
                if (empty($value['validation'])) {
                    $value['validation'] = 'modules/articles';
                }
                // fall through
            case 'imagelist':
                if (empty($value['validation'])) {
                    $value['validation'] = 'modules/articles/xarimages';
                }
                // fall through
            case 'email':
            //fall through
            case 'dropdown':
            default:
                if (!empty($article[$field])) {

                    $data[$field] = xarModAPIFunc('dynamicdata','user','showoutput',
                                                  array('name' => $field,
                                                        'label' => isset($value['label'])?$value['label']:'',
                                                        'type' => $value['format'],
                                                        'validation' => $data['validation'][$field],
                                                        'value' => $article[$field],
                                                        ));
                } else {
                    $data[$field] = '';
                }
                 $data[$field.'_output'] =& $data[$field];
                break;

        }
       // $data['value'][$field]=$article[$field];
    }

    //checkout fields in case we need them
    $data['checkout'] =isset($article['checkout'])?$article['checkout']:0;
    $data['editor'] =isset($article['editor'])?$article['editor']:'';
    $data['checkouttime'] =isset($article['checkouttime'])?$article['checkouttime']:0;

    //only now
    unset($article);

    if (xarModIsHooked('uploads', 'articles', $pubtypeid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    // temp. fix to include dynamic data fields without changing templates
      $statuslist = array(1,2,4); //active(1), display/input (2), view/input (4)

    if (xarModIsHooked('dynamicdata','articles',$pubtypeid)) {
        list($properties) = xarModAPIFunc('dynamicdata','user','getitemfordisplay',
                                          array('module'   => 'articles',
                                                'itemtype' => $pubtypeid,
                                                'itemid'   => $aid,
                                                'preview'  => 0)
                                                );
        if (!empty($properties) && count($properties) > 0) {
            foreach (array_keys($properties) as $field) {
                if (in_array($properties[$field]->status,$statuslist)) {
                    $data[$field] = $properties[$field]->getValue();
                    // POOR mans flagging for transform hooks
                    $validation = $properties[$field]->validation;
                    if(substr($validation,0,10) == 'transform:') {
                        $data['transform'][] = $field;
                    }
                    // TODO: clean up this temporary fix
                    $data[$field.'_output'] = $properties[$field]->showOutput();
                }else {
                    //jojo - should we really do this?
                    //i suppose people that are using DD now are getting
                    //the object in the template in any case in full.
                    unset($properties[$field]);
                }
            }
        }
        $data['properties'] = $properties;
    }

    // Let any transformation hooks know that we want to transform some text.
    // You'll need to specify the item id, and an array containing all the
    // pieces of text that you want to transform (e.g. for autolinks, wiki,
    // smilies, bbcode, ...).
    $data['itemtype'] = $pubtypeid;
    // TODO: what about transforming DDfields ?
    // <mrb> see above for a hack, needs to be a lot better.

    // Summary is always included, is that handled somewhere else? (article config says i can ex/include it)
    // <mikespub> articles config allows you to call transforms for the articles summaries in the view function
    if (!isset($titletransform)) {

        if (empty($settings['titletransform'])) {
            $data['transform'][] = 'summary';
            $data['transform'][] = 'body';
            $data['transform'][] = 'notes';

        } else {
            $data['transform'][] = 'title';
            $data['transform'][] = 'summary';
            $data['transform'][] = 'body';
            $data['transform'][] = 'notes';
        }
    }
            $data['current_module'] = 'articles';
            $data['current_itemtype'] = $data['itemtype'];
    $data = xarModCallHooks('item', 'transform', $aid, $data, 'articles',$data['itemtype']);

    if (!empty($data['title'])) {

        $title = strip_tags($data['title']);
        xarTplSetPageTitle($title, xarVarPrepForDisplay($pubtypes[$pubtypeid]['descr']));

        // Save some variables to (temporary) cache for use in blocks etc.
        xarVarSetCached('Comments.title','title',$data['title']);
    }

/*
    if (!empty($q)) {
    // TODO: split $q into search terms + add style (cfr. handlesearch in search module)
        foreach ($data['transform'] as $field) {
            $data[$field] = preg_replace("/$q/","<span class=\"xar-search-match\">$q</span>",$data[$field]);
        }
    }
*/



    // Tell the hitcount hook not to display the hitcount, but to save it
    // in the variable cache.
    if (xarModIsHooked('hitcount','articles',$pubtypeid)) {
        xarVarSetCached('Hooks.hitcount','save',1);
        $dohits = 1;
    } else {
        $dohits = 0;
    }

    // Tell the ratings hook to save the rating in the variable cache.
    if (xarModIsHooked('ratings','articles',$pubtypeid)) {
        xarVarSetCached('Hooks.ratings','save',1);
        $dorating = 1;
    } else {
        $dorating = 0;
    }

    $data['ispreview']=0;

    $data['hooks'] = xarModCallHooks('item', 'display', $aid,
                                     array('module'    => 'articles',
                                           'itemtype'  => $pubtypeid,
                                           'title'     => $title,
                                           'returnurl' => xarModURL('articles', 'user', 'display',
                                                                    array('ptid' => $ptid,
                                                                          'aid' => $aid))
                                          ),
                                     'articles'
                                    );

    // Retrieve the current hitcount from the variable cache
    if ($dohits && xarVarIsCached('Hooks.hitcount','value')) {
        $data['counter'] = xarVarGetCached('Hooks.hitcount','value');
    } else {
        $data['counter'] = '';
    }

    // Retrieve the current rating from the variable cache
    if ($dorating && xarVarIsCached('Hooks.ratings','value')) {
        $data['rating'] = intval(xarVarGetCached('Hooks.ratings','value'));
    } else {
        $data['rating'] = '';
    }

    // Save some variables to (temporary) cache for use in blocks etc.
    xarVarSetCached('Blocks.articles','title',$data['title']);

    // Generating keywords from the API now instead of setting the entire
    // body into the cache.
    $keywords = xarModAPIFunc('articles',
                              'user',
                              'generatekeywords',
                              array('incomingkey' => $data['body']));

    xarVarSetCached('Blocks.articles','body',$keywords);
    xarVarSetCached('Blocks.articles','summary',$data['summary']);
    xarVarSetCached('Blocks.articles','aid',$aid);
    xarVarSetCached('Blocks.articles','ptid',$ptid);
    xarVarSetCached('Blocks.articles','cids',$cids);
    xarVarSetCached('Blocks.articles','authorid',$authorid);
    if (isset($data['author'])) {
        xarVarSetCached('Blocks.articles','author',$data['author']);
    }
// TODO: add this to articles configuration ?
//if ($shownavigation) {
    $data['aid'] = $aid;
    $data['cids'] = $cids;
    xarVarSetCached('Blocks.categories','module','articles');
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    xarVarSetCached('Blocks.categories','itemid',$aid);
    xarVarSetCached('Blocks.categories','cids',$cids);

    if (!empty($ptid) && !empty($pubtypes[$ptid]['descr'])) {
        xarVarSetCached('Blocks.categories','title',$pubtypes[$ptid]['descr']);
    }

    // optional category count
    if ($showcatcount && !empty($ptid)) {
        $pubcatcount = xarModAPIFunc('articles', 'user','getpubcatcount',
                                    // frontpage or approved
                                    array('status' => array(3,2),
                                          'ptid' => $ptid));
        if (!empty($pubcatcount[$ptid])) {
            xarVarSetCached('Blocks.categories','catcount',$pubcatcount[$ptid]);
        }
    } else {
    //    xarVarSetCached('Blocks.categories','catcount',array());
    }
//}
    // Module template depending on publication type
    $template = $pubtypes[$pubtypeid]['name'];
    $themedir = xarTplGetThemeDir();
    if (file_exists($themedir.'/modules/articles/user-archived-'.$template.'.xt')) {
        $template = $pubtypes[$pubtypeid]['name'];
    }else {
        $template = '';
    }

    // Page template depending on publication type (optional)
    // Note : this cannot be overridden in templates
    if (!empty($settings['page_template'])) {
        xarTplSetPageTemplateName($settings['page_template']);
    }

    // Specific layout within a template (optional)
    if (isset($layout)) {
        $data['layout'] = $layout;
    }

    // return template out
    return xarTplModule('articles', 'user', 'archived', $data, $template);
}

?>
