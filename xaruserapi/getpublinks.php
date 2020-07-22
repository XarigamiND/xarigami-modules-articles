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
 */
/**
 * get array of links and counts for publication types
 * @param $args['ptid'] optional publication type ID for which you *don't*
 *                      want a link (e.g. for the current publication type)
 * @param $args['all'] optional flag (1) if you want to include publication
 *                     types that don't have articles too (default 0)
 * @param $args['status'] array of requested status(es) for the articles
 * @param $args['func'] optional function to be called with the link
 * @param $args['count'] true (default) means counting the number of articles
 * @return array of array('pubtitle' => descr,
 *                        'pubid' => id,
 *                        'publink' => link,
 *                        'pubcount' => count)
 */
function articles_userapi_getpublinks($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (!isset($ptid)) {
        $ptid = null;
    }
    if (!isset($all)) {
        $all = 0;
    }
    if (!isset($func)) {
        $func = 'view';
    }
    if (!isset($typemod)) {
        $typemod = 'user';
    }
    if (!isset($count)) {
        $count = true;
    }
    if (!$count) {
        $all = 1;
    }

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    if ($count) {
        if (isset($status)) {
            $pubcount = xarModAPIFunc('articles','user','getpubcount',
                                     array('status' => $status));
        } else {
            $pubcount = xarModAPIFunc('articles','user','getpubcount');
        }
    }

    $publinks = array();
    $isfirst = 1;
    foreach ($pubtypes as $id => $pubtype) {
        if (!xarSecurityCheck('ViewArticles',0,'Article',$id.':All:All:All')) {
            continue;
        }
        if ($all || (isset($pubcount[$id]) && $pubcount[$id] > 0)) {
             $item['pubtitle'] = $pubtype['descr'];
             $item['pubid'] = $id;
             if (isset($ptid) && $ptid == $id) {
                 $item['publink'] = '';
             } else {
                 $item['publink'] = xarModURL('articles',$typemod,$func,array('ptid' => $id));
             }
             if ($count && isset($pubcount[$id])) {
                 $item['pubcount'] = $pubcount[$id];
             } else {
                 $item['pubcount'] = 0;
             }
             if ($isfirst) {
                 $isfirst = 0;
                 $item['pubjoin'] = '';
             } else {
                 $item['pubjoin'] = ' - ';
             }
             $publinks[] = $item;
        }
    }

    return $publinks;
}

?>
