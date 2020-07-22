<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}

 * @copyright (C)2008-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author Xarigami Team
 * @author mikespub
 */
/**
 * return the path for a short URL to xarModURL for this module
 *
 * @param array $args the function and arguments passed to xarModURL
 * @return string path to be added to index.php for a short URL, or empty if failed
 */
function articles_userapi_encode_shorturl($args)
{
    // Get arguments from argument array
    extract($args);

    // check if we have something to work with
    if (!isset($func)) {
        return;
    }

    // Get the article settings for this publication type
    //Sometimes $settings can be set but $string can return empty eg importing a pubtype
    //Let's make provision for this
    $string=''; //initialize
    if (!empty($ptid)) {
        $string = xarModGetVar('articles', 'settings.'.$ptid);
    } else {
        $string = xarModGetVar('articles', 'settings');
    }
    if (!empty($string)) {
        $settings = unserialize($string);
    }

    // check if we want to encode URLs using their titles rather then their ID
    $encodeUsingTitle= empty($settings['usetitleforurl']) ? 0 : $settings['usetitleforurl'];
    // check if we want to pass the catid (single cid) to have a continuous navigation (display function)
    $passNavCategory = empty($settings['passnavcategory']) ? 0 : $settings['passnavcategory'];

    // Coming from categories etc.
    if (!empty($objectid)) {
        $aid = $objectid;
    }
    if (!empty($itemtype)) {
        $ptid = $itemtype;
    }
    if (empty($catid) && !empty($cids) && count($cids) > 0) {
        if (!empty($andcids)) {
            $catid = join('+',$cids);
        } else {
            $catid = join('-',$cids);
        }
    }

    // make sure you don't pass the following variables as arguments too

    // default path is empty -> no short URL
    $path = '';
    // if we want to add some common arguments as URL parameters below
    $join = '?';
    // we can't rely on xarModGetName() here (yet) !
    $module = 'articles';

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    // specify some short URLs relevant to your module
    if ($func == 'main') {
        $alias = xarModGetAlias('frontpage');
        if ($module == $alias) {
            // OK, we can use a 'fake' module name here
            $path = '/frontpage/';
        } else {
            $path = '/' . $module . '/';
        }
    } elseif ($func == 'view') {
// TODO: review logic of possible combinations
        if (isset($authorid) && $authorid > 0) {
            if (isset($ptid) && isset($pubtypes[$ptid])) {
                $alias = xarModGetAlias($pubtypes[$ptid]['name']);
                if ($module == $alias) {
                    // OK, we can use a 'fake' module name here
                    $path = '/' . $pubtypes[$ptid]['name'] . '/';
                } else {
                    $path = '/' . $module . '/' . $pubtypes[$ptid]['name'] . '/';
                }
            } else {
                $path = '/' . $module . '/';
            }
            $path .= xarML('by_author') . '/' . $authorid;
        } elseif (!empty($catid)) {
            if (isset($ptid) && isset($pubtypes[$ptid])) {
                if (isset($bycat)) {
                    $path = '/' . $module . '/c' . $catid
                            . '/' . $pubtypes[$ptid]['name'] . '/';
                } else {
                    $alias = xarModGetAlias($pubtypes[$ptid]['name']);
                    if ($module == $alias) {
                        // OK, we can use a 'fake' module name here
                        $path = '/' . $pubtypes[$ptid]['name']
                                . '/c' . $catid . '/';
                    } else {
                        $path = '/' . $module . '/' . $pubtypes[$ptid]['name']
                                . '/c' . $catid . '/';
                    }
                }
            } else {
                $path = '/' . $module . '/c' . $catid . '/';
            }
/*
// perhaps someday, with a convertor to 7-bit ASCII or something...
            // use a cache to avoid re-querying for each URL in the same cat
            static $catcache = array();
            $cid = $cids[0];
            if (xarModAPILoad('categories','user')) {
                if (isset($catcache[$cid])) {
                    $cat = $catcache[$cid];
                } else {
                    $cat = xarModAPIFunc('categories','user','getcatinfo',
                                        array('cid' => $cid));
                    // put the category in cache
                    $catcache[$cid] = $cat;
                }
                if (!empty($cat) && !empty($cat['name'])) {
                    // use the category name as part of the path here
                    $name = preg_replace('/\s+/','_',$cat['name']);
                    $name = strtolower($name);
                    $path = '/' . $module . '/c/' . rawurlencode($name) .'/';
                }
            }
*/
        } elseif (isset($ptid) && isset($pubtypes[$ptid])) {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $pubtypes[$ptid]['name'] . '/';
            } else {
                $path = '/' . $module . '/' . $pubtypes[$ptid]['name'] . '/';
            }
        } else {
            $alias = xarModGetAlias('frontpage');
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/frontpage/';
            } else {
                $path = '/' . $module . '/';
            }
        }
    } elseif ($func == 'display' && isset($aid)) {
        if (isset($ptid) && isset($pubtypes[$ptid]))
        {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);

            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $pubtypes[$ptid]['name'] . "/";
            } else {
                $path = '/' . $module . '/' . $pubtypes[$ptid]['name'] . "/";
            }

            // pass the cateogry of origin for navigation (xart-000596)
            // /[module or alias name]/[pubtype]/c[cid]/[aid]
            if ($passNavCategory && !empty($catid)) {
                $path .= 'c' . $catid . '/' ;
            }


            // Check to see if we want to encode using Title
            if( $encodeUsingTitle )
            {
                $path .= articles_encodeUsingTitle($aid, $encodeUsingTitle, $ptid);
            } else {
                $path .= $aid;
            }

        } else {
            $path = '/' . $module . "/";
            // pass the cateogry of origin for navigation (xart-000596)
            if ($passNavCategory && !empty($catid) && is_numeric($catid)) {
                $path .= 'c' . $catid . '/';
            }
            // Check to see if we want to encode using Title
            if( $encodeUsingTitle )
            {
                $path .= articles_encodeUsingTitle($aid, $encodeUsingTitle, '');
            } else {
                $path .= "$aid";
            }
        }
        // TODO: do we want to include categories in the display URL too someday ?
    } elseif ($func == 'redirect' && isset($aid)) {
        if (isset($ptid) && isset($pubtypes[$ptid])) {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $path = '/' . $pubtypes[$ptid]['name'] . "/redirect/$aid";
            } else {
                $path = '/' . $module . '/' . $pubtypes[$ptid]['name'] . "/redirect/$aid";
            }
        } else {
            $path = '/' . $module . "/redirect/$aid";
        }
    } elseif ($func == 'monthview') {
        if (isset($ptid) && isset($pubtypes[$ptid])) {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $module = $pubtypes[$ptid]['name'];
            } else {
                $module .= '/' . $pubtypes[$ptid]['name'];
            }
        }
        if (!empty($month)) {
            if ($month == 'all') {
                $path = '/' . $module . '/monthview/' . $month .'/';
            } else {
                list($year,$mon) = preg_split('/-/',$month);
                $path = '/' . $module . '/monthview/' . $year .'/'. $mon . '/';
            }
        } else {
            $path = '/' . $module . '/monthview/';
        }
    } elseif ($func == 'viewmap') {
        if (isset($ptid) && isset($pubtypes[$ptid])) {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $module = $pubtypes[$ptid]['name'];
            } else {
                $module .= '/' . $pubtypes[$ptid]['name'];
            }
        }
        $path = '/' . $module . '/map/';
    } elseif ($func == 'search') {
        if (isset($ptid) && isset($pubtypes[$ptid])) {
            $alias = xarModGetAlias($pubtypes[$ptid]['name']);
            if ($module == $alias) {
                // OK, we can use a 'fake' module name here
                $module = $pubtypes[$ptid]['name'];
            } else {
                $module .= '/' . $pubtypes[$ptid]['name'];
            }
        }
        $path = '/' . $module . '/search';
        if (!empty($catid)) {
            $path .= '/c' . $catid;
        }
    }
    // anything else does not have a short URL equivalent

