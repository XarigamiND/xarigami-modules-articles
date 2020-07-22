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
 * search articles (called as hook from search module, or directly with pager)
 *
 * @param id $args['objectid'] could be the query ? (currently unused)
 * @param array $args['extrainfo'] all other parameters ? (currently unused)
 * @param string andcids
 * @param string catid
 * @param id ptid Publication type id
 * @param array ptids Array of publication type ids
 * @return array output
 */
function articles_user_search($args)
{
    // pager stuff
    if(!xarVarFetch('startnum', 'int:0', $startnum,  NULL, XARVAR_NOT_REQUIRED)) {return;}

    // categories stuff
    if(!xarVarFetch('cids',     'array', $cids,      NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('andcids',  'str',   $andcids,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('catid',    'str',   $catid,     NULL, XARVAR_NOT_REQUIRED)) {return;}

    // single publication type when called via the pager
    if(!xarVarFetch('ptid',     'id',    $ptid,      NULL, XARVAR_NOT_REQUIRED)) {return;}

    // multiple publication types when called via search hooks
    if(!xarVarFetch('ptids',    'array', $ptids,     NULL, XARVAR_NOT_REQUIRED)) {return;}

    // date stuff via forms
    if(!xarVarFetch('articles_startdate','str', $startdate, NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('articles_enddate',  'str', $enddate,   NULL, XARVAR_NOT_REQUIRED)) {return;}

    // date stuff via URLs
    if(!xarVarFetch('start',    'int:0', $start,     NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('end',      'int:0', $end,       NULL, XARVAR_NOT_REQUIRED)) {return;}

    // search button was pressed
    if(!xarVarFetch('search',   'str',   $search,    NULL, XARVAR_NOT_REQUIRED)) {return;}

    // select by article status (array or string)
    if(!xarVarFetch('status',   'isset', $status,    NULL, XARVAR_NOT_REQUIRED)) {return;}

    // yes, this is the query
    if(!xarVarFetch('q',        'str',   $q,         NULL, XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('author',   'str',   $author,    NULL, XARVAR_NOT_REQUIRED)) {return;}

    // filter by category
    if(!xarVarFetch('by',       'checkbox',   $by,     NULL, XARVAR_NOT_REQUIRED)) {return;}

    // can't use list enum here, because we don't know which sorts might be used
    if(!xarVarFetch('sort', 'regexp:/^[\w,]*$/', $sort, NULL, XARVAR_NOT_REQUIRED)) {return;}

    // boolean AND/OR for words (no longer used)
    //if(!xarVarFetch('bool',     'str',   $bool,   NULL, XARVAR_NOT_REQUIRED)) {return;}

    // search in specific fields
    if(!xarVarFetch('articles_fields', 'isset', $fields, NULL, XARVAR_NOT_REQUIRED)) {return;}

    if(!xarVarFetch('searchtype', 'isset', $searchtype, NULL, XARVAR_NOT_REQUIRED)) {return;}

    if (isset($args['objectid'])) {
        $ishooked = 1;
    } else {
        $ishooked = 0;
        if (empty($fields)) {
            // search in specific fields via URLs
            if(!xarVarFetch('fields', 'isset', $fields, NULL, XARVAR_NOT_REQUIRED)) {return;}
        }
    }

// TODO: could we need this someday ?
    if (isset($args['extrainfo'])) {
        extract($args['extrainfo']);
    }

// TODO: clean up this copy & paste stuff :-)

    // Default parameters
    if (!isset($startnum)) {
        $startnum = 1;
    }
    if (!isset($numitems)) {
        $numitems = 20;
    }

    if (!xarModAPILoad('articles', 'user')) return;

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    if (xarSecurityCheck('AdminArticles',0)) {
        $isadmin = true;
    } else {
        $isadmin = false;
    }

    // frontpage or approved status
    if (!$isadmin || !isset($status)) {
        $status = array(3,2);
    } elseif (is_string($status)) {
        if (strpos($status,' ')) {
            $status = explode(' ',$status);
        } elseif (strpos($status,'+')) {
            $status = explode('+',$status);
        } else {
            $status = array($status);
        }
    }
    $seenstatus = array();
    foreach ($status as $that) {
        if (empty($that) || !is_numeric($that)) continue;
        $seenstatus[$that] = 1;
    }
    $status = array_keys($seenstatus);
    if (count($status) != 2 || !in_array(2,$status) || !in_array(3,$status)) {
        $statusline = implode('+',$status);
    } else {
        $statusline = null;
    }

    if (!isset($sort)) {
        $sort = null;
    }

    // default publication type(s) to search in
    if (!empty($ptid) && isset($pubtypes[$ptid])) {
        $ptids = array($ptid);
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
        if (empty($settings['showcategories'])) {
            $showcategories = 0;
        } else {
            $showcategories = 1;
        }
    } elseif (!empty($ptids) && count($ptids) > 0) {
        foreach ($ptids as $curptid) {
            // default view doesn't apply here ?!
        }
        $showcategories = 1;
    } elseif (!isset($ptids)) {
    //    $ptids = array(xarModGetVar('articles','defaultpubtype'));
        $ptids = array();
        foreach ($pubtypes as $pubid => $pubtype) {
            $ptids[] = $pubid;
        }
        $showcategories = 1;
    } else {
    // TODO: rethink this when we're dealing with multi-pubtype categories
        $showcategories = 0;
    }

    // turn $catid into $cids array (and set $andcids flag)
    if (!empty($catid)) {
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } else {
            $cids = explode('-',$catid);
            $andcids = false;
        }
    }
    $seencid = array();
    $catid = null;
    if (isset($cids) && is_array($cids)) {
        foreach ($cids as $cid) {
            if (empty($cid) || !preg_match('/^_?[0-9]+$/',$cid)) continue;
            $seencid[$cid] = 1;
        }
        $cids = array_keys($seencid);
        if ($andcids) {
            $catid = join('+',$cids);
        } else {
            $catid = join('-',$cids);
        }
    }
    $seenptid = array();
    if (isset($ptids) && is_array($ptids)) {
        foreach ($ptids as $curptid) {
            if (empty($curptid) || !isset($pubtypes[$curptid])) continue;
            $seenptid[$curptid] = 1;
        }
        $ptids = array_keys($seenptid);
    }
    /* Ensure whitespace alone not passed to api -causes errors */
    if (isset($q) && trim($q) === '') {
        $q = null;
    }

    // Find the uid of the author we're looking for
    if (!empty($author)) {
        // Load API
        if (!xarModAPILoad('roles', 'user')) return;
        $user = xarModAPIFunc('roles','user','get',
                             array('name' => $author));
        if (!empty($user['uid'])) {
            $authorid = $user['uid'];
        } else {
            $authorid = null;
            $author = null;
        }
    } else {
        $authorid = null;
        $author = null;
    }

    if (isset($start) && is_numeric($start)) {
        $startdate = xarLocaleFormatDate("%Y-%m-%d %H:%M:%S",$start);
    }
    if (isset($end) && is_numeric($end)) {
        $enddate = xarLocaleFormatDate("%Y-%m-%d %H:%M:%S",$end);
    }

    if (empty($fields)) {
        $fieldlist = array('title', 'summary', 'body');
    } else {
        $fieldlist = array_keys($fields);
        // don't pass fields via URLs if we stick to the default list
        if (count($fields) == 3 && isset($fields['title']) && isset($fields['summary']) && isset($fields['body'])) {
            $fields = null;
        }
    }

    // Set default searchtype to 'fulltext' if necessary
    $fulltext = xarModGetVar('articles', 'fulltextsearch');
    if (!isset($searchtype) && !empty($fulltext)) {
        $searchtype = 'fulltext';
    }
// FIXME: fulltext only supports searching in all configured text fields !
    if (empty($fields) && !empty($fulltext) && !empty($searchtype) && $searchtype == 'fulltext') {
        $fieldlist = explode(',', $fulltext);
    }

    $data = array();
    $data['results'] = array();
    $data['by'] = isset($by)?$by:false;
    $data['status'] = '';
    $data['ishooked'] = $ishooked;
    // TODO: MichelV: $ishooked is never empty, but either 0 or 1
    if (empty($ishooked)) {
        $data['q'] = isset($q) ? xarVarPrepForDisplay($q) : null;
        $data['author'] = isset($author) ? xarVarPrepForDisplay($author) : null;
        $data['searchtype'] = $searchtype;
    }
    if ($isadmin) {
        $states = xarModAPIFunc('articles','user','getstates');
        $data['statuslist'] = array();
        foreach ($states as $id => $name) {
            $data['statuslist'][] = array('id' => $id, 'name' => $name, 'checked' => in_array($id,$status));
        }
    // TODO: show field labels when we're dealing with only 1 pubtype
        $data['fieldlist'] = array(
                                    array('id' => 'title', 'name' => xarML('title'), 'checked' => in_array('title',$fieldlist)),
                                    array('id' => 'summary', 'name' => xarML('summary'), 'checked' => in_array('summary',$fieldlist)),
                                    array('id' => 'body', 'name' => xarML('body'), 'checked' => in_array('body',$fieldlist)),
                                    array('id' => 'notes', 'name' => xarML('notes'), 'checked' => in_array('notes',$fieldlist)),
                                   );
    }

    $data['publications'] = array();
    foreach ($pubtypes as $pubid => $pubtype) {
        if (!empty($seenptid[$pubid])) {
            $checked = ' checked="checked"';
        } else {
            $checked = '';
        }
        $data['publications'][] = array('pubid' => $pubid,
                                        'pubdescr' => xarVarPrepForDisplay($pubtype['descr']),
                                        'pubchecked' => $checked);
    }

    $data['categories'] = array();
    if ($by) {
        $catarray = array();
        foreach ($ptids as $curptid) {
            // get root categories for this publication type
            $catlinks = xarModAPIFunc('articles', 'user', 'getrootcats',
                                     array('ptid' => $curptid));
            foreach ($catlinks as $cat) {
                $catarray[$cat['catid']] = $cat['cattitle'];
            }
        }

        foreach ($catarray as $cid => $title) {
            $select = xarModAPIFunc('categories', 'visual', 'makeselect',
                                    Array('cid' => $cid,
                                          'return_itself' => true,
                                          'select_itself' => true,
                                          'values' => &$seencid,
                                          'multiple' => 1));
            $data['categories'][] = array('cattitle' => $title,
                                          'catselect' => $select);
        }
        $data['searchurl'] = xarModURL('search','user','main');
    } else {
        $data['searchurl'] = xarModURL('search','user','main',
                                       array('by' => 'cat'));
    }

    $now = time();
    if (empty($startdate)) {
        $startdate = null;
        $data['startdate'] = 'N/A';
    } else {
        if (!preg_match('/[a-zA-Z]+/',$startdate)) {
            $startdate .= ' GMT';
        }
        $startdate = strtotime($startdate);
        // adjust for the user's timezone offset
        $startdate -= xarMLS_userOffset() * 3600;
        if ($startdate > $now && !$isadmin) {
            $startdate = $now;
        }
        $data['startdate'] = $startdate;
    }
    if (empty($enddate)) {
        $enddate = $now;
        $data['enddate'] = 'N/A';
    } else {
        if (!preg_match('/[a-zA-Z]+/',$enddate)) {
            $enddate .= ' GMT';
        }
        $enddate = strtotime($enddate);
        // adjust for the user's timezone offset
        $enddate -= xarMLS_userOffset() * 3600;
        if ($enddate > $now && !$isadmin) {
            $enddate = $now;
        }
        $data['enddate'] = $enddate;
    }

    if (!empty($q) || (!empty($author) && isset($authorid)) || !empty($search) || !empty($ptid) || !empty($startdate) || $enddate != $now || !empty($catid)) {
        $getfields = array('aid','title', 'pubdate','pubtypeid','cids');
        // Return the relevance when using MySQL full-text search
        //if (!empty($search) && !empty($searchtype) && substr($searchtype,0,8) == 'fulltext') {
        //    $getfields[] = 'relevance';
        //}
        $count = 0;
        // TODO: allow combination of searches ?
        foreach ($ptids as $curptid) {
            $articles = xarModAPIFunc('articles',
                                     'user',
                                     'getall',
                                     array('startnum' => $startnum,
                                           'cids' => $cids,
                                           'andcids' => $andcids,
                                           'ptid' => $curptid,
                                           'authorid' => $authorid,
                                           'sort' => $sort,
                                           'numitems' => $numitems,
                                           'status' => $status,
                                           'startdate' => $startdate,
                                           'enddate' => $enddate,
                                           'searchfields' => $fieldlist,
                                           'searchtype' => $searchtype,
                                           'search' => $q,
                                           'fields' => $getfields
                                          )
                                    );
        // TODO: re-use article output code from elsewhere (view / archive / admin)
            if (!empty($articles) && count($articles) > 0) {

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
                        $catinfo = xarModAPIFunc('categories','user','getcatinfo',
                                                 array('cids' => array_keys($cidlist)));
                        // get root categories for this publication type
                        $catroots = xarModAPIFunc('articles',
                                                  'user',
                                                  'getrootcats',
                                                  array('ptid' => $curptid));

                    }
                    foreach ($catinfo as $cid => $info) {
                        $catinfo[$cid]['name'] = xarVarPrepForDisplay($info['name']);
                        $catinfo[$cid]['link'] = xarModURL('articles','user','view',
                                                           array('ptid' => $curptid,
                                                                 'catid' => (($catid && $andcids) ? $catid . '+' . $cid : $cid) ));
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

                }

                // needed for sort function below
                $GLOBALS['artsearchcatinfo'] = $catinfo;

                $items = array();
                foreach ($articles as $article) {
                    $count++;
                    $curptid = $article['pubtypeid'];
                    $link = xarModURL('articles','user','display',
                                     array('ptid' => $article['pubtypeid'],
                                           'aid' => $article['aid']));
                    // publication date of article (if needed)
                    if (!empty($pubtypes[$curptid]['config']['pubdate']['label'])
                        && !empty($article['pubdate'])) {
                        $date = xarLocaleFormatDate('%a, %d %B %Y %H:%M:%S %Z', $article['pubdate']);
                        $pubdate = $article['pubdate'];
                    } else {
                        $date = '';
                        $pubdate = 0;
                    }
                    if (empty($article['title'])) {
                        $article['title'] = xarML('(none)');
                    }

                    // categories this article belongs to
                    $categories = array();
                    if ($showcategories && !empty($article['cids']) &&
                        is_array($article['cids']) && count($article['cids']) > 0) {

                        $cidlist = $article['cids'];
                        // order cids by root category order
                        usort($cidlist,'articles_search_sortbyorder');
                        // order cids by root category id
                        //usort($cidlist,'articles_search_sortbyroot');
                        // order cids by position in Celko tree
                        //usort($cidlist,'articles_search_sortbyleft');

                        $join = '';
                        foreach ($cidlist as $cid) {
                            $item = array();
                            if (!isset($catinfo[$cid])) {
                                // oops
                                continue;
                            }
                            $categories[] = array('cname' => $catinfo[$cid]['name'],
                                                  'clink' => $catinfo[$cid]['link'],
                                                  'cjoin' => $join,
                                                  'cid'   => $cid,
                                                  'root'  => $catinfo[$cid]['root']);
                            if (empty($join)) {
                                $join = ' | ';
                            }
                        }
                    }

                    $items[] = array('title' => xarVarPrepHTMLDisplay($article['title']),
                                     'link' => $link,
                                     'date' => $date,
                                     'pubdate' => $pubdate,
                                     'relevance' => isset($article['relevance']) ? $article['relevance'] : null,
                                     'categories' => $categories);
                }
                unset($articles);

                // Pager
// TODO: make count depend on language in the future
                $pager = xarTplGetPager($startnum,
                                        xarModAPIFunc('articles', 'user', 'countitems',
                                                      array('cids' => $cids,
                                                            'andcids' => $andcids,
                                                            'ptid' => $curptid,
                                                            'authorid' => $authorid,
                                                            'status' => $status,
                                                            'startdate' => $startdate,
                                                            'enddate' => $enddate,
                                                            'searchfields' => $fieldlist,
                                                            'searchtype' => $searchtype,
                                                            'search' => $q)),

/* trick : use *this* articles search instead of global search for pager :-)
                                        xarModURL('search', 'user', 'main',
*/
                                        xarModURL('articles', 'user', 'search',
                                                  array('ptid' => $curptid,
                                                        'catid' => $catid,
                                                        'q' => isset($q) ? $q : null,
                                                        'author' => isset($author) ? $author : null,
                                                        'start' => $startdate,
                                                        'end' => ($enddate != $now) ? $enddate : null,
                                                        'status' => $statusline,
                                                        'sort' => $sort,
                                                        'fields' => $fields,
                                                        'searchtype' => !empty($searchtype) ? $searchtype : null,
                                                        'startnum' => '%%')),
                                        $numitems);

                if (strlen($pager) > 5) {
                    if (!isset($sort) || $sort == 'date') {
                        $othersort = 'title';
                    } else {
                        $othersort = null;
                    }
                    $sortlink = xarModURL('articles',
                                         'user',
                                         'search',
                                         array('ptid' => $curptid,
                                               'catid' => $catid,
                                               'q' => isset($q) ? $q : null,
                                               'author' => isset($author) ? $author : null,
                                               'start' => $startdate,
                                               'end' => ($enddate != $now) ? $enddate : null,
                                               'status' => $statusline,
                                               'fields' => $fields,
                                               'searchtype' => !empty($searchtype) ? $searchtype : null,
                                               'sort' => $othersort));
                    if (!isset($othersort)) {
                        $othersort = 'date';
                    }
                    $pager .= '&nbsp;&nbsp;<a href="' . $sortlink . '">' .
                              xarML('sort by') . ' ' . xarML($othersort) . '</a>';
                }

                $data['results'][] = array('description' => xarVarPrepForDisplay($pubtypes[$curptid]['descr']),
                                           'items' => $items,
                                           'pager' => $pager);
            }
        }
        unset($catinfo);
        unset($items);
        unset($GLOBALS['artsearchcatinfo']);

        if ($count > 0) {
            // bail out, we have what we needed
            return xarTplModule('articles','user','search',$data);
        }

        $data['status'] = xarML('No articles found matching this search');
    }

    return xarTplModule('articles','user','search',$data);
}

/**
 * sorting function for article categories
 */
function articles_search_sortbyroot ($a,$b)
{
    if ($GLOBALS['artsearchcatinfo'][$a]['root'] == $GLOBALS['artsearchcatinfo'][$b]['root']) {
        return articles_search_sortbyleft($a,$b);
    }
    return ($GLOBALS['artsearchcatinfo'][$a]['root'] > $GLOBALS['artsearchcatinfo'][$b]['root']) ? 1 : -1;
}

function articles_search_sortbyleft ($a,$b)
{
    if ($GLOBALS['artsearchcatinfo'][$a]['left'] == $GLOBALS['artsearchcatinfo'][$b]['left']) return 0;
    return ($GLOBALS['artsearchcatinfo'][$a]['left'] > $GLOBALS['artsearchcatinfo'][$b]['left']) ? 1 : -1;
}

function articles_search_sortbyorder ($a,$b)
{
    if ($GLOBALS['artsearchcatinfo'][$a]['order'] == $GLOBALS['artsearchcatinfo'][$b]['order']) {
        return articles_search_sortbyleft($a,$b);
    }
    return ($GLOBALS['artsearchcatinfo'][$a]['order'] > $GLOBALS['artsearchcatinfo'][$b]['order']) ? 1 : -1;
}

?>
