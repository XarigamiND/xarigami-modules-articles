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
 * @author Xarigami Team
 * @author mikespub
 */
/**
 * Extract function and arguments from short URLs for this module, and pass
 * them back to xarGetRequestInfo()
 *
 * @param $params array containing the elements of PATH_INFO
 * @return array containing func the function to be called and args the query
 *         string arguments, or empty if it failed
 */
function articles_userapi_decode_shorturl($params)
{
    $args = array();
    $module = 'articles';

    $foundalias = 0;

    // Check if we're dealing with an alias here
    if ($params[0] != $module) {
        $alias = xarModGetAlias($params[0]);
        if ($module == $alias) {
            // yup, looks like it
            $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
            foreach ($pubtypes as $id => $pubtype) {
                if ($params[0] == $pubtype['name']) {
                    $foundalias = 1;
                    $args['ptid'] = $id;
                    break;
                }
            }
        }
    }

    // Get the article settings for this publication type
    if (!empty($args['ptid'])) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$args['ptid']));
    } else {
        $string = xarModGetVar('articles', 'settings');
        if (!empty($string)) {
            $settings = unserialize($string);
        }
    }

    // check if we want to decode URLs using their titles rather then their ID
    $decodeUsingTitle = empty($settings['usetitleforurl']) ? 0 : $settings['usetitleforurl'];

    if (empty($params[1])) {
        return array('view', $args);

    } elseif (preg_match('/^index/i',$params[1])) {
        return array('main', $args);

    } elseif (preg_match('/^map/i',$params[1])) {
        return array('viewmap', $args);

    } elseif (preg_match('/^search\&|^search\?|^search$/',$params[1])) {
        if (!empty($params[2]) && preg_match('/^c(_?[0-9 +-]+)/',$params[2],$matches)) {
            $catid = $matches[1];
            $args['catid'] = $catid;
        }
        return array('search', $args);

    } elseif (preg_match('/^(\d+)$/',$params[1],$matches)) {
        $aid = $matches[1];
        $args['aid'] = $aid;
        return array('display', $args);

    } elseif ($params[1] == 'monthview') {
        if (!empty($params[2]) && preg_match('/^(\d{4}|all)/',$params[2],$matches)) {
            $args['month'] = $matches[1];
            if ($args['month'] != 'all' && !empty($params[3]) && is_numeric($params[3])) {
                $args['month'] .= '-' . $params[3];
            }
        }
        return array('monthview', $args);

    } elseif ($params[1] == xarML('by_author')) {
        if (!empty($params[2]) && preg_match('/^(\d+)/',$params[2],$matches)) {
            $args['authorid'] = $matches[1];
            return array('view', $args);
        }

    } elseif ($params[1] == 'redirect') {
        if (!empty($params[2]) && preg_match('/^(\d+)/',$params[2],$matches)) {
            $args['aid'] = $matches[1];
            return array('redirect', $args);
        }

    } elseif (preg_match('/^c(_?[0-9 +-]+)/',$params[1],$matches)) {
        $catid = $matches[1];
        $args['catid'] = $catid;
        // /[modules or alias name]/c[cid] or /[modules or alias name]/[pubtype]/c[cid]

        $hasPtidParam1 = false;
        if (!$foundalias && !empty($params[2])) {
            // /[modules or alias name]/c[cid]/pubtype/
            $pubtypes = xarModAPIFunc('articles', 'user', 'getpubtypes');

            foreach ($pubtypes as $id => $pubtype) {
                if ($params[1] == $pubtype['name']) {
                    $args['ptid'] = $id;
                    $args['bycat'] = 1;
                    $hasPtidParam1 = true;
                    break;
                }
            }
        }
        // Decode should return the same array of arguments that was passed to encode
        if (strpos($catid,'+') === FALSE ) {
            $args['cids'] = explode('-',$catid);
        } else {
            $args['cids'] = explode('+',$catid);
            $args['andcids'] = TRUE;
        }
        if (empty($params[2]) || !$foundalias && empty($params[3])) {
            return array('view', $args);
        }

        // We may also pass some pubtype and aid or article title. (xart-000596)
        // and also catid providing the category of origin
        // /[modules or alias name]/[pubtype]/c[cid]/[aid or article title]
        //if (!$foundalias) return; // raise an exception in the future.

                // normalize $params to articles/pubtype/... for title decoding
        if ($foundalias) {
            array_unshift($params, $module);
        }

        // Now that we find out that we're in a specific pubtype, get specific pubtype settings again
        $settings = unserialize(xarModGetVar('articles', 'settings.' . $args['ptid']));

        // check if we want to decode URLs using their titles rather then their ID
        $decodeUsingTitle = empty($settings['usetitleforurl']) ? 0 : $settings['usetitleforurl'];

        // Keep compability with what is following
        unset($params[2]);
        $params = array_values($params);

        // Decode using title
        if ($decodeUsingTitle) {
            if (($decodeUsingTitle == 3)  && !empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches))
            {
                 $args['aid'] = $params[3];
            } else {
                $args['aid'] = articles_decodeAIDUsingTitle($params, $args['ptid'], $decodeUsingTitle );
            }
            return array('display', $args);
        } else {
            // Get the article aid
            // We should raise a not found exception if this is not a numeric value
            if ($hasPtidParam1)
                $args['aid'] = $params[3];
            else
                $args['aid'] = $params[2];
            return array('display', $args);
        }


    } elseif ($params[1] == 'c') {
        // perhaps someday...

    } else {

        // normalize $params to articles/pubtype/... for title decoding
        if ($foundalias) {
            array_unshift($params, $module);
        }

        // Get all publication types present
        $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
        foreach ($pubtypes as $id => $pubtype) {
            if ($params[1] == $pubtype['name']) {
                $args['ptid'] = $id;
                break;
            }
        }

        if (!empty($params[2])) {
            if (preg_match('/^(\d+)$/',$params[2],$matches)) {

                $aid = $matches[1];
                $args['aid'] = $aid;
                return array('display', $args);
            } elseif (empty($params[3]) && preg_match('/^c(_?[0-9 +-]+)/',$params[2],$matches)) {
                $catid = $matches[1];
                $args['catid'] = $catid;
                // Decode should return the same array of arguments that was passed to encode
                if( strpos($catid,'+') === FALSE )
                {
                    $args['cids'] = explode('-',$catid);
                } else {
                    $args['cids'] = explode('+',$catid);
                    $args['andcids'] = TRUE;
                }
                return array('view', $args);
            } elseif (preg_match('/^map/i',$params[2])) {
                return array('viewmap', $args);
            } elseif (preg_match('/^search\&|^search\?|^search$/i',$params[2])) {
                return array('search', $args);
            } elseif ($params[2] == 'monthview') {
                if (!empty($params[3]) && preg_match('/^(\d{4}|all)/',$params[3],$matches)) {
                    $args['month'] = $matches[1];
                    if ($args['month'] != 'all' && !empty($params[4]) && is_numeric($params[4])) {
                        $args['month'] .= '-' . $params[4];
                    }
                }
                return array('monthview', $args);
            } elseif ($params[2] == 'redirect') {
                if (!empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches)) {
                    $args['aid'] = $matches[1];
                    return array('redirect', $args);
                }
            } else {

                // We may also pass (xart-000596)
                // catid providing the category of origin
                // /[modules]/[pubtype]/c[cid]/[aid or article title]
                if (preg_match('/^c(_?[0-9 +-]+)/', $params[2], $matches)) {
                    $args['catid'] = $catid = $matches[1];
                    if (strpos($catid,'+') === FALSE ) {
                        $args['cids'] = explode('-',$catid);
                    } else {
                        $args['cids'] = explode('+',$catid);
                        $args['andcids'] = TRUE;
                    }

                    // Keep compability with what is following
                    unset($params[2]);
                    $params = array_values($params);
                }

                // Now that we find out that we're in a specific pubtype, get specific pubtype settings again
                if (isset($args['ptid'])) {
                    $settings = unserialize(xarModGetVar('articles', 'settings.'.$args['ptid']));
                } else {
                    $msg = xarML('That page does not have a publication type we can identify');
                    return xarResponse::NotFound($msg);
                }

                // check if we want to decode URLs using their titles rather then their ID
                $decodeUsingTitle = empty($settings['usetitleforurl']) ? 0 : $settings['usetitleforurl'];

                // Decode using title
                if ($decodeUsingTitle) {
                    if (($decodeUsingTitle == 3)  && !empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches))
                    {
                         $args['aid'] = $params[3];
                    } else {
                        $args['aid'] = articles_decodeAIDUsingTitle($params, $args['ptid'], $decodeUsingTitle );
                    }
                    return array('display', $args);
                } else if (is_numeric($params[2])) {
                    $args['aid'] = $params[2];
                    return array('display', $args);
                }

                return array('view', $args);
            }
        } else {
            return array('view', $args);
        }

        // Decode using title
        if( $decodeUsingTitle ) {
            if (($decodeUsingTitle == 3)  && !empty($params[3]) && preg_match('/^(\d+)/',$params[3],$matches))
            {
                 $args['itemid'] = $params[3];
            } else {
                $args['aid'] = articles_decodeAIDUsingTitle( $params, '', $decodeUsingTitle );
            }
            return array('display', $args);
        }
    }

    // default : return nothing -> no short URL
    // (e.g. for multiple category selections)
}
/**
 * Find the article ID by its title.
 * @access private
 * @return int aid The article ID
 * @todo bug 5878 Why does a title need higher privileges than the usual aid in a short title?
 */
