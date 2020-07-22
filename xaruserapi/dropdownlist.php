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
 * get an array of articles (id => field) for use in dropdown lists
 *
 * E.g. to specify the parent of an article for parent-child relationships,
 * add a dynamic data field of type Dropdown List with the validation rule
 * xarModAPIFunc('articles','user','dropdownlist',array('ptid' => 1))
 *
 * Note : for additional optional parameters, see the getall() function
 *
 * @param $args['ptid'] publication type ID (for news, sections, reviews, ...)
 * @param $args['field'] field to use in the dropdown list (default 'title')
 * @param $args['showunpub'] (= 1) allow non-admin to see unpublished articles
 * @returns array
 * @return array of articles, or false on failure
 */
function articles_userapi_dropdownlist($args)
{
    if (!isset($args['ptid'])) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'publication type', 'user', 'dropdownlist',
                    'Articles');
         throw new BadParameterException(null,$msg);
    }
    // Add default arguments
    if (!isset($args['field'])) {
        $args['field'] = 'title';
    }
    if (!isset($args['fields'])) {
        $args['fields'] = array('aid', $args['field'], 'cids');
    }
    if (!isset($args['sort'])) {
        $args['sort'] = $args['field'];
    }
    // Don't let users see unpublished articles, unless $showunpub is 1
    if ( xarSecurityCheck('AdminArticles',0) ||
        (isset($args['showunpub']) && ($args['showunpub']=='1')) ) {
        $isadmin = true;
    } else {
        $isadmin = false;
    }
    if (!isset($args['status']) || !$isadmin) {
        $args['status'] = array(2, 3);
    }
    if (!isset($args['enddate']) || !$isadmin) {
        $args['enddate'] = time();
    }

    // Get the articles
    $articles = xarModAPIFunc('articles','user','getall',$args);
    if (!$articles) return;

    // Fill in the dropdown list
    $list = array();
    //$list[0] = '';
    $field = $args['field'];
    foreach ($articles as $article) {
        if (!isset($article[$field])) continue;
    // TODO: support other formatting options here depending on the field type ?
        $list[$article['aid']] = xarVarPrepForDisplay($article[$field]);
    }
    return $list;
}

?>
