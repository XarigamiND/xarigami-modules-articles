<?php
/**
 * Random Block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @copyright (C) 2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author Xarigami Team 
 * @author mikespub
 */
/**
 * initialise block
 * @author Roger Keays
 */
function articles_randomblock_init()
{
    // Default values to initialize the block.
    return array(
        'pubtypeid'     => 0,
        'catfilter'     => 0,
        'status'        => '3,2',
        'language'      => '',
        'numitems'      => 1,
        'alttitle'      => '',
        'altsummary'    => '',
        'showtitle'     => true,
        'showsummary'   => true,
        'showpubdate'   => false,
        'showsubmit'    => false,
        'showdynamic'   => false,
        'nocache'       => 0, // Cache this block
        'pageshared'    => 1, // Share across all pages
        'usershared'    => 1, // Share between group members
        'cacheexpire'   => null, // Default cache expiration time
        'linkpubtype'   => false
    );
}

/**
 * get information on block
 */
function articles_randomblock_info()
{
    // Return details about this block.
    return array(
        'text_type' => 'Random article',
        'module' => 'articles',
        'text_type_long' => 'Show a single random article',
        'allow_multiple' => true,
        'form_content' => false, // Deprecated
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display block
 */
function articles_randomblock_display($blockinfo)
{
    // Get variables from block content.
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // frontpage or approved status
    if (empty($vars['status'])) {
            $statusarray = array(2,3);
    } elseif (!is_array($vars['status'])) {
            $statusarray = preg_split('/,/', $vars['status']);
    } else {
            $statusarray = $vars['status'];
    }

    if (empty($vars['language'])) {
        $lang = null;
    } elseif ($vars['language'] == 'current') {
        $lang = xarMLSGetCurrentLocale();
    } else {
        $lang = $vars['language'];
    }

    // get cids for security check in getall
    $fields = array('aid', 'title', 'body', 'notes', 'pubtypeid', 'cids', 'authorid');

    if (!empty($vars['showpubdate'])) {
        array_push($fields, 'pubdate');
    }
    if (!empty($vars['showsummary'])) {
        array_push($fields, 'summary');
    }
    if (!empty($vars['showauthor'])) {
        array_push($fields, 'author');
    }
    if (!empty($vars['alttitle'])) {
        $blockinfo['title'] = $vars['alttitle'];
    }
    if (empty($vars['pubtypeid'])) {
        $vars['pubtypeid'] = 0;
    }

    if (!empty($vars['catfilter'])) {
        // use admin defined category
        $cidsarray = array($vars['catfilter']);
        $cid = $vars['catfilter'];
    } else {
        $cid = 0;
        $cidsarray = array();
    }

    // check if dynamicdata is hooked for all pubtypes or the current one (= defaults to 0 anyway here)
    if (!empty($vars['showdynamic']) && xarModIsHooked('dynamicdata', 'articles', $vars['pubtypeid'])) {
        array_push($fields, 'dynamicdata');
    }

    if (empty($vars['numitems'])) $vars['numitems'] = 1;

    $articles = xarModAPIFunc('articles','user','getrandom',
                              array('ptid'     => $vars['pubtypeid'],
                                    'cids'     => $cidsarray,
                                    'andcids'  => false,
                                    'status'   => $statusarray,
                                    'language' => $lang,
                                    'numitems' => $vars['numitems'],
                                    'fields'   => $fields,
                                    'unique'   => true));

    if (!isset($articles) || !is_array($articles) || count($articles) == 0) {
        return;
    } else {
        foreach (array_keys($articles) as $key) {
            // for template compatibility :-(
            if (!empty($articles[$key]['author']) && !empty($vars['showauthor'])) {
                $articles[$key]['authorname'] = $articles[$key]['author'];
            }
            $vars['items'][] = $articles[$key];
        }
    }

    // Pass details back for rendering.
    if (count($vars['items']) > 0) {
        $blockinfo['content'] = $vars;
        return $blockinfo;
    }

    // Nothing to render.
    return;
}
/**
 * built-in block help/information system.
 */
function articles_randomblock_help()
{
    // No information yet.
    return '';
}

?>