function articles_decodeAIDUsingTitle( $params, $ptid = '', $decodeUsingTitle = 1 )
{
    switch ($decodeUsingTitle)
    {
        case 1:
            $dupeResolutionMethod = 'Append Date';
            break;
        case 2:
            $dupeResolutionMethod = 'Append AID';
            break;
        case 3:
            $dupeResolutionMethod = 'Use AID'; // always use AID  on the end - we should never get here now with changes 3/2011 <jojo>
            break;
        case 4:
        default:
            $dupeResolutionMethod = 'Ignore';
            break;
    }

    // The $params passed in does not match on all legal URL characters and
    // so some urls get cut off -- my test cases included parents and commands "this(here)" and "that,+there"
    // So lets parse the path info manually here.
    //
    // DONE: fix xarServer.php, line 421 to properly deal with this
    // xarServer.php[421] :: preg_match_all('|/([a-z0-9_ .+-]+)|i', $path, $matches);
    //
    // I've moved the following code into xarServer to fix this problem.
    //
    //     $pathInfo = xarServerGetVar('PATH_INFO');
    //     preg_match_all('|/([^/]+)|i', $pathInfo, $matches);
    //     $params = $matches[1];

    if( isset($ptid) && !empty($ptid) ) {
        $searchArgs['ptid'] = $ptid;
        $paramidx = 2;
    } else {
        $paramidx = 1;
    }
    $decodedTitle = urldecode($params[$paramidx]);

    // see if we need to append anything else to the title (= when it contains a /)
    if (count($params) > $paramidx + 1) {
        for ($i = $paramidx + 1; $i < count($params); $i++) {
            if ($dupeResolutionMethod == 'Append AID' && preg_match('/^\d+$/',$params[$i])) {
                break;
            } elseif ($dupeResolutionMethod == 'Append Date' && preg_match('/^\d+-\d+-\d+ \d+:\d+$/',$params[$i])) {
                break;
            } elseif ($dupeResolutionMethod == 'ALL' && preg_match('/^\d+(|-\d+-\d+ \d+:\d+)$/',$params[$i])) {
                break;
            }
            $decodedTitle .= '/' . urldecode($params[$i]);
            $paramidx = $i;
        }
    }
    $paramidx++;

    $decodedTitle = str_replace("\\'","'", $decodedTitle);
    $searchArgs['search'] = $decodedTitle;
    $searchArgs['searchfields'] = array('title');
    $searchArgs['searchtype'] = 'equal whole string';

    // Get the articles via a search
    $articles = xarModAPIFunc('articles', 'user', 'getall', $searchArgs);
    $spacecode= xarModGetVar('base','urlspaces')?xarModGetVar('base','urlspaces'):'_';
    if( (count($articles) == 0) && (strpos($decodedTitle,$spacecode) !== false) ) {
        $searchArgs['search'] = str_replace($spacecode,' ',$decodedTitle);
        $searchArgs['searchfields'] = array('title');
        $searchArgs['searchtype'] = 'equal whole string';
        $articles = xarModAPIFunc('articles', 'user', 'getall', $searchArgs);
    }

    if( count($articles) == 1 ) {
        $theArticle = $articles[0];
    } else {
        // NOTE: We could probably just loop through the various dupe detection methods rather then
        // pulling from a config variable.  This would allow old URLs encoded using one system
        // to keep working even if the configuration changes.
        switch( $dupeResolutionMethod )
        {
            case 'Append AID':
                // Look for AID appended after title
                if( !empty($params[$paramidx]) )
                {
                    foreach ($articles as $article)
                    {
                        if( $article['aid'] == $params[$paramidx] )
                        {
                            $theArticle = $article;
                            break;
                        }
                    }
                }
                break;

            case 'Append Date':
                // Look for date appended after title
                if( !empty($params[$paramidx]) )
                {
                    foreach ($articles as $article)
                    {
                        if( date('Y-m-d H:i',$article['pubdate']) == $params[$paramidx] )
                        {
                            $theArticle = $article;
                            break;
                        }
                    }
                }
                break;

            case 'ALL':
                if( !empty($params[$paramidx]) )
                {
                    foreach ($articles as $article)
                    {
                        if( date('Y-m-d H:i',$article['pubdate']) == $params[$paramidx] )
                        {
                            $theArticle = $article;
                            break;
                        } else if( $article['aid'] == $params[$paramidx] )
                        {
                            $theArticle = $article;
                            break;
                        }
                    }
                }
                break;

            case 'Ignore':
            default:
                // Just use the first one that came back
                if (!empty($articles)) {
                    $theArticle = $articles[0];
                }
        }
    }

    if( !empty($theArticle) )
    {
        $aid = $theArticle['aid'];
        return $aid;
    }
}

?>