// TODO: add *any* extra args we didn't use yet here
    // add some other module arguments as standard URL parameters
    if (!empty($path)) {
        // search
        if (isset($q)) {
            $path .= $join . 'q=' . $q;
            $join = '&';
        }
        // by author
        if (isset($author)) {
            $path .= $join . 'author=' . $author;
            $join = '&';
        }
        // by startdate
        if (isset($start)) {
            $path .= $join . 'start=' . $start;
            $join = '&';
        }
        // by enddate
        if (isset($end)) {
            $path .= $join . 'end=' . $end;
            $join = '&';
        }
        // by status
        if (isset($status)) {
            $path .= $join . 'status=' . $status;
            $join = '&';
        }
        // field list
        if (isset($fields)) {
            foreach ($fields as $field => $val) {
                $path .= $join . 'fields[' . $field . ']=' . $val;
                $join = '&';
            }
        }
        // search type
        if (isset($searchtype)) {
            $path .= $join . 'searchtype=' . $searchtype;
            $join = '&';
        }
        // sort
        if (isset($sort)) {
            $path .= $join . 'sort=' . $sort;
            $join = '&';
        }
        // letter
        if (isset($letter)) {
            $path .= $join . 'letter=' . $letter;
            $join = '&';
        }
        // pager
        if (isset($startnum) && $startnum != 1) {
            $path .= $join . 'startnum=' . $startnum;
            $join = '&';
        }
        // multi-page articles
        if (isset($page)) {
            $path .= $join . 'page=' . $page;
            $join = '&';
        }
        // number of columns
        if (isset($numcols)) {
            $path .= $join . 'numcols=' . $numcols;
            $join = '&';
        }
        // view map by ...
        if (isset($by)) {
            $path .= $join . 'by=' . $by;
            $join = '&';
        }
    }

    return $path;
}

