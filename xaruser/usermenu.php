<?php
/**
 * Articles module
 *
 * @package modules
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage articles
 * @copyright (C) 2011-2012 2skies.com
 * @link http://xarigami.com
 * @author Jo Dalle Nogare <icedlava@2skies.com>
 */
/**
 * The user menu that is used in roles/account
 */
function articles_user_usermenu($args)
{
    extract($args);

    // Security Check
    if (xarSecurityCheck('ReadArticles',0)) {

    if(!xarVarFetch('phase','str', $phase, 'menu', XARVAR_NOT_REQUIRED)) {return;}
    xarTplSetPageTitle(xarModGetVar('themes', 'SiteName').' :: '.
                       xarVarPrepForDisplay(xarML('Contrails'))
                       .' :: '.xarVarPrepForDisplay(xarML('Your Account Preferences')));

    if (!in_array($phase ,array('menu','form','update'))) {
        $phase = 'menu';
    }
    $articlelist = array();
    $myarticles = array();
    switch(strtolower($phase)) {
        case 'menu':

            $icon = xarTplGetImage('articles.gif', 'articles');
            $data = xarTplModule('articles','user', 'usermenu_icon',
                array('icon' => $icon,
                      'usermenu_form_url' => xarModURL('articles', 'user', 'usermenu', array('phase' => 'form'))
                     ));
            break;

        case 'form':
             $currentuser = xarUserGetVar('uid');
             //get project options
             $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
             $myarticles = xarModAPIFunc('articles','user','getall', array('authorid'=>$currentuser));
             $articlestatus = xarModAPIFunc('articles','user','getstates');
             
             foreach ($myarticles as $k => $v) {
                $articlelist[$v['pubtypeid']][] = array('aid'=>$v['aid'],'title'=>$v['title'],'status'=>$v['status'], 'pubdate'=>$v['pubdate'],'link'=>xarModURL('articles','user','display',array('aid'=>$v['aid'],'ptid'=>$v['pubtypeid'])));
             }
            $authid = xarSecGenAuthKey();
            $data = xarTplModule('articles','user', 'usermenu_form', array('authid'   => $authid,
                                                                           'articlelist' => $articlelist,
                                                                           'pubtypes'=>$pubtypes,
                                                                           'articlestatus' => $articlestatus));
            break;

        case 'update':


            if(!xarVarFetch('settings','array', $settings, array(), XARVAR_NOT_REQUIRED)) {return;}

            if (count($settings) <= 0) {
                $msg = xarML('Settings passed from form are empty!');
                 throw new BadParameterException(null,$msg);
            }

            // Confirm authorisation code.
            if (!xarSecConfirmAuthKey())
                return;


            // Redirect
            xarResponseRedirect(xarModURL('roles', 'user', 'account'));

            break;
        }

    }
    return $data;
}

?>