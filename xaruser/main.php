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
 * the main user function
 */
function articles_user_main($args)
{
    return xarModFunc('articles','user','view',$args);
// TODO: make this configurable someday ?
    // redirect to default view (with news articles)
    //xarResponseRedirect(xarModURL('articles', 'user', 'view'));
    //return;
}

?>
