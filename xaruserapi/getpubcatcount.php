<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * get the number of articles per publication type and category
 *
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['ptid'] publication type ID
 * @param $args['cids'] array of category IDs (OR/AND)
 * @param $args['andcids'] true means AND-ing categories listed in cids
 * @param $args['groupcids'] the number of categories you want items grouped by
 * @param $args['reverse'] default is ptid => cid, reverse (1) is cid => ptid
 * @return array array( $ptid => array( $cid => $count) ),
 *         or false on failure
 */
function articles_userapi_getpubcatcount($args)
{
/*
    static $pubcatcount = array();

    if (count($pubcatcount) > 0) {
        return $pubcatcount;
    }
*/
    $pubcatcount = array();

    // Get database setup
    $dbconn = xarDBGetConn();

    // Get the LEFT JOIN ... ON ...  and WHERE parts from articles
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

    // Load API
    if (!xarModAPILoad('categories', 'user')) return;

    $args['modid'] = xarModGetIDFromName('articles');
    if (isset($args['ptid']) && !isset($args['itemtype'])) {
        $args['itemtype'] = $args['ptid'];
    }
    // Get the LEFT JOIN ... ON ...  and WHERE parts from categories
    $categoriesdef = xarModAPIFunc('categories','user','leftjoin',$args);

    // Get count
    $query = 'SELECT '. $articlesdef['pubtypeid'] .', '. $categoriesdef['cid']
           .', COUNT(*)
            FROM '. $articlesdef['table'] . '
            LEFT JOIN ' . $categoriesdef['table'] .'
            ON '. $categoriesdef['field'] . ' = ' . $articlesdef['field'] .
            $categoriesdef['more'] . '
            WHERE '. $categoriesdef['where'] .' AND '. $articlesdef['where'] .'
            GROUP BY '. $articlesdef['pubtypeid'] .', '. $categoriesdef['cid'];

    $result = $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        if (!empty($args['ptid']) && empty($args['reverse'])) {
            $pubcatcount[$args['ptid']] = array();
        }
        return $pubcatcount;
    }
    while (!$result->EOF) {
        // we may have 1 or more cid fields here, depending on what we're
        // counting (cfr. AND in categories)
        $fields = $result->fields;
        $ptid = array_shift($fields);
        $count = array_pop($fields);
// TODO: use multi-level array for multi-category grouping ?
        $cid = join('+',$fields);
        if (empty($args['reverse'])) {
            $pubcatcount[$ptid][$cid] = $count;
        } else {
            $pubcatcount[$cid][$ptid] = $count;
        }
        $result->MoveNext();
    }
    foreach ($pubcatcount as $id1 => $val) {
        $total = 0;
        foreach ($val as $id2 => $count) {
            $total += $count;
        }
        $pubcatcount[$id1]['total'] = $total;
    }

    return $pubcatcount;
}

?>
