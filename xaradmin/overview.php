<?php
/**
 * Articles module Overview
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
 * Overview displays standard Overview page
 */
function articles_admin_overview()
{
   /* Security Check */
    if (!xarSecurityCheck('ModerateArticles',0)) return xarResponseForbidden();

    $data=array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    /* if there is a separate overview function return data to it
     * else just call the main function that usually displays the overview
     */
    return $data;
    
    //return xarTplModule('articles', 'admin', 'main', $data,'main');
}

?>