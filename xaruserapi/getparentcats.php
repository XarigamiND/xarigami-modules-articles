<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * get an array of parent categories with links and counts
 *
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['ptid'] publication type ID
 * @param $args['cids'] array of category IDs
 * @param $args['showcids'] true (default) means keeping a link for the cids
 * @param $args['sort'] currently used only to override default start view
 * @param $args['count'] true (default) means counting the number of articles
 * @return array
// TODO: specify return format
 */
function articles_userapi_getparentcats($args)
{
    extract($args);

    if (!isset($cids) || !is_array($cids) || count($cids) == 0) {
        return array();
    }
    if (empty($ptid)) {
        $ptid = null;
    }
    if (!isset($status)) {
        // frontpage or approved
        $status = array(3,2);
    }
    if (!isset($showcids)) {
        $showcids = true;
    }
    if (!isset($sort)) {
        $sort = null;
    }
    if (!isset($count)) {
        $count = true;
    }

    // get the counts for all child categories
    if ($count) {
        $pubcatcount = xarModAPIFunc('articles',
                                    'user',
                                    'getpubcatcount',
                                    array('status' => $status,
                                          'cids' => $cids,
                                          'ptid' => $ptid,
                                          'reverse' => 1));
    }

    if (!empty($ptid)) {
        $curptid = $ptid;
    } else {
        $curptid = 'total';
    }

    $trails = array();
    foreach ($cids as $cid) {
        $trailitem = array();
        $trailitem['cid'] = $cid;
// TODO : retrieve all parents in 1 call ?
        $trail = xarModAPIFunc('categories',
                              'user',
                              'getcat',
                              array('cid' => $cid,
                                    'return_itself' => true,
                                    'getparents' => true));

        if ($count && isset($pubcatcount[$cid][$curptid])) {
            $trailitem['cidcount'] = $pubcatcount[$cid][$curptid];
        } else {
            $trailitem['cidcount'] = '';
        }

        $trailitem['parentlinks'] = array();
        $item = array();
        $item['plink'] = xarModURL('articles','user','view',
                                  array('ptid' => $ptid,
                                        'sort' => $sort));
        $item['ptitle'] = xarML('All');
        $item['pjoin'] = ' &gt; ';
        $trailitem['parentlinks'][] = $item;
// TODO: make sure permissions are taken into account here !
        foreach ($trail as $info) {
            $item['plink'] = xarModURL('articles',
                                      'user',
                                      'view',
                                       array('ptid' => $ptid,
                                             'catid' => $info['cid']));
            $item['ptitle'] = xarVarPrepForDisplay($info['name']);
            if ($info['cid'] == $cid) {
// TODO: test for neighbourhood
                $trailitem['info'] = $info;

                $item['pjoin'] = '';
                // remove link again in this case :-)
                if (!$showcids) {
                    $item['plink'] = '';
                }
// TODO: improve the case where we have several icons :)
                if (!empty($info['image'])) {
                    $trailitem['icon'] = array('image' => $info['image'],
                                               'text' => $item['ptitle'],
                                               'link' =>
                          xarModURL('articles','user','view',
                                   array('ptid' => $ptid,
                                         'catid' => $info['cid'])));
                }
            } else {
                $item['pjoin'] = ' &gt; ';
            }
            $trailitem['parentlinks'][] = $item;
        }
        $trails[] = $trailitem;
    }
    return $trails;
}

?>
