<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2010-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * Archive articles based on days from publishing 
 *
 * @access public
 */
function articles_schedulerapi_archivearticles($args)
{
    /*
        1. Check autoarchiving is on
        2. Check for pubtypes to process (could be set at none : -1)
        3. We only process articles that are approved or Frontpage - let us retrieve only those - quicker
        4. Only archive  articles that are X days from publication date 
    */
    //check archiving is on
    $usearchiving = xarModGetVar('articles','usearchiving');
    //get pubtypes to archive
    $pubtypestoprocess = xarModGetVar('articles','autoarchive');
    $pubtypestoprocess = unserialize($pubtypestoprocess);
    $doprocess = (isset($usearchiving) && ($usearchiving == true)) ? true : false;
    $archiveage = xarModGetVar('articles','archiveage');
    $archiveage = isset($archiveage)?$archiveage:0;
    $counter = count($pubtypestoprocess);
    if ($counter ==1 && current($pubtypestoprocess) <0) {
        $doprocess = false;    
    }
    
    if ($archiveage == 0)  {
        $doprocess = false;
    }
    if ($doprocess == false) return; // nothing to process

    //now we have an array of pubtypes
    //get the articles
    $dbconn = xarDBGetConn();
    $xartables = xarDBGetTables();

    $now = time();
    $archivesecs = (int)$archiveage * 24 * 60 * 60;
    $pubdate = $now - $archivesecs;
    // and still have a published or frontpage status  2 or 3 
    $oldstatus = array(2,3);
    // will receive the new status 5 which is archived
    $newstatus = 5;
    $articlestable = $xartables['articles'] ;

    //let's loop for each pubtype that we want archiving
    // articles of publication type 1 (= news or whatever)
    foreach ($pubtypestoprocess as $pubtypeid) {
        // that were published at least $achiveage days ago
       
         xarLogMessage("SCHEDULER TABLE:". $articlestable);
        $query = "UPDATE  $articlestable 
                    SET xar_status =  ? 
                  WHERE xar_pubtypeid = ? 
                    AND xar_pubdate < ?
                    AND (xar_status = 2 OR xar_status = 3) ";
                    
        $bindvars = array((int)$newstatus,(int)$pubtypeid,(int)$pubdate);
        $result = $dbconn->Execute($query, $bindvars);
        if (!$result) return;
    }

    return true;
}

?>