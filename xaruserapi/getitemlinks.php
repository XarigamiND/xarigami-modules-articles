<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
*/
/**
 * utility function to pass individual item links to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @param $args['authorid'] int authorid (optional)
 * @param $args['itemids'] array of item ids to get
 * @param $args['field'] field to return as label in the list (default 'title')
 * @return array Array containing the itemlink(s) for the item(s).
 */
function articles_userapi_getitemlinks($args)
{
    extract($args);

    $itemlinks = array();
    if (empty($itemtype)) {
        $itemtype = NULL;
    }
    // get cids for security check in getall
    $fields = array('aid','title','pubtypeid','cids');

    // make sure we have the title field we want here
    if (empty($field)) {
        $field = 'title';
    } 
    if (empty($fields)) $fields = array();
    if (!in_array($field, $fields)) {
        $fields[] = $field;
    }
    if (empty($sort)) {
        $sort = NULL;
    }

    if (xarSecurityCheck('AdminArticles',0)) {
        // get all articles for admins (not editors)
        $status = NULL;
    } else {
// CHECKME: make sure we don't need other statuses somewhere
        // get approved and frontpage articles only
        $status = array(2, 3);
    }
    
    $getallargs = array('fields' => $fields,
                        'status' => $status,
                        'sort' => $sort
                        );
    if (isset($itemtype)) {
        $getallargs['ptid'] = $itemtype;
    }
    if (isset($itemids)) {
        $getallargs['aids'] = $itemids;
    }
    if (isset($args['authorid']) && is_numeric($args['authorid'])) {
        $getallargs['authorid'] = (int) $args['authorid'];
    }
    

    $articles = xarModAPIFunc('articles','user','getall', $getallargs);
    
    if (!isset($articles) || !is_array($articles) || count($articles) == 0) {
       return $itemlinks;
    }

    // if we didn't have a list of itemids, return all the articles we found
    if (empty($itemids)) {
        foreach ($articles as $article) {
            $itemid = $article['aid'];
            if (!isset($article[$field])) continue;
            $itemlinks[$itemid] = array('url'   => xarModURL('articles', 'user', 'display',
                                                             array('ptid' => $article['pubtypeid'],
                                                                   'aid' => $article['aid'])),
                                        'title' => xarML('Display Article'),
                                        'label' => xarVarPrepForDisplay($article[$field]));
        }
        return $itemlinks;
    }

    // if we had a list of itemids, return only those articles
    $itemid2key = array();
    foreach ($articles as $key => $article) {
        $itemid2key[$article['aid']] = $key;
    }
    foreach ($itemids as $itemid) {
        if (!isset($itemid2key[$itemid])) continue;
        $article = $articles[$itemid2key[$itemid]];
        if (!isset($article[$field])) continue;
        $itemlinks[$itemid] = array('url'   => xarModURL('articles', 'user', 'display',
                                                                 array('ptid' => $article['pubtypeid'],
                                                                       'aid' => $article['aid'])),
                                            'title' => xarML('Display Article'),
                                            'label' => xarVarPrepForDisplay($article[$field]));
    }
    return $itemlinks;
}

?>
