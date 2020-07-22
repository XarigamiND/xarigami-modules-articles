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
 * view statistics
 */
function articles_admin_stats($args = array())
{
    if (!xarSecurityCheck('AdminArticles')) return xarResponseForbidden();
    if (!xarVarFetch('group','isset',$group,array(),XARVAR_NOT_REQUIRED)) return;
    extract($args);

    if (!empty($group)) {
        $newgroup = array();
        foreach ($group as $field) {
            if (empty($field)) continue;
            $newgroup[] = $field;
        }
        $group = $newgroup;
    }
    if (empty($group)) {
        $group = array('pubtypeid', 'status', 'authorid');
    }

    $data = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
    $data['group'] = $group;
    $data['stats'] = xarModAPIFunc('articles','admin','getstats',
                                   array('group' => $group));

    $data['pubtypes'] = xarModAPIFunc('articles','user','getpubtypes');
    if (count($data['pubtypes']) <= 1 ) $data['pubtypes'] = current($data['pubtypes']);
    $data['statuslist'] = xarModAPIFunc('articles','user','getstates');
    $data['fields'] = array('pubtypeid'     => xarML('Publication Type'),
                            'status'        => xarML('Status'),
                            'authorid'      => xarML('Author'),
                            'pubdate_year'  => xarML('Publication Year'),
                            'pubdate_month' => xarML('Publication Month'),
                            'pubdate_day'   => xarML('Publication Day'),
                            'language'      => xarML('Language'));

    return $data;
}

?>
