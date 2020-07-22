<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * update item from articles_admin_modify
 */
function articles_admin_updatestatus()
{
    // Get parameters
    if(!xarVarFetch('aids',   'isset', $aids,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('status', 'isset', $status,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',  'isset', $catid,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptid',   'isset', $ptid,    NULL, XARVAR_DONT_SET)) {return;}

    // Confirm authorisation code
    if (!xarSecConfirmAuthKey()) return;

    if (!isset($aids) || count($aids) == 0) {
        $msg = xarML('No articles were selected for change - please check articles for actioning.');
        xarSessionSetVar('statusmsg', $msg);
        $args['ptid'] = isset($ptid)?$ptid:0;
        $args['catid'] = isset($catid)?$catid:'';

        $returnurl = xarModURL('articles','admin','view', $args);
        xarResponseRedirect($returnurl);
        return;
    }
    $states = xarModAPIFunc('articles','user','getstates');
    if (!isset($status) ||
        !is_numeric($status) ||
        $status < -3 ||
        ( ($status != -1 && !isset($states[$status]))&&
          ($status != -2 && !isset($states[$status]))&&
          ($status != -3 && !isset($states[$status]))
        )) {

        $msg = xarML('Invalid status');
        throw new BadParameterException(null,$msg);
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    if (!empty($ptid)) {
        $descr = $pubtypes[$ptid]['descr'];
    } else {
        $descr = xarML('Articles');
        $ptid = null;
    }

    // We need to tell some hooks that we are coming from the update status screen
    // and not from update in actual article screen.  Right now, the keywords vanish
    // into thin air.  Bug 1960 and 3161
    xarVarSetCached('Hooks.all','noupdate',1);

    foreach ($aids as $aid => $val) {
        if ($val != 1) {
            continue;
        }
        // Get original article information
        $article = xarModAPIFunc('articles', 'user', 'get',
                                 array('aid' => $aid,
                                       'withcids' => 1));
        if (!isset($article) || !is_array($article)) {
            $msg = xarML('Unable to find #(1) item #(2)',
                         $descr, xarVarPrepForDisplay($aid));
            throw new IDNotFoundException(null,$msg);
        }
        $article['ptid'] = $article['pubtypeid'];
        // Security check
        $input = array();
        $input['article'] = $article;
        if ($status < 0) {
            $input['mask'] = 'DeleteArticles';
        } else {
            $input['mask'] = 'ModerateArticles';
        }
        if (!xarModAPIFunc('articles','user','checksecurity',$input)) {
            $msg = xarML('You have no permission to change article status for #(1) item #(2)',
                         $descr, xarVarPrepForDisplay($aid));
            return xarResponseForbidden($msg);
        }
        $publishtest = array(2,3);
        if ($status == -1) {
            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'delete', $article)) {
                return; // throw back
            }
        } elseif ( $status == -2) {
             $article['checkout'] = 0;
             $article['checkouttime'] = 0;//being used as a flag which as 0 means 'leave time value as is - the original modify time'
            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'checkout', $article)) {
                return; // throw back
            }
        } elseif ( $status == -3) { //coming from somewhere other than the modify article function
             $article['checkout'] = 1;
             $article['checkouttime'] = 1;//being used as a flag which as 0 means 'leave time value as is - the original modify time'
             $article['editor'] = (int)xarUserGetVar('uid');
            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'checkout', $article)) {
                return; // throw back
            }
        } elseif (($status == 5) && !in_array($article['status'],$publishtest)) {
        //we cannot archive documents unless they are approved or frontpage, skip to next article
           //do nothing
        } else {
            // Update the status now
            $article['status'] = $status;

            // Pass to API
            if (!xarModAPIFunc('articles', 'admin', 'update', $article)) {
                return; // throw back
            }
        }
    }
    unset($article);

    // Return to the original admin view
    $lastview = xarSessionGetVar('Articles.LastView');
    if (isset($lastview)) {
        $lastviewarray = unserialize($lastview);
        if (!empty($lastviewarray['ptid']) && $lastviewarray['ptid'] == $ptid) {
            extract($lastviewarray);
            xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                          array('ptid' => $ptid,
                                                'catid' => $catid,
                                                'status' => $status,
                                                'startnum' => $startnum)));
            return true;
        }
    }

    if (empty($catid)) {
        $catid = null;
    }
    xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                  array('ptid' => $ptid, 'catid' => $catid)));

    return true;
}

?>
