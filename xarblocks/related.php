<?php
/**
 * Articles module related articles block
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
function articles_relatedblock_init()
{
    return array(
        'numitems' => 5,
        'dynamictitle' => false,
        'showonview' => false,
        'showondisplay' => false,
        'showvalue' => true,
        'showpubtype' => true,
        'showauthor' => true,
        'showcategories' => false,
        'showkeywords' => false, // need to check for keywords mod
        'nocache' => 0, // oh, let's cache by default. why not?
        'pageshared' => 0, // don't share across pages
        'usershared' => 1, // share across group members
        'cacheexpire' => 3600
    );
}

/**
 * get information on block
 */
function articles_relatedblock_info()
{
    // Values
    return array(
        'text_type' => 'Related',
        'module' => 'articles',
        'text_type_long' => 'Show related article information',
        'allow_multiple' => true,
        'form_content' => false,
        'form_refresh' => false,
        'show_preview' => true
    );
}

/**
 * display block
 */
function articles_relatedblock_display($blockinfo)
{
    // Get variables from content block
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }
    if (empty($vars['showvalue'])) {
        $vars['showvalue'] = false;
    }
    //new options added, 2007
    if (empty($vars['dynamictitle'])) {$vars['dynamictitle'] = false;}
    if (empty($vars['showonview'])) {$vars['showonview'] = false;}
    if (empty($vars['showondisplay'])) {$vars['showondisplay'] = false;}
    if (empty($vars['sorttype'])) {$vars['sorttype'] = 'alpha';}
    if (empty($vars['sortorder'])) {$vars['sortorder'] = 'random';}
    if (empty($vars['showpubtype'])) {$vars['showpubtype'] = false;}
    if (empty($vars['showauthor'])) {$vars['showauthor'] = false;}
    if (empty($vars['showcategories'])) {$vars['showcategories'] = false;}
    if (empty($vars['showkeywords'])) {$vars['showkeywords'] = false;}
    
    // Method : work with cached variables here (set by the module function)

    //check to see where we are in articles
    if (!xarVarIsCached('Blocks.articles','aid') && xarVarIsCached('Blocks.articles','ptid')) {
        if ($vars['showonview'] === false) {
            return;
        }
    } elseif (xarVarIsCached('Blocks.articles','aid')) {
        if ($vars['showondisplay'] === false) {
            return;
        }
    } elseif (!xarVarIsCached('Blocks.articles','aid') && !xarVarIsCached('Blocks.articles','ptid')) {
        return;
    }

    $links = 0;
    if ($vars['dynamictitle'] === true) {
//        $blockinfo['title'] .= ' ' . $vars['dynamictitle'];
        $blockinfo['title'] .= ' ' . 'hmmm';
        $links++;
    }

    if ($vars['showpubtype'] === true) {
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

        if (xarVarIsCached('Blocks.articles','ptid')) {
            $ptid = xarVarGetCached('Blocks.articles','ptid');
            if (!empty($ptid) && isset($pubtypes[$ptid]['descr'])) {
                $vars['pubtypelink'] = xarModURL('articles','user','view',
                                             array('ptid' => $ptid));
                $vars['pubtypename'] = $pubtypes[$ptid]['descr'];
                $links++;
            }
        }
    }


    if ($vars['showcategories'] === true) {
    if (!xarVarFetch('catid',  'str',   $vars['catid'],   NULL, XARVAR_NOT_REQUIRED)) {return;}


        if (!empty($vars['catid'])) {

            if (strstr($vars['catid'], '_')) {
                $vars['catid'] = substr($vars['catid'],1);
            }

            $cids = array($vars['catid']);
            
            if (!empty($cids) && is_array($cids)) {
                $vars['cids'] = $cids;
                
                if ($vars['showkeywords'] === true) {
                    $vars['keywords'] = xarModAPIFunc('keywords','user','getmultiplewords',
                                            array(
                                            'objectids' => $cids,
                                            'itemtype' => 0,
                                            'modid' => xarModGetIDFromName('categories')
                                            ));
                if (!empty($vars['keywords']) && is_array($vars['keywords'])) {
                    $keywords = current($vars['keywords']);
                    
                    foreach ($keywords as $k => $keyword) {
                        
                        $relatedcats[$keyword['keyword']] = xarModAPIFunc('keywords','user','getitems',array(
                                                           'keyword' => $keyword['keyword'],
                                                           'modid' => xarModGetIDFromName('categories')
                                                            ));
                    }
                    
                    if (!empty($relatedcats) && is_array($relatedcats)) {
                        foreach ($relatedcats as $c => $related) {
                            //build our array of cids
                            $relatedids = array();
  
                            foreach ($related as $cat) {
                              if ($vars['catid'] != $cat['itemid']) {
                                 $relatedids[] = $cat['itemid'];
                              }
                            }
                            $relatedids = array_rand(array_flip($relatedids),$vars['numitems']);
                          
                            //get the catinfo
                            $relatedcatsinfo[$c] = xarModAPIFunc('categories','user','getcatinfo',array(
                                                   'cids' => $relatedids
                                                    ));
/*                        $sortedcatsinfo[$c] = xarModAPIFunc('base','user','sortit',array(
                                           'items' => $relatedcatsinfo[$c]
                                           ,'field' => 'name'
                                           ));*/
                        }
                        foreach ($relatedcatsinfo as $s => $sorting) {
//                        print_r(array_rand($sorting,3));die;
                        $sortedcatsinfo[$s] = xarModAPIFunc('base','user','sortit',array(
//                        'items' => array_values(array_rand($sorting,$vars['numitems'])),
                        'items' => array_values($sorting),
                        'field' => 'name'));
                        }
                        
                                          //    print_r($sortedcatsinfo);die;
                        $vars['relatedcatsinfo'] = $sortedcatsinfo;
                    }
                }
                
                } // end of showkeywords if

                $links++;
            }
        }
    }
    
    if ($vars['showauthor'] === true) {
        if (xarVarIsCached('Blocks.articles','authorid') &&
            xarVarIsCached('Blocks.articles','author')) {
            $authorid = xarVarGetCached('Blocks.articles','authorid');
            $author = xarVarGetCached('Blocks.articles','author');
            if (!empty($authorid) && !empty($author)) {
                $vars['authorlink'] = xarModURL('articles','user','view',
                                            array('ptid' => (!empty($ptid) ? $ptid : null),
                                                  'authorid' => $authorid));
            $vars['authorname'] = $author;
            $vars['authorid'] = $authorid;
            if (!empty($vars['showvalue'])) {
                $vars['authorcount'] = xarModAPIFunc('articles','user','countitems',
                                                     array('ptid' => (!empty($ptid) ? $ptid : null),
                                                           'authorid' => $authorid,
                                                           // limit to approved / frontpage articles
                                                           'status' => array(2,3),
                                                           'enddate' => time()));
                }
                $links++;
            }
        }
    }

    $vars['blockid'] = $blockinfo['bid'];

    // Populate block info and pass to theme
    if ($links > 0) {
        $blockinfo['content'] = $vars;
        return $blockinfo;
    }

    return;
}


