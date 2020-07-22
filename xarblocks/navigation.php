<?php
/**
 * Articles module Categories Navigation Block
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
 * Original Author of file: Jim McDonald
 */

/**
 * initialise block
 */
function articles_navigationblock_init()
{
    return array(
        'layout' => 1,
        'showcatcount' => 0,
        'showchildren' => 0,
        'showempty' => false,
        'startmodule' => '',
        'dynamictitle' => false,
        'nocache' => 1, // don't cache by default
        'pageshared' => 0, // don't share across pages here
        'usershared' => 1, // share across group members
        'cacheexpire' => null
    );
}

/**
 * get information on block
 */
function articles_navigationblock_info()
{
    // Values
    return array(
        'text_type' => 'Navigation',
        'module' => 'articles',
        'text_type_long' => 'Show navigation for immediate children items',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display block
 */
function articles_navigationblock_display($blockinfo)
{
    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    if (!empty($vars) && is_array($vars)) {
        extract($vars);
    }

    // Get requested layout
    if (empty($layout)) {
        $layout = 1; // default tree here
    }

    if (!empty($startmodule)) {
        // static behaviour
        list($module,$itemtype,$rootcid) = explode('.',$startmodule);
        if (empty($rootcid)) {
            $rootcids = null;
        } elseif (strpos($rootcid,' ')) {
            $rootcids = explode(' ',$rootcid);
        } elseif (strpos($rootcid,'+')) {
            $rootcids = explode('+',$rootcid);
        } else {
            $rootcids = explode('-',$rootcid);
        }
    }

// TODO: for multi-module pages, we'll need some other reference point(s)
//       (e.g. cross-module categories defined in categories admin ?)
    // Get current module
    if (empty($module)) {
        if (xarVarIsCached('Blocks.categories','module')) {
           $modname = xarVarGetCached('Blocks.categories','module');
        }
        if (empty($modname)) {
            $modname = xarModGetName();
        }
    } else {
        $modname = $module;
    }
    $modid = xarModGetIDFromName($modname);
    if (empty($modid)) {
        return;
    }

    // Get current item type (if any)
    if (!isset($itemtype)) {
        if (xarVarIsCached('Blocks.categories','itemtype')) {
            $itemtype = xarVarGetCached('Blocks.categories','itemtype');
        } else {
            // try to get itemtype from input
            xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET);
            if (empty($itemtype)) {
              xarVarFetch('ptid', 'isset', $itemtype, NULL, XARVAR_DONT_SET);
            }// if
        }
    }
    if (empty($itemtype)) {
        $itemtype = null;
    }

    // Get current item id (if any)
    if (!isset($itemid)) {
        if (xarVarIsCached('Blocks.categories','itemid')) {
            $itemid = xarVarGetCached('Blocks.categories','itemid');
        } else {
            // try to get itemid from input
            xarVarFetch('itemid', 'isset', $itemid, NULL, XARVAR_DONT_SET);
            if (empty($itemid)) {
              xarVarFetch('aid', 'isset', $itemid, NULL, XARVAR_DONT_SET);
            }// if
        }
    }
    if (empty($itemid)) {
        $itemid = null;
    }

    if (isset($rootcids)) {
        $mastercids = $rootcids;
    } else {
        // Get number of categories for this module + item type

        $numcats = xarModAPIfunc(
            'categories', 'user', 'countcatbases',
            array(
                'module'=>$modname,
                'itemtype'=>(empty($itemtype) ? NULL : $itemtype)
            )
          );

        if (empty($numcats)) {
            // no categories to show here -> return empty output
            return;
        }

        // Get master cids for this module + item type
        $mastercids = xarModAPIfunc(
            'categories', 'user', 'getallcatbases',
            array(
                'module' => $modname,
                'format' => 'cids',
                'order' => 'cid',
                'itemtype' => (empty($itemtype) ? NULL : $itemtype)
            )
        );

        if (empty($mastercids)) {
            // no categories to show here -> return empty output
            return;
        }

        $mastercids = array_unique($mastercids);

        if (!empty($startmodule)) {
            $rootcids = $mastercids;
        }
    }

    // See if we need to show a count per category
    if (!isset($showcatcount)) {
        $showcatcount = 0;
    }

    // See if we need to show the children of current categories
    if (!isset($showchildren)) {
        $showchildren = 1;
    }

    // Get current category counts (optional array of cid => count)
    if (empty($showcatcount)) {
        $catcount = array();
    }
    if (empty($showempty) || !empty($showcatcount)) {
        // A 'deep count' sums the totals at each node with the totals of all descendants.
        if (xarVarIsCached('Blocks.categories', 'deepcount')) {
            $deepcount = xarVarGetCached('Blocks.categories', 'deepcount');
        } else {
            $deepcount = xarModAPIFunc(
                'categories', 'user', 'deepcount',
                array('modid' => $modid, 'itemtype' => $itemtype)
            );
            xarVarSetCached('Blocks.categories','deepcount', $deepcount);
        }
    }

    if (!empty($showcatcount)) {
        if (xarVarIsCached('Blocks.categories', 'catcount')) {
            $catcount = xarVarGetCached('Blocks.categories', 'catcount');
        } else {
            // Get number of items per category (for this module).
            // If showcatcount == 2 then add in all descendants too.

            if ($showcatcount == 1) {
                // We want to display only children category counts.
                $catcount = xarModAPIFunc(
                    'categories','user', 'groupcount',
                    array('modid' => $modid, 'itemtype' => $itemtype)
                );
            } else {
                // We want to display the deep counts.
                $catcount =& $deepcount;
            }

            xarVarSetCached('Blocks.categories', 'catcount', $catcount);
        }
    }

    // Specify type=... & func = ... arguments for xarModURL()
    if (empty($type)) {
        if (xarVarIsCached('Blocks.categories','type')) {
            $type = xarVarGetCached('Blocks.categories','type');
        }
        if (empty($type)) {
            $type = 'user';
        }
    }
    if (empty($func)) {
        if (xarVarIsCached('Blocks.categories','func')) {
            $func = xarVarGetCached('Blocks.categories','func');
        }
        if (empty($func)) {
            $func = 'view';
        }
    }

    // Get current categories
    if (xarVarIsCached('Blocks.categories','catid')) {
       $catid = xarVarGetCached('Blocks.categories','catid');
    }
    if (empty($catid)) {
        // try to get catid from input
        xarVarFetch('catid', 'isset', $catid, NULL, XARVAR_DONT_SET);
    }
    // turn $catid into $cids array (and set $andcids flag)
    $istree = 0;
    if (!empty($catid)) {
        // if we're viewing all items below a certain category, i.e. catid = _NN
        if (strstr($catid,'_')) {
             $catid = preg_replace('/_/','',$catid);
             $istree = 1;
        }
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } else {
            $cids = explode('-',$catid);
            $andcids = false;
        }
    } elseif (empty($cids)) {
        if (xarVarIsCached('Blocks.categories','cids')) {
            $cids = xarVarGetCached('Blocks.categories','cids');
        }
        if (xarVarIsCached('Blocks.categories','andcids')) {
            $andcids = xarVarGetCached('Blocks.categories','andcids');
        }
        if (empty($cids)) {
            // try to get cids from input
            xarVarFetch('cids',    'isset', $cids,    NULL,  XARVAR_DONT_SET);
            xarVarFetch('andcids', 'isset', $andcids, false, XARVAR_NOT_REQUIRED);

            if (empty($cids)) {
                $cids = array();
                if ((empty($module) || $module == $modname) && !empty($itemid)) {
                    $links = xarModAPIFunc('categories','user','getlinks',
                                          array('modid' => $modid,
                                                'itemtype' => $itemtype,
                                                'iids' => array($itemid)));
                    if (!empty($links) && count($links) > 0) {
                        $cids = array_keys($links);
                    }
                }
            }
        }
    }
    if (count($cids) > 0) {
        $seencid = array();
        foreach ($cids as $cid) {
            if (empty($cid) || ! is_numeric($cid)) {
                continue;
            }
            $seencid[$cid] = 1;
        }
        $cids = array_keys($seencid);
    }

    $data = array();
    $data['cids'] = $cids;
    // pass information about current module, item type and item id (if any) to template
    $data['module'] = $modname;
    $data['itemtype'] = $itemtype;
    $data['itemid'] = $itemid;
    // pass information about current function to template
    $data['type'] = $type;
    $data['func'] = $func;

    $blockinfo['content'] = '';

    // Generate output
    switch ($layout) {

        case 3: // menu category
            $template = 'menu';
            break;

        case 2: // crumbtrails
            $template = 'trails';
            $data['cattrees'] = array();

            if (empty($cids) || sizeof($cids) <= 0) {
              return;
            }// if

            $cid = current($cids);
            $data['cid'] = $cid;

            # Get category parents
            $parents = xarModAPIFunc('categories','user','getparents',
                                    array('cid' => $cid));
            if (empty($parents)) {
                return;
            }// if

            $root = '';
            $parentid = 0;
            foreach ($parents as $id => $info) {
              $articles = xarModAPIFunc('articles', 'user', 'getall',
                                        array('cid'=>$info['cid'],
                                              'ptid'=>$itemtype,
                                              'fields'=>array('aid','title')
                                              )
                                        );
              foreach($articles as $k=>$article) {
                $articles[$article['title']] = $article['aid'];
                unset($articles[$k]);
              }// foreach

              $label = xarVarPrepForDisplay($info['name']);

              if (isset($articles[$label])) {
                $link = xarModURL($modname,$type,'display',
                                  array('ptid' => $itemtype,
                                        'catid' => $info['cid'],
                                        'aid'=>$articles[$label]));
              } else {
                $link = xarModURL($modname,$type,$func,
                                  array('itemtype' => $itemtype,
                                        'catid' => $info['cid']));
              }// if

              if (empty($root)) {
                $link = xarModURL('', '', '');
                $root = $label;
              }// if

              if (!empty($catcount[$info['cid']])) {
                  $count = $catcount[$info['cid']];
              } else {
                  $count = 0;
              }// if
              $catparents[] = array('catlabel' => $label,
                                    'catid' => $info['cid'],
                                    'catlink' => $link,
                                    'catcount' => $count);
            }// foreach

            $data['cattrees'][] = array('catparents' => $catparents);
            $data['crumbSeparator'] = '&nbsp;>&nbsp;';
            break;



        case 1: // tree
        default:

            $template = 'tree';
            $data['cattrees'] = array();

            if (empty($cids) || sizeof($cids) <= 0) {
              return;
            }// if

            $cid = current($cids);

            $cat = xarModAPIFunc('categories','user','getcatinfo',
                                 array('cid' => $cid));

            $blockinfo['title'] = xarVarPrepForDisplay($cat['name']);
            if (isset($cat['blockimage'])) {
              $data['catimage'] = $cat['blockimage'];
            }// if


            # Get child categories
            $childrenCategories = xarModAPIFunc('categories','user','getchildren',
                                     array('cid' => $cid));


            # get all the pubtypes so we can digest the ids
            $pubtypes = xarModAPIFunc('articles', 'user', 'getpubtypes', array());

            # get immediate items in current category
            $items = xarModAPIFunc('articles', 'user', 'getall',
                                      array('cids'=>array($cid),
                                            'fields'=>array('aid', 'pubtypeid', 'title')
                                            )
                                      );
            $tmpArticles = array();
            foreach($items as $k=>$item) {
              if (strtolower($item['title']) == strtolower($cat['name'])) {
                unset($items[$k]);
              } else {
                $label = xarVarPrepForDisplay($item['title']);
                $class = ($item['aid'] == $itemid) ? 'xar-menu-item-current' : 'xar-menu-item';
                $link = xarModURL($modname,$type,'display',
                                  array('aid'       => $item['aid'],
                                        'itemtype'  => $item['pubtypeid'],
                                        'catid'     => $cid));

                $count = 0;
                $items[$k] = array('label' => $label,
                                   'aid' => $item['aid'],
                                   'class'=>$class,
                                   'link' => $link);

                $tmpArticles[$pubtypes[$item['pubtypeid']]['descr']][] = $items[$k];
              }// if
            }// foreach
            $items = $tmpArticles;
            unset($tmpArticles);


            if (empty($itemid) && empty($andcids)) {
                $link = '';
            }

            $catitems = array();
            if (!empty($childrenCategories) && count($childrenCategories) > 0) {
              foreach ($childrenCategories as $child) {
                  $articles = xarModAPIFunc('articles', 'user', 'getall',
                                            array('cid'=>$child['cid'],
                                                  'ptid'=>$itemtype,
                                                  'fields'=>array('aid','title')
                                                  )
                                            );
                  foreach($articles as $k=>$article) {
                    $articles[$article['title']] = $article['aid'];
                    unset($articles[$k]);
                  }// foreach

                  $clabel = xarVarPrepForDisplay($child['name']);

                  if (isset($articles[$clabel])) {
                    $clink = xarModURL($modname,$type,'display',
                                      array('ptid' => $itemtype,
                                            'catid' => $child['cid'],
                                            'aid'=>$articles[$clabel]));
                  } else {
                    $clink = xarModURL($modname,$type,$func,
                                      array('itemtype' => $itemtype,
                                            'catid' => $child['cid']));
                  }// if

                  if (!empty($catcount[$child['cid']])) {
                      $ccount = $catcount[$child['cid']];
                  } else {
                      $ccount = 0;
                  }
                  $catitems[] = array('catlabel' => $clabel,
                                         'catid' => $child['cid'],
                                         'catlink' => $clink,
                                         'catcount' => $ccount,
                                         'catchildren'=>array());
              }// foreach
            }// if

            if (sizeof($catitems) > 0 || sizeof($items) > 0) {
              $data['cattrees'][] = array('catitems' => $catitems,
                                          'items' => $items);
            } else {
              return;
            }// if

            break;
    }
    $data['blockid'] = $blockinfo['bid'];

    // Populate block info for passing back to theme.

    // The template base is set by this block if not already provided.
    // The base is 'nav-tree', 'nav-trails' or 'nav-prevnext', but allow
    // the admin to override this completely.
    $blockinfo['_bl_template_base'] = 'nav-' . $template;

    // Return data, not rendered content.
    $blockinfo['content'] = $data;
    if (!empty($blockinfo['content'])) {
        return $blockinfo;
    }

    return;
}

?>
