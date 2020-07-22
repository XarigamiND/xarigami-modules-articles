<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * count number of items depending on additional module criteria
 *
 * @param $args['catid'] string of category id(s) that we're counting in, or
 * @param $args['cids'] array of cids that we are counting in (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 *
 * @param $args['authorid'] the ID of the author
 * @param $args['ptid'] publication type ID (for news, sections, reviews, ...)
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['startdate'] articles published at startdate or later
 *                           (unix timestamp format)
 * @param $args['enddate'] articles published before enddate
 *                         (unix timestamp format)
 * @return int number of items
 */
function articles_userapi_countitems($args)
{
    // Database information
    $dbconn = xarDBGetConn();

    // Get the field names and LEFT JOIN ... ON ... parts from articles
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the articles-specific columns too now
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

// TODO: make sure this is SQL standard
    // Start building the query
    if($dbconn->databaseType == 'sqlite') {
        $query = 'SELECT COUNT(*)
                  FROM ( SELECT DISTINCT '. $articlesdef['field'].'
                         FROM '. $articlesdef['table']; // WATCH OUT, UNBALANCED
    } else {
        $query = 'SELECT COUNT(DISTINCT ' . $articlesdef['field'] . ')';
        $query .= ' FROM ' . $articlesdef['table'];
    }

    if (!isset($args['cids'])) {
        $args['cids'] = array();
    }
    if (!isset($args['andcids'])) {
        $args['andcids'] = false;
    }
    if (count($args['cids']) > 0 || !empty($args['catid'])) {
        // Load API
        if (!xarModAPILoad('categories', 'user')) return;

        // Get the LEFT JOIN ... ON ...  and WHERE (!) parts from categories
        $args['modid'] = xarModGetIDFromName('articles');
        if (isset($args['ptid']) && !isset($args['itemtype'])) {
            $args['itemtype'] = $args['ptid'];
        }
        $categoriesdef = xarModAPIFunc('categories','user','leftjoin',$args);

        $query .= ' LEFT JOIN ' . $categoriesdef['table'];
        $query .= ' ON ' . $categoriesdef['field'] . ' = '
                . $articlesdef['aid'];
        $query .= $categoriesdef['more'];
        $docid = 1;
    }

    // Create the WHERE part
    $where = array();
    // we rely on leftjoin() to create the necessary articles clauses now
    if (!empty($articlesdef['where'])) {
        $where[] = $articlesdef['where'];
    }
    if (!empty($docid)) {
        // we rely on leftjoin() to create the necessary categories clauses
        $where[] = $categoriesdef['where'];
    }
    if (count($where) > 0) {
        $query .= ' WHERE ' . join(' AND ', $where);
    }

    // Balance parentheses
    if($dbconn->databaseType == 'sqlite') $query .=')';
    // Run the query - finally :-)
    $result = $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        return;
    }

    $num = $result->fields[0];
    $result->Close();

    return $num;
}

?>