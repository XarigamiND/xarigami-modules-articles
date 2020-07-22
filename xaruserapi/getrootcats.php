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
 * get an array of root categories with links
 *
 * @param int $args['ptid'] publication type ID
 * @param $args['all'] boolean if we need to return all root categories when
 *                     ptid is empty (default false)
 * @return array
 * @TODO specify return format
 */
function articles_userapi_getrootcats($args)
{
    extract($args);

    if (empty($ptid) || !is_numeric($ptid)) {
        $ptid = null;
    }

    // see which root categories we need to handle
    $rootcats = array();
    if (!empty($ptid)) {
        $cidstring = xarModGetVar('articles','mastercids.'.$ptid);
        if (!empty($cidstring)) {
            $rootcats = explode(';',$cidstring);
        }
    } elseif (empty($all)) {
        $cidstring = xarModGetVar('articles','mastercids');
        if (!empty($cidstring)) {
            $rootcats = explode(';',$cidstring);
        }
    } else {
        // Get publication types
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
        // get base categories for all publication types here
        $publist = array_keys($pubtypes);
        // add the defaults too, in case we have other base categories there
        $publist[] = '';
        // build the list of root categories for all required publication types
        $catlist = array();
        foreach ($publist as $pubid) {
            if (empty($pubid)) {
                $cidstring = xarModGetVar('articles','mastercids');
            } else {
                $cidstring = xarModGetVar('articles','mastercids.'.$pubid);
            }
            if (!empty($cidstring)) {
                $rootcats = explode(';',$cidstring);
                foreach ($rootcats as $cid) {
                    $catlist[$cid] = 1;
                }
            }
        }
        if (count($catlist) > 0) {
            $rootcats = array_keys($catlist);
        }
    }

    if (count($rootcats) < 1) {
        return array();
    }

    if (!xarModAPILoad('categories', 'user')) return;

    $isfirst = 1;
    $catlinks = array();
    $catlist = xarModAPIFunc('categories',
                            'user',
                            'getcatinfo',
                            array('cids' => $rootcats));
    if (empty($catlist)) {
        return $catlinks;
    }
    // preserve order of root categories if possible
    foreach ($rootcats as $cid) {
        if (!isset($catlist[$cid])) continue;
        $info = $catlist[$cid];
        $item = array();
        $item['catid'] = $info['cid'];
        $item['cattitle'] = xarVarPrepForDisplay($info['name']);
        $item['catlink'] = xarModURL('articles','user','view',
                                    array('ptid' => $ptid,
                                          'catid' => $info['cid']));
        if ($isfirst) {
            $item['catjoin'] = '';
            $isfirst = 0;
        } else {
            $item['catjoin'] = ' | ';
        }
        $item['catleft'] = $info['left'];
        $item['catright'] = $info['right'];
        $catlinks[] = $item;
    }
    return $catlinks;
}

?>
