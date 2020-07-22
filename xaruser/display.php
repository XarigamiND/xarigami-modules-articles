<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author Xarigami Team
 * @author mikespub
 */
/**
 * Display article
 *
 * @param int aid
 * @param int page
 * @param int ptid The publication Type ID
 * @return array with template information
 */
function articles_user_display($args)
{
    // Get parameters from user
    if(!xarVarFetch('aid',  'id',    $aid,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    // page within the article for multipaged articles
    //- allow int or string in case we want to have nicer url
    // int always will be used for inbuilt multipaged articles using <!--pagebreak-->
    //also used for $c_section_head = '/<!-- section: (.+) -->/';
    if(!xarVarFetch('page', 'isset', $page,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('tab', 'isset', $tab,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    // this is used to determine whether we come from a pubtype-based view or a
    // categories-based navigation
    if(!xarVarFetch('ptid', 'id',    $ptid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
/*
    // TEST - highlight search terms
    if(!xarVarFetch('q',     'str',  $q,     NULL, XARVAR_NOT_REQUIRED)) {return;}
*/
    // if passed in via URL we can override
    extract ($args);

    // Defaults
    if (!isset($page)) {
        $page = 1;
    }
     // Defaults
    if (!isset($tab)) {
        $tab = 1;
    }
    // via arguments only
    if (!isset($preview)) {
        $preview = 0;
    }
    // via arguments only: if $sandbox is set, it will not export title and block infos in order
    // to not interfere with any other article display function call.
    $sandbox = isset($sandbox) ? $sandbox != FALSE : FALSE;

    // Load API
    if (!xarModAPILoad('articles', 'user')) return;

    if ($preview) {
        if (!isset($article)) {
            return xarML('Invalid article');
        }
        $aid = $article['aid'];
    } elseif (!isset($aid) || !is_numeric($aid) || $aid < 1) {
        // Try to get the last default frontpage
        $articles = xarModAPIFunc('articles', 'user', 'getall',
        array(
            'cids' => NULL,
            'andcids' => NULL,
            'ptid' => NULL,
            'status' => array(3), // only frontpage status
            'sort' =>  'pubdate',
            'numitems' => 1));
        if (!empty($articles) && isset($articles[0]['aid'])) {
            $aid = $articles[0]['aid'];
        } else {
            return xarResponseNotFound(xarML('Could not find any article using this reference.'));
        }
    }

    // Get article
    if (!$preview) {
        $article = xarModAPIFunc('articles', 'user', 'get',
                                array('aid' => $aid,
                                      'withcids' => TRUE));
    }
    // jojo  $article is an array if valid article, FALSE if no privilege and NULL if not found
    if (!is_array($article)) {
        if ($article === FALSE) { //the article is there but there is a view privilege issue
            $msg = xarML('You do not have the required access level to read this article', 'user', 'get', 'articles');
            return xarResponseForbidden($msg);
        } else { //returns NULL - there is no such article
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


    $usearchiving = xarModGetVar('articles','usearchiving');
    $data['archived'] = FALSE;
    if ($article['status'] == 5 )   { //archived
        $data['archived'] = TRUE;
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

    $viewstatus = array(2,3); //only show published and front page to those without at least edit level privs

    // Security check for EDIT access
    if (!$preview) {
        $input = array();
        $input['article'] = $article;
        $input['mask'] = 'EditArticles';
        $data['aid'] = $article['aid'];
        $data['ptid'] = $article['pubtypeid'];

        $data['cids'] = $article['cids'];
        $ret = '';
        //first check if the article is archived and setup the template to be shown
        if ($data['archived'] && $usearchiving) { //We want to use archiving and the article is archived
            $data['title']= $article['title'];
            $data['archivenotice'] =TRUE;
            $data['displayarchived'] = 0;
            $hasaccess = xarModGetVar('articles','archiveaccess');
            $testaccess = xarModAPIFunc('roles','user','checkgroup',array('gid'=>$hasaccess,'uid'=>xarUserGetVar('uid'),'isancestor'=>TRUE));
            //Administration level access?
            $isadmin = xarSecurityCheck('AdminArticles', 0, 'Article', "{$article['pubtypeid']}:All:All:All");
            $data['testaccess'] = $isadmin || $testaccess;
             $viewstatus = array(2,3,5);
             $template = $pubtypes[$pubtypeid]['name'];
             $themedir =xarTplGetThemeDir();
            if (file_exists($themedir.'/modules/articles/user-archived-'.$template.'.xt')) {
                $template = $pubtypes[$pubtypeid]['name'];
            }else {
                $template = '';
            }
            $ret= xarTplModule('articles','user','archived',$data,$template);
        }
        //now check for edit and access rights
        if (xarModAPIFunc('articles','user','checksecurity',$input)) {
            if (isset($usecheckoutin) && $usecheckoutin == 1 && $article['checkout']==1 && ($thisuser != $article['editor'])) {
                $data['editurl'] = '';
            } else {
                $return_url = xarServerGetCurrentURL(array(), FALSE);
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
    }
    $data['edittitle'] = xarML('Edit');


    $data['topic_icons'] = '';
    $data['topic_images'] = array();
    $data['topic_urls'] = array();
    $data['topic_names'] = array();

    // Handles categories


    // Continuous category navigation. The categories are then included in the articles urls.
    $passNavCategory = empty($settings['passnavcategory']) ? FALSE : $settings['passnavcategory'] != 0;

    if ($passNavCategory) {
        $artcids = $cids;
        unset($cids);
        // TODO: his is a copy of view.php code. This is also used a lot in categories API. Could we not prevent redundant code?
        // turn $catid into $cids array and set $andcids flag
        if (!xarVarFetch('cids',     'array', $cids,      NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('andcids',  'str',   $andcids,   NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!xarVarFetch('catid',    'str',   $catid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
        if (!empty($catid)) {
            if (strpos($catid, ' ')) {
                $cids = explode(' ', $catid);
                $andcids = TRUE;
            } elseif (strpos($catid, '+')) {
                $cids = explode('+', $catid);
                $andcids = TRUE;
            } elseif (strpos($catid, '-')) {
                $cids = explode('-', $catid);
                $andcids = FALSE;
            } else {
                $cids = array($catid);
                if (strstr($catid, '_')) {
                    $andcids = FALSE; // don't combine with current category
                } else {
                    $andcids = TRUE;
                }
            }
        } else {
            if (empty($cids)) $cids = array();
            if (!isset($andcids)) $andcids = TRUE;
        }
        // rebuild $catid in standard format again
        $catid = NULL;
        if (count($cids) > 0) {
            $seencid = array();
            foreach ($cids as $cid) {
                // make sure cids are numeric
                if (!empty($cid) && preg_match('/^_?[0-9]+$/', $cid) && in_array($cid, $artcids)) {
                    $seencid[$cid] = 1;
                    // xart-000639: we don't really want the page to be displayed if the user has no
                    // viewprivilege for the $cid categories passed in args.
                    // we might prefer to raison an exception or a xarResponseForbidden() or user error
                    $name = xarModAPIFunc('categories', 'user', 'cid2name', array('cid' => $cid));
                    if (!xarSecurityCheck('ViewCategories', 0, 'Category', "$name:$cid")) {
                        return xarResponseForbidden(xarML('You have no permission to access a category.'));
                    }
                }
            }
            $cids = array_keys($seencid);
            //
            sort($cids, SORT_NUMERIC);
            if ($andcids) {
                $catid = join('+', $cids);
            } else {
                $catid = join('-', $cids);
            }
        }
    } else {
        $andcids = FALSE;
    }

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
            if ($preview) {
                $article['body'] = preg_replace('/<!--pagebreak-->/',
                                                '<hr/><div style="text-align: center;">'.xarML('Page Break').'</div><hr/>',
                                                $article['body']);
                $data['previous'] = '';
                $data['next'] = '';
            } else {
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
                    $passNavCategory ?
                        array('ptid' => $ptid, 'aid' => $aid, 'page' => '%%', 'cids' => $cids, 'andcids' => $andcids) :
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
            }
        } else if (!empty($sectioncount)) {
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
    if (isset($prevnextart)) {
        $settings['prevnextart'] = $prevnextart;
    }
    if (!empty($settings['prevnextart']) && (!$preview || $preview ==0)) {
        if(!array_key_exists('defaultsort',$settings)) {
            $settings['defaultsort'] = 'aid';
        }

        $prevart = xarModAPIFunc('articles','user','getprevious',
                        $passNavCategory ?
                            array('aid' => $aid, 'ptid' => $ptid, 'sort' => $settings['defaultsort'], 'status' => array(3,2),
                                  'enddate' => time(), 'cids' => $cids, 'andcids' => $andcids) :
                            array('aid' => $aid, 'ptid' => $ptid, 'sort' => $settings['defaultsort'], 'status' => array(3,2), 'enddate' => time())
                               );

        if (!is_array($prevart)) {
            //We cannot throw an exception here if the prev article cannot
            //be viewed otherwise it may prevent a good article from being displayed
            //if we are displaying an article within a specific category we need to get prev in that category now
            //(see changes in get prev)
            $prevart= '';
        }

        if (!empty($prevart['aid'])) {
            //Make all previous article info available to template
            $data['prevartinfo'] = $prevart;

            $data['prevart'] = xarModURL('articles','user','display',
                                    $passNavCategory ?
                                         array('ptid' => $prevart['pubtypeid'], 'aid' => $prevart['aid'], 'cids' => $cids, 'andcids' => $andcids) :
                                         array('ptid' => $prevart['pubtypeid'], 'aid' => $prevart['aid'])
                                );
        } else {
            $data['prevart'] = '';
        }

        $nextart = xarModAPIFunc('articles','user','getnext',
                        $passNavCategory ?
                             array('aid' => $aid, 'ptid' => $ptid, 'sort' => $settings['defaultsort'], 'status' => array(3,2),
                                  'enddate' => time(), 'cids' => $cids, 'andcids' => $andcids) :
                             array('aid' => $aid, 'ptid' => $ptid, 'sort' => $settings['defaultsort'], 'status' => array(3,2), 'enddate' => time())
                                );

        if (!is_array($nextart)) {
            //We cannot throw an exception here if the next article cannot
            //be viewed otherwise it may prevent a good article from being displayed
            //if we are displaying an article within a specific category we need to get next in that category
             //(see changes in get next)
           $nextart= '';
        }

        if (!empty($nextart['aid'])) {
            //Make all next art info available to template
            $data['nextartinfo'] = $nextart;

            $data['nextart'] = xarModURL('articles','user','display',
                                    $passNavCategory ?
                                        array('ptid' => $nextart['pubtypeid'], 'aid' => $nextart['aid'], 'cids' => $cids, 'andcids' => $andcids) :
                                        array('ptid' => $nextart['pubtypeid'], 'aid' => $nextart['aid'])
                            );
        } else {
            $data['nextart'] = '';
        }
    } else {
        $data['prevart'] = '';
        $data['nextart'] = '';
    }

    // Display article
    //make sure we set the uploads is hooked before we do displays
    if (xarModIsHooked('uploads', 'articles', $pubtypeid)) {
        xarVarSetCached('Hooks.uploads','ishooked',1);
    }
    // Fill in the fields based on the pubtype configuration for standard pubtype fields
    foreach ($pubtypes[$pubtypeid]['config'] as $field => $value) {
        if (empty($value['label'])) {
            $data[$field] = '';
            continue;
        }
       $data[$field.'_value'] = $article[$field];
       $data[$field] = $article[$field];
        if (isset($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
            $data['validation'][$field] = unserialize($value['validation']);
        } else {
             $data['validation'][$field] = array();
        }
        switch ($value['format']) {
            case 'username':
            case 'userlist':
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
                $data[$field] = $article[$field];
                break;
            case 'calendar':
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
                break;
            case 'urltitle':
                // fall through
        // TEST ONLY
            case 'webpage':
            case 'imagelist':
            case 'image':
            case 'email':
            case 'fileupload':
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
                break;
        }

        $data[$field.'_output'] = $data[$field]; //compatibility with DD . Would be nice to not have both of these...

    }

    //checkout fields in case we need them
    $data['checkout'] = isset($article['checkout'])?$article['checkout']:0;
    $data['editor'] = isset($article['editor'])?$article['editor']:'';
    $data['checkouttime'] = isset($article['checkouttime'])?$article['checkouttime']:0;
    //only now
    unset($article);

    // temp. fix to include dynamic data fields without changing templates
    //status list of properties to display (eg not disabled in any way) for display function
    $statuslist = array(1,2,5,7); //active(1), input and display (2), ignored on input (5), hidden input and display (7)
    if (xarModIsHooked('dynamicdata','articles',$pubtypeid)) {
        //use getitemfordisplay function - this should by default get only items that are 'displayable'
        list($properties) = xarModAPIFunc('dynamicdata','user','getitemfordisplay',
                                          array('module'   => 'articles',
                                                'itemtype' => $pubtypeid,
                                                'itemid'   => $aid,
                                                'preview'  => $preview));

        if (!empty($properties) && count($properties) > 0) {

            foreach ($properties as $fieldname=> $field) {
                if (in_array($field->status,$statuslist)) {
                    $data[$fieldname] = $properties[$fieldname]->getValue();

                    // POOR mans flagging for transform hooks
                    $validation = $properties[$fieldname]->validation;
                    if(substr($validation,0,10) == 'transform:') {
                        $data['transform'][] = $fieldname;
                    }
                    // TODO: clean up this temporary fix
                    $data[$fieldname.'_output'] = $properties[$fieldname]->showOutput();
                }else { //not set as displayable
                    //jojo - should we really do this?
                    //i suppose people that are using DD now are getting
                    //the object in the template in any case in full but
                    unset($properties[$fieldname]);
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
        if (!$sandbox) {
            xarTplSetPageTitle($title, xarVarPrepForDisplay($pubtypes[$pubtypeid]['descr']));

            // Save some variables to (temporary) cache for use in blocks etc.
            xarVarSetCached('Comments.title','title',$data['title']);
        }
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

    // Hooks
    if ($preview) {
        $data['hooks'] = '';
        $data['ispreview']=1;
    } else {
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
    }

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

    if (!$sandbox) {
        // Save some variables to (temporary) cache for use in blocks etc.
        xarVarSetCached('Blocks.articles','title',$data['title']);
    
        // Generating keywords from the API now instead of setting the entire
        // body into the cache.
        $keywords = xarModAPIFunc('articles', 'user', 'generatekeywords',
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
        
        xarVarSetCached('Blocks.categories','module','articles');
        xarVarSetCached('Blocks.categories','itemtype',$ptid);
        xarVarSetCached('Blocks.categories','itemid',$aid);
        xarVarSetCached('Blocks.categories','cids',$cids);
    
        if (!empty($ptid) && !empty($pubtypes[$ptid]['descr'])) {
            xarVarSetCached('Blocks.categories','title',$pubtypes[$ptid]['descr']);
        }
    }
    
    // TODO: add this to articles configuration ?
    //if ($shownavigation) {
    $data['aid'] = $aid;
    $data['cids'] = $cids;
    

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

    // Page template depending on publication type (optional)
    // Note : this cannot be overridden in templates
    if (empty($preview) && !empty($settings['page_template'])) {
        xarTplSetPageTemplateName($settings['page_template']);
    }

    // Specific layout within a template (optional)
    if (isset($layout)) {
        $data['layout'] = $layout;
    }
    $data['ispreview'] = $preview;

    // return template out
    return xarTplModule('articles', 'user', 'display', $data, $template);
}

?>
