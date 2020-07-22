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
 * get the number of articles per publication type
 * @param $args['status'] array of requested status(es) for the articles
 * @return array array(id => count), or false on failure
 */
function articles_userapi_getpubcount($args)
{
    if (empty($args['status'])) {
        $key = 'all';
    } else {
        sort($args['status']);
        $key = join('+',$args['status']);
    }
    if (xarVarIsCached('Articles.PubCount',$key)) {
        return xarVarGetCached('Articles.PubCount',$key);
    }

    $pubcount = array();

    // Get database setup
    $dbconn = xarDBGetConn();

    // Get the LEFT JOIN ... ON ...  and WHERE parts from articles
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

    $query = 'SELECT ' . $articlesdef['pubtypeid'] . ', COUNT(*)
            FROM ' . $articlesdef['table'];
    if (!empty($articlesdef['where'])) {
        $query .= ' WHERE ' . $articlesdef['where'];
    }
    $query .= ' GROUP BY ' . $articlesdef['pubtypeid'];
    $result = $dbconn->Execute($query);
    if (!$result) return;

    if ($result->EOF) {
        xarVarSetCached('Articles.PubCount',$key,$pubcount);
        return $pubcount;
    }
    while (!$result->EOF) {
        list($id, $count) = $result->fields;
        $pubcount[$id] = $count;
        $result->MoveNext();
    }

    xarVarSetCached('Articles.PubCount',$key,$pubcount);
    return $pubcount;
}

?>
