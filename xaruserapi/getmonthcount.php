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
 * count the number of items per month
 * @param $args['cids'] not supported here (yet ?)
 * @param $args['ptid'] publication type ID we're interested in
 * @param $args['status'] array of requested status(es) for the articles
 * @return array array(month => count), or false on failure
 */
function articles_userapi_getmonthcount($args)
{
    // Get database setup
    $dbconn = xarDBGetConn();

    // Get the field names and LEFT JOIN ... ON ... parts from articles
    // By passing on the $args, we can let leftjoin() create the WHERE for
    // the articles-specific columns too now
    $articlesdef = xarModAPIFunc('articles','user','leftjoin',$args);

    // Bug 1590 - Create custom query supported by each database.
    $dbtype = xarDBGetType();
    switch ($dbtype) {
        case 'mysql':
            $query = "SELECT LEFT(FROM_UNIXTIME(xar_pubdate),7) AS mymonth, COUNT(*) FROM " . $articlesdef['table'];
            break;
        case 'postgres':
            $query = "SELECT TO_CHAR(ABSTIME(xar_pubdate),'YYYY-MM') AS mymonth, COUNT(*) FROM " . $articlesdef['table'];
            break;
        case 'mssql':
            $query = "SELECT LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),7) as mymonth, COUNT(*) FROM " . $articlesdef['table'];
            break;
        // TODO:  Add SQL queries for Oracle, etc.
        default:
            return;
    }
    if (!empty($articlesdef['where'])) {
        $query .= ' WHERE ' . $articlesdef['where'];
    }
    switch ($dbtype) {
        case 'mssql':
            $query .= " GROUP BY LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),7)";
            break;
        default:
            $query .= ' GROUP BY mymonth';
            break;
    }
    $result = $dbconn->Execute($query);
    if (!$result) return;

    $months = array();
    while (!$result->EOF) {
        list($month, $count) = $result->fields;
        $months[$month] = $count;
        $result->MoveNext();
    }

    return $months;
}

?>
