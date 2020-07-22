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
*/
/**
 * view articles
 *
 * catid=1   : category 1        == cids[0]=1
 * catid=1-2 : category 1 OR 2   == cids[0]=1&cids[1]=2
 * catid=1+2 : category 1 AND 2  == cids[0]=1&cids[1]=2&andcids=1
 *
 * @param template string Alternative default view-template name.
 * @param showcatcount integer Show the number of articles for each category (0..1)
 * @param showpubcount integer Show the number of articles for each publication type (0..1)
 *
 * @todo Provide a 'data only' mode that returns each item as data rather than through a rendered template
 *
 */
function articles_user_view($args)
{
    // Get parameters
    if (!xarVarFetch('startnum', 'int:0', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('cids',     'array', $cids,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('andcids',  'str',   $andcids,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('catid',    'str',   $catid,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('ptid',     'id',    $ptid,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('itemtype', 'id',    $itemtype,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    // TODO: put the query string through a proper parser, so searches on multiple words can be done.
    if (!xarVarFetch('q',        'pre:trim:passthru:str:1:200',   $q,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    // can't use list enum here, because we don't know which sorts might be used
    // TRUE - but we can provide some form of validation and normalisation.
    // The original 'regexp:/^[\w,]*$/' lets through *any* non-space character.
    // This validation will accept a list of comma-separated words, and will lower-case, trim
    // and strip out non-alphanumeric characters from each word.
    if (!xarVarFetch('sort',     'strlist:,:pre:trim:lower:alnum', $sort, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('numcols',  'int:0', $numcols,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('authorid', 'id',    $authorid,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('pubdate',  'str:1', $pubdate,   NULL, XARVAR_NOT_REQUIRED)) {return;}

    // This may not be set via user input, only e.g. via template tags, API calls, blocks etc.
    //    if(!xarVarFetch('startdate','int:0', $startdate, NULL, XARVAR_NOT_REQUIRED)) {return;}
    //    if(!xarVarFetch('enddate',  'int:0', $enddate,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    //    if(!xarVarFetch('where',    'str',   $where,     NULL, XARVAR_NOT_REQUIRED)) {return;}

    // Added to impliment an Alpha Pager
    if (!xarVarFetch('letter', 'pre:lower:passthru:str:1:20', $letter, NULL, XARVAR_NOT_REQUIRED)) return;

    // Override if needed from argument array (e.g. ptid, numitems etc.)
    extract($args);

    // Constants used throughout.
    //
    // articles module ID
    $c_modid = xarModGetIDFromName('articles');
    // status: front page or approved
    $c_posted = array(3,2);

    // Default parameters
    if (!isset($startnum)) $startnum = 1;

    if (!isset($ptid) && !empty($itemtype) && is_numeric($itemtype)) $ptid = $itemtype;

    // Get publication types
    $pubtypes = xarModAPIFunc('articles', 'user', 'getpubtypes');

    // Check that the publication type is valid
    if (!empty($ptid) && !isset($pubtypes[$ptid])) $ptid = NULL;

    // Check if we want the default 'front page'
    if (!isset($catid) && !isset($cids) && empty($ptid) && !isset($authorid)) {
        $ishome = 1;
        // default publication type
        $ptid = xarModGetVar('articles', 'defaultpubtype');
        // frontpage status
        $status = array(3);
    } else {
        $ishome = 0;
        // frontpage or approved status
        $status = $c_posted;
    }
    //convenience
    if (!isset($catid) && !isset($cids) && !isset($authorid)) {
        $pubtypehome = 1;
    } else {
        $pubtypehome = 0;
    }


    if (!isset($authorid)) {
        $authorid = NULL;
    }

    $isdefault = 0;
    $groupcats = 0;
    if (!empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
        // check default view for this type of articles
        if (empty($catid) && empty($cids) && empty($authorid) && empty($sort)) {
            if (substr($settings['defaultview'], 0, 1) == 'c') {
                $catid = substr($settings['defaultview'], 1);
            }
        }
        // check default grouping for this type of articles
        if (isset($settings['defaultgrouping']) && $settings['defaultgrouping']>0)  {
           $groupcats =$settings['defaultgrouping'];
        } else {
           $settings['defaultgrouping']=0;
        }
    } else {
        $string = xarModGetVar('articles', 'settings');
        if (!empty($string)) {
            $settings = unserialize($string);
        } else {
            $settings = array();
        }
    }

    // showpubcount is set on by default.
    if (!isset($settings['showpubcount'])) $settings['showpubcount'] = 1;

    // Set a number of flag defaults, if not over-ridden by the pubtype or user parameters.
    $flag_names = array(
        'showcategories', 'showprevnext', 'showcomments', 'showkeywords',
        'showhitcounts', 'showratings', 'showmonthview', 'showmap',
        'showpublinks', 'showcatcount', 'showpubcount', 'dotransform',
        'titletransform',
    );
    foreach($flag_names as $flag_name) {
        if (!isset($$flag_name)) $$flag_name = (empty($settings[$flag_name]) ? 0 : 1);
    }
    $dotransform = (int)$settings['dotransform'];
    //set for old installs that can error here
    $titletransform = isset($settings['titletransform']) ?(int)$settings['titletransform'] :0;

    // Do not transform titles if we are not transforming output at all.
    if (!$dotransform) $titletransform = 0;

    // Page template for frontpage or depending on publication type (optional)
    // Note : this cannot be overridden in templates
    if (!empty($settings['page_template'])) {
        xarTplSetPageTemplateName($settings['page_template']);
    }

    // TODO: make user-configurable too ?
    if (empty($settings['defaultsort'])) {
        $defaultsort = 'date';
    } else {
        $defaultsort = $settings['defaultsort'];
    }
    if (empty($sort)) {
        $sort = $defaultsort;
    }

    // support multi-column output
    if (!isset($numcols) || !is_numeric($numcols)) {
        if (empty($settings['number_of_columns'])) {
            // default is no multi-column output
            $numcols = 0;
        } else {
            $numcols = $settings['number_of_columns'];
        }
    }

    if ($numcols == 1) $numcols = 0;

    // allow articles to work without comments being activated
    if ($showcomments && !xarModIsHooked('comments', 'articles', $ptid)) $showcomments = 0;

    // allow articles to work without keywords being activated
    if ($showkeywords && !xarModIsHooked('keywords', 'articles', $ptid)) $showkeywords = 0;

    // allow articles to work without hitcounts being activated
    if ($showhitcounts && !xarModIsHooked('hitcount', 'articles', $ptid)) $showhitcounts = 0;

    // allow articles to work without ratings being activated
    if ($showratings && !xarModIsHooked('ratings', 'articles', $ptid)) $showratings = 0;

    $data = array();
    $data['pubtypehome'] = $pubtypehome;
    if (isset($ptid)) {
        xarVarSetCached('articles.'.$ptid, 'pubtypehome', $pubtypehome);
    }
    // TODO: show this *after* category list when we start from categories :)
    // Navigation links
    $data['publabel'] = xarML('Publication');
    $data['publinks'] = xarModAPIFunc('articles', 'user', 'getpublinks',
        array(
            'ptid' => $ishome ? '' : $ptid,
            'status' => $c_posted,
            'count' => $showpubcount
        )
    );
    if ($showmap) {
        $data['maplabel'] = xarML('View Article Map');
        $data['maplink'] = xarModURL('articles', 'user', 'viewmap', array('ptid' => !empty($ptid) ? $ptid : NULL));
    }
    if ($showmonthview) {
        $data['monthviewlabel'] = xarML('View by Month');
        $data['monthviewlink'] = xarModURL('articles', 'user', 'monthview', array('ptid' => !empty($ptid) ? $ptid : NULL));
    }

    $data['pager'] = '';
    $data['viewpager'] = '';

    // Add Sort to data passed to template so that we can automatically turn on alpha pager, if needed
    $data['sort'] = $sort;

    // Add current display letter, so that we can highlight the current filter in the alpha pager
    $data['letter']=$letter;

    // Get the users requested number of stories per page.
    // If user doesn't care, use the site default
    if (xarUserIsLoggedIn()) {
        // TODO: figure how to let users specify their settings
        // COMMENT: if the settings were split into separate module variables,
        // then they could all be individually over-ridden by each user.
        //$numitems = xarModUserGetVar('itemsperpage');
    }
    if (empty($numitems)) {
        if (!empty($settings['itemsperpage'])) {
            $numitems = $settings['itemsperpage'];
        } else {
            $numitems = 20;
        }
    }

    // turn $catid into $cids array and set $andcids flag
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
            if (!empty($cid) && preg_match('/^_?[0-9]+$/', $cid)) {
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

    // every field you always wanted to know about but were afraid to ask for :)
    $extra = array();
    $extra[] = 'author';

    // Note: we always include cids for security checks now (= performance impact if showcategories was 0)
    $extra[] = 'cids';
    if ($showhitcounts) $extra[] = 'counter';
    if ($showratings) $extra[] = 'rating';
    if (xarModIsHooked('dynamicdata', 'articles', $ptid)) $extra[] = 'dynamicdata';
    if (xarModIsHooked('uploads', 'articles', $ptid)) xarVarSetCached('Hooks.uploads', 'ishooked', 1);

    $now = time();

    if (empty($startdate) || !is_numeric($startdate) || $startdate > $now) $startdate = NULL;
    if (empty($enddate) || !is_numeric($enddate) || $enddate > $now) $enddate = $now;
    if (empty($pubdate) || !preg_match('/^\d{4}(-\d+(-\d+|)|)$/',$pubdate)) $pubdate = NULL;
    if (empty($where)) $where = NULL;

    // Keep the where clause with no filter for further use
    $wherenofilter = $where;

    // Modify the where clause if an Alpha filter has been specified.
    if (!empty($letter)) {
        // We will allow up to three initial letters, anything more than that is assumed to be 'Other'.
        // Need to also be very wary of SQL injection, since we are not using bind variables here.
        // TODO: take into account international characters.
        if (preg_match('/^[a-z]{1,3}$/i', $letter)) {
            $extrawhere = "title LIKE '$letter%'";
        } else {
            // Loop through the alphabet for the 'not in' part.
            $letterwhere = array();
            for($i = ord('a'); $i <= ord('z'); $i++) {
                $letterwhere[] = "title NOT LIKE '" . chr($i) . "%'";
            }
            $extrawhere = implode(' and ', $letterwhere);
        }
        if ($where == NULL) {
            $where = $extrawhere;
        } else {
            $where .= $extrawhere;
        }
    }
    // Provides an empty array for alphapager display (if empty)
    $data['alphapager'] = array();
    // Get articles
    $articles = xarModAPIFunc(
        'articles', 'user', 'getall',
        array(
            'startnum' => $startnum,
            'cids' => $cids,
            'andcids' => $andcids,
            'ptid' => (isset($ptid) ? $ptid : NULL),
            'authorid' => $authorid,
            'status' => $status,
            'sort' => $sort,
            'extra' => $extra,
            'where' => $where,
            'search' => $q,
            'numitems' => $numitems,
            'pubdate' => $pubdate,
            'startdate' => $startdate,
            'enddate' => $enddate
        )
    );

        // No articles
    if (!is_array($articles)) {
        // Error getting articles

       /* jojo - we still have a current error type!! amazing
          for now just return
       if (xarCurrentErrorType() == XAR_SYSTEM_EXCEPTION) {
             return; // throw back
        } elseif (xarCurrentErrorType() == XAR_USER_EXCEPTION) {
            // Get back the reason in string format
            $reason = xarCurrentError();
        }*/

        $data['output'] = xarML('Failed to retrieve articles');
        return $data;
    }

    // TODO : support different 'index' templates for different types of articles
    //        (e.g. News, Sections, ...), depending on what "view" the user
    //        selected (per category, per publication type, a combination, ...) ?

    if (!empty($authorid)) {
        $data['author'] = xarUserGetVar('name', $authorid);
        if (empty($data['author'])) {
            $data['author'] = xarML('Unknown');
        }
    }

    if (!empty($pubdate)) {
        $data['pubdate'] = $pubdate;
    }

    // Save some variables to (temporary) cache for use in blocks etc.
    xarVarSetCached('Blocks.articles', 'ptid', $ptid);
    xarVarSetCached('Blocks.articles', 'cids', $cids);
    xarVarSetCached('Blocks.articles', 'authorid', $authorid);
    if (isset($data['author'])) {
        xarVarSetCached('Blocks.articles', 'author', $data['author']);
    }
    if (isset($data['pubdate'])) {
        xarVarSetCached('Blocks.articles', 'pubdate', $data['pubdate']);
    }

    // TODO: add this to articles configuration ?
    if ($ishome) {
        $data['ptid'] = NULL;
        if (xarSecurityCheck('SubmitArticles',0)) {
            $data['submitlink'] = xarModURL('articles', 'admin', 'new');
        }
    } else {
        $data['ptid'] = $ptid;
        if (!empty($ptid)) {
            $curptid = $ptid;
        } else {
            $curptid = 'All';
        }
        if (count($cids) > 0) {
            foreach ($cids as $cid) {
                if (xarSecurityCheck('SubmitArticles', 0, 'Article', "$curptid:$cid:All:All")) {
                    $data['submitlink'] = xarModURL('articles', 'admin', 'new', array('ptid' => $ptid, 'catid' => $catid));
                    break;
                }
            }
        } elseif (xarSecurityCheck('SubmitArticles', 0, 'Article', "$curptid:All:All:All")) {
            $data['submitlink'] = xarModURL('articles', 'admin', 'new', array('ptid' => $ptid));
        }
    }
    $data['cids'] = $cids;
    $data['catid'] = $catid;
    xarVarSetCached('Blocks.categories', 'module', 'articles');
    xarVarSetCached('Blocks.categories', 'itemtype', $ptid);
    xarVarSetCached('Blocks.categories', 'cids', $cids);
    if (!empty($ptid) && !empty($pubtypes[$ptid]['descr'])) {
        xarVarSetCached('Blocks.categories', 'title', $pubtypes[$ptid]['descr']);
        // Note : this gets overriden by the categories navigation if necessary
        xarTplSetPageTitle(xarVarPrepForDisplay($pubtypes[$ptid]['descr']));
    }

    // optional category count
    if ($showcatcount) {
        if (!empty($ptid)) {
            $pubcatcount = xarModAPIFunc('articles', 'user', 'getpubcatcount',
                // frontpage or approved
                array('status' => $c_posted, 'ptid' => $ptid)
            );
            if (isset($pubcatcount[$ptid])) {
                xarVarSetCached('Blocks.categories','catcount',$pubcatcount[$ptid]);
            }
            unset($pubcatcount);
        } else {
            $pubcatcount = xarModAPIFunc('articles', 'user', 'getpubcatcount',
                // frontpage or approved
                array('status' => $c_posted, 'reverse' => 1)
            );

            if (isset($pubcatcount) && count($pubcatcount) > 0) {
                $catcount = array();
                foreach ($pubcatcount as $cat => $count) {
                    $catcount[$cat] = $count['total'];
                }
                xarVarSetCached('Blocks.categories','catcount',$catcount);
            }
            unset($pubcatcount);
        }
    } else {
        // xarVarSetCached('Blocks.categories','catcount',array());
    }

    $data['showpublinks'] = $showpublinks;
    $data['showprevnext'] = $showprevnext;
    $data['showcatcount'] = $showcatcount;

    if (empty($articles)) {
        $data['output'] = '';
        if ($ishome) {
            $template = 'frontpage';
        } elseif (!empty($ptid)) {
            $template = $pubtypes[$ptid]['name'];
        } else {
            // TODO: allow templates per category ?
            if (!isset($template)) $template = NULL;
        }
        return xarTplModule('articles', 'user', 'view', $data, $template);
    }

    // retrieve the number of comments for each article
    if ($showcomments) {
        $aidlist = array();
        foreach ($articles as $article) {
            $aidlist[] = $article['aid'];
        }
        $numcomments = xarModAPIFunc('comments', 'user', 'get_countlist',
            array('modid' => $c_modid, 'objectids' => $aidlist)
        );
    }

    // retrieve the keywords for each article
    if ($showkeywords) {
        $aidlist = array();
        foreach ($articles as $article) {
            $aidlist[] = $article['aid'];
        }

        $keywords = xarModAPIFunc('keywords', 'user', 'getmultiplewords',
            array(
                'modid' => $c_modid,
                'objectids' =>  $aidlist,
                'itemtype'  => $ptid
            )
        );
    }

    // retrieve the categories for each article
    $catinfo = array();
    if ($showcategories) {
        $cidlist = array();
        foreach ($articles as $article) {
            if (!empty($article['cids']) && count($article['cids']) > 0) {
                 foreach ($article['cids'] as $cid) {
                     $cidlist[$cid] = 1;
                 }
            }
        }
        if (count($cidlist) > 0) {
            $catinfo = xarModAPIFunc('categories','user','getcatinfo', array('cids' => array_keys($cidlist)));
            // get root categories for this publication type
            // get base categories for all if needed
            $catroots = xarModAPIFunc('articles', 'user', 'getrootcats',
                array('ptid' => $ptid, 'all' => TRUE)
            );
        }
        foreach ($catinfo as $cid => $info) {
            $catinfo[$cid]['name'] = xarVarPrepForDisplay($info['name']);
            $catinfo[$cid]['link'] = xarModURL('articles', 'user', 'view',
                array('ptid' => $ptid, 'catid' => (($catid && $andcids) ? $catid . '+' . $cid : $cid) )
            );

            // only needed when sorting by root category id
            $catinfo[$cid]['root'] = 0; // means not found under a root category
            // only needed when sorting by root category order
            $catinfo[$cid]['order'] = 0; // means not found under a root category
            $rootidx = 1;
            foreach ($catroots as $rootcat) {
                // see if we're a child category of this rootcat (cfr. Celko model)
                if ($info['left'] >= $rootcat['catleft'] && $info['left'] < $rootcat['catright']) {
                    // only needed when sorting by root category id
                    $catinfo[$cid]['root'] = $rootcat['catid'];
                    // only needed when sorting by root category order
                    $catinfo[$cid]['order'] = $rootidx;
                    break;
                }
                $rootidx++;
            }
        }
        // needed for sort function below
        $GLOBALS['artviewcatinfo'] = $catinfo;
    }

    $data['titles'] = array();

    // test 2-column output on frontpage
    $columns = array();
    $data['numcols'] = $numcols;

    $number = 0;
    $catgrouping = array();
    foreach ($articles as $article)
    {

        // TODO: don't include ptid and catid if we don't use short URLs
        // link to article
        $article['link'] = xarModURL('articles', 'user', 'display',
            // don't include pubtype id if we're navigating by category
            array(
                'ptid' => empty($ptid) ? NULL : $article['pubtypeid'],
                'catid' => $catid,
                'aid' => $article['aid']
            )
        );

        // N words/bytes more in article
        if (!empty($article['body'])) {
            // note : this is only an approximate number
            $wordcount = count(preg_split("/\s+/", strip_tags($article['body']), -1, PREG_SPLIT_NO_EMPTY));
            $article['words'] = $wordcount;

            // byte-count is less CPU-intensive -> make configurable ?
            $article['bytes'] = strlen($article['body']);
        } else {
            $article['words'] = 0;
            $article['bytes'] = 0;
        }

        // current publication type
        $curptid = $article['pubtypeid'];

    // TODO: make time display user/config dependent
        // publication date of article (if needed)
        //jojo - review all this and how we can do this with the new DD scenario
        //this works but some seems redundant
        foreach ($pubtypes[$curptid]['config'] as $field => $value) {
            if (empty($value['label'])) {
                continue;
            }
             $article[$field.'_value'] = $article[$field];
            if (!empty($value['validation']) && (substr($value['validation'],0,2) == 'a:')) {
                $data['validation'][$field] = unserialize($value['validation']);
            } else {
                 $data['validation'][$field] = array();
            }
            switch ($value['format']) {
                case 'calendar':
                    if (!empty($article[$field])) {
                        // legacy support for $date variable in templates
                          $value[$field] =  $article[$field];
                        if ($field == 'pubdate') {
                            // the date for this field is represented in the user's timezone for display
                            $article['date'] = trim(xarLocaleFormatDate("%a, %d %b %Y %H:%M:%S %Z",$article[$field]));
                        }
                    } else {
                        $article[$field] = '';
                        // legacy support for $date variable in templates
                        if ($field == 'pubdate') {
                            $article['date'] = '';
                        }
                    }
                    // all calendar fields are now passed "as is", so you can format them in the templates
                    break;

                case 'textbox':
                case 'textarea':
                    $data[$field] = xarVarPrepHTMLDisplay($article[$field]);
                    // fall through
                case 'urltitle':
                case 'dropdown':
                case 'fileupload':
                default:
                    if (!empty($article[$field])) {
                        if (empty($value['validation'])) {
                            $value['validation'] = '';
                        }
                        $value[$field] =  $article[$field];
                        $article[$field] = xarModAPIFunc('dynamicdata','user','showoutput',
                            array(
                                'name' => $field,
                                'type' => $value['format'],
                                'label' => isset($value['label'])?$value['label']:'',
                                'validation' => $value['validation'],
                                'value' => $article[$field]
                            )
                        );
                    }
                    break;
            }
            $article[$field.'_output'] =&  $article[$field];
        }
        // TODO: make configurable?
        $article['redirect'] = xarModURL('articles', 'user', 'redirect',
            array('ptid' => $curptid, 'aid' => $article['aid'])
        );

        // number of comments for this article
        if ($showcomments) {
            if (empty($numcomments[$article['aid']])) {
                $article['numcomments'] = 0;
                $article['comments'] = xarML('no comments');
            } elseif ($numcomments[$article['aid']] == 1) {
                $article['numcomments'] = 1;
                $article['comments'] = xarML('1 comment');
            } else {
                $article['numcomments'] = $numcomments[$article['aid']];
                $article['comments'] = xarML('#(1) comments', $numcomments[$article['aid']]);
            }
        } else {
            $article['comments'] = '';
        }

        // keywords for this article
        if ($showkeywords) {
            if (empty($keywords[$article['aid']])) {
                $article['keywords'] = '';
            } else {
                $article['keywords'] = $keywords[$article['aid']];
            }
        } else {
            $article['keywords'] = '';
        }

        // TODO: improve the case where we have several icons :)
        $article['topic_icons'] = '';
        $article['topic_images'] = array();
        $articles['topic_urls'] = array();
        $articles['topic_names'] = array();

        // categories this article belongs to
        $article['categories'] = array();
       //setup an array with articles in each array by category

        if ($showcategories && !empty($article['cids']) &&
            is_array($article['cids']) && count($article['cids']) > 0) {

            $cidlist = $article['cids'];
            // order cids by root category order
            usort($cidlist,'articles_view_sortbyorder');
            // order cids by root category id
            //usort($cidlist,'articles_view_sortbyroot');
            // order cids by position in Celko tree
            //usort($cidlist,'articles_view_sortbyleft');

            $isfirst = 1;
            foreach ($cidlist as $cid) {
                $item = array();
                if (!isset($catinfo[$cid])) {
                    // oops
                    continue;
                } elseif (in_array($cid,$cids) && $andcids) {
                    // we're already selecting on this category -> don't show
                    continue;
                }
                $item['cname'] = $catinfo[$cid]['name'];
                $item['clink'] = $catinfo[$cid]['link'];
                $item['root'] = $catinfo[$cid]['root'];
                $item['order'] = $catinfo[$cid]['order'];
                $item['parent'] = $catinfo[$cid]['parent'];
                $item['cid'] = $catinfo[$cid]['cid'];
                $item['description'] = empty($catinfo[$cid]['description']) ? $catinfo[$cid]['name'] : $catinfo[$cid]['description'];

                if ($isfirst) {
                    $item['cjoin'] = '';
                    $isfirst = 0;
                } else {
                    $item['cjoin'] = '|';
                }
                $article['categories'][] = $item;
                if ($groupcats >0) { ///we want grouping

                    if ($item['parent'] == $groupcats) {
                       //add the article to the cid array for output and placeholder for later processing
                       $catgrouping[$cid][$article['aid']]= $article['summary'];
                    }
                }
                $article['topic_urls'][] = $catinfo[$cid]['link'];
                $article['topic_names'][] = xarVarPrepForDisplay($catinfo[$cid]['name']);

                if (!empty($catinfo[$cid]['image'])) {
                    $image = xarTplGetImage($catinfo[$cid]['image'],'categories');
                    $article['topic_icons'] .= '<a href="'. $catinfo[$cid]['link'] .'">'.
                                            '<img src="'. $image .
                                            '" alt="'. xarVarPrepForDisplay($catinfo[$cid]['name']) .'" />'.
                                            '</a>';
                    $article['topic_images'][] = $image;
                }
            }
        }


        // multi-column display (default from left to right, then from top to bottom)
        $article['number'] = $number;
        if (!empty($numcols)) {
            $col = $number % $numcols;
        } else {
            $col = 0;
        }
        if (!isset($columns[$col])) {
            $columns[$col] = array();
        }
        // RSS Processing
        $current_theme = xarVarGetCached('Themes.name', 'CurrentTheme');
        if (($current_theme == 'rss') or ($current_theme == 'atom')){
            $article['rsstitle'] = htmlspecialchars($article['title']);
            //$article['rssdate'] = strtotime($article['date']);
            $article['rsssummary'] = preg_replace('<br />', "\n", $article['summary']);
            $article['rsssummary'] = xarVarPrepForDisplay(strip_tags($article['rsssummary']));
            $article['rsscomment'] = xarModURL('comments', 'user', 'display', array('modid' => $c_modid, 'objectid' => $article['aid']));
            // $article['rsscname'] = htmlspecialchars($item['cname']);
            // <category>#$rsscname#</category>
        }



        if ($dotransform) {
            $article['itemtype'] = (int)$article['pubtypeid'];

            if ($titletransform) {
                $article['transform'] = array('title', 'summary', 'body', 'notes');
            } else {
                $article['transform'] = array('summary', 'body', 'notes');
            }
            $article = xarModCallHooks('item', 'transform', (int)$article['aid'], $article, 'articles',$article['itemtype']);
        }

        $data['titles'][$article['aid']] = $article['title'];
        //$data['categories'][$article['aid']] =  $item;
        // fill in the summary template for this article
        $summary_template = $pubtypes[$article['pubtypeid']]['name'];
        if (!empty($settings['defaultgrouping']) && !empty($cid)) {
            $catgrouping[$cid][$article['aid']] = xarTplModule('articles', 'user', 'summary', $article, $summary_template);
        }
        $columns[$col][$article['aid']] = xarTplModule('articles', 'user', 'summary', $article, $summary_template);
        $number++;

    }
    unset($articles);

    //we need to get the values into the catgrouping
    if (is_array($catgrouping) && count($catgrouping) > 0) {
          asort($catgrouping);
    } else {
          $catgrouping='';
    }
    $data['catgrouping'] = $catgrouping;

    if ($showcategories) {
        unset($GLOBALS['artviewcatinfo']);
    }

    $data['number'] = $number;
    $data['columns'] = $columns;

    if (!empty($numcols) && $number > 0) {
        $maxcols = $number > $numcols ? $numcols : $number;
        $data['colwidth'] = round(100 / $maxcols);
    }

    // TODO: verify for other URLs as well
    if ($ishome) {
        if (!empty($numcols) && $numcols > 1) {
            // if we're currently showing more than 1 column
            $data['showcols'] = 1;
        } else {
            $defaultcols = $settings['number_of_columns'];
            if ($defaultcols > 1) {
                // if the default number of columns is more than 1
                $data['showcols'] = $defaultcols;
            }
        }
    }
    $data['output'] = '';

    // Count items applying eventually alpha filter
    $itemscount = xarModAPIFunc('articles', 'user', 'countitems',
            array(
                'cids' => $cids,
                'andcids' => $andcids,
                'ptid' => (isset($ptid) ? $ptid : NULL),
                'authorid' => $authorid,
                'status' => $status,
                'where' => $where,
                'q' => $q,
                'pubdate' => $pubdate,
                'startdate' => $startdate,
                'enddate' => $enddate
            )
    );

    // If there is an alpha filter get the count without alpha filtering.
    if ($wherenofilter != $where) {
        $itemscountnofilter = xarModAPIFunc('articles', 'user', 'countitems',
            array(
                'cids' => $cids,
                'andcids' => $andcids,
                'ptid' => (isset($ptid) ? $ptid : NULL),
                'authorid' => $authorid,
                'status' => $status,
                'where' => $wherenofilter,
                'q' => $q,
                'pubdate' => $pubdate,
                'startdate' => $startdate,
                'enddate' => $enddate
            )
        );
    }
    else {
        $itemscountnofilter = $itemscount;
    }

    // Pager
    $data['pager'] = xarTplGetPager($startnum, $itemscount,
        xarModURL('articles', 'user', 'view',
            array(
                'ptid' => ($ishome ? NULL : $ptid),
                'catid' => $catid,
                'authorid' => $authorid,
                'sort' => ($sort == $defaultsort ? NULL : $sort),
                'letter' => $letter,
                'startnum' => '%%'
            )
        ),
    $numitems);

    $data['viewpager'] = $data['pager'];
    $data['sortlinks'] = array();

    // TODO: sorting on other fields ?
    if ($itemscountnofilter > 1) {
        $data['pager'] .= '<br /><br />' . xarML('Sort by');
        $sortlist = array();
        $sortlist['date'] = xarML('Date');
        $sortlist['title'] = xarML('Title');
        if ($showhitcounts) {
            $sortlist['hits'] = xarML('Hits');
        }
        if ($showratings) {
            $sortlist['rating'] = xarML('Rating');
        }
        foreach ($sortlist as $sname => $stitle) {
            if (empty($sort) && $sname == $defaultsort) {
                $data['pager'] .= '&nbsp;' . $stitle . '&nbsp;';
                $data['sortlinks'][] = array('stitle' => $stitle, 'slink'  => '');
                continue;
            } elseif ($sname == $sort) {
                $data['pager'] .= '&nbsp;' . $stitle . '&nbsp;';
                $data['sortlinks'][] = array('stitle' => $stitle, 'slink'  => '');
                continue;
            }
            // Note: 'sort' is used to override the default start view too
            if ($sname == $defaultsort && !$isdefault) {
                $sortlink = xarModURL('articles','user','view',
                                     array('ptid' => ($ishome ? NULL : $ptid),
                                           'catid' => $catid,
                                           'authorid' => $authorid));
            } else {
                $sortlink = xarModURL('articles','user','view',
                                     array('ptid' => ($ishome ? NULL : $ptid),
                                           'catid' => $catid,
                                           'authorid' => $authorid,
                                           'sort' => $sname));
            }
            $data['pager'] .= '&nbsp;<a href="' . $sortlink . '">' .
                              $stitle . '</a>&nbsp;';
            $data['sortlinks'][] = array('stitle' => $stitle, 'slink'  => $sortlink);
        }
    }

    // Provides only available first letters for the alpha pager display
    if ($sort == 'title' && $itemscountnofilter > $numitems) {
        // Get all articles
        $articles_nofilter = xarModAPIFunc(
            'articles', 'user', 'getall',
            array(
                'cids' => $cids,
                'andcids' => $andcids,
                'ptid' => (isset($ptid) ? $ptid : NULL),
                'authorid' => $authorid,
                'status' => $status,
                'fields' => array('title'),
                'sort' => $sort,
                'extra' => NULL,
                'where' => $wherenofilter, // the alpha filter is not applied
                'search' => $q,
                'pubdate' => $pubdate,
                'startdate' => $startdate,
                'enddate' => $enddate
            )
        );

        $nchar = 1; // Set the number of chars
        $alpha_letters = array();
        $alpha_others = 0;

        foreach($articles_nofilter as $article) {
            // TODO: use UTF-8 safe functions and deaccent function for internationalization
            $first_letter = strtolower(substr($article['title'], 0, $nchar));
            if (preg_match('/^[a-z]{1,3}$/i', $first_letter)) {
                if (!array_key_exists($first_letter, $alpha_letters)) {
                    $alpha_letters[$first_letter]['count'] = 1;
                }
                else {
                    $alpha_letters[$first_letter]['count']++;
                }
            }
            else {
                $alpha_others++;
            }
        }
        // Order alphabetically
        ksort($alpha_letters);

        // Unshift the 'all' item if there is at least one article to display
        if (count($articles_nofilter)>0) {
            $alpha_letters = array_reverse($alpha_letters, TRUE);
            $alpha_letters['all']['count'] = count($articles_nofilter);
            $alpha_letters = array_reverse($alpha_letters, TRUE);
        }

        // Add eventually an 'other' item to the end.
        if ($alpha_others > 0)
            $alpha_letters['other']['count'] = $alpha_others;

        // Create link URLs and captions
        foreach($alpha_letters as $l => &$el) {
            switch($l) {
                case 'all':
                    $el['link'] = xarServerGetCurrentURL(array('letter' => NULL, 'startnum' => NULL, 'q' => NULL));
                    $el['caption'] = xarML('All');
                    break;
                case 'other':
                    $el['link'] = xarServerGetCurrentURL(array('letter' => 'other', 'startnum' => NULL, 'q' => NULL));
                    $el['caption'] = xarML('Other');
                    break;
                default:
                    $el['link'] = xarServerGetCurrentURL(array('letter' => strtoupper($l), 'startnum' => NULL, 'q' => NULL));
                    $el['caption'] = strtoupper($l);
                    // TODO: use UTF-8 safe function instead.
                    break;
            }
        }
        $data['alphapager'] = $alpha_letters;
    }

    // Specific layout within a template (optional)
    if (isset($layout)) $data['layout'] = $layout;

    if ($ishome == 1) {
        $template = 'frontpage';
    } elseif (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
    } else {
        // TODO: allow templates per category ?
        if (!isset($template)) $template = NULL;
    }
    $data['itemtype'] = isset($ptid)?$ptid:0;

    //common menu items
    $data['menulinks'] = xarModAPIFunc('articles','user','getmenulinks');
     return xarTplModule('articles', 'user', 'view', $data, $template);
}

/**
 * sorting function for article categories
 */

function articles_view_sortbyroot ($a,$b)
{
    if ($GLOBALS['artviewcatinfo'][$a]['root'] == $GLOBALS['artviewcatinfo'][$b]['root']) {
        return articles_view_sortbyleft($a,$b);
    }
    return ($GLOBALS['artviewcatinfo'][$a]['root'] > $GLOBALS['artviewcatinfo'][$b]['root']) ? 1 : -1;
}

function articles_view_sortbyleft ($a,$b)
{
    if ($GLOBALS['artviewcatinfo'][$a]['left'] == $GLOBALS['artviewcatinfo'][$b]['left']) return 0;
    return ($GLOBALS['artviewcatinfo'][$a]['left'] > $GLOBALS['artviewcatinfo'][$b]['left']) ? 1 : -1;
}

function articles_view_sortbyorder ($a,$b)
{
    if ($GLOBALS['artviewcatinfo'][$a]['order'] == $GLOBALS['artviewcatinfo'][$b]['order']) {
        return articles_view_sortbyleft($a,$b);
    }
    return ($GLOBALS['artviewcatinfo'][$a]['order'] > $GLOBALS['artviewcatinfo'][$b]['order']) ? 1 : -1;
}

function articles__view__mlstrings()
{
    // For some reason strings are repeated again and again. We need to be sure it ends in common.
    xarML('Read More');
    xarML('word');
    xarML('words');
    // @TODO we need to investigate why only those ML strings are not parsed correctly in MLS/translation in theme overrides not existing in modules.
    // eg user-summary-mynewpubtype.xt
    // This should not be necessary!
}
?>
