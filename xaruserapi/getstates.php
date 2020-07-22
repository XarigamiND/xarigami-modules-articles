<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2009 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * return an array with coded states
 * @return array
 */
function articles_userapi_getstates()
{
    // Simplistic getstates function
    // Obviously needs to be smarter along with the other state functions
    $statuslist = array(0 => xarML('Submitted'),
                        1 => xarML('Rejected'),
                        2 => xarML('Approved'),
                        3 => xarML('Frontpage'),
                        4 => xarML('Draft'),
                        5 => xarML('Archived')
                        )
                        ;
    return $statuslist;
}
?>
