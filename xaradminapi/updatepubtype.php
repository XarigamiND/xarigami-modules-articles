<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * Update a publication type
 *
 * @param id $args['ptid'] ID of the publication type
 * @param string $args['name'] name of the publication type
 * @param string $args['descr'] description of the publication type
 * @param array $args['config'] configuration of the publication type
 * @return bool true on success, false on failure
 */
function articles_adminapi_updatepubtype($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    // Note : since we have several arguments we want to check here, we'll
    // report all those that are invalid at the same time...
    $invalid = array();
    if (!isset($ptid) || !is_numeric($ptid) || $ptid < 1) {
        $invalid[] = 'publication type ID';
    }
    if (!isset($name) || !is_string($name) || empty($name)) {
        $invalid[] = 'name';
    }

    if (!isset($descr) || !is_string($descr) || empty($descr)) {
        $invalid[] = 'description';
    }
    if (!isset($config) || !is_array($config) || count($config) == 0) {
        $invalid[] = 'configuration';
    }
    if (count($invalid) > 0) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    join(', ',$invalid), 'admin', 'updatepubtype','Articles');
        throw new BadParameterException(null,$msg);
    }

    // Security check - we require ADMIN rights here
    if (!xarSecurityCheck('AdminArticles',1,'Article',"$ptid:All:All:All")) return;

    // Load user API to obtain item information function
    if (!xarModAPILoad('articles', 'user')) return;

    // Get current publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    if (!isset($pubtypes[$ptid])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'publication type ID', 'admin', 'updatepubtype',
                    'Articles');
        throw new BadParameterException(null,$msg);;
    }

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

    // Update the publication type (don't allow updates on name)
    // Overwrite input with old name if change is not allowed
    if (!xarModGetVar('articles', 'ptypenamechange')) {
        $name = $pubtypes[$ptid]['name'];
    }
    $query = "UPDATE $pubtypestable
            SET xar_pubtypedescr = ?,
                xar_pubtypename = ?,
                xar_pubtypeconfig = ?
            WHERE xar_pubtypeid = ?";
    $bindvars = array($descr, $name, serialize($config), $ptid);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
     xarLogMessage('ARTICLES: pubtype '.$ptid.' updated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>
