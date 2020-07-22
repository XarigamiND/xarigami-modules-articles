<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * count number of items depending on additional module criteria
 *
 * @param array group
 * @return array number of items with descriptors
 */
function articles_adminapi_getstats($args)
{
    extract($args);

    $allowedfields = array('pubtypeid', 'status', 'authorid', 'language', 'pubdate_year', 'pubdate_month', 'pubdate_day');
    if (empty($group)) {
        $group = array();
    }
    $newfields = array();
    $newgroups = array();
    foreach ($group as $field) {
        if (empty($field) || !in_array($field,$allowedfields)) {
            continue;
        }
        if ($field == 'pubdate_year') {
            $dbtype = xarDBGetType();
            switch ($dbtype) {
                case 'mysql':
                    $newfields[] = "LEFT(FROM_UNIXTIME(xar_pubdate),4) AS myyear";
                    $newgroups[] = "myyear";
                    break;
                case 'postgres':
                    $newfields[] = "TO_CHAR(ABSTIME(xar_pubdate),'YYYY') AS myyear";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroups[] = "myyear";
                    break;
                case 'mssql':
                    $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),4) as myyear";
                    $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),4)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    continue;
            }
        } elseif ($field == 'pubdate_month') {
            $dbtype = xarDBGetType();
            switch ($dbtype) {
                case 'mysql':
                    $newfields[] = "LEFT(FROM_UNIXTIME(xar_pubdate),7) AS mymonth";
                    $newgroups[] = "mymonth";
                    break;
                case 'postgres':
                    $newfields[] = "TO_CHAR(ABSTIME(xar_pubdate),'YYYY-MM') AS mymonth";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroups[] = "mymonth";
                    break;
                case 'mssql':
                    $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),7) as mymonth";
                    $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),7)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    continue;
            }
        } elseif ($field == 'pubdate_day') {
            $dbtype = xarDBGetType();
            switch ($dbtype) {
                case 'mysql':
                    $newfields[] = "LEFT(FROM_UNIXTIME(xar_pubdate),10) AS myday";
                    $newgroups[] = "myday";
                    break;
                case 'postgres':
                    $newfields[] = "TO_CHAR(ABSTIME(xar_pubdate),'YYYY-MM-DD') AS myday";
                // CHECKME: do we need to use TO_CHAR(...) for the group field too ?
                    $newgroups[] = "myday";
                    break;
                case 'mssql':
                    $newfields[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),10) as myday";
                    $newgroups[] = "LEFT(CONVERT(VARCHAR,DATEADD(ss,xar_pubdate,'1/1/1970'),120),10)";
                    break;
                // TODO:  Add SQL queries for Oracle, etc.
                default:
                    continue;
            }
        } else {
            $newfields[] = 'xar_' . $field;
            $newgroups[] = 'xar_' . $field;
        }
    }
    if (empty($newfields) || count($newfields) < 1) {
        $newfields = array('xar_pubtypeid', 'xar_status', 'xar_authorid');
        $newgroups = array('xar_pubtypeid', 'xar_status', 'xar_authorid');
    }

    // Database information
    $dbconn = xarDBGetConn();
    $xartables = xarDBGetTables();

    $query = 'SELECT ' . join(', ', $newfields) . ', COUNT(*)
              FROM ' . $xartables['articles'] . '
              GROUP BY ' . join(', ', $newgroups) . '
              ORDER BY ' . join(', ', $newgroups);

    $result = $dbconn->Execute($query);
    if (!$result) return;

    $stats = array();
    while (!$result->EOF) {
        if (count($newfields) > 3) {
            list($field1,$field2,$field3,$field4,$count) = $result->fields;
            $stats[$field1][$field2][$field3][$field4] = $count;
        } elseif (count($newfields) == 3) {
            list($field1,$field2,$field3,$count) = $result->fields;
            $stats[$field1][$field2][$field3] = $count;
        } elseif (count($newfields) == 2) {
            list($field1,$field2,$count) = $result->fields;
            $stats[$field1][$field2] = $count;
        } elseif (count($newfields) == 1) {
            list($field1,$count) = $result->fields;
            $stats[$field1] = $count;
        }
        $result->MoveNext();
    }
    $result->Close();

    return $stats;
}

?>
