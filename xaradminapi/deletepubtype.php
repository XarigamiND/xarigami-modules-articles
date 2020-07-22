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
 * Delete a publication type
 *
 * @param $args['ptid'] ID of the publication type
 * @return bool true on success, false on failure
 */
function articles_adminapi_deletepubtype($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check - make sure that all required arguments are present
    // and in the right format, if not then set an appropriate error
    // message and return
    if (!isset($ptid) || !is_numeric($ptid) || $ptid < 1) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'publication type ID', 'admin', 'deletepubtype',
                    'Articles');
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
                    'publication type ID', 'admin', 'deletepubtype',
                    'Articles');
       throw new BadParameterException(null,$msg);;
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $pubtypestable = $xartable['publication_types'];

    // Delete the publication type
    $query = "DELETE FROM $pubtypestable
            WHERE xar_pubtypeid = ?";
    $result = $dbconn->Execute($query,array($ptid));
    if (!$result) return;

    $articlestable = $xartable['articles'];

    // Delete all articles for this publication type
    $query = "DELETE FROM $articlestable
            WHERE xar_pubtypeid = ?";
    $result = $dbconn->Execute($query,array($ptid));
    if (!$result) return;

// TODO: call some kind of itemtype delete hooks here, once we have those
    //xarModCallHooks('itemtype', 'delete', $ptid,
    //                array('module' => 'articles',
    //                      'itemtype' =>'ptid'));
     xarLogMessage('ARTICLES: pubtype '.$ptid.' deleted by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>
