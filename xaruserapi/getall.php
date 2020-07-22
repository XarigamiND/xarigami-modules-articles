<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub, jojodee
*/
/**
 * Get a selection of all articles
 * Note : the following parameters are all optional
 *
 * @param int    $args['numitems'] number of articles to get
 * @param string $args['sort'] sort order ('pubdate','title','hits','rating','author','aid','summary','notes',...)
 * @param int    $args['startnum'] starting article number
 * @param array  $args['aids'] array of article ids to get
 * @param int    $args['authorid'] the ID of the author
 * @param int    $args['editor'] the ID of the editor where applicable
 * @param int    $args['ptid'] publication type ID (for news, sections, reviews, ...)
 * @param array  $args['status'] array of requested status(es) for the articles
 * @param int    $args['checkout'] the check in/out state  where 0=checked in, 1=checkedout
 * @param string $args['search'] search parameter(s)
 * @param array  $args['searchfields'] array of fields to search in (TODO)
 * @param string $args['searchtype'] start, end, like, eq, gt, ... (TODO)
 * @param array  $args['cids'] array of category IDs for which to get articles (OR/AND)
 *                      (for all categories don?t set it)
 * @param bool   $args['andcids'] true means AND-ing categories listed in cids
 * @param string $args['pubdate'] articles published in a certain year (YYYY), month (YYYY-MM) or day (YYYY-MM-DD)
 * @param int    $args['startdate'] articles published at startdate or later
 *                           (unix timestamp format)
 * @param int    $args['enddate'] articles published before enddate
 *                         (unix timestamp format)
 * @param array $args['fields'] array with all the fields to return per article
 *                        Default list is : 'aid','title','summary','authorid',
 *                        'pubdate','pubtypeid','notes','status','body'
 *                        Optional fields : 'cids','author','counter','rating','dynamicdata'
 * @param array $args['extra'] array with extra fields to return per article (in addition
 *                       to the default list). So you can EITHER specify *all* the
 *                       fields you want with 'fields', OR take all the default
 *                       ones and add some optional fields with 'extra'
 * @param string $args['where'] additional where clauses (e.g. myfield gt 1234)
 * @param string $args['wheredd'] where clauses for hooked dd fields (e.g. myddfield gt 1234) [requires 'ptid' is defined]
 * @param string $args['language'] language/locale (if not using multi-sites, categories etc.)
 * @param string $args['order'] order (asc or desc.)
 * @return array Array of articles, or false on failure
 */
