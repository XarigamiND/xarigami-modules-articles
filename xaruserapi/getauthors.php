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
 */
/**
 * get a list of article authors depending on additional module criteria
 *
 * @param $args['cids'] array of cids that we are counting for (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 *
 * @param $args['authorid'] the ID of the author
 * @param $args['ptid'] publication type ID (for news, sections, reviews, ...)
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['startdate'] articles published at startdate or later
 *                           (unix timestamp format)
 * @param $args['enddate'] articles published before enddate
 *                         (unix timestamp format)
 * @return array of author id => author name
 */
function articles_userapi_getauthors($args)
{
    // Database information
    $dbconn = xarDBGetConn();

    // Get the field names and LEFT JOIN ... ON ... parts from articles
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the articles-specific columns too now
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

    // Load API
    if (!xarModAPILoad('roles', 'user')) return;

    // Get the field names and LEFT JOIN ... ON ... parts from users
    $usersdef = xarModAPIFunc('roles','user','leftjoin');

// TODO: make sure this is SQL standard
    // Start building the query
    $query = 'SELECT DISTINCT ' . $articlesdef['authorid'] . ', ' . $usersdef['name'];
    $query .= ' FROM ' . $articlesdef['table'];

    // Add the LEFT JOIN ... ON ... parts from users
    $query .= ' LEFT JOIN ' . $usersdef['table'];
    $query .= ' ON ' . $usersdef['field'] . ' = ' . $articlesdef['authorid'];

    if (!isset($args['cids'])) {
        $args['cids'] = array();
    }
    if (!isset($args['andcids'])) {
        $args['andcids'] = false;
    }
    if (count($args['cids']) > 0) {
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

    // Order by author name
    $query .= ' ORDER BY ' . $usersdef['name'] . ' ASC';

    // Run the query - finally :-)
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $authors = array();
    while (!$result->EOF) {
        list($uid, $name) = $result->fields;
        $authors[$uid] = $name;
        $result->MoveNext();
    }

    $result->Close();

    return $authors;
}

?>