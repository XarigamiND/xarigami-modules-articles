<?php
/**
 * Articles module
 *
 * @package modules
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2010 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * Update an article's links
 * @param id $args['aid'] ID of the item (mandatory argument)
 * @param int $args['ptid'] publication type ID (mandatory argument)
 * @param int $args['newptid'] newpublication type ID (mandatory argument)
 * @return bool true on success, false on failure
 */

//get the hooklist array with table data
function articles_adminapi_updatelinks($args)
{
    // Get arguments from argument array
    extract($args);

    // Argument check
    if (empty($aid) || !is_numeric($aid) || empty($ptid) || !is_numeric($ptid) || empty($newptid) || !is_numeric($newptid)) {
        $msg = xarML('Invalid #(1) for #(2) function #(3)() in module #(4)',
                    'requiredparam', 'admin', 'updatelinks',
                    'Articles'); //jojo - fix this later
         throw new BadParameterException(null,$msg);;
    }
    $article = xarModAPIFunc('articles','user','get',array('aid'=>$aid));
    if (!$article || !is_array($article)) return;

    $article['mask'] = 'ModerateArticles';
    if (!xarModAPIFunc('articles','user','checksecurity',$article)) {
        $msg = xarML('Not authorized to update #(1) items',
                    'Article');
        return xarResponseForbidden($msg);
    }
    //get all modules that are hooked and have some type of create/update api for items
    //these are the only ones that have linkage tables (i think!!)
    $hookedmodules = xarModAPIFunc('modules','admin','gethooks',array('modName'=>'articles','hookObject'=>'item'));
    $updatelist = array();
    include 'modules/articles/xaradminapi/hooklist.php';
    if (!isset($hookapilist) && !is_array($hookapilist)) {
        $msg = xarML('Hook listing is missing in articles_adminapi_updatelinks');
        throw new FileNotFoundException(null,$msg);

    }
    //get the table data for any hooked modules
    foreach ($hookedmodules  as $modname=>$typedata) {
        if (isset($hookapilist[$modname])) {
            //now check for itemtypes that are relevant
            foreach($typedata as $itype =>$ishooked) {
                //must be all itemtypes (0), or new itemtype in list - we do not care if no new itemtype but has old itemtype
                if ($itype ==0 || $itype == $newptid) {
                    $updatelist[$modname] = $hookapilist[$modname];
                }
            }
        }
    }

    //now we need to update each of the tables
    //and replace the itemtype with the new itemtype
   // Get database setup
    $dbconn = xarDBGetConn();
    $siteprefix = xarDBGetSystemTablePrefix();

    foreach ($updatelist as $modname => $tabledata) {
        $bindvars = array();

        $modidentifier = xarModGetIDFromName('articles');
        $hookregid = $tabledata['hookregid'];
        if (!empty($tabledata['hookmod'])) {
           //we need to insert module name not modregid
           $modidentifier = 'articles';
           $hookregid = $tabledata['hookmod'];
        }
        $actiontable = $siteprefix.'_'.$tabledata['hooktable'];
        if ($tabledata['action'] == 'delete') {
            //Delete the item
            $query = "DELETE FROM $actiontable
                      WHERE $hookregid = ?
                       AND  $tabledata[hookitemtype] = ?
                       AND $tabledata[hookitemid] = ?";
            $bindvars = array($modidentifier,$ptid,$aid);
        } elseif ($tabledata['action'] == 'update') {
            // Update the item
            $query = "UPDATE $actiontable
                      SET $tabledata[hookitemtype] = ?
                      WHERE $hookregid = ?
                       AND  $tabledata[hookitemtype] = ?
                       AND $tabledata[hookitemid] = ?";
            $bindvars = array($newptid,$modidentifier,$ptid,$aid);
        }

        $result = $dbconn->Execute($query,$bindvars);
        if (!$result) {
        //return;
           xarLogMessage('ARTICLES CHANGE PUBTYPE: NO result for table '.$actiontable);
        }
    }

    return true;
}

?>
