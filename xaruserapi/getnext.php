<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub, jojodee, lakys
 */
/**
 * get next article
 * Note : the following parameters are all optional (except aid and ptid)
 *
 * @param $args['aid'] the article ID we want to have the next article of
 * @param $args['ptid'] publication type ID (for news, sections, reviews, ...)
 * @param $args['sort'] sort order ('date','title','hits','rating',...)
 * @param $args['authorid'] the ID of the author
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['enddate'] articles published before enddate (unix timestamp format)
 * @param $args['cids'] cids used to filter the articles
 * @param $args['andcids'] and/or filter the articles
 * 
 * @return  array of article fields,
 *          empty array for no result found
 *          false for privilege failure
 */
function articles_userapi_getnext($args)
{
    // Get arguments from argument array
    $andcids = FALSE;
    extract($args);

    // Optional arguments
    if (isset($cids)) {
        $incids = $cids;
        unset($cids);
    } else {
        $incids = NULL;
    }
    if (empty($sort)) {
        $sort = 'date';
    }
    if (!isset($status)) {
        // frontpage or approved
        $status = array(3,2);
    }

    // Default fields in articles (for now)
    $fields = array('aid','title');

     // Database information
    $dbconn = xarDBGetConn();

    // Get the field names and LEFT JOIN ... ON ... parts from articles
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the articles-specific columns too now
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

    // Create the query
    $query = "SELECT $articlesdef[aid], $articlesdef[title], $articlesdef[pubtypeid], $articlesdef[authorid]
                FROM $articlesdef[table] WHERE ";

    // we rely on leftjoin() to create the necessary articles clauses now
    if (!empty($articlesdef['where'])) {
        $query .= " $articlesdef[where] AND ";
    }

    // Get current article
    $current = xarModAPIFunc('articles','user','get',array('aid' => $aid));

     // Create the ORDER BY part
    switch($sort) {
    case 'title':
        $query .= $articlesdef['title'] . ' > ' . $dbconn->qstr($current['title']) . ' ORDER BY ' . $articlesdef['title'] . ' ASC, ' . $articlesdef['aid'] . ' ASC';
        break;
    case 'aid':
        $query .= $articlesdef['aid'] . ' > ' . $current['aid'] . ' ORDER BY ' . $articlesdef['aid'] . ' ASC';
        break;
    // case 'date':
    default:
        if ($sort != 'date' && array_key_exists($sort, $articlesdef)) {
            $query .= $articlesdef[$sort] . ' > ' . $dbconn->qstr($current[$sort]) . ' ORDER BY ' . $articlesdef[$sort] . ' ASC, ' . $articlesdef['aid'] . ' DESC';       
        } else {
            // by date
            $query .= $articlesdef['pubdate'] . ' > ' . $dbconn->qstr($current['pubdate']) . ' ORDER BY ' . $articlesdef['pubdate'] . ' ASC, ' . $articlesdef['aid'] . ' DESC';
        }
        break;
    }

    // Initialize the default return
    $offset = '0'; //use string for some vers of php
    $canread = false;
    $canview = false;
    $cando = false;
    $ret = array();
    $currinfo = xarRequestGetInfo();
    $currfunc = $currinfo[2];
    //sometimes the current function might not be display or view. Assume the one require max privs if not
    $currfunc = $currfunc == 'display' || $currfunc == 'view' ?$currfunc: 'display';
    $article = array();
    // Find the previous article allowed to be read - loop until $cando true or end of file
    do {
        // Run the query
        $result = $dbconn->SelectLimit($query, 1, $offset);

        // Xaraya 1.1.3 behavior - no more element to find - break with an empty array
        if (!$result || !is_array($result->fields) ) {
            $ret[$offset] = array();
            $cando = false;
             break;
        }
        list($aid,$title,$pubtypeid,$authorid) = $result->fields;
        if ($current['aid'] == $aid) {//we don't want a link - get out of here
            $cando = false;
            break;
        }
       //for now fix for xart-000576
        //get the article and cids
        $article = xarModAPIFunc('articles','user','get',array('aid' => $aid,'withcids'=>1));

        $cids = $article['cids'];
        $cids = !isset($cids) || empty($cids)?array():$cids;
        //jojo - we really need to check next and previous within the current category for correctness
        if (!empty($cids) && is_array($cids)) {
            // cids filtering for categories navigation
            if (is_array($incids)) {
                $catin = $andcids != FALSE;
                foreach ($incids as $cid) {
                    if ($andcids) {
                        if (!in_array($cid, $cids)) {
                            // one not found
                            $catin = FALSE;
                            break;
                        }
                    } else {
                        if (in_array($cid, $cids)) {
                            // one found
                            $catin = TRUE;
                            break;
                        }
                    }
                }
                if (!$catin) {
                    // we skip this category as it is not found in the cids list passed
                    $offset++;
                    continue;
                }
            }
            foreach ($cids as $cid) {
            //maybe we need a switch here too - to check or not check cats (performance hit?)
            //loop through and see if any prevent reading or view - if so set cando false at first occurance
            // NOTE: adjust if/when necessary to allow browsing in a cat if that cat is not denied to the user
            //See issue xart-000553 where this is under discussion
                $canread = xarSecurityCheck('ReadArticles',0,'Article',"$pubtypeid:$cid:$authorid:$aid")?1:0;
                $canview = xarSecurityCheck('ViewArticles',0,'Article', "$pubtypeid:$cid:$authorid:$aid")?1:0;
                // if we can read and is a display function, or can view and is a view function then cando is true
                $cando = ($currfunc == 'display' && $canread) || ($currfunc == 'view' && $canview) ? 1:0;
                if (!$cando) {
                    $ret[$offset] = false;
                    break; //go get the next article, no use going on with cids
                }
            }
        } else {
            $canread = xarSecurityCheck('ReadArticles',0,'Article',"$pubtypeid:All:$authorid:$aid")?1:0;
            $canview = xarSecurityCheck('ViewArticles',0,'Article', "$pubtypeid:All:$authorid:$aid")?1:0;
            // if we can read and is a display function, or can view and is a view function then cando is true
            $cando = ($currfunc == 'display' && $canread) || ($currfunc == 'view' && $canview) ? 1:0;
            if (!$cando) {
                $ret[$offset] = false;
                break; //go get the next article, no use going on with cids
            }
        }
        $offset++;
    } while (!$cando);

    $result->Close();

    // We have never found an article with read or view privilege
    if (!$cando) {
        // In case we have not looped more than once and it is empty
        if (!isset($ret[0])) {
            $r = $ret;
        }elseif (empty($ret[0]) && is_array($ret[0])) {
           $r = $ret[0];
        } else {
            $r = false;
        }
        return $r;
    }

    return $article;
}

?>
