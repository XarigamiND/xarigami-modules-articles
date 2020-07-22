<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * get the name and description of all publication types
 *
 * @param $args['ptid'] int publication type ID (optional) OR
 * @param $args['name'] string publication type name (optional)
 * @return array(id => array('name' => name, 'descr' => descr)), or false on
 *         failure
 */
function articles_userapi_getpubtypes($args)
{
    static $all_pubtypes;

    $bindvars = array();
    $pubtypes = array();

    //if we're doing a simple retrieval, use same results as last time
    //otherwise we need to re-query the database
    if (count($args) == 0 && isset($all_pubtypes)) {
        return $all_pubtypes;
    }

    if (!empty($args['ptid']) && isset($all_pubtypes[$args['ptid']])) return array($all_pubtypes[$args['ptid']]);

    if (isset($args['sort'])) {
        $sort = $args['sort'];
    } else {
        $sort = xarModGetVar('articles','sortpubtypes');
    }
    if (empty($sort)) {
        $sort = 'id';
    }

    //optional parameters to restrict results
    if (isset($args['name']) && is_string($args['name'])) {
        $name = $args['name'];
    }
    if (isset($args['ptid']) && is_numeric($args['ptid'])) {
        $ptid = $args['ptid'];
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $pubtypestable = $xartable['publication_types'];

    // Get item
    $query = 'SELECT xar_pubtypeid, xar_pubtypename, xar_pubtypedescr, xar_pubtypeconfig'
        . ' FROM ' . $pubtypestable;

    //WHERE clause begins
    if(isset($name)) {
        $query .= ' WHERE xar_pubtypename = ? ';
        $bindvars[] = $name;
    } else if (isset($ptid)) {
        $query .= ' WHERE xar_pubtypeid = ? ';
        $bindvars[] = $ptid;
    }

    //different sort options
    switch ($sort) {
        case 'name':
            $query .= ' ORDER BY xar_pubtypename ASC';
            break;
        case 'descr':
            $query .= ' ORDER BY xar_pubtypedescr ASC';
            break;
        case 'id':
        default:
            $query .= ' ORDER BY xar_pubtypeid ASC';
            break;
    }
    $result = $dbconn->Execute($query, $bindvars);
    if (!$result) return;

    if (!$result->EOF) {
        while (!$result->EOF) {
            list($id, $name, $descr, $config) = $result->fields;
            $pubtypes[$id] = array(
                'ptid' => $id,
                'name' => $name,
                'descr' => $descr,
                'config' => unserialize($config)
            );
            $result->MoveNext();
        }
    }

    // Cache the results if we are fetching all of them.
    if (count($args) == 0) $all_pubtypes = $pubtypes;

    return $pubtypes;
}

?>