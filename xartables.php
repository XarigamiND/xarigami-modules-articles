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
 * Manage the tables in articles
 *
 * @return array with the tables used in articles
 */
function articles_xartables()
{
    // Initialise table array
    $xartable = array();
    // Get Site Prefix
    $sitePrefix = xarDBGetSiteTablePrefix();
    // Name for articles database entities
    $articles = $sitePrefix . '_articles';

    // Table name
    $xartable['articles'] = $articles;

    // Column names
    $xartable['articles_column'] = array('aid'      => $articles . '.xar_aid',
                                        'title'    => $articles . '.xar_title',
                                        'summary'  => $articles . '.xar_summary',
                                        'authorid' => $articles . '.xar_authorid',
                                        'pubdate'  => $articles . '.xar_pubdate',
                                        'pubtypeid' => $articles . '.xar_pubtypeid',
                                        'pages'    => $articles . '.xar_pages',
                                        'body'     => $articles . '.xar_body',
                                        'notes'    => $articles . '.xar_notes',
                                        'status'   => $articles . '.xar_status',
                                        'language' => $articles . '.xar_language');

    // Name for publication types table
    $publicationtypes = $sitePrefix . '_publication_types';

    // Table name
    $xartable['publication_types'] = $publicationtypes;

    // Column names
    $xartable['publication_types_column'] = array(
                'pubtypeid'      => $publicationtypes . '.xar_pubtypeid',
                'pubtypename'    => $publicationtypes . '.xar_pubtypename',
                'pubtypedescr'   => $publicationtypes . '.xar_pubtypedescr',
                'pubtypeconfig'   => $publicationtypes . '.xar_pubtypeconfig');

    // Return table information
    return $xartable;
}

?>
