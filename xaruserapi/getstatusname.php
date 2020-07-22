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
 * Function to decode status
 */
function articles_userapi_getstatusname( $args )
{
    extract($args);
    $states = xarModAPIFunc('articles','user','getstates');
    if (isset($status) && isset($states[$status])) {
        return $states[$status];
    } else {
        return xarML('Unknown');
    }
}
?>
