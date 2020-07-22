<?php
/**
 * Xarigami Articles Initialization
 *
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2006-2013 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
*/

$modversion['name']         = 'articles';
$modversion['directory']    = 'articles';
$modversion['id']           = '151';
$modversion['version']      = '1.7.5';
$modversion['displayname']  = 'Articles';
$modversion['description']  = 'Creation and management of articles of different publication types';
$modversion['credits']      = 'xardocs/credits.txt';
$modversion['help']         = 'xardocs/help.txt';
$modversion['changelog']    = 'xardocs/changelog.txt';
$modversion['license']      = 'xardocs/license.txt';
$modversion['official']     = 1;
$modversion['author']       = 'mikespub,jojodee';
$modversion['contact']      = 'http://xarigami.com/';
$modversion['homepage']     = 'http://xarigami.com/project/xarigami_articles';
$modversion['admin']        = 1;
$modversion['user']         = 1;
$modversion['class']        = 'Complete';
$modversion['category']     = 'Content';
// this module depends on the categories module
$modversion['dependencyinfo']   = array(
                                    0 => array(
                                            'name' => 'core',
                                            'version_ge' => '1.4.0'
                                         ),
                                    147 => array(
                                            'name' => 'categories',
                                            'version_ge' => '2.5.2' // changes in visual select.
                                        )
                                );
if (false) { //Load and translate once
    xarML('Articles');
    xarML('Creation and management of articles of different publication types');
}
?>