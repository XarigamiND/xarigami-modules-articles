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
 */
/**
 * change the status of articles based on some criteria (executed by the scheduler module)
 * e.g. to expire articles from the frontpage or whatever
 *
 * @author mikespub
 * @access public
 */
function articles_schedulerapi_changestatus($args)
{

// TODO: get some configuration info about which pubtypes, categories, statuses, ... are
//       concerned, if there is any minimum number of articles to leave in a certain status,
//       etc. Then retrieve the relevant articles and change their status accordingly :-)

// Note: for more advanced/customised status handling, you should define a workflow

/*
Here you could e.g. update the status of all articles of a certain
publication type, that have the frontpage status, and that have
been published more than x time ago. The fastest way is to
do that directly via SQL :
*/

/*
    $dbconn = xarDBGetConn();
    $xartables = xarDBGetTables();

    // articles of publication type 1 (= news or whatever)
    $pubtypeid = 1;
    // that were published at least 7 days ago
    $pubdate = time() - 7 * 24 * 60 * 60;
    // and still have the status 3 (= frontpage)
    $oldstatus = 3;
    // will receive the new status 2 (= approved)
    $newstatus = 2;

    $query = 'UPDATE ' . $xartables['articles'] . '
                SET xar_status = ' . $newstatus . '
              WHERE xar_pubtypeid = ' . $pubtypeid . '
                AND xar_pubdate < ' . $pubdate . '
                AND xar_status = ' . $oldstatus;

    $result = $dbconn->Execute($query);
    if (!$result) return;
*/

/*
If you put this in changestatus.php and schedule that every day (or whatever)
those articles will "expire" automatically.

[Note : instead of the SQL, you can also use the getall() function to retrieve
the articles you want, and the update() function shown above to update
them individually.]

Some extension might be to "expire" only articles that were not published
by a certain author (e.g. yourself), or that are (not) in a certain category,
or whatever, but you get the idea :-)
*/

    return true;
}

?>
