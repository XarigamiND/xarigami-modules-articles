<?php
/**
 * Modify Random Block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 *
 */
/**
 * modify block settings
 * @author Roger Keays
 */

function articles_randomblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['pubtypeid'])) {$vars['pubtypeid'] = '';}
    if (empty($vars['catfilter'])) {$vars['catfilter'] = '';}
    if (empty($vars['status'])) {$vars['status'] = array(3, 2);}
    if (empty($vars['language'])) {$vars['language'] = '';}
    if (empty($vars['numitems'])) {$vars['numitems'] = 5;}
    if (empty($vars['alttitle'])) {$vars['alttitle'] = '';}
    if (empty($vars['altsummary'])) {$vars['altsummary'] = '';}
    if (empty($vars['showtitle'])) {$vars['showtitle'] = false;}
    if (empty($vars['showsummary'])) {$vars['showsummary'] = false;}
    if (empty($vars['showpubdate'])) {$vars['showpubdate'] = false;}
    if (empty($vars['showauthor'])) {$vars['showauthor'] = false;}
    if (empty($vars['showsubmit'])) {$vars['showsubmit'] = false;}
    if (empty($vars['showdynamic'])) {$vars['showdynamic'] = false;}
    if (empty($vars['linkpubtype'])) {$vars['linkpubtype'] = false;}
    $vars['pubtypes'] = xarModAPIFunc('articles', 'user', 'getpubtypes');
    $vars['categorylist'] = xarModAPIFunc('categories', 'user', 'getcat');
    $vars['statusoptions'] = array(
        array('id' => '', 'name' => xarML('All Published')),
        array('id' => '3', 'name' => xarML('Frontpage')),
        array('id' => '2', 'name' => xarML('Approved'))
    );
    if(!empty($vars['catfilter'])) {
        $cidsarray = array($vars['catfilter']);
    } else {
        $cidsarray = array();
    }

    $vars['locales'] = xarMLSListSiteLocales();
    asort($vars['locales']);

    $vars['blockid'] = $blockinfo['bid'];
    // Return output (template data)
    return $vars;
}

/**
 * update block settings
 */

function articles_randomblock_update($blockinfo)
{
    // Make sure we retrieve the new pubtype from the configuration form.
    // TODO: use xarVarFetch()
    xarVarFetch('pubtypeid', 'id', $vars['pubtypeid'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('catfilter', 'id', $vars['catfilter'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('status', 'int:0:4', $vars['status'], NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('language', 'str', $vars['language'], '', XARVAR_NOT_REQUIRED);
    xarVarFetch('alttitle', 'str', $vars['alttitle'], '', XARVAR_NOT_REQUIRED);
    xarVarFetch('altsummary', 'str', $vars['altsummary'], '', XARVAR_NOT_REQUIRED);
    if (!xarVarFetch('numitems', 'int:1:100', $vars['numitems'], 5, XARVAR_NOT_REQUIRED)) {return;}
    xarVarFetch('showtitle', 'checkbox', $vars['showtitle'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showsummary', 'checkbox', $vars['showsummary'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showpubdate', 'checkbox', $vars['showpubdate'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showauthor', 'checkbox', $vars['showauthor'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showsubmit', 'checkbox', $vars['showsubmit'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showdynamic', 'checkbox', $vars['showdynamic'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('linkpubtype', 'checkbox', $vars['linkpubtype'], false, XARVAR_NOT_REQUIRED);

    $vars['blockid'] = $blockinfo['bid'];
    $blockinfo['content'] = $vars;
    return $blockinfo;
}

?>
