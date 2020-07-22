<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2006-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * utility function pass individual menu items to the main menu
 *
 * @author the Articles module development team
 * @return array containing the menulinks for the main menu items.
 */
function articles_adminapi_getmenulinks($args)
{
extract($args);
    $menulinks = array();

    if(!isset($ptid)) $ptid='';
// Security Check
    if (xarSecurityCheck('EditArticles',0)) {

        $menulinks[] = Array('url'   => xarModURL('articles', 'admin', 'view'),
                              'title' => xarML('List, and view articles by status and publication type'),
                              'label' => xarML('Manage Articles'),
                              'active' => array('view','modify','delete'),
                              'activelabels' =>array('',xarML('Edit article'),xarML('Delete article'))
                              );
    }

// Security Check
    if (xarSecurityCheck('SubmitArticles',0)) {

        $menulinks[] = Array('url'   => xarModURL('articles','admin','new',array('ptid'=>$ptid)),
                              'title' => xarML('Add a new article'),
                              'label' => xarML('Add Article'),
                              'active' => array('new','clone','create'),
                              'activelabels'=> array('',xarML('Clone article'),xarML('Add'))
                              );
    }


// Security Check
    if (xarSecurityCheck('AdminArticles',0)) {

        $menulinks[] = Array('url'   => xarModURL('articles','admin', 'pubtypes'),
                              'title' => xarML('View and edit publication types'),
                              'label' => xarML('Manage Publication Types'),
                              'active' => array('pubtypes','importpubtype','exportpubtype'),
                              'activelabels' =>array('',xarML('Import pubtype'),xarML('Export pubtype'))

                              );

        $menulinks[] = Array('url'   => xarModURL('articles','admin','stats'),
                              'title' => xarML('Utilities'),
                              'label' => xarML('Utilities'),
                              'active' => array('stats','importpages','importpictures'),
                              'activelabels' =>array('Statistics',xarML('Import pages'),xarML('Import pictures'))
                              );

        $menulinks[] = Array('url'   => xarModURL('articles','admin', 'modifyconfig'),
                              'title' => xarML('Modify the articles module configuration'),
                              'label' => xarML('Modify Config'),
                              'active' => array('modifyconfig')

                              );
    }

    return $menulinks;
}

?>