function articles_encodeUsingTitle( $aid, $encodeUsingTitle = 1, $ptid = '' )
{
    $searchArgs['aid'] = $aid;
    $article = xarModAPIFunc('articles','user','get', $searchArgs);

    if (empty($article)) {
        // default to just the article ID
        $path = $aid;
        return $path;
    }
    $spacecode = xarModGetVar('base','urlspaces')?xarModGetVar('base','urlspaces'):'_';
    switch ($encodeUsingTitle)
    {
        case 1:
            $dupeResolutionMethod = 'Append Date';
            break;
        case 2:
            $dupeResolutionMethod = 'Append AID';
            break;
        case 3:
            $dupeResolutionMethod = 'Use AID'; //always - we could also get rid of punctuations, spaces
            $nsearch =  array('?', ',', ';', ':', '.',"'", '"',' ');
            $nreplace = array('',  '',  '',  '',  '','','',$spacecode);
            $article['title'] = str_replace($nsearch,$nreplace, strtolower(rtrim($article['title'])));
            //remove any double spacecodes here - not later as it may affect other encodings and quicker ot use str_replace
            $article['title'] = str_replace($spacecode.$spacecode,$spacecode, $article['title']);
            $article['title'] = rtrim($article['title'],$spacecode);
            break;
        case 4:
        default:
            $dupeResolutionMethod = 'Ignore';
            break;
    }

    if (($dupeResolutionMethod != 'Ignore')  && ($dupeResolutionMethod != 'Use AID')) {
        $searchArgs = array();
        $searchArgs['search'] = $article['title'];
        $searchArgs['searchfields'] = array('title');
        $searchArgs['searchtype'] = 'equal whole string';
        // if $ptid is set, it will be part of the URL so we can use it to refine the search
        if (!empty($ptid)) {
            $searchArgs['ptid'] = $article['pubtypeid'];
        }

        $articles = xarModAPIFunc('articles', 'user', 'getall', $searchArgs);
    }

    if ( strpos($article['title'],$spacecode) === FALSE ) // will break short urls if there is a - or _ in their already and replaces
    {
        $article['title'] = str_replace(' ',$spacecode,$article['title']);
        $article['title'] = strtolower($article['title']);
    }
    $encodedTitle = rawurlencode($article['title']);
    //  the URL encoded / (%2F) is not accepted by Apache in PATH_INFO
    $encodedTitle = str_replace(array('%2F','%2B'),array('/','%252B'),$encodedTitle);

    if ($dupeResolutionMethod == 'Ignore') {
        // Ignore duplicates
        $path = $encodedTitle;

    }elseif  ($dupeResolutionMethod == 'Use AID') {
        // always use AID with title

         $path = $encodedTitle.'/'.$aid;
    } elseif ( count($articles) == 1 ) {
        // Only finding one article through search, we're good to go.
        $path = $encodedTitle;

    } elseif (count($articles) == 0) {
        // Can't find article through search, won't be able to find it on decode
        // default to just the article ID
        $path = $aid;

    } else {
        // Finding multiple articles through search, add a duplication resolution flag
        switch( $dupeResolutionMethod )
        {
            case 'Append AID':
                // User Title and AID
                $path = $encodedTitle .'/'.$aid;
                break;

            case 'Append Date':
                // User Title and Date

                $path = $encodedTitle .'/'.date('Y-m-d H:i',$article['pubdate']) ;
                break;
            //the following should theoretically not be used now tih the new use of 'Use AID
            case 'Use AID':
            default:
                // Just use ID instead of title
                $path = $aid;
        }
    }

    return $path;
}
?>
