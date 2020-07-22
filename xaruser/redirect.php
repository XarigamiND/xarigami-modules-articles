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
 * redirect to a site based on some URL field of the item
 */
function articles_user_redirect($args)
{
    // Get parameters from user
    if(!xarVarFetch('aid', 'id', $aid, NULL, XARVAR_NOT_REQUIRED)) {return;}

    // Override if needed from argument array
    extract($args);

    if (!isset($aid) || !is_numeric($aid) || $aid < 1) {
        return xarML('Invalid article ID');
    }

    // Load API
    if (!xarModAPILoad('articles', 'user')) return;

    // Get article
    $article = xarModAPIFunc('articles',
                            'user',
                            'get',
                            array('aid' => $aid));

    if (!is_array($article)) {
        $msg = xarML('Failed to retrieve article in #(3)_#(1)_#(2).php', 'user', 'get', 'articles');
        return xarResponseNotFound($msg);
    }

    $ptid = $article['pubtypeid'];

    // Get publication types
    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

// TODO: improve this e.g. when multiple URL fields are present
    // Find an URL field based on the pubtype configuration
    foreach ($pubtypes[$ptid]['config'] as $field => $value) {
        if (empty($value['label'])) {
            continue;
        }
        if ($value['format'] == 'url' && !empty($article[$field]) && $article[$field] != 'http://') {
// TODO: add some verifications here !
            xarResponseRedirect($article[$field]);
            return true;
        } elseif ($value['format'] == 'urltitle' && !empty($article[$field]) && substr($article[$field],0,2) == 'a:') {
            $array = unserialize($article[$field]);
            if (!empty($array['link']) && $array['link'] != 'http://') {
                xarResponseRedirect($array['link']);
                return true;
            }
        }
    }

    return xarML('Unable to find valid redirect field');
}

?>