/**
 * modify block settings
 */
function articles_relatedblock_modify($blockinfo)
{
    // Get current content
    if (!is_array($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars = $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['numitems'])) {
        $vars['numitems'] = 5;
    }
    if (empty($vars['showvalue'])) {
        $vars['showvalue'] = false;
    }

    $vars['bid'] = $blockinfo['bid'];

    // Return output
    return $vars;
}

/**
 * update block settings
 */
function articles_relatedblock_update($blockinfo)
{
    $vars = array();
    if (!xarVarFetch('numitems', 'int:1:100', $vars['numitems'], 5, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showonview', 'checkbox', $vars['showonview'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showondisplay', 'checkbox', $vars['showondisplay'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showvalue', 'checkbox', $vars['showvalue'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showpubtype', 'checkbox', $vars['showpubtype'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showauthor', 'checkbox', $vars['showauthor'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showcategories', 'checkbox', $vars['showcategories'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('showkeywords', 'checkbox', $vars['showkeywords'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('dynamictitle', 'checkbox', $vars['dynamictitle'], false, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('sorttype',  'str',   $vars['sorttype'],   'alpha', XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('sortorder',  'str',   $vars['sortorder'],   'random', XARVAR_NOT_REQUIRED)) {return;}
    $blockinfo['content'] = $vars;

    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function articles_relatedblock_help()
{
    return '';
}

?>
