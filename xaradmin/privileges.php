<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * Manage definition of instances for privileges (unfinished)
 *
 * @return array for template
 */
function articles_admin_privileges($args)
{
    extract($args);

    // fixed params
    if (!xarVarFetch('ptid',         'isset', $ptid,         NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('cid',          'isset', $cid,          NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('cids',         'isset', $cids,         NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('uid',          'isset', $uid,          NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('author',       'isset', $author,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('aid',          'isset', $aid,          NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('apply',        'isset', $apply,        NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extpid',       'isset', $extpid,       NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extname',      'isset', $extname,      NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extrealm',     'isset', $extrealm,     NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extmodule',    'isset', $extmodule,    NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extcomponent', 'isset', $extcomponent, NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extinstance',  'isset', $extinstance,  NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('extlevel',     'isset', $extlevel,     NULL, XARVAR_DONT_SET)) {return;}
    if (!xarVarFetch('pparentid',    'isset', $pparentid,    NULL, XARVAR_DONT_SET)) {return;}

    if (!empty($extinstance)) {
        $parts = explode(':',$extinstance);
        if (count($parts) > 0 && !empty($parts[0])) $ptid = $parts[0];
        if (count($parts) > 1 && !empty($parts[1])) $cid = $parts[1];
        if (count($parts) > 2 && !empty($parts[2])) $uid = $parts[2];
        if (count($parts) > 3 && !empty($parts[3])) $aid = $parts[3];
    }

    if (empty($ptid) || $ptid == 'All' || !is_numeric($ptid)) {
        $ptid = 0;
        if (!xarSecurityCheck('AdminArticles')) return xarResponseForbidden();
    } else {
        if (!xarSecurityCheck('AdminArticles',1,'Article',"$ptid:All:All:All")) return xarResponseForbidden();
    }

// TODO: do something with cid for security check

// TODO: figure out how to handle more than 1 category in instances
    if (empty($cid) || $cid == 'All' || !is_numeric($cid)) {
        $cid = 0;
    }
    if (empty($cid) && isset($cids) && is_array($cids)) {
        foreach ($cids as $catid) {
            if (!empty($catid)) {
                $cid = $catid;
                // bail out for now
                break;
            }
        }
    }

    if (empty($aid) || $aid == 'All' || !is_numeric($aid)) {
        $aid = 0;
    }
    $title = '';
    if (!empty($aid)) {
        $article = xarModAPIFunc('articles','user','get',
                                 array('aid'      => $aid,
                                       'withcids' => true));
        if (empty($article)) {
            $aid = 0;
        } else {
            // override whatever other params we might have here
            $ptid = $article['pubtypeid'];
        // TODO: review when we can handle multiple categories and/or subtrees in privilege instances
            if (!empty($article['cids']) && count($article['cids']) == 1) {
                // if we don't have a category, or if we have one but this article doesn't belong to it
                if (empty($cid) || !in_array($cid, $article['cids'])) {
                    // we'll take that category
                    $cid = $article['cids'][0];
                }
            } else {
                // we'll take no categories
                $cid = 0;
            }
            $uid = $article['authorid'];
            $title = $article['title'];
        }
    }

// TODO: figure out how to handle groups of users and/or the current user (later)
    if (strtolower($uid) == 'myself') {
        $uid = 'Myself';
        $author = 'Myself';
    } elseif (empty($uid) || $uid == 'All' || (!is_numeric($uid) && (strtolower($uid) != 'myself'))) {
        $uid = 0;
        if (!empty($author)) {
            $user = xarModAPIFunc('roles', 'user', 'get',
                                  array('name' => $author));
            if (!empty($user) && !empty($user['uid'])) {
                if (strtolower($author) == 'myself') $uid = 'Myself';
                else $uid = $user['uid'];
            } else {
                $author = '';
            }
        }
    } else {
        $author = '';
/*
        $user = xarModAPIFunc('roles', 'user', 'get',
                              array('uid' => $uid));
        if (!empty($user) && !empty($user['name'])) {
            $author = $user['name'];
        }
*/
    }

    // define the new instance
    $newinstance = array();
    $newinstance[] = empty($ptid) ? 'All' : $ptid;
    $newinstance[] = empty($cid) ? 'All' : $cid;
    $newinstance[] = empty($uid) ? 'All' : $uid;
    $newinstance[] = empty($aid) ? 'All' : $aid;

    if (!empty($apply)) {
        // create/update the privilege
        $pid = xarReturnPrivilege($extpid,$extname,$extrealm,$extmodule,$extcomponent,$newinstance,$extlevel,$pparentid);
        if (empty($pid)) {
            return; // throw back
        }

        // redirect to the privilege
        xarResponseRedirect(xarModURL('privileges', 'admin', 'modifyprivilege',
                                      array('pid' => $pid)));
        return true;
    }

    // get the list of current authors
    $authorlist =  xarModAPIFunc('articles','user','getauthors',
                                 array('ptid' => $ptid,
                                       'cids' => empty($cid) ? array() : array($cid)));
    if (!empty($author) && isset($authorlist[$uid])) {
        $author = '';
    }

    if (empty($aid)) {
        $numitems = xarModAPIFunc('articles','user','countitems',
                                  array('ptid' => $ptid,
                                        'cids' => empty($cid) ? array() : array($cid),
                                        'authorid' => $uid));
    } else {
        $numitems = 1;
    }

    $data = array(
                  'ptid'         => $ptid,
                  'cid'          => $cid,
                  'uid'          => $uid,
                  'author'       => xarVarPrepForDisplay($author),
                  'authorlist'   => $authorlist,
                  'aid'          => $aid,
                  'title'        => xarVarPrepForDisplay($title),
                  'numitems'     => $numitems,
                  'extpid'       => $extpid,
                  'extname'      => $extname,
                  'extrealm'     => $extrealm,
                  'extmodule'    => $extmodule,
                  'extcomponent' => $extcomponent,
                  'extlevel'     => $extlevel,
                  'extinstance'  => xarVarPrepForDisplay(join(':',$newinstance)),
                  'pparentid'    => $pparentid
                 );

    // Get publication types
    $data['pubtypes'] = xarModAPIFunc('articles','user','getpubtypes');

    $catlist = array();
    if (!empty($ptid)) {
        $cidstring = xarModGetVar('articles', 'mastercids.'.$ptid);
        if (!empty($cidstring)) {
            $rootcats = explode (';', $cidstring);
            foreach ($rootcats as $catid) {
                $catlist[$catid] = 1;
            }
        }
        if (empty($data['pubtypes'][$ptid]['config']['authorid']['label'])) {
            $data['showauthor'] = 0;
        } else {
            $data['showauthor'] = 1;
        }
    } else {
        foreach (array_keys($data['pubtypes']) as $pubid) {
            $cidstring = xarModGetVar('articles', 'mastercids.'.$pubid);
            if (!empty($cidstring)) {
                $rootcats = explode (';', $cidstring);
                foreach ($rootcats as $catid) {
                    $catlist[$catid] = 1;
                }
            }
        }
        $data['showauthor'] = 1;
    }

    $seencid = array();
    if (!empty($cid)) {
        $seencid[$cid] = 1;
/*
        $data['catinfo'] = xarModAPIFunc('categories',
                                         'user',
                                         'getcatinfo',
                                         array('cid' => $cid));
*/
    }

    $data['cats'] = array();
    $data['cats'][] = xarModAPIFunc('categories', 'visual', 'makeselect',
                                    array('cids' => array_keys($catlist),
                                          'return_itself' => true,
                                          'values' => &$seencid,
                                          'multiple' => 0,
                                          'size' => 'auto',
                                          'javascript' => 'onchange="submit()"'));

    $data['refreshlabel'] = xarML('Refresh Selection');
    $data['applylabel'] = xarML('Finish and Apply to Privilege');

    $data['privlevels'] = array(
                            array('id'=>'0', 'name'=>xarML('No Access')),
                            array('id'=>'100', 'name'=>xarML('Overview')),
                            array('id'=>'200', 'name'=>xarML('Read access')),
                            array('id'=>'300', 'name'=>xarML('Submit(Comment)')),
                            array('id'=>'400', 'name'=>xarML('Edit access')),
                            array('id'=>'500', 'name'=>xarML('Moderate access')),
                            array('id'=>'600', 'name'=>xarML('Add access')),
                            array('id'=>'700', 'name'=>xarML('Delete access')),
                            array('id'=>'800', 'name'=>xarML('Admin access')),
                                );

    return $data;
}

?>
