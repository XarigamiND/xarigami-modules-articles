<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2010 2skies.com
 * @subpackage Articles Module
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * A list of hooks with item api functions that update tables
 * @jojo - unmanageable longterm- should have standardized hooks table columns and names or function in hook
 * @return bool true on success, false on failure
 */

//yikes what an array of non-standardization - some have module name instead of id as well ..
//columns include prefixes, table names do not
//we update only tables where we want to retain data or can retain data
//others will update when we update article
//we need to DELETE other info

  $hookapilist = array(
                'categories'    => array('action'=>'update', 'module'=>'categories','hooktable'=>'categories_linkage',  'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_iid'),
                'changelog'     => array('action'=>'update', 'module'=>'changelog', 'hooktable'=>'changelog',           'hookmod'=>'','hookregid'=>'xar_moduleid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'comments'      => array('action'=>'update', 'module'=>'comments',  'hooktable'=>'comments',            'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_objectid'),
                'hitcount'      => array('action'=>'update', 'module'=>'hitcount',  'hooktable'=>'hitcount',            'hookmod'=>'','hookregid'=>'xar_moduleid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'keywords'      => array('action'=>'update', 'module'=>'keywords',  'hooktable'=>'keywords',            'hookmod'=>'','hookregid'=>'xar_moduleid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'owner'         => array('action'=>'update', 'module'=>'owner',     'hooktable'=>'owner',               'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'polls'         => array('action'=>'update', 'module'=>'polls',     'hooktable'=>'polls',               'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'pubsub'        => array('action'=>'update', 'module'=>'pubsub',    'hooktable'=>'pubsub_events',       'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_cid'),
                'ratings'       => array('action'=>'update', 'module'=>'ratings',   'hooktable'=>'ratings',             'hookmod'=>'','hookregid'=>'xar_moduleid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid'),
                'security'      => array('action'=>'update', 'module'=>'security',  'hooktable'=>'security_roles',      'hookmod'=>'','hookregid'=>'modid','hookitemtype'=>'itemtype','hookitemid'=>'itemid'),
                'uploads'       => array('action'=>'update', 'module'=>'uploads',   'hooktable'=>'file_assoc',          'hookmod'=>'','hookregid'=>'xar_modid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_objectid'),
                'xlink'         => array('action'=>'update', 'module'=>'xlink','    hooktable'=>'xlink',                'hookmod'=>'','hookregid'=>'xar_moduleid','hookitemtype'=>'xar_itemtype','hookitemid'=>'xar_itemid')
  
    );
?>
