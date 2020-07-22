<?php
/**
 * Top Items Block
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @copyright (C)2008,2009 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author Xarigami Team 
 * @author mikespub
 *
 */
/**
 * initialise block
 * @author Jim McDonald
 */
function articles_topitemsblock_init()
{
    // Initial values when the block is created.
    return array(
        'numitems' => 5,
        'pubtypeid' => '0',
        'nopublimit' => false,
        'linkpubtype' => true,
        'catfilter' => 0,
        'includechildren' => false,
        'nocatlimit' => true,
        'linkcat' => false,
        'dynamictitle' => true,
        'toptype' => 'hits',
        'showvalue' => true,
        'showsummary' => false,
        'showdynamic' => false,
        'alldefault' => false,
        'status' => '2,3',
        'nocache' => 0, // cache by default
        'pageshared' => 1, // share across pages (change if you use dynamic pubtypes et al.)
        'usershared' => 1, // share across group members
        'cacheexpire' => null
    );
}

/**
 * get information on block
 */
function articles_topitemsblock_info()
{
    // Values
    return array(
        'text_type' => 'Top Items',
        'module' => 'articles',
        'text_type_long' => 'Show top articles',
        'allow_multiple' => true,
        'form_content' => false, // Deprecated.
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display block
 * @author Jim McDonald
 */
function articles_topitemsblock_display($blockinfo)
{
    // Get variables from content block
    if (is_string($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // This is to maintain legacy consistancy
    if (!isset($vars['linkpubtype'])) {
        $vars['linkpubtype'] = true;
    }
   if (!isset($vars['alldefault'])) {
        $vars['alldefault'] = false;
    }
    // see if we're currently displaying an article
    if (xarVarIsCached('Blocks.articles', 'aid')) {
        $curaid = xarVarGetCached('Blocks.articles', 'aid');
    } else {
        $curaid = -1;
    }

    if (!empty($vars['dynamictitle'])) {
        if ($vars['toptype'] == 'rating') {
            $blockinfo['title'] = xarML('Top Rated');
        } elseif ($vars['toptype'] == 'hits') {
            $blockinfo['title'] = xarML('Top');
        } elseif ($vars['toptype'] == 'date2') { 
            $blockinfo['title'] = xarML('Listed');   
        } else  {
            $blockinfo['title'] = xarML('Latest');
        }
    }

    if (!empty($vars['nocatlimit'])) {
        // don't limit by category
        $cid = 0;
        $cidsarray = array();
    } else {
        if (!empty($vars['catfilter'])) {
            // use admin defined category
            $cidsarray = array($vars['catfilter']);
            $cid = $vars['catfilter'];
        } else {
            // use the current category
            // Jonn: this currently only works with one category at a time
            // it could be reworked to support multiple cids
            if (xarVarIsCached('Blocks.articles', 'cids')) {
                $curcids = xarVarGetCached('Blocks.articles', 'cids');
                if (!empty($curcids)) {
                    if ($curaid == -1) {
                        //$cid = $curcids[0]['name'];
                        $cid = $curcids[0];
                        $cidsarray = array($curcids[0]);
                    } else {
                        $cid = $curcids[0];
                        $cidsarray = array($curcids[0]);
                    }
                } else {
                    $cid = 0;
                    $cidsarray = array();
                }
            } else {
                // pull from all categories
                $cid = 0;
                $cidsarray = array();
            }
        }

        //echo $includechildren;
        if (!empty($vars['includechildren']) && !empty($cidsarray[0]) && !strstr($cidsarray[0],'_')) {
            $cidsarray[0] = '_' . $cidsarray[0];
        }

        if (!empty($cid)) {
            // if we're viewing all items below a certain category, i.e. catid = _NN
            $cid = str_replace('_', '', $cid);
            $thiscategory = xarModAPIFunc(
                'categories','user','getcat',
                array('cid' => $cid, 'return_itself' => 'return_itself')
            );
        }
        if ((!empty($cidsarray)) && (isset($thiscategory[0]['name'])) && !empty($vars['dynamictitle'])) {
            $blockinfo['title'] .= ' ' . $thiscategory[0]['name'];
        }
    }

    // Get publication types
    $pubtypes = xarModAPIFunc('articles', 'user', 'getpubtypes');

    if (!empty($vars['nopublimit'])) {
        //don't limit by pubtype
        $ptid = '0';
        if (!empty($vars['dynamictitle'])) {
            $blockinfo['title'] .= ' ' . xarML('Content');
        }
    } else {
        // MikeC: Check to see if admin has specified that only a specific
        // Publication Type should be displayed.  If not, then default to original TopItems configuration.
        if ($vars['pubtypeid'] == '0')
        {
            if (xarVarIsCached('Blocks.articles', 'ptid')) {
                $ptid = xarVarGetCached('Blocks.articles', 'ptid');
            }
            if (empty($ptid)) {
                // default publication type
                $ptid = xarModGetVar('articles', 'defaultpubtype');
            }
        } else {
            // MikeC: Admin Specified a publication type, use it.
            $ptid = $vars['pubtypeid'];
        }

        if (!empty($vars['dynamictitle'])) {
            if (!empty($ptid) && isset($pubtypes[$ptid]['descr'])) {
                $blockinfo['title'] .= ' ' . xarVarPrepForDisplay($pubtypes[$ptid]['descr']);
            } else {
                $blockinfo['title'] .= ' ' . xarML('Content');
            }
        }
    }

    // frontpage or approved status
    if (empty($vars['status'])) {
        $statusarray = array(2,3);
    } elseif (!is_array($vars['status'])) {
        $statusarray = preg_split('/,/', $vars['status']);
    } else {
        $statusarray = $vars['status'];
    }

    // get cids for security check in getall
    if ($vars['alldefault'] == TRUE) {
        $fields = array('aid', 'title', 'pubtypeid','summary','body','notes','cids');
    } else {
        $fields = array('aid', 'title', 'pubtypeid','cids');
    }

    if ($vars['toptype'] == 'rating' && xarModIsHooked('ratings', 'articles', $ptid)) {
        array_push($fields, 'rating');
        $sort = 'rating';
    } elseif ($vars['toptype'] == 'hits' && xarModIsHooked('hitcount', 'articles', $ptid)) {
        array_push($fields, 'counter');
        $sort = 'hits';
    } elseif ($vars['toptype'] == 'date2') {
         $sort = 'date ASC';    
    } else {
        array_push($fields, 'pubdate');
        $sort = 'date';
    }

    if (!empty($vars['showsummary']) && empty($vars['alldefault'])) {
        array_push($fields, 'summary');
    }
    if (!empty($vars['showdynamic']) && xarModIsHooked('dynamicdata', 'articles', $ptid)) {
        array_push($fields, 'dynamicdata');
    }

    $articles = xarModAPIFunc(
        'articles','user','getall',
        array(
            'ptid' => $ptid,
            'cids' => $cidsarray,
            'andcids' => 'false',
            'status' => $statusarray,
            'enddate' => time(),
            'fields' => $fields,
            'sort' => $sort,
            'numitems' => $vars['numitems']
        )
    );

    if (!isset($articles) || !is_array($articles) || count($articles) == 0) {
       return;
    }

    $items = array();
    foreach ($articles as $article) {
    
        $article['title'] = xarVarPrepHTMLDisplay($article['title']);
        if ($article['aid'] != $curaid) {
            // Use the filtered category if set, and not including children
            $article['link'] = xarModURL(
                'articles', 'user', 'display',
                array(
                    'aid' => $article['aid'],
                    'ptid' => (!empty($vars['linkpubtype']) ? $article['pubtypeid'] : NULL),
                    'catid' => ((!empty($vars['linkcat']) && !empty($vars['catfilter'])) ? $vars['catfilter'] : NULL)
                )
            );
        } else {
            $article['link'] = '';
        }

        if (!empty($vars['showvalue'])) {
            if (($vars['toptype'] == 'rating') && xarModIsHooked('ratings','articles',$ptid)) {
                $articlestyle = xarModGetVar('ratings','style.articles');
                $defaultstyle = xarModGetVar('ratings','style');
                $ratestyle = isset($articlestyle) ? $articlestyle: $defaultstyle;
                if (!empty($article['rating'])) {
                  switch ($ratestyle) {
                    case 'percentage':
                        $article['value'] =$article['rating'].' %';  
                        break;
                    case 'outoften':
                    case 'outoftenstars':
                         $article['value'] =($article['rating']/10).'/10';
                        break;
                    case 'outoffivestars':
                    case 'outoffive':
                    default:
                        $article['value'] = ($article['rating']/20).'/5';
                    
                  }  
                  
                }else {
                    $article['value']=0;
                }
            } elseif ($vars['toptype'] == 'hits') {
                if (!empty($article['counter'])) {
                    $article['value'] = $article['counter'];
                } else {
                    $article['value'] = 0;
                }
            } else {
                // TODO: make user-dependent
                if (!empty($article['pubdate'])) {
                    //$article['value'] = strftime("%Y-%m-%d", $article['pubdate']);
                      $article['value'] = xarLocaleGetFormattedDate('short',$article['pubdate']);
                } else {
                    $article['value'] = 0;
                }
            }
        } else {
            $article['value'] = 0;
        }

        // MikeC: Bring the summary field back as $desc
        if (!empty($vars['showsummary'])) {
            $article['summary']  = xarVarPrepHTMLDisplay($article['summary']);
            $article['transform'] = array('summary', 'title');
            $article = xarModCallHooks('item', 'transform', $article['aid'], $article, 'articles');
        } else {
            $article['summary'] = '';
        }

        // MarieA: Bring the pubtype description back as $descr
        if (!empty($vars['nopublimit'])) {
            $article['pubtypedescr'] = $pubtypes[$article['pubtypeid']]['descr'];
            //jojodee: while we are here bring the pubtype name back as well
            $article['pubtypename'] = $pubtypes[$article['pubtypeid']]['name'];
        }
        // this will also pass any dynamic data fields (if any)
        $items[] = $article;
    }

    // Populate block info and pass to theme
    if (count($items) > 0) {
        $blockinfo['content'] = array('items' => $items);
        return $blockinfo;
    }
}

/**
 * built-in block help/information system.
 */
function articles_topitemsblock_help()
{
    return '';
}

?>