function articles_userapi_getall($args)
{
    // Get arguments from argument array
    extract($args);
;
    // Used lots.
    $modid = xarModGetIDFromName('articles');
    // Optional argument
    if (!isset($startnum)) $startnum = 1;
    if (empty($cids)) $cids = array();
    if (!isset($andcids)) $andcids = false;
    if (!isset($ptid)) $ptid = null;

    // The ptid could be an array of pubtypes.
    // Convert the single ptid to an array anyway, for consistent handling.
    if (isset($ptid)) {
        $ptids = !empty($ptid) ?array($ptid): array('0');
    } else {
        $ptids = array();
    }
    // do the wheredd bit first
    //
    // A note just so you know what is actually happening here:
    // All article IDs that match the DD where-clause are fetched and put into an array.
    // This getall function is then called up again with the array passed in as a parameter.
    // If the number of matching articles is large (and there really is no limit to this) then
    // the second call to getall would result in an extremely long query string. It is not really
    // scaleable and should probably be discouraged.
    if (isset($wheredd) && !empty($ptids)) {
        // Save any old article IDs for use as a filter.
        if (!empty($args['aids'])) $filter_aids = $args['aids'];
        $args['aids'] = array();

        foreach($ptids as $dd_ptid) {
            if (!xarModIsHooked('dynamicdata', 'articles', $dd_ptid)) continue;

            // (is it possible to determine ptid(s) from the other args? not easily)
            $dditems = xarModApiFunc(
                'dynamicdata', 'user', 'getitems',
                array('module' => 'articles', 'itemtype' => $dd_ptid, 'where' => $wheredd)
            );

            // If we get nothing; then try the next ptid
            if (empty($dditems) || !count($dditems)) continue;

            $ddaids = array_keys($dditems);
            if (!empty($filter_aids)) {
                // allow filter on passed in aids
                $args['aids'] = array_merge($args['aids'], array_intersect($filter_aids, $ddaids));
            } else {
                $args['aids'] = array_merge($args['aids'], $ddaids);
            }
        }

        if (empty($args['aids'])) return array();

        // Make sure we don't come back to this section the next time around.
        unset($args['wheredd']);

        return xarModApiFunc('articles', 'user', 'getall', $args);
    }
    // Default fields in articles (for now)
    $columns = array('aid','title','summary','authorid','pubdate','pubtypeid',
                     'notes','status','body','checkout','checkouttime','editor');

    // Optional fields in articles (for now)
    // + 'cids' = list of categories an article belongs to
    // + 'author' = user name of authorid
    // + 'counter' = number of times this article was displayed (hitcount)
    // + 'rating' = rating for this article (ratings)
    // + 'dynamicdata' = dynamic data fields for this article (dynamicdata)
    // + 'relevance' = relevance for this article (MySQL full-text search only)
    // $optional = array('cids','author','counter','rating','dynamicdata','relevance');

    if (!isset($fields)) $fields = $columns;
    if (isset($extra) && is_array($extra) && count($extra) > 0) $fields = array_merge($fields,$extra);

    if (empty($sort)) {
        if (!empty($search) && !empty($searchtype) && substr($searchtype, 0, 8) == 'fulltext') {
            if ($searchtype == 'fulltext boolean' && !in_array('relevance', $fields)) {
                // add the relevance to the field list for sorting
                $fields[] = 'relevance';
            }
            // let the database sort by relevance (= default for fulltext)
            $sortlist = array();
        } else {
            // default sort by pubdate
            $sortlist = array('pubdate');
        }
    } elseif (is_array($sort)) {
        $sortlist = $sort;
    } else {
        $sortlist = explode(',', $sort);
    }

    $articles = array();

    // Security check
    if (!xarSecurityCheck('ViewArticles')) return;

    // Fields requested by the calling function
    $required = array();
    foreach ($fields as $field) $required[$field] = 1;

    // mandatory fields for security
    $required['aid'] = 1;
    $required['title'] = 1;
    $required['pubtypeid'] = 1;
    $required['pubdate'] = 1;
    $required['authorid'] = 1; // not to be confused with author (name) :-)

    // force cids as required when categories are given
    if (count($cids) > 0) $required['cids'] = 1;

    // TODO: put all this in dynamic data and retrieve everything via there (including hooked stuff)

    // Database information
    $dbconn = xarDBGetConn();

    // Get the field names and LEFT JOIN ... ON ... parts from articles
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the articles-specific columns too now
    $articlesdef = xarModAPIFunc('articles', 'user', 'leftjoin', $args);
    // TODO : how to handle the case where xar_name is empty, but xar_uname isn't

    if (!empty($required['author'])) {
        // Load API
        if (!xarModAPILoad('roles', 'user')) return;

        // Get the field names and LEFT JOIN ... ON ... parts from users
        $usersdef = xarModAPIFunc('roles', 'user', 'leftjoin');
        if (empty($usersdef)) return;
    }

    if (!empty($required['cids'])) {
        // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
        // This function supports itemtype arrays, so pass in ptids.
        $categoriesdef = xarModAPIFunc(
            'categories', 'user', 'leftjoin',
            array(
                'cids' => $cids,
                'andcids' => $andcids,
                'itemtype' => (isset($ptids) ? $ptids : null),
                'modid' => $modid,
            )
        );

        if (empty($categoriesdef)) return;
    }

    // TODO: It would be easier if xarModIsHooked() supported checking multiple pubtypes at once.
    if (!empty($required['counter'])) {
        // Check hooks for all pubtypes, and do the join if any are hooked.
        foreach($ptids as $hit_ptid) {
            if (!xarModIsHooked('hitcount', 'articles', $hit_ptid)) continue;
            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from hitcount
            // This function supports array itemtypes, so pass in ptids
            $hitcountdef = xarModAPIFunc(
                'hitcount', 'user', 'leftjoin',
                array(
                    'modid' => $modid,
                    'itemtype' => (isset($ptids) ? $ptids : null),
                )
            );
            break;
        }
    }

    if (!empty($required['rating'])) {
        // Check hooks for all pubtypes, and do the join if any are hooked.
        foreach($ptids as $rate_ptid) {
            if (!xarModIsHooked('ratings', 'articles', $rate_ptid)) continue;

            // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from ratings
            $ratingsdef = xarModAPIFunc(
                'ratings', 'user', 'leftjoin',
                array(
                    'modid' => $modid,
                    'itemtype' => (isset($ptids) ? $ptids : null)
                )
            );
            break;
        }
    }

    // Create the SELECT part
    $select = array();
    foreach ($required as $field => $val) {

        // we'll handle this later
        if ($field == 'cids') {
            continue;
        } elseif ($field == 'dynamicdata') {
            continue;
        } elseif ($field == 'author') {
            $select[] = $usersdef['name'];

        } elseif ($field == 'counter') {
            if (!empty($hitcountdef['hits'])) {
                $select[] = $hitcountdef['hits'];
            }
        } elseif ($field == 'rating') {
            if (!empty($ratingsdef['rating'])) {
                $select[] = $ratingsdef['rating'];
            }
        } else {
            if (isset($articlesdef[$field])) {
                $select[] = $articlesdef[$field];
            }
        }
    }
    // FIXME: <rabbitt> PostgreSQL requires that all fields in an 'Order By' be in the SELECT
    //        this has been added to remove the error that not having it creates
    // FIXME: <mikespub> Oracle doesn't allow having the same field in a query twice if you
    //        don't specify an alias (at least in sub-queries, which is what SelectLimit uses)
    if (!in_array($articlesdef['pubdate'], $select)) {
        $select[] = $articlesdef['pubdate'];
    }

    // we need distinct for multi-category OR selects where articles fit in more than 1 category
    if (count($cids) > 0) {
        $query = 'SELECT DISTINCT ' . join(', ', $select);
    } else {
        $query = 'SELECT ' . join(', ', $select);
    }

    // Create the FROM ... [LEFT JOIN ... ON ...] part
    $from = $articlesdef['table'];
    $addme = 0;
    if (!empty($required['author'])) {
        // Add the LEFT JOIN ... ON ... parts from users
        $from .= ' LEFT JOIN ' . $usersdef['table'];
        $from .= ' ON ' . $usersdef['field'] . ' = ' . $articlesdef['authorid'];
        $addme = 1;
    }

    if (!empty($required['counter']) && isset($hitcountdef)) {
        // add this for SQL compliance when there are multiple JOINs
        // bug 4429: sqlite doesnt like the parentheses
        if ($addme && ($dbconn->databaseType != 'sqlite')) {
            $from = '(' . $from . ')';
        }
        // Add the LEFT JOIN ... ON ... parts from hitcount
        $from .= ' LEFT JOIN ' . $hitcountdef['table'];
        $from .= ' ON ' . $hitcountdef['field'] . ' = ' . $articlesdef['aid'];
        $addme = 1;
    }
    if (!empty($required['rating']) && isset($ratingsdef)) {
        // add this for SQL compliance when there are multiple JOINs
        // bug 4429: sqlite doesnt like the parentheses
        if ($addme && ($dbconn->databaseType != 'sqlite')) {
            $from = '(' . $from . ')';
        }
        // Add the LEFT JOIN ... ON ... parts from ratings
        $from .= ' LEFT JOIN ' . $ratingsdef['table'];
        $from .= ' ON ' . $ratingsdef['field'] . ' = ' . $articlesdef['aid'];
        $addme = 1;
    }
    if (count($cids) > 0) {
        // add this for SQL compliance when there are multiple JOINs
        // bug 4429: sqlite doesnt like the parentheses
        if ($addme && ($dbconn->databaseType != 'sqlite')) {
            $from = '(' . $from . ')';
        }
        // Add the LEFT JOIN ... ON ... parts from categories
        $from .= ' LEFT JOIN ' . $categoriesdef['table'];
        $from .= ' ON ' . $categoriesdef['field'] . ' = ' . $articlesdef['aid'];
        if (!empty($categoriesdef['more']) && ($dbconn->databaseType != 'sqlite')) {
            $from = '(' . $from . ')';
            $from .= $categoriesdef['more'];
        }
    }
    $query .= ' FROM ' . $from;

// TODO: check the order of the conditions for brain-dead databases ?
    // Create the WHERE part
    $where = array();

    // we rely on leftjoin() to create the necessary articles clauses now
    if (!empty($articlesdef['where'])) $where[] = $articlesdef['where'];
    if (!empty($required['counter']) && !empty($hitcountdef['where'])) $where[] = $hitcountdef['where'];
    if (!empty($required['rating']) && !empty($ratingsdef['where'])) $where[] = $ratingsdef['where'];
    // We rely on leftjoin() to create the necessary categories clauses
    if (count($cids) > 0) $where[] = $categoriesdef['where'];

    if (count($where) > 0) $query .= ' WHERE ' . join(' AND ', $where);
      // TODO: support other non-articles fields too someday ?

    // Create the ORDER BY part
    if (count($sortlist) > 0) {
        $sortparts = array();
        $seenaid = 0;
        foreach ($sortlist as $criteria) {
            // ignore empty sort criteria
            if (empty($criteria)) continue;
            // split off trailing ASC or DESC
            if (preg_match('/^(.+)\s+(ASC|DESC)\s*$/i',$criteria,$matches)) {
                $criteria = trim($matches[1]);
                $sortorder = strtoupper($matches[2]);
            } else {
                $sortorder = '';
            }

            if ($criteria == 'title') {
                $sortparts[] = $articlesdef['title'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
            } elseif ($criteria == 'pubdate' || $criteria == 'date' || $criteria == 'dateup' ||  $criteria == 'datedown') {
                if ($criteria == 'dateup') {
                   $sortparts[] = $articlesdef['pubdate'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                } else {
                    $sortparts[] = $articlesdef['pubdate'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
                }
            } elseif ($criteria == 'hits' && !empty($hitcountdef['hits'])) {
                $sortparts[] = $hitcountdef['hits'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
            } elseif ($criteria == 'rating' && !empty($ratingsdef['rating'])) {
                $sortparts[] = $ratingsdef['rating'] . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
            } elseif ($criteria == 'author' && !empty($usersdef['name'])) {
                $sortparts[] = $usersdef['name'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
            } elseif ($criteria == 'relevance' && !empty($articlesdef['relevance'])) {
                $sortparts[] = 'relevance' . ' ' . (!empty($sortorder) ? $sortorder : 'DESC');
            } elseif ($criteria == 'aid') {
                $sortparts[] = $articlesdef['aid'] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
                $seenaid = 1;
            // other articles fields, e.g. summary, notes, ...
            } elseif (!empty($articlesdef[$criteria])) {
                $sortparts[] = $articlesdef[$criteria] . ' ' . (!empty($sortorder) ? $sortorder : 'ASC');
            } else {
                // ignore unknown sort fields
            }
        }
        // add sorting by aid for unique sort order
        if (count($sortparts) < 2 && empty($seenaid)) {
            $sortparts[] = $articlesdef['aid'] . ' DESC';
        }
        $query .= ' ORDER BY ' . join(', ',$sortparts);

    } elseif (!empty($search) && !empty($searchtype) && substr($searchtype,0,8) == 'fulltext') {
        // For fulltext, let the database return the articles by relevance here (= default)

        // For fulltext in boolean mode, add MATCH () ... AS relevance ... ORDER BY relevance DESC (cfr. leftjoin)
        if (!empty($required['relevance']) && $searchtype == 'fulltext boolean') {
            $query .= ' ORDER BY relevance DESC, ' . $articlesdef['pubdate'] . ' DESC, ' . $articlesdef['aid'] . ' DESC';
        }

    } else { // default is 'pubdate'
        $query .= ' ORDER BY ' . $articlesdef['pubdate'] . ' DESC, ' . $articlesdef['aid'] . ' DESC';
    }

    // Run the query - finally :-)
    if (isset($numitems) && is_numeric($numitems)) {
        $result = $dbconn->SelectLimit($query, $numitems, $startnum-1);
    } else {
        $result = $dbconn->Execute($query);
    }
    if (!$result) return;

    $itemids_per_type = array();
    // Put articles into result array
    for (; !$result->EOF; $result->MoveNext()) {
        $data = $result->fields;

        $item = array();
        // loop over all required fields again
        foreach ($required as $field => $val) {
            if ($field == 'cids' || $field == 'dynamicdata' || $val != 1) {
                continue;
            }
            $value = array_shift($data);
            if ($field == 'rating') {
                $value = intval($value);
            }
            $item[$field] = $value;
        }
        // check security - don't generate an exception here
        if (empty($required['cids']) && !xarSecurityCheck('ViewArticles',0,'Article',"$item[pubtypeid]:All:$item[authorid]:$item[aid]")) {
            continue;
        }
        $articles[] = $item;
        if (!empty($required['dynamicdata'])) {
            $pubtype = $item['pubtypeid'];
            if (!isset($itemids_per_type[$pubtype])) {
                $itemids_per_type[$pubtype] = array();
            }
            $itemids_per_type[$pubtype][] = $item['aid'];
        }
    }
    $result->Close();

    if (!empty($required['cids']) && count($articles) > 0) {
        // Get all the categories at once
        $aids = array();
        foreach ($articles as $article) {
            $aids[] = $article['aid'];
        }

        // Get the links for the Array of iids we have
        $cids = xarModAPIFunc(
            'categories', 'user', 'getlinks',
            array(
                'iids' => $aids,
                'reverse' => 1,
                // Note : we don't need to specify the item type here for articles, since we use unique ids anyway
                'modid' => $modid,
            )
        );

        // Inserting the corresponding Category ID in the Article Description
        $delete = array();
        $cachesec = array();
        foreach ($articles as $key => $article) {
           // Get the article settings for this publication type -- sheesh more hits
            //try cache
            $checkpubdate = xarVarGetCached('articles.checkdate',$item['pubtypeid']);
            if (!isset($checkpubdate)) {
                if (empty($item['pubtypeid'])) {
                    $settings = unserialize(xarModGetVar('articles', 'settings'));
                } else {
                    $settings = unserialize(xarModGetVar('articles', 'settings.'.$article['pubtypeid']));
                }
                $checkpubdate = isset($settings['checkpubdate'])?$settings['checkpubdate']:0;
                xarVarSetCached('articles.checkdate',$article['pubtypeid'],$checkpubdate);
            }
            if (isset($cids[$article['aid']]) && count($cids[$article['aid']]) > 0) {
                $articles[$key]['cids'] = $cids[$article['aid']];
                foreach ($cids[$article['aid']] as $cid) {

                    if (($checkpubdate ==1) && $article['pubdate']> time()) { //don't display article unless a person has edit level privs
                        if (!xarSecurityCheck('EditArticles',0,'Article',"$article[pubtypeid]:$cid:$article[authorid]:$article[aid]")) {
                          $delete[$key] = 1;
                          break;
                        }
                    //now check loser level security
                    }elseif (!xarSecurityCheck('ViewArticles',0,'Article',"$article[pubtypeid]:$cid:$article[authorid]:$article[aid]")) {
                        $delete[$key] = 1;
                        break;
                    }
                    if (!isset($cachesec[$cid])) {
                    // TODO: combine with ViewCategoryLink check when we can combine module-specific
                    // security checks with "parent" security checks transparently ?
                        $cachesec[$cid] = xarSecurityCheck('ReadCategories',0,'Category',"All:$cid");
                    }
                    if (!$cachesec[$cid]) {
                        $delete[$key] = 1;
                        break;
                    }
                }
            } else {

                if (($checkpubdate ==1) && $article['pubdate']> time()) { //don't display article unless a person has edit level privs
                    if (!xarSecurityCheck('EditArticles',0,'Article',"$article[pubtypeid]:All:$article[authorid]:$article[aid]")) return;
                    $delete[$key] = 1;
                    continue;
                //now check the lower level security
                } elseif (!xarSecurityCheck('ViewArticles',0,'Article',"$article[pubtypeid]:All:$article[authorid]:$article[aid]")) {
                    $delete[$key] = 1;
                    continue;
                }
            }
        }

        if (count($delete) > 0) {
            foreach ($delete as $key => $val) {
                unset($articles[$key]);
            }
        }
    }

    if (!empty($required['dynamicdata']) && count($articles) > 0) {
        foreach ($itemids_per_type as $pubtype => $itemids) {
            if (!xarModIsHooked('dynamicdata', 'articles', $pubtype)) continue;

            list($properties, $items) = xarModAPIFunc(
                'dynamicdata', 'user', 'getitemsforview',
                array(
                    'module' => 'articles',
                    'itemtype' => $pubtype,
                    'itemids' => $itemids,
                    // ignore the display-only properties
                    'status'   => 1,
                )
            );

            if (empty($properties) || count($properties) == 0) continue;
            foreach ($articles as $key => $article) {

                // otherwise articles (of different pub types) with dd properties having the same
                // names reset previously set values to empty string for each iteration based on the pubtype
                if ($article['pubtypeid'] != $pubtype) continue;

                foreach (array_keys($properties) as $name) {
                    if (isset($items[$article['aid']]) && isset($items[$article['aid']][$name])) {
                        $value = $items[$article['aid']][$name];
                    } else {
                        $value = $properties[$name]->default;
                    }

                    $articles[$key][$name] = $value;

                    // TODO: clean up this temporary fix
                    if (!empty($value)) {
                        $articles[$key][$name.'_output'] = $properties[$name]->showOutput(array('value' => $value));
                    }
                }
            }
        }
    }

    return $articles;
}

?>