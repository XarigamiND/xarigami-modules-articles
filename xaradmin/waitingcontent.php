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
 * display waiting content as a hook
 */
function articles_admin_waitingcontent()
{

    // Get publication types
    unset($publinks);
    $publinks = xarModAPIFunc('articles', 'user', 'getpublinks',
                          array('status' => array(0),
                                'typemod' => 'admin'));

     $data['loop'] = $publinks;
     return $data;
}
?>