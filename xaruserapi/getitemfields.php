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
 * utility function to pass item field definitions to whoever
 *
 * @param $args['itemtype'] item type (optional)
 * @return array Array containing the item field definitions
 */
function articles_userapi_getitemfields($args)
{
    extract($args);

    $itemfields = array();

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    if (!empty($itemtype) && !empty($pubtypes[$itemtype])) {
        $fields = $pubtypes[$itemtype]['config'];
    } else {
        $fields = xarModAPIFunc('articles','user','getpubfields');
    }
    foreach ($fields as $name => $info) {
        if (empty($info['label'])) continue;
        $itemfields[$name] = array('name'  => $name,
                                   'label' => $info['label'],
                                   'type'  => $info['format']);
    }

    return $itemfields;
}

?>
