<?php
/**
 * Featured items
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 * @link http://www.xaraya.com
 *
 * @subpackage Articles Module
 * @link http://xaraya.com/index.php/release/151.html
 * @author mikespub
 *
 */
/**
 * Featured initialise block
 *
 * @author Jonn Beams (based on code from TopItems block)
 *
 */

function articles_featureditemsblock_init()
{
    return array(
        'featuredaid'       => 0,
        'alttitle'          => '',
        'altsummary'        => '',
        'moreitems'         => array(),
        'toptype'           => 'date',
        'showvalue'         => true,
        'pubtypeid'         => '',
        'catfilter'         => '',
        'status'            => array(3, 2),
        'itemlimit'         => 10,
        'showfeaturedsum'   => false,
        'showfeaturedbod'   => false,
        'moreitems'         => array(),
        'showsummary'       => false,
        'linkpubtype'       => false,
        'linkcat'           => false
    );
}

/**
 * get information on block
 */
function articles_featureditemsblock_info()
{
    // Details of block.
    return array(
        'text_type'         => 'Featured Items',
        'module'            => 'articles',
        'text_type_long'    => 'Show featured articles',
        'allow_multiple'    => true,
        'form_content'      => false,
        'form_refresh'      => false,
        'show_preview'      => true
    );
}

/**
 * display block
 */
