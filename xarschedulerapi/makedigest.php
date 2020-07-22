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
 * make a digest of new articles (executed by the scheduler module)
 * e.g. for sending out to users, for use by the pubsub module, ...
 *
 * @author mikespub
 * @access public
 */
function articles_schedulerapi_makedigest($args)
{

// TODO: get some configuration info about which pubtypes, categories, statuses, ... are
//       concerned, if there are any limits to the number of articles to put in the digest,
//       etc. Then retrieve the relevant articles, create the digest and do whatever :-)

/* Simple example for HTML digests :

    $ptid = 1; // News Articles
    $catid = null; // all categories

    $now = time();
    // see when we last created a digest
    $lastdigest = xarModGetVar('articles','lastdigest');
    if (empty($lastdigest)) {
        // if we haven't created a digest yet, let's skip all past articles
        $lastdigest = $now;
    }

    // count the number of new items since the last digest
    $count = xarModAPIFunc('articles','user','countitems',
                           array('ptid' => $ptid, // some publication type(s)
                                 'catid' => $catid, // some categories
                                 'status' => array(2,3), // approved or frontpage
                                 'startdate' => $lastdigest)); // since the last digest
    // specify some minimum number of items before we create a digest
    if ($count < 1) {
        // Note: don't update the last digest time here - we're waiting for enough articles
        // we're done here
        return true;
    }

    // create some HTML digest
    $htmldigest = xarModFunc('articles','user','view',
                             array('ptid' => $ptid,
                                   'catid' => $catid,
                                   'startdate' => $lastdigest,
                                   // override some defaults here if you want
                                   'numitems' => 20,
                                   'showcategories' => 1,
                                   'showprevnext' => 0,
                                   'showcomments' => 0,
                                   'showhitcounts' => 0,
                                   'showratings' => 0,
                                   'showmonthview' => 0,
                                   'showmap' => 0,
                                   'showpublinks' => 0));

    // update the last digest time
    xarModSetVar('articles','lastdigest',$now);

    // ... do something with this digest ...

    $lastdate = xarLocaleFormatDate("%a, %d %b %Y %H:%M:%S %Z", $lastdigest);
    $subject = xarML('New articles since #(1)', $lastdate);

    $textdigest = strip_tags($htmldigest);
    $textdigest = preg_replace('/[ \t]+/',' ',$textdigest);
    $textdigest = preg_replace("/\s*\r?\n(\s*\r?\n)+/","\n\n",$textdigest);

    // get a list of users from somewhere
    $userlist = array(3); // typical Admin account here

    // send the digest to all of them
    foreach ($userlist as $uid) {
        $name = xarUserGetVar('name',$uid);
        $email = xarUserGetVar('email',$uid);
        if (!xarModAPIFunc('mail', 'admin', 'sendhtmlmail',
                           array('info' => $email,
                                 'name' => $name,
                                 'subject' => $subject,
                                 'message' => $textdigest,
                                 'htmlmessage' => $htmldigest))) {
            // oops
        }
    }
*/

    return true;
}

?>
