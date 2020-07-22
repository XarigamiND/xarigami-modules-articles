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
 * Update an article checkout status
 * @author jojodee
 * @param id $args['aid'] ID of the item (mandatory argument)
 * @param int $args['editor'] ID of the actual editor
 * @param int $args['checkout'] checkout status of the item, 0-in, 1-out, 2-archived
 * @param int $args['checkouttime'] checkout time as unix time format
 * @return bool true on success, false on failure
 */
function articles_adminapi_checkout($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($aid) || !is_numeric($aid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'article ID', 'admin', 'update',
                    'Articles');
        throw new BadParameterException(null,$msg);
    }
    if (!isset($checkout)) {
        return; //do we want to display error here? think about it
    }
    // Security check
    if (!xarModAPILoad('articles', 'user')) return;

    $args['mask'] = 'EditArticles';
    if (!xarModAPIFunc('articles','user','checksecurity',$args)) {
        $msg = xarML('Not authorized to update #(1) items',
                    'Article');
         return xarResponseForbidden($msg);
    }
  // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $articlestable = $xartable['articles'];

    $bindvars = array();
    // Update the item
    $query = "UPDATE $articlestable
              SET xar_checkout = ?";
    $bindvars[] = (int) $checkout;

    if (isset($editor)&& is_numeric($editor)){
        $query .= ", xar_editor = ?";
        $bindvars[] = (int) $editor;
    }
    if (isset($checkouttime) && $checkouttime !=0) {//keep the checkout time as time of last edit
        $query .= ", xar_checkouttime = ?";
        $bindvars[] = (int) $checkouttime;
    }

    $query .= " WHERE xar_aid = ?";
    $bindvars[] =  (int) $aid;
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;
     xarLogMessage('ARTICLES: article '.$aid.' checked out by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return true;
}

?>
