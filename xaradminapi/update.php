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
 * @author mikespub
 * @author xarigami articles dev team
 */
/**
 * Update an article
 * Usage : if (xarModAPIFunc('articles', 'admin', 'update', $article)) {...}
 *
 * @param id $args['aid'] ID of the item (mandatory argument)
 * @param string $args['title'] name of the item (mandatory argument)
 * @param string $args['summary'] summary of the item
 * @param string $args['body'] body of the item
 * @param string $args['notes'] notes for the item
 * @param $args['status'] status of the item
 * @param int $args['ptid'] publication type ID for the item (*cough*)
 * @param int $args['pubdate'] publication date in unix time format
 * @param int $args['authorid'] ID of the new author (*cough*)
 * @param $args['language'] language of the item
 * @param $args['cids'] category IDs this item belongs to
 * @param int $args['editor'] ID of the actual editor
 * @param int $args['checkout'] checkout status of the item, 0-in, 1-out, 2-archived
 * @param int $args['checkouttime'] checkout time as unix time format
 * @return bool true on success, false on failure
 */
function articles_adminapi_update($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($aid) || !is_numeric($aid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'article ID', 'admin', 'update',
                    'Articles');
        throw new BadParameterException(null,$msg);
    } elseif (empty($title)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'title', 'admin', 'update',
                    'Articles');
        throw new BadParameterException(null,$msg);
        return false;
    }

// Note : this will take care of checking against the current article values
//        too if nothing is passed as arguments except aid & title

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
            SET xar_title = ?";
    $bindvars[] = (string) $title;
// Note : we use isset() here because we *do* care whether it's set to ''
//        or if it's not set at all

    if (isset($summary)) {
        $query .= ", xar_summary = ?";
        $bindvars[] = (string) $summary;
    }

    if (isset($body)) {
        $query .= ", xar_body = ?";
        $bindvars[] = (string) $body;
    }

    if (isset($notes)) {
        $query .= ", xar_notes = ?";
        $bindvars[] = (string) $notes;
    }

    if (isset($status) && is_numeric($status)) {
        $oldversion= xarModAPIFunc('articles','user','get',
                                    array('aid'=>$aid,
                                          'fields'=>array('status')));
        $args['oldstatus'] = $oldversion['status'];
        $query .= ", xar_status = ?";
        $bindvars[] = (int) $status;
    }

    // not recommended
    if (isset($ptid) && is_numeric($ptid)) {
        $query .= ", xar_pubtypeid = ?";
        $bindvars[] = (int) $ptid;
    }

    if (isset($pubdate) && is_numeric($pubdate)) {
        $query .= ", xar_pubdate = ?";
        $bindvars[] = (int) $pubdate;
    }

    // not recommended
    if (isset($authorid) && is_numeric($authorid)) {
        $query .= ", xar_authorid = ?";
        $bindvars[] = (int) $authorid;
    }

    if (isset($language)) {
        $query .= ", xar_language = ?";
        $bindvars[] = (string) $language;
    }

    if (isset($checkout)) {
        $query .= ", xar_checkout = ?";
        $bindvars[] = (int) $checkout;
    }
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

    if (empty($cids)) {
        $cids = array();
    }
   xarLogMessage('ARTICLES: article '.$aid.' updated by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    // Call update hooks for categories etc.
    // We need to tell some hooks that we are coming from the update status screen
    // and not the update the actual article screen.  Right now, the keywords vanish
    // into thin air.  Bug 1960 and 3161
    if (xarVarIsCached('Hooks.all','noupdate')){
        $args['statusflag'] = true; // legacy support for old method - remove later on
    }

    $args['module'] = 'articles';
    if (isset($ptid)) {
        $args['itemtype'] = $ptid;
    } elseif (isset($pubtypeid)) {
        $args['itemtype'] = $pubtypeid;
    }
    $args['cids'] = $cids;
    xarModCallHooks('item', 'update', $aid, $args);

    return true;
}

?>
