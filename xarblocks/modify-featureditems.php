<?php
/**
 * Featured items
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
 * @author Jonn Beames et al
 */

function articles_featureditemsblock_modify($blockinfo)
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
    if (empty($vars['itemlimit'])) {$vars['itemlimit'] = 0;}
    if (empty($vars['featuredaid'])) {$vars['featuredaid'] = 0;}
    if (empty($vars['alttitle'])) {$vars['alttitle'] = '';}
    if (empty($vars['altsummary'])) {$vars['altsummary'] = '';}
    if (empty($vars['showfeaturedsum'])) {$vars['showfeaturedsum'] = false;}
    if (empty($vars['showfeaturedbod'])) {$vars['showfeaturedbod'] = false;}
    if (empty($vars['moreitems'])) {$vars['moreitems'] = array();}
    if (empty($vars['toptype'])) {$vars['toptype'] = 'date';}
    if (empty($vars['showsummary'])) {$vars['showsummary'] = false;}
    if (empty($vars['linkpubtype'])) {$vars['linkpubtype'] = false;}
    if (!isset($vars['linkcat'])) {$vars['linkcat'] = false;}

    if (!isset($vars['showvalue'])) {
        if ($vars['toptype'] == 'rating') {
            $vars['showvalue'] = false;
        } else {
            $vars['showvalue'] = true;
        }
    }

    $vars['fields'] = array('aid', 'title');

    if (!is_array($vars['status'])) {
        $statusarray = array($vars['status']);
    } else {
        $statusarray = $vars['status'];
    }

    if(!empty($vars['catfilter'])) {
        $cidsarray = array($vars['catfilter']);
    } else {
        $cidsarray = array();
    }

    // Create array based on modifications
    $article_args = array();

    // Only include pubtype if a specific pubtype is selected
    if (!empty($vars['pubtypeid'])) {
        $article_args['ptid'] = $vars['pubtypeid'];
    }

    // If itemlimit is set to 0, then don't pass to getall
    if ($vars['itemlimit'] != 0 ) {
        $article_args['numitems'] = $vars['itemlimit'];
    }

    // Add the rest of the arguments
    $article_args['cids'] = $cidsarray;
    $article_args['enddate'] = time();
    $article_args['status'] = $statusarray;
    $article_args['fields'] = $vars['fields'];
    $article_args['sort'] = $vars['toptype'];

    $vars['filtereditems'] = xarModAPIFunc(
        'articles', 'user', 'getall', $article_args );

    // Check for exceptions
    if (!isset($vars['filtereditems']) )
        return; // throw back

    // Try to keep the additional headlines select list width less than 50 characters
    for ($idx = 0; $idx < count($vars['filtereditems']); $idx++) {
        if (strlen($vars['filtereditems'][$idx]['title']) > 50) {
            $vars['filtereditems'][$idx]['title'] = substr($vars['filtereditems'][$idx]['title'], 0, 47) . '...';
        }
    }

    $vars['pubtypes'] = xarModAPIFunc('articles', 'user', 'getpubtypes');
    $vars['categorylist'] = xarModAPIFunc('categories', 'user', 'getcat');
    $vars['statusoptions'] = array(
        array('id' => '', 'name' => xarML('All Published')),
        array('id' => '3', 'name' => xarML('Frontpage')),
        array('id' => '2', 'name' => xarML('Approved'))
    );

    $vars['sortoptions'] = array(
        array('id' => 'author', 'name' => xarML('Author')),
        array('id' => 'date', 'name' => xarML('Date')),
        array('id' => 'hits', 'name' => xarML('Hit Count')),
        array('id' => 'rating', 'name' => xarML('Rating')),
        array('id' => 'title', 'name' => xarML('Title'))
    );

    //Put together the additional featured articles list
    for($idx=0; $idx < count($vars['filtereditems']); ++$idx) {
        $vars['filtereditems'][$idx]['selected'] = '';
        for($mx=0; $mx < count($vars['moreitems']); ++$mx) {
            if (($vars['moreitems'][$mx]) == ($vars['filtereditems'][$idx]['aid'])) {
                $vars['filtereditems'][$idx]['selected'] = 'selected';
            }
        }
    }
    $vars['morearticles'] = $vars['filtereditems'];
    $vars['blockid'] = $blockinfo['bid'];

    // Return output (template data)
    return $vars;
}

/**
 * update block settings
 */

function articles_featureditemsblock_update($blockinfo)
{
    // Make sure we retrieve the new pubtype from the configuration form.
    // TODO: use xarVarFetch()
    xarVarFetch('pubtypeid', 'id', $vars['pubtypeid'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('catfilter', 'id', $vars['catfilter'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('status', 'int:0:4', $vars['status'], NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('itemlimit', 'int:1', $vars['itemlimit'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('toptype', 'enum:author:date:hits:rating:title', $vars['toptype'], 'date', XARVAR_NOT_REQUIRED);
    xarVarFetch('featuredaid', 'id', $vars['featuredaid'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('alttitle', 'str', $vars['alttitle'], '', XARVAR_NOT_REQUIRED);
    xarVarFetch('altsummary', 'str', $vars['altsummary'], '', XARVAR_NOT_REQUIRED);
    xarVarFetch('moreitems', 'list:id', $vars['moreitems'], NULL, XARVAR_NOT_REQUIRED);
    xarVarFetch('showfeaturedbod', 'checkbox', $vars['showfeaturedbod'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showfeaturedsum', 'checkbox', $vars['showfeaturedsum'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showsummary', 'checkbox', $vars['showsummary'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('showvalue', 'checkbox', $vars['showvalue'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('linkpubtype', 'checkbox', $vars['linkpubtype'], false, XARVAR_NOT_REQUIRED);
    xarVarFetch('linkcat', 'checkbox', $vars['linkcat'], false, XARVAR_NOT_REQUIRED);

    $blockinfo['content'] = $vars;
    return $blockinfo;
}

?>