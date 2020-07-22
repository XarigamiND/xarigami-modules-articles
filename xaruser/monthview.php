<?php
/**
 * Articles module - month view
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
*/
/**
 * show Month View (Archives-like)
 */
function articles_user_monthview($args)
{
    // Get parameters from user
    if (!xarVarFetch('ptid',  'id',           $ptid,  xarModGetVar('articles','defaultpubtype'), XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('sort',  'enum:d:t:1:2', $sort,  'd',  XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('month', 'str',          $month, '',   XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('cids',  'array',        $cids,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('catid', 'str',          $catid, '',   XARVAR_NOT_REQUIRED)) {return;}

    // Override if needed from argument array
    extract($args);

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    // Check that the publication type is valid
    if (empty($ptid) || !isset($pubtypes[$ptid])) {
        $ptid = null;
    }

    if (empty($ptid)) {
        if (!xarSecurityCheck('ViewArticles',0,'Article','All:All:All:All')) {
            return xarML('You have no permission to view these items');
        }
    } elseif (!xarSecurityCheck('ViewArticles',0,'Article',$ptid.':All:All:All')) {
        return xarML('You have no permission to view these items');
    }

    $status = array(2,3);

    $seencid = array();
    $andcids = false;
    // turn $catid into $cids array and set $andcids flag
    if (!empty($catid)) {
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } elseif (strpos($catid,'-')) {
            $cids = explode('-',$catid);
            $andcids = false;
        } else {
            $cids = array($catid);
            if (strstr($catid,'_')) {
                $andcids = false; // don't combine with current category
            } else {
                $andcids = true;
            }
        }
    }
    if (isset($cids) && is_array($cids)) {
        foreach ($cids as $cid) {
            if (!empty($cid) && preg_match('/^_?[0-9]+$/',$cid)) {
                $seencid[$cid] = 1;
            }
        }
        $cids = array_keys($seencid);
        sort($cids,SORT_NUMERIC);
        if (empty($catid) && count($cids) > 1) {
            $andcids = true;
        }
    } else {
        $cids = null;
    }

// QUESTION: work with user-dependent time settings or not someday ?
    // Set the start and end date for that month
    if (!empty($month) && preg_match('/^(\d{4})-(\d+)$/',$month,$matches)) {
        //is_dst parameter deprecated at 5.1
        //$startdate = gmmktime(0,0,0,$matches[2],1,$matches[1],0);
        $startdate = gmmktime(0,0,0,$matches[2],1,$matches[1]);
        // PHP allows month > 12 :-)
        //$enddate = gmmktime(0,0,0,$matches[2]+1,1,$matches[1],0);
        $enddate = gmmktime(0,0,0,$matches[2]+1,1,$matches[1]);
        if ($enddate > time()) {
            $enddate = time();
        }
    } else {
        $startdate = '';
        $enddate = time();
        if (!empty($month) && $month != 'all') {
            $month = '';
        }
    }

    // Load API
    if (!xarModAPILoad('articles', 'user')) return;

    if (!empty($ptid) && !empty($pubtypes[$ptid]['config']['pubdate']['label'])) {
        $showdate = 1;
    } else {
        $showdate = 0;
        foreach (array_keys($pubtypes) as $pubid) {
            if (!empty($pubtypes[$pubid]['config']['pubdate']['label'])) {
                $showdate = 1;
                break;
            }
        }
    }

    // Get monthly statistics
    $monthcount = xarModAPIFunc('articles','user','getmonthcount',
                               array('ptid' => $ptid,
                                     'status' => $status,
                                     'enddate' => time()));
    if(empty($monthcount)) {
        $monthcount = array();
    }
    krsort($monthcount);
    reset($monthcount);
    $months = array();
    $total = 0;
    foreach ($monthcount as $thismonth => $count) {
        if ($thismonth == $month) {
            $mlink = '';
        } else {
            $mlink = xarModURL('articles','user','monthview',
                              array('ptid' => $ptid,
                                    'month' => $thismonth));
        }
        $months[] = array('month' => $thismonth,
                          'mcount' => $count,
                          'mlink' => $mlink);
        $total += $count;
    }
    if (empty($ptid)) {
        $thismonth = xarML('All Articles');
    } else {
        $thismonth = xarML('All') . ' ' . $pubtypes[$ptid]['descr'];
    }
    if ($month == 'all') {
        $mlink = '';
    } else {
        $mlink = xarModURL('articles','user','monthview',
                          array('ptid' => $ptid,
                                'month' => 'all'));
    }
    $months[] = array('month' => $thismonth,
                      'mcount' => $total,
                      'mlink' => $mlink);

    // Load API
    if (!xarModAPILoad('categories', 'user')) return;

    // Get the list of root categories for this publication type
    if (!empty($ptid)) {
        $cidstring = xarModGetVar('articles', 'mastercids.'.$ptid);
        if (!empty($cidstring)) {
            $rootcats = explode(';',$cidstring);
        }
    } else {
        $cidstring = xarModGetVar('articles', 'mastercids');
        if (!empty($cidstring)) {
            $rootcats = explode (';', $cidstring);
        }
    }
    $catlist = array();
    $catinfo = array();
    $catsel = array();
    if (!empty($rootcats)) {
// TODO: do this in categories API ?
        $count = 1;
        foreach ($rootcats as $cid) {
            if (empty($cid)) {
                continue;
            }
            // save the name and root category for each child
            $cats = xarModAPIFunc('categories',
                                 'user',
                                 'getcat',
                                 array('cid' => $cid,
                                       'return_itself' => true,
                                       'getchildren' => true));
            foreach ($cats as $info) {
                $item = array();
                $item['name'] = $info['name'];
                $item['root'] = $cid;
                $catinfo[$info['cid']] = $item;
            }
            // don't allow sorting by category when viewing all articles
            //if ($sort == $count || $month == 'all') {
            if ($sort == $count) {
                $link = '';
            } else {
                $link = xarModURL('articles','user','monthview',
                                 array('ptid' => $ptid,
                                       'month' => $month,
                                       'sort' => $count));

            }
            // catch more faulty categories assignments
            if (isset($catinfo[$cid])) {
                $catlist[] = array('cid' => $cid,
                                   'name' => $catinfo[$cid]['name'],
                                   'link' => $link);
                $catsel[] = xarModAPIFunc('categories',
                                          'visual',
                                          'makeselect',
                                          Array('cid' => $cid,
                                                'return_itself' => true,
                                                'select_itself' => true,
                                                'values' => &$seencid,
                                                'multiple' => 0));
                $count++;
            }
        }
    }

    // Get articles
    if ($month == 'all' || ($startdate && $enddate)) {
        $articles = xarModAPIFunc('articles',
                                 'user',
                                 'getall',
                                 array('ptid' => (isset($ptid) ? $ptid : null),
                                       'startdate' => $startdate,
                                       'enddate' => $enddate,
                                       'status' => $status,
                                       'cids' => $cids,
                                       'andcids' => $andcids,
                                       'fields' => array('aid','title',
                                                  'pubdate','pubtypeid','cids')
                                      )
                                );
        if (!is_array($articles)) {
            $msg = xarML('Failed to retrieve articles in #(3)_#(1)_#(2).php', 'user', 'getall', 'articles');
            return xarResponseNotFound($msg);
        }
    } else {
        $articles = array();
    }

// TODO: add print / recommend_us link for each article ?
// TODO: add view count to table/query/template someday ?
    foreach ($articles as $key => $article) {
        $articles[$key]['link'] = xarModURL('articles','user','display',
                               array('ptid' => isset($ptid) ? $articles[$key]['pubtypeid'] : null,
                                     'aid' => $articles[$key]['aid']));
        if (empty($articles[$key]['title'])) {
            $articles[$key]['title'] = xarML('(none)');
        }
/* TODO: move date formatting to template, delete this code after testing
        if ($showdate && !empty($articles[$key]['pubdate'])) {
            $articles[$key]['date'] = xarLocaleFormatDate("%Y-%m-%d %H:%M:%S",
                                               $articles[$key]['pubdate']);
        } else {
            $articles[$key]['date'] = '';
        }
*/
// TODO: find some better way to do this...
        $list = array();
        // get all the categories for that article and put them under the
        // right root category
        if (!isset($articles[$key]['cids'])) {
            $articles[$key]['cids'] = array();
        }
        foreach ($articles[$key]['cids'] as $cid) {
            // skip unknown categories (e.g. when not under root categories)
            if (!isset($catinfo[$cid])) {
                continue;
            }
            if (!isset($list[$catinfo[$cid]['root']])) {
                $list[$catinfo[$cid]['root']] = array();
            }
            array_push($list[$catinfo[$cid]['root']],$cid);
        }
        // fill in the column corresponding to each root category
        $articles[$key]['cats'] = array();
        foreach ($catlist as $cat) {
            if (isset($list[$cat['cid']])) {
                $descr = '';
// TODO: add links to category someday ?
                foreach ($list[$cat['cid']] as $cid) {
                    if (!empty($descr)) {
                        $descr .= '<br />';
                    }
                    $descr .= $catinfo[$cid]['name'];
                }
                $articles[$key]['cats'][] = array('list' => $descr);
            } else {
                $articles[$key]['cats'][] = array('list' => '-');
            }
        }
    }

    // sort articles as requested
    if ($sort == 2 && count($catlist) > 1) {
        usort($articles,'articles_monthview_sortbycat10');
    } elseif ($sort == 1) {
        if (count($catlist) > 1) {
            usort($articles,'articles_monthview_sortbycat01');
        } elseif (count($catlist) > 0) {
            usort($articles,'articles_monthview_sortbycat0');
        }
    } elseif ($sort == 't') {
        usort($articles,'articles_monthview_sortbytitle');
    } else {
        $sort = 'd';
        // default sort by date is already done in getall() function
    }

    // add title header
    if ($sort == 't') {
        $link = '';
    } else {
        $link = xarModURL('articles','user','monthview',
                         array('ptid' => $ptid,
                               'month' => $month,
                               'sort' => 't'));
    }
    $catlist[] = array('cid' => 0,
                       'name' => xarML('Title'),
                       'link' => $link);
    $catsel[] = '<input type="submit" value="' . xarML('Filter') . '" />';

    if ($showdate) {
        // add date header
        if ($sort == 'd') {
            $link = '';
        } else {
            $link = xarModURL('articles','user','monthview',
                             array('ptid' => $ptid,
                                   'month' => $month));
        }
        $catlist[] = array('cid' => 0,
                           'name' => xarML('Date'),
                           'link' => $link);
        $catsel[] = '&nbsp;';
    }

    // Save some variables to (temporary) cache for use in blocks etc.
    xarVarSetCached('Blocks.articles','ptid',$ptid);
    if (!empty($cids)) {
        xarVarSetCached('Blocks.articles','cids',$cids);
    }
//if ($shownavigation) {
    xarVarSetCached('Blocks.categories','module','articles');
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    if (!empty($ptid) && !empty($pubtypes[$ptid]['descr'])) {
        xarVarSetCached('Blocks.categories','title',$pubtypes[$ptid]['descr']);
        xarTplSetPageTitle(xarML('View by Month'), $pubtypes[$ptid]['descr']);
    } else {
        xarTplSetPageTitle(xarML('View by Month'));
    }
//}
    if (!empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
    } else {
        $string = xarModGetVar('articles', 'settings');
        if (!empty($string)) {
            $settings = unserialize($string);
        }
    }
    if (!isset($showpublinks)) {
        if (!empty($settings['showpublinks'])) {
            $showpublinks = 1;
        } else {
            $showpublinks = 0;
        }
    }
    // show the number of articles for each publication type
    if (!isset($showpubcount)) {
        if (!isset($settings['showpubcount']) || !empty($settings['showpubcount'])) {
            $showpubcount = 1; // default yes
        } else {
            $showpubcount = 0;
        }
    }
//    $showcatcount = 0; // unused here

    // return template out
    $data = array('months' => $months,
                 'articles' => $articles,
                 'catlist' => $catlist,
                 'catsel' => $catsel,
                 'ptid' => $ptid,
                 'month' => $month,
                 'curlink' => xarModURL('articles','user','monthview',
                                        array('ptid' => $ptid,
                                              'month' => $month,
                                               'sort' => $sort)),
                 'showdate' => $showdate,
                 'showpublinks' => $showpublinks,
                 'publabel' => xarML('Publication'),
                 'publinks' => xarModAPIFunc('articles','user','getpublinks',
                                            array('ptid' => $ptid,
                                                  'status' => array(3,2),
                                                  'count' => $showpubcount,
                                                  // override default 'view'
                                                  'func' => 'monthview')),
                 'maplabel' => xarML('View Article Map'),
                 'maplink' => xarModURL('articles','user','viewmap',
                                       array('ptid' => $ptid)),
                 'viewlabel' => (empty($ptid) ? xarML('Back to Articles') : xarML('Back to') . ' ' . $pubtypes[$ptid]['descr']),
                 'viewlink' => xarModURL('articles','user','view',
                                        array('ptid' => $ptid)));

    if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
    } else {
// TODO: allow templates per category ?
       $template = null;
    }

    return xarTplModule('articles', 'user', 'monthview', $data, $template);
}

/**
 * sorting functions for monthview
 */

function articles_monthview_sortbycat0 ($a,$b)
{
    return strcmp($a['cats'][0]['list'],$b['cats'][0]['list']);
}

function articles_monthview_sortbycat01 ($a,$b)
{
    if ($a['cats'][0]['list'] == $b['cats'][0]['list']) {
        return strcmp($a['cats'][1]['list'],$b['cats'][1]['list']);
    } else {
        return strcmp($a['cats'][0]['list'],$b['cats'][0]['list']);
    }
}

function articles_monthview_sortbycat10 ($a,$b)
{
    if ($a['cats'][1]['list'] == $b['cats'][1]['list']) {
        return strcmp($a['cats'][0]['list'],$b['cats'][0]['list']);
    } else {
        return strcmp($a['cats'][1]['list'],$b['cats'][1]['list']);
    }
}

function articles_monthview_sortbytitle ($a,$b)
{
    return strcmp($a['title'],$b['title']);
}

?>