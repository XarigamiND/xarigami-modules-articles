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
 * the main administration function
 *
 * It currently redirects to the admin-view function
 * @return bool true on success
 */
function articles_admin_main()
{

// Security Check
    if (!xarSecurityCheck('EditArticles')) return xarResponseForbidden();
       $welcome = '';
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
        // Return the template variables defined in this function
        //return array('welcome' => $welcome);
        xarResponseRedirect(xarModURL('articles', 'admin', 'view'));
    // success
    return true;

}

?>
