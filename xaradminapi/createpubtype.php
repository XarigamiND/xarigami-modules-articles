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
 * Create a new publication type
 *
 * @param $args['name'] name of the publication type
 * @param $args['descr'] description of the publication type
 * @param $args['config'] configuration of the publication type
 * @return int publication type ID on success, false on failure
 */
function articles_adminapi_createpubtype($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    // Note : since we have several arguments we want to check here, we'll
    // report all those that are invalid at the same time...
    $invalid = array();
    if (!isset($name) || !is_string($name) || empty($name)) {
        $invalid[] = 'name';
    }
    if (!isset($config) || !is_array($config) || count($config) == 0) {
        $invalid[] = 'configuration';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'createpubtype','Articles');
        throw new BadParameterException(null,$msg);
    }

    if (empty($descr)) {
        $descr = $name;
    }

    // Publication type names *must* be lower-case for now
    $name = strtolower($name);

    // Security check - we require ADMIN rights here
    if (!xarSecurityCheck('AdminArticles')) return;

    if (!xarModAPILoad('articles', 'user')) return;

    // Make sure we have all the configuration fields we need
    $pubfields = xarModAPIFunc('articles','user','getpubfields');
    foreach ($pubfields as $field => $value) {
        if (!isset($config[$field])) {
            $config[$field] = '';
        }
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $pubtypestable = $xartable['publication_types'];

    // Get next ID in table
    $nextId = $dbconn->GenId($pubtypestable);

    // Insert the publication type
    $query = "INSERT INTO $pubtypestable (xar_pubtypeid, xar_pubtypename,
            xar_pubtypedescr, xar_pubtypeconfig)
            VALUES (?,?,?,?)";
    $bindvars = array($nextId, $name, $descr, serialize($config));
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Get ptid to return
    $ptid = $dbconn->PO_Insert_ID($pubtypestable, 'xar_pubtypeid');

    // Don't call creation hooks here...
    //xarModCallHooks('item', 'create', $ptid, 'ptid');
     xarLogMessage('ARTICLES: pubtype '.$ptid.' created by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return $ptid;
}

?>
