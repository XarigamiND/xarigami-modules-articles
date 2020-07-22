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
 * Create a new article
 * Usage : $aid = xarModAPIFunc('articles', 'admin', 'create', $article);
 *
 * @param string $args['title'] name of the item (this is the only mandatory argument)
 * @param string $args['summary'] summary for this item
 * @param string $args['body'] body text for this item
 * @param string $args['notes'] notes for the item
 * @param int    $args['status'] status of the item
 * @param int    $args['ptid'] publication type ID for the item
 * @param int    $args['pubdate'] publication date in unix time format (or default now)
 * @param int    $args['authorid'] ID of the author (default is current user)
 * @param string $args['language'] language of the item
 * @param array  $args['cids'] category IDs this item belongs to
 * @return int articles item ID on success, false on failure
 */
function articles_adminapi_create($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check (all the rest is optional, and set to defaults below)
    if (empty($title)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'title', 'admin', 'create', 'Articles');
        throw new BadParameterException(null,$msg);
    }

// Note : we use empty() here because we don't care whether it's set to ''
//        or if it's not set at all - defaults will apply in either case !

    // Default publication type is defined in the admin interface
    if (empty($ptid) || !is_numeric($ptid)) {
        $ptid = xarModGetVar('articles', 'defaultpubtype');
        if (empty($ptid)) {
            $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                        'ptid', 'admin', 'create', 'Articles');
            throw new BadParameterException(null,$msg);
        }
        // for security check below
        $args['ptid'] = $ptid;
    }

    // Default author ID is the current user, or Anonymous (1) otherwise
    if (empty($authorid) || !is_numeric($authorid)) {
        $authorid = xarUserGetVar('uid');
        if (empty($authorid)) {
            $authorid = _XAR_ID_UNREGISTERED;
        }
        // for security check below
        $args['authorid'] = $authorid;
    }

    // Default categories is none
    if (empty($cids) || !is_array($cids) ||
        // catch common mistake of using array('') instead of array()
        (count($cids) > 0 && empty($cids[0])) ) {
        $cids = array();
        // for security check below
        $args['cids'] = $cids;
    }

    // Security check
    if (!xarModAPILoad('articles', 'user')) return;

    $args['mask'] = 'SubmitArticles';
    if (!xarModAPIFunc('articles','user','checksecurity',$args)) {
        $msg = xarML('Not authorized to add #(1) items',
                    'Article');
         return xarResponseForbidden($msg);
    }

    // Default publication date is now
    if (empty($pubdate) || !is_numeric($pubdate)) {
        $pubdate = time();
    }

    // Default status is Submitted (0)
    if (empty($status) || !is_numeric($status)) {
        $status = 0;
    }

    // Default language is current locale
    if (empty($language)) {
        $language = xarMLSGetCurrentLocale();
    }

    // Default summary is empty
    if (empty($summary)) {
        $summary = '';
    }

    // Default notes is empty
    if (empty($notes)) {
        $notes = '';
    }

    // Default body text is empty
    if (empty($body) || !is_string($body)) {
        $body = '';
    }

    //defaults for checkin checkout on create
    if (empty($checkout)) $checkout = 0;
    if (empty($checkouttime)) $checkouttime = 0;
    if (empty($editor)) $editor = $authorid;

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $articlestable = $xartable['articles'];

    // Get next ID in table
    if (empty($aid) || !is_numeric($aid) || $aid == 0) {
        $nextId = $dbconn->GenId($articlestable);
    } else {
        $nextId = $aid;
    }

    // Add item
    $query = "INSERT INTO $articlestable (
              xar_aid,
              xar_title,
              xar_summary,
              xar_body,
              xar_authorid,
              xar_pubdate,
              xar_pubtypeid,
              xar_notes,
              xar_status,
              xar_language,
              xar_checkout,
              xar_checkouttime,
              xar_editor)
            VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?)";
    $bindvars = array($nextId,
                      (string)  $title,
                      (string)  $summary,
                      (string)  $body,
                      (int)     $authorid,
                      (int)     $pubdate,
                      (int)     $ptid,
                      (string)  $notes,
                      (int)     $status,
                      (string)  $language,
                      (int)     $checkout,
                      (int)     $checkouttime,
                      (int)     $editor);
    $result = $dbconn->Execute($query,$bindvars);
    if (!$result) return;

    // Get aid to return
    if (empty($aid) || !is_numeric($aid) || $aid == 0) {
        $aid = $dbconn->PO_Insert_ID($articlestable, 'xar_aid');
    }

    if (empty($cids)) {
        $cids = array();
    }

    // Call create hooks for categories, hitcount etc.
    $args['aid'] = $aid;
// Specify the module, itemtype and itemid so that the right hooks are called
    $args['module'] = 'articles';
    $args['itemtype'] = $ptid;
    $args['itemid'] = $aid;
// TODO: get rid of this
    $args['cids'] = $cids;
    xarModCallHooks('item', 'create', $aid, $args);
    xarLogMessage('ARTICLES: article '.$aid.' created by '.xarSession::getVar('uid'), XARLOG_LEVEL_AUDIT);
    return $aid;
}

?>
