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
 * utility function to retrieve the list of item types of this module (if any)
 *
 * @return array Array containing the item types and their description
 */
function articles_userapi_getitemtypes($args)
{
    $itemtypes = array();

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    foreach ($pubtypes as $id => $pubtype) {
        $itemtypes[$id] = array('label' => xarVarPrepForDisplay($pubtype['descr']),
                                'title' => xarVarPrepForDisplay(xarML('Display #(1)',$pubtype['descr'])),
                                'url'   => xarModURL('articles','user','view',array('ptid' => $id))
                               );
    }
    return $itemtypes;
}

?>