function articles_featureditemsblock_display(& $blockinfo)
{

    // Get variables from content block
    if (is_string($blockinfo['content'])) {
        $vars = @unserialize($blockinfo['content']);
    } else {
        $vars =& $blockinfo['content'];
    }

    // Defaults
    if (empty($vars['featuredaid'])) {$vars['featuredaid'] = 0;}
    if (empty($vars['alttitle'])) {$vars['alttitle'] = '';}
    if (empty($vars['altsummary'])) {$vars['altsummary'] = '';}
    if (empty($vars['toptype'])) {$vars['toptype'] = 'date';}
    if (empty($vars['moreitems'])) {$vars['moreitems'] = array();}
    if (empty($vars['linkcat'])) {$vars['linkcat'] = false;}
    if (!isset($vars['showvalue'])) {
        if ($vars['toptype'] == 'rating') {
            $vars['showvalue'] = false;
        } else {
            $vars['showvalue'] = true;
        }
    }

    $featuredaid = $vars['featuredaid'];

    $fields = array('aid', 'title', 'cids');

    $fields[] = 'dynamicdata';

    // Initialize arrays
    $data['feature'] = array();
    $data['items'] = array();

    // Setup featured item
    if ($featuredaid > 0) {

        if (xarModIsHooked('uploads', 'articles', $vars['pubtypeid'])) {
            xarVarSetCached('Hooks.uploads','ishooked',1);
        }

        if($featart = xarModAPIFunc('articles','user','getall',
            array(  'aids' => array($featuredaid),
                    'extra' => array('cids','dynamicdata'))))
            {

            foreach($featart as $featuredart) {
                $fieldlist = array('aid', 'title', 'summary', 'authorid', 'pubdate',
                               'pubtypeid', 'notes', 'status', 'body', 'cids');

                $featuredlink = xarModURL('articles', 'user', 'display',
                        array(  'aid' => $featuredart['aid'],
                                'ptid' => ($featuredart['pubtypeid'] ? $featuredart['pubtypeid'] : NULL),
                                'catid' => ((!empty($vars['linkcat']) && !empty($vars['catfilter'])) ? $vars['catfilter'] : NULL)
                            )
                );
                if (empty($vars['showfeaturedbod'])) {$vars['showfeaturedbod'] = false;}
                if(!isset($featuredart['cids'])) $featuredart['cids'] = "";

                $feature= array(
                        'featuredlabel'     => $featuredart['title'],
                        'featuredlink'      => $featuredlink,
                        'alttitle'          => $vars['alttitle'],
                        'altsummary'        => $vars['altsummary'],
                        'showfeaturedsum'   => $vars['showfeaturedsum'],
                        'showfeaturedbod'   => $vars['showfeaturedbod'],
                        'featureddesc'      => $featuredart['summary'],
                        'featuredbody'      => $featuredart['body'],
                        'featuredcids'      => $featuredart['cids'],
                        'pubtypeid'         => $featuredart['pubtypeid'],
                        'featuredaid'       => $featuredart['aid'],
                        'featureddate'      => $featuredart['pubdate']
                );

                // Get rid of the default fields so all we have left are the DD ones
                foreach ($fieldlist as $field) {
                    if (isset($featuredart[$field])) {
                        unset($featuredart[$field]);
                    }
                }

                // now add the DD fields to the featuredart
                $feature = array_merge($featuredart, $feature);
                $data['feature'][] = $feature;
            }
        }
    }

    // Setup additional items
    $fields = array('aid', 'title', 'pubtypeid', 'cids');

    // Added the 'summary' field to the field list.
    if (!empty($vars['showsummary'])) {
        $fields[] = 'summary';
    }

    if ($vars['toptype'] == 'rating') {
        $fields[] = 'rating';
        $sort = 'rating';
    } elseif ($vars['toptype'] == 'hits') {
        $fields[] = 'counter';
        $sort = 'hits';
    } elseif ($vars['toptype'] == 'date') {
        $fields[] = 'pubdate';
        $sort = 'date';
    } else {
       $sort = $vars['toptype'];
    }

    if (!empty($vars['moreitems'])) {
        $articles = xarModAPIFunc(
            'articles', 'user', 'getall',
            array(
                'aids' => $vars['moreitems'],
                'enddate' => time(),
                'fields' => $fields,
                'sort' => $sort
            )
        );

        // See if we're currently displaying an article
        if (xarVarIsCached('Blocks.articles', 'aid')) {
            $curaid = xarVarGetCached('Blocks.articles', 'aid');
        } else {
            $curaid = -1;
        }

        foreach ($articles as $article) {
            if ($article['aid'] != $curaid) {
                $link = xarModURL(
                    'articles', 'user', 'display',
                    array (
                        'aid' => $article['aid'],
                        'itemtype' => (!empty($vars['linkpubtype']) ? $article['pubtypeid'] : NULL),
                        'catid' => ((!empty($vars['linkcat']) && !empty($vars['catfilter'])) ? $vars['catfilter'] : NULL)
                    )
                );
            } else {
                $link = '';
            }

            $count = '';
            // TODO: find a nice clean way to show all sort types
            if ($vars['showvalue']) {
                if ($vars['toptype'] == 'rating') {
                    $count = intval($article['rating']);
                } elseif ($vars['toptype'] == 'hits') {
                    $count = $article['counter'];
                } elseif ($vars['toptype'] == 'date') {
                    // TODO: make user-dependent
                    if (!empty($article['pubdate'])) {
                        $count = strftime("%Y-%m-%d", $article['pubdate']);
                    } else {
                        $count = 0;
                    }
                } else {
                    $count = 0;
                }
            } else {
                $count = 0;
            }
            if (isset($article['cids'])) {
               $cids=$article['cids'];
            }else{
               $cids='';
            }
            if (isset($article['pubdate'])) {
               $pubdate=$article['pubdate'];
            }else{
               $pubdate='';
            }
            // Pass $desc to items[] array so that the block template can render it
            $data['items'][] = array(
                'label' => $article['title'],
                'link' => $link,
                'count' => $count,
                'cids' => $cids,
                'pubdate' => $pubdate,
                'desc' => ((!empty($vars['showsummary']) && !empty($article['summary'])) ? $article['summary'] : ''),
                'aid' => $article['aid']
            );
        }
    }

    if (empty($data['feature']) && empty($data['items'])) {
        // Nothing to display.
        return;
    }
    // Set the data to return.
    $data['name'] = $blockinfo['name'];
    $data['bid'] = $blockinfo['bid'];
    $data['module'] = $blockinfo['module'];
    $data['type'] = $blockinfo['type'];
    $data['pubtypeid'] = $vars['pubtypeid'];
    $data['catfilter'] = $vars['catfilter'];
    $blockinfo['content'] = $data;
    return $blockinfo;
}

/**
 * built-in block help/information system.
 */
function articles_featureditemsblock_help()
{
    // No information yet.
    return '';
}

?>