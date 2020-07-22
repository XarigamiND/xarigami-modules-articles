<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2009 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
*/
/**
 * utility function pass individual menu items to the main menu
 *
 * @return array Array containing the menulinks for the main menu items.
 */
function articles_userapi_getmenulinks()
{
    $menulinks = array();

    // Security Check
    if (!xarSecurityCheck('ViewArticles',0)) {
        return $menulinks;
    }

    if(!xarVarFetch('ptid',     'isset', $ptid,      NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype,  NULL, XARVAR_DONT_SET)) {return;}
    if (empty($ptid)) {
        if (!empty($itemtype)) {
            $ptid = $itemtype;
        } else {
            $ptid = null;
        }
    }

    $publinks = xarModAPIFunc('articles','user','getpublinks',
                              //array('status' => array(3,2), 'ptid' => $ptid));
                              // we show all links here
                              array('status' => array(3,2)));

    $menulinks[] = Array('url'   => xarModURL('articles', 'user', 'view'),
                         'title' => xarML('Featured Articles'),
                         'label' => xarML('Front Page'),
                         'active' => array('view'),
                         'activelabels'=>array('View'),
                         'itemtype'=>0
                         );

    foreach ($publinks as $pubitem) {
        $menulinks[] = Array('url'   => $pubitem['publink'],
                             'title' => xarML('Display #(1)',$pubitem['pubtitle']),
                             'label' => $pubitem['pubtitle'],
                             'active'=> array('view','display'),
                             'activelabels' => array(xarML('Browse'), xarML('Display')),
                             'itemtype'=>$pubitem['pubid'],

                             );
        if (isset($ptid) && $pubitem['pubid'] == $ptid) {
            if (xarSecurityCheck('SubmitArticles',0,'Article',$ptid.':All:All:All')) {
                $menulinks[] = Array('url'   => xarModURL('articles', 'admin', 'new',
                                                          array('ptid' => $ptid)),
                                     'title' => xarML('Submit #(1)',$pubitem['pubtitle']),
                                     'label' => '&nbsp;' . xarML('Submit Now'),
                                     'active'=>array('new'),
                                     'activelabels'=>array(xarML('Create new')),
                                     'itemtype'=>$ptid);
            }

            $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
            if (!empty($settings['showmonthview'])) {
                $menulinks[] = Array('url'   => xarModURL('articles', 'user', 'monthview',
                                                          array('ptid' => $ptid)),
                                     'title' => xarML('View #(1) by Month',$pubitem['pubtitle']),
                                     'label' => xarML('View by Month'),
                                      'active'=>array('monthview'),
                                     'itemtype'=>$ptid);
            }
        }
    }

        $menulinks[] = Array('url'   => xarModURL('articles','user','viewmap',
                                                  array('ptid' => $ptid)),
                             'title' => xarML('Displays a map of all published content'),
                             'label' => xarML('Article Map'),
                              'active'=>array('viewmap'),
                             'itemtype'=>$ptid);

    return $menulinks;
}

?>
