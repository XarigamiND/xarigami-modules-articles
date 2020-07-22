<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub, jojodee
 */
/**
 * get a specific article by aid, or by a combination of other fields
 *
 * @param id     $args['aid'] id of article to get, or
 * @param id     $args['pubtypeid'] pubtype id of article to get, and optional
 * @param string $args['title'] title of article to get, and optional
 * @param string $args['summary'] summary of article to get, and optional
 * @param string $args['body'] body of article to get, and optional
 * @param int    $args['authorid'] id of the author of article to get, and optional
 * @param        $args['pubdate'] pubdate of article to get, and optional
 * @param string $args['notes'] notes of article to get, and optional
 * @param int    $args['status'] status of article to get, and optional
 * @param string $args['language'] language of article to get
 * @param int    $args['editor'] ID of the actual editor
 * @param int    $args['checkout'] checkout status of the item, 0-in, 1-out, 2-archived
 * @param $args['checkouttime'] checkout time as unix time format
 * @param bool   $args['withcids'] (optional) if we want the cids too (default false)
 * @param array  $args['fields'] array with all the fields to return per article
 *                        Default list is : 'aid','title','summary','authorid',
 *                        'pubdate','pubtypeid','notes','status','body'
 *                        Optional fields : 'cids','author','counter','rating','dynamicdata'
 * @param array  $args['extra'] array with extra fields to return per article (in addition
 *                       to the default list). So you can EITHER specify *all* the
 *                       fields you want with 'fields', OR take all the default
 *                       ones and add some optional fields with 'extra'
 * @param id     $args['ptid'] same as 'pubtypeid'
 * @return array article array, or false on failure
 */
function articles_userapi_get($args)
{
    // Get arguments from argument array
    extract($args);
    if (!isset($aid) && isset($itemid)) {
        $aid = $itemid;
    }
    // Argument check
    if (isset($aid) && (!is_numeric($aid) || $aid < 1)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'article ID', 'user', 'get',
                    'Articles');
         throw new BadParameterException(null,$msg);
    }

    // allow ptid instead of pubtypeid, like getall and other api's (if both specified, ptid wins)
    if (!empty($ptid))
        $pubtypeid = $ptid;

    // bypass this function, call getall instead?
    if (isset($fields) || isset($extra)) {
        if (!empty($aid))
            $args['aids'] = array($aid);
        if (!empty($pubtypeid))
            $args['ptid'] = $pubtypeid;
        $wheres = array();
        if (!empty($title))
            $wheres[] = "title eq '$title'";
        if (!empty($summary))
            $wheres[] = "summary eq '$summary'";
        if (!empty($body))
            $wheres[] = "body eq '$body'";
        if (!empty($notes))
            $wheres[] = "notes eq '$notes'";
       if (!empty($editor))
            $wheres[] = "editor eq '$editor'";
       if (!empty($checkout))
            $wheres[] = "checkout eq '$checkout'";
        if (!empty($withcids))
            $fields[] = "cids";
        foreach ($wheres as $w) {
            if (isset($where))
                $where .= " || $w";
            else
                $where = $w;
        }
        if (isset($where))
            $args['where'] = $where;
        $arts = xarModApiFunc('articles','user','getall', $args );
        if (!empty($arts))
            return current($arts);
        else
            return false;
    }

// TODO: put all this in dynamic data and retrieve everything via there (including hooked stuff)

    $bindvars = array();
    if (!empty($aid)) {
        $where = "WHERE xar_aid = ?";
        $bindvars[] = $aid;
    } else {
        $wherelist = array();
        $fieldlist = array('title','summary','authorid','pubdate','pubtypeid',
                           'notes','status','body','language,checkout,checkouttime,editor');
        foreach ($fieldlist as $field) {
            if (isset($$field)) {
                $wherelist[] = "xar_$field = ?";
                $bindvars[] = $$field;
            }
        }
        if (count($wherelist) > 0) {
            $where = "WHERE " . join(' AND ',$wherelist);
        } else {
            $where = '';
        }
    }

    // Get database setup
    $dbconn = xarDBGetConn();
    $xartable = xarDBGetTables();
    $articlestable = $xartable['articles'];

    // Get item
    $query = "SELECT xar_aid,
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
                   xar_editor
            FROM $articlestable
            $where";
    if (!empty($aid)) {
        $result = $dbconn->Execute($query,$bindvars);
    } else {
        $result = $dbconn->SelectLimit($query,1,0,$bindvars);
    }
    if (!$result) return NULL;

    if ($result->EOF) {
        return NULL;
    }

    list($aid, $title, $summary, $body, $authorid, $pubdate, $pubtypeid, $notes,
         $status, $language,$checkout,$checkouttime,$editor) = $result->fields;

    $article = array('aid' => $aid,
                     'title' => $title,
                     'summary' => $summary,
                     'body' => $body,
                     'authorid' => $authorid,
                     'pubdate' => $pubdate,
                     'pubtypeid' => $pubtypeid,
                     'notes' => $notes,
                     'status' => $status,
                     'language' => $language,
                     'checkout' =>$checkout,
                     'checkouttime'=>$checkouttime,
                     'editor' =>$editor);

    if (!empty($withcids)) {
        $article['cids'] = array();
        if (!xarModAPILoad('categories', 'user')) return;

        $articlecids = xarModAPIFunc('categories', 'user', 'getlinks',
                                    array('iids' => Array($aid),
                                          'itemtype' => $pubtypeid,
                                          'modid' =>
                                               xarModGetIDFromName('articles'),
                                          'reverse' => 0
                                         )
                                   );
        if (is_array($articlecids) && count($articlecids) > 0) {
            $article['cids'] = array_keys($articlecids);
        }
    }
   // Get the article settings for this publication type
    if (empty($pubtypeid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings'));
    } else {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$pubtypeid));
    }
    $settings['checkpubdate']=isset( $settings['checkpubdate'])? $settings['checkpubdate']:0;
    // Security check
    if (isset($article['cids']) && count($article['cids']) > 0) {
   // TODO: do we want all-or-nothing access here, or is one access enough ?
        foreach ($article['cids'] as $cid) {
            if (($settings['checkpubdate'] ==1) && ($article['pubdate']> time())) { //don't display article unless a person has edit level privs
                if (!xarSecurityCheck('EditArticles',0,'Article',"$pubtypeid:$cid:$authorid:$aid")) return false;
            } elseif (xarSecurityCheck('ViewArticles',0,'Article',"$pubtypeid:$cid:$authorid:$aid") &&
                      !xarSecurityCheck('ReadArticles',0,'Article',"$pubtypeid:$cid:$authorid:$aid")
                     ) {
               //we have view article access only
               return $aid;
            } elseif (!xarSecurityCheck('ReadArticles',0,'Article',"$pubtypeid:$cid:$authorid:$aid")) {
               //we don't have read access or view
               return false;
            }
        // TODO: combine with ViewCategoryLink check when we can combine module-specific
        // security checks with "parent" security checks transparently ?
            if (!xarSecurityCheck('ReadCategories',0,'Category',"All:$cid")) return false;
        }
    } else {
         if (($settings['checkpubdate'] ==1) && ($article['pubdate']> time())) { //don't display article unless a person has edit level privs
                if (!xarSecurityCheck('EditArticles',0,'Article',"$pubtypeid:All:$authorid:$aid")) return false;
        }elseif (!xarSecurityCheck('ReadArticles',0,'Article',"$pubtypeid:All:$authorid:$aid")) {
            return false;
        }    }

    return $article;
}

?>