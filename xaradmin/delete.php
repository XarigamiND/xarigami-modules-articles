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
 * delete item
 */
function articles_admin_delete()
{
    // Get parameters
    if (!xarVarFetch('aid', 'id', $aid)) return;
    if (!xarVarFetch('confirm', 'checkbox', $confirm, false, XARVAR_NOT_REQUIRED)) return;
    if (!xarVarFetch('return_url', 'str:1', $return_url, NULL, XARVAR_NOT_REQUIRED)) {return;}

    // Get article information
    $article = xarModAPIFunc('articles', 'user', 'get',
                             array('aid' => $aid,
                                   'withcids' => true));
    if (!isset($article) || $article == false) {
        $msg = xarML('Unable to find #(1) item #(2)',
                     'Article', xarVarPrepForDisplay($aid));
        throw new IDNotFoundException(null,$msg);
    }
    $ptid = $article['pubtypeid'];

    // Security check
    $input = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    $input['article'] = $article;
    $input['mask'] = 'DeleteArticles';
    if (!xarModAPIFunc('articles','user','checksecurity',$input)) {
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
        $msg = xarML('You have no permission to delete #(1) item #(2)',
                     $pubtypes[$ptid]['descr'], xarVarPrepForDisplay($aid));
        return xarResponseForbidden($msg);
    }

    // Check for confirmation
    if (!$confirm) {
        $data = array();

        // Specify for which item you want confirmation
        $data['aid'] = $aid;
        $data['article'] = $article;
        // Use articles user GUI function (not API) for preview
        if (!xarModLoad('articles','user')) return;
        $data['preview'] = xarModFunc('articles', 'user', 'display',
                                      array('preview' => true, 'article' => $article));

        // Add some other data you'll want to display in the template
        $data['confirmtext'] = xarML('Confirm deleting this article');
        $data['confirmlabel'] = xarML('Confirm');

        // Generate a one-time authorisation code for this operation
        $data['authid'] = xarSecGenAuthKey();

        $data['return_url'] = $return_url;

        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
        $template = $pubtypes[$ptid]['name'];

        // Return the template variables defined in this function
        return xarTplModule('articles', 'admin', 'delete', $data, $template);
    }

    // Confirmation present
    if (!xarSecConfirmAuthKey()) return;

    // Pass to API
    if (!xarModAPIFunc('articles', 'admin', 'delete',
                     array('aid' => $aid,
                           'ptid' => $ptid))) {
        $msg = xarML('Article was not deleted due to a database issue.');
        xarTplSetMessage($msg,'error');
        return;
    } else {
        $msg = xarML('Article was successfully deleted.');
        xarTplSetMessage($msg,'status');

    }
    // Return return_url
    if (!empty($return_url)) {
        xarResponseRedirect($return_url);
        return true;
    }

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

    xarResponseRedirect(xarModURL('articles', 'admin', 'view',
                                  array('ptid' => $ptid)));

    return true;
}

?>
