<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 */
/**
 * Original Author of file: Jim McDonald
 * Purpose of file: Articles Block
 */

/**
 * initialise block
 */
function articles_glossaryblock_init()
{
    return array(
        'paramname' => 'glossaryterm',
        'ptid' => 0,
        'cid' => 0,
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // don't share across pages
        'usershared' => 1, // share across group members
        'cacheexpire' => null
    );
}

/**
 * get information on block
 */
function articles_glossaryblock_info()
{
    // Values
    return array(
        'text_type' => 'Glossary',
        'module' => 'articles',
        'text_type_long' => 'Show a glossary summary in a side block.',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display block
 */
function articles_glossaryblock_display($blockinfo)
{
    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Get the glossary parameter.
    // TODO: make parameter name configurable.
    xarVarFetch($vars['paramname'], 'str', $glossaryterm, NULL, XARVAR_NOT_REQUIRED);

    if (empty($glossaryterm)) {
        // No glossary parameter found.
        return;
    }

    $articlecriteria = array();
    $articlecriteria['title'] = $glossaryterm;

    if (!empty($vars['ptid'])) {
        $articlecriteria['ptid'] = $vars['ptid'];
    }

    if (!empty($vars['cid'])) {
        $articlecriteria['withcids'] = true;
    }

    // Attempt to find an article with this title and optional category/pubtype.
    $article = xarModAPIfunc('articles', 'user', 'get', $articlecriteria);

    if (!empty($vars['cid']) && array_search($vars['cid'], $article['cids']) === NULL) {
        // Category not assigned to article.
        unset($article);
    }

    // Matching glossary item found.
    if (!empty($article)) {
        $vars['definition'] = $article['summary'];
        $vars['term'] = $glossaryterm;
        $vars['detailurl'] = xarModURL(
            'articles', 'user', 'display',
            array('aid' => $article['aid'], 'ptid' => $article['pubtypeid'])
        );
        $vars['detailavailable'] = !empty($article['body']);
    }

    // TODO: who uses blockid? Can this be done centrally?
    $vars['blockid'] = $blockinfo['bid'];

    // Replace the string '{term}' in the title with the term.
    // Note: the prep display prevents injected tags being rendered.
    // The title of a block does not go through any further tag stripping
    // because it is normally under admin control (the admin may wish to
    // add working tags to the title).
    $blockinfo['title'] = str_replace('{term}', xarVarPrepForDisplay($glossaryterm), $blockinfo['title']);

    $blockinfo['content'] = $vars;

    return $blockinfo;
}


/**
 * modify block settings
 */
function articles_glossaryblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Pub type drop-down list values.
    $vars['pubtypes'] = xarModAPIFunc('articles', 'user', 'getpubtypes');

    // Categories drop-down list values.
    $vars['categorylist'] = xarModAPIFunc('categories', 'user', 'getcat');

    // Defaults.
    if (empty($vars['ptid'])) {
        $vars['ptid'] = 0;
    }
    if (empty($vars['cid'])) {
        $vars['cid'] = 0;
    }
    if (empty($vars['paramname'])) {
        $vars['paramname'] = 'glossaryterm';
    }

    $vars['bid'] = $blockinfo['bid'];

    // Return output
    return $vars;
}

/**
 * update block settings
 */
function articles_glossaryblock_update($blockinfo)
{
    $vars = array();

    xarVarFetch('paramname', 'str:1:20', $vars['paramname'], 'glossaryterm', XARVAR_NOT_REQUIRED);
    xarVarFetch('ptid', 'int:0:', $vars['ptid'], 0, XARVAR_NOT_REQUIRED);
    xarVarFetch('cid', 'int:0:', $vars['cid'], 0, XARVAR_NOT_REQUIRED);

    $blockinfo['content'] = $vars;
    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function articles_glossaryblock_help()
{
    return (
        'Use {term} in the block title as a placeholder for the glossary term.'
        . ' Glossary terms will match an article title.'
        . ' In the block admin, choose the optional pub type and category that will define the glossary terms.'
        . ' The glossary term displayed will be the article summary.'
        . ' A link to the full article will be provided if the body of the article contains text.'
    );
}

?>
