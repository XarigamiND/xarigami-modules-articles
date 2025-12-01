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
 * @author lion, 2024
 */

// NOTE: this has been totally rewritten in 2024 (lion)
// The original approach was to identify as many of the URL parts
// as possible, and then display something, regardless if the URL
// had a lot of other bogus parts, e.g. coming from a broken
// indexing bot. That made totally random URLs exist as a real
// page and displayed based on the identified parts.
//
// The new approach is to strictly stick to URLs produced by
// encude_shorturl.php, and anything outside that will display an
// error 404 page.
//
// That also needs a little change in core system that calls this
// function, so that null can be returned to indicate failure of
// decoding.
//
// Other changes:
// The module setting for appending date or ID to idential article
// titles is now totally ignored. This code had a note to do that
// because then old URLs after changing the setting will still work.
// Words like "index", "monthview" were case insensitive, now aren't
// and were identified as "starts with" I think.

/**
 * Class to handle segments of a short URL path (parts between /).
 * Every segment is an insteance of this class, and is collected
 * into an array. The contructror uses strict rules to identify
 * segments based on position, content, and preceededing segment.
 * Always one of the type-bools are true and if true, their extra
 * properties will have a valid value.
 */
class ArticleUrlSegment
{
    public static $pubtypes = array(); // <* Associative array, name => id
    public static $module = 'articles';

    // Always only ONE of these bools can be true
    // When true, the corresponding data field is set
    public bool $isFinal = false; // <* Whether URL must end with this segment

    public bool $isModule = false;
    public bool $isFrontpage = false; // <* When module uses /frontpage/ as main URL
    public bool $isIndex = false;

    public bool $isPubType = false;
    public int $pubTypeID = 0;
    public string $pubTypeName = '';

    public bool $isCategory = false;
    public string $category = ''; // <* Original string
    public bool $subCategoriesToo = false; // <* Whether user clicked an "include subcategories"

    public bool $isByAuthor = false;
    public bool $isAuthorID = false;
    public int $authorID = 0;

    public bool $isMonthView = false;
    public bool $isMonthViewAll = false;
    public bool $isMonthViewYear = false;
    public int $monthViewYear = 0;
    public bool $isMonthViewMonth = false;
    public int $monthViewMonth = 0;

    public bool $isSearch = false;
    public bool $isSearchCategory = false;
    public string $searchCategory = '';

    public bool $isMap = false;

    public bool $isRedirect = false;
    public bool $isRedirectAid = false;
    public int $redirectAid;

    public bool $isArticleID = false;
    public int $articleID = 0;

    public bool $isTitle = false;
    public string $title = '';

    public bool $isAppendixDate = false; // <* Colliding title's date
    public string $appendixDate = '';

    public bool $isAppendixID = false; // <* Colliding title's article ID
    public int $appendixID = 0;

    /**
     * Initalizes a new short URL segment (i.e. a part between "/" chars)
     *
     * $index int Zero based index of this segment in the URL.
     * $segment string The URL path component that is being processed.
     * $prev ArticleUrlSegment Previous segment, or null if this is the first
     * $nextStr string Next segment, not parsed yet, null if there is no more.
     */
    function __construct($index, $segment, $prev, $nextStr)
    {
        // view
        // / ( PUBTYPE | articles/PUBTYPE | articles ) / by_author / USERID
        // / articles / c CATID / PUBTYPE /
        // / ( PUBTYPE | articles/PUBTYPE ) / c CATID /
        // / articles / c CATID /
        // / ( PUBTYPE | articles/PUBTYPE ] /

        // Module name itself as first item
        if($index == 0 && $segment == ArticleUrlSegment::$module) {
            $this->isModule = true;
            return;
        }
        // Module uses "frontpage" as main short URL (won't check for exact word, just "not a pubtype but module alias")
        if($index == 0 && !array_key_exists($segment, ArticleUrlSegment::$pubtypes)) {
            $alias = xarModGetAlias($segment); // A pubtype alias to articles module?
            if($alias == ArticleUrlSegment::$module) {
                $this->isFrontpage = true;
                $this->isFinal = true;
                return;
            }
        }
        // Module alias (pubtype) as first item
        if($index == 0 && array_key_exists($segment, ArticleUrlSegment::$pubtypes)) {
            $alias = xarModGetAlias($segment); // A pubtype alias to articles module?
            if($alias == ArticleUrlSegment::$module) {
                $this->isPubType = true;
                $this->pubTypeID = ArticleUrlSegment::$pubtypes[$segment];
                return;
            }
        }
        // Index after pubtype or module
        if ($index == 1 && ($prev->isModule || $prev->isPubType) && $segment == 'index') {
            $this->isIndex = true;
            $this->isFinal = true;
            return;
        }
        // Pubtype after module
        if ($index == 1 && array_key_exists($segment, ArticleUrlSegment::$pubtypes) && $prev->isModule) {
            $this->isPubType = true;
            $this->pubTypeID = ArticleUrlSegment::$pubtypes[$segment];
            return;
        }
        // Pubtype name after category (bycat)
        if ($index == 2 && array_key_exists($segment, ArticleUrlSegment::$pubtypes) && $prev->isCategory) {
            $this->isPubType = true;
            $this->pubTypeID = ArticleUrlSegment::$pubtypes[$segment];
            return;
        }
        // Category reference(s)
        if (($index == 1 || $index == 2) && preg_match('/^c(_?[0-9 +-]+)$/',$segment,$matches)) {
            $this->isCategory = true;
            $this->category = $matches[1];
            $this->subCategoriesToo = strpos($this->category, '_') === 0;
            return;
        }
        // The word "by_author" (or translation of)
        if(($index == 1 || $index == 2) && $segment == xarML('by_author')) {
            $this->isByAuthor = true;
            return;
        }
        // The number after "by_author"
        if(($index == 2 || $index == 3) && $prev->isByAuthor && is_numeric($segment)) {
            $this->isAuthorID = true;
            $this->authorID = (int)$segment;
            $this->isFinal = true;
            return;
        }

        // other features
        // / ( PUBTYPE | articles/PUBTYPE | articles ) / monthview / [ ( all | YEAR / MONTH ) / ]
        // / ( PUBTYPE | articles/PUBTYPE | articles ) / map /
        // / ( PUBTYPE | articles/PUBTYPE | articles ) / search [ / c CID ]
        // / ( PUBTYPE | articles/PUBTYPE | articles ) / redirect / AID
        if(($index == 1 || $index == 2) && ($prev->isModule || $prev->isPubType) && $segment == 'monthview'
            && (empty($nextStr) || is_numeric($nextStr) || $nextStr == 'all')) {
            $this->isMonthView = true;
            return;
        }
        if(($index == 2 || $index == 3) && $prev->isMonthView && $segment == 'all') {
            $this->isMonthViewAll = true;
            $this->isFinal = true;
            return;
        }
        if(($index == 2 || $index == 3) && $prev->isMonthView && is_numeric($segment)) {
            $this->isMonthViewYear = true;
            $this->monthViewYear = $segment;
            return;
        }
        if(($index == 3 || $index == 4) && $prev->isMonthViewYear && is_numeric($segment)) {
            $this->isMonthViewMonth = true;
            $this->monthViewMonth = $segment;
            $this->isFinal = true;
            return;
        }
        if(($index == 1 || $index == 2) && ($prev->isModule || $prev->isPubType) && $segment == 'map' && empty($nextStr)) {
            $this->isMap = true;
            $this->isFinal = true;
            return;
        }
        if(($index == 1 || $index == 2) && ($prev->isModule || $prev->isPubType) && $segment == 'search' && empty($nextStr)) {
            $this->isSearch = true;
            return;
        }
        if (($index == 2 || $index == 3) && $prev->isSearch && preg_match('/^c(_?[0-9 +-]+)$/',$segment,$matches)) {
            $this->isSearchCategory = true;
            $this->searchCategory = $segment;
            return;
        }
        if(($index == 1 || $index == 2) && ($prev->isModule || $prev->isPubType) && $segment == 'redirect'
            && !empty($nextStr) && is_numeric($nextStr)) {
            $this->isRedirect = true;
            return;
        }
        if(($index == 2 || $index == 3) && $prev->isRedirect && is_numeric($segment) && empty($nextStr)) {
            $this->isRedirectAid = true;
            $this->redirectAid = (int)$segment;
            $this->isFinal = true;
            return;
        }

        // display
        // / ( PUBTYPE | articles/PUBTYPE ) / [ c CATID / ] ( Article_Title | AID )
        // / articles / [ c CATID / ] ( Article_Title | AID )

        // Article ID (with no title)
        if($index >= 1 && $index <= 3 && is_numeric($segment) &&
            ($prev->isModule || $prev->isPubType || $prev->isCategory)) {
            $this->isArticleID = true;
            $this->articleID = (int)$segment;
            $this->isFinal = true;
            return;
        }
        // Article Title
        if($index >= 1 && $index <= 3 &&
            ($prev->isModule || $prev->isPubType || $prev->isCategory)) {
            $this->isTitle = true;
            $this->title = $segment;
            return;
        }
        // Colliding article title appendix date
        if($index > 1 && $prev->isTitle && preg_match('/^\d+-\d+-\d+ \d+:\d+$/',$segment)) {
            $this->isAppendixDate = true;
            $this->appendixDate = $segment;
            $this->isFinal = true;
            return;
        }
        // Colliding article title appendix article ID
        if($index > 1 && $prev->isTitle && is_numeric($segment)) {
            $this->isAppendixID = true;
            $this->appendixID = $segment;
            $this->isFinal = true;
            return;
        }
    }

    /**
     * Returns the parsed c45+46 string as list of IDs
     * and whether it's + or - in between them.
     */
    public function getCategories() {
        if(!$this->isCategory) { throw new Exception("Attempting to get categories on a non-category article short URL segment."); }

        if (strpos($this->category, '+') === false ) {
            $cids = explode('-',$this->category);
            $andcids = false;
        } else {
            $cids = explode('+',$this->category);
            $andcids = true;
        }
        return array($cids, $andcids);
    }
}

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

    // Fill pubtypes into the parser class
    ArticleUrlSegment::$pubtypes = array();
    $pubtypelist = xarModAPIFunc('articles','user','getpubtypes');
    foreach ($pubtypelist as $id => $pubtype) {
        ArticleUrlSegment::$pubtypes[$pubtype['name']] = $id;
    }

    // Load up each URL path segment into the parser class
    $p = array();
    for($i = 0; $i < count($params); $i++) {
        $p[$i] = new ArticleUrlSegment($i, $params[$i],($i == 0 ? null : $p[$i-1]), (count($params)>$i+2 ? $params[$i+1] : null));
        if($p[$i]->isFinal) { break; }
    }

    $pc = count($p);

    // Give up if there are unprocessable URL segments or empty result
    if(count($params) != $pc || $pc == 0) { return null; }

    $last = $p[$pc-1];

    // Main page if no extra parameters
    // / articles
    if($pc == 1 && $last->isModule) {
        return array('view', $args);
    }
    // / frontpage
    if($pc == 1 && $last->isFrontpage) {
        return array('view', $args);
    }
    // / ( articles | PUBTYPE ) / index
    if($pc == 2 && $last->isIndex) {
        return array('main', $args);
    }

    // If first or second is pubtype, store in advance
    if($pc >= 1 && $p[0]->isPubType) {
        $args['ptid'] = $p[0]->pubTypeID;
    } elseif ($pc >= 2 && $p[1]->isPubType) {
        $args['ptid'] = $p[1]->pubTypeID;
    }

    // view
    // (This list is from encode_shorturl.php)
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / by_author / USERID
    // / articles / c CATID / PUBTYPE /
    // / ( PUBTYPE | articles/PUBTYPE ) / c CATID /
    // / articles / c CATID /
    // / ( PUBTYPE | articles/PUBTYPE ] /
    // / ( frontpage | articles ) /

    // / ( PUBTYPE | articles/PUBTYPE | articles ) / by_author / USERID
    // / PUBTYPE / by_author / USERID
    if($pc == 3 && $p[0]->isPubType && $p[1]->isByAuthor && $p[2]->isAuthorID) {
        $args['authorid'] = $p[2]->authorID;
        return array('view', $args);
    }
    // articles / PUBTYPE / by_author / USERID
    if($pc == 4 && $p[0]->isModule && $p[1]->isPubType && $p[2]->isByAuthor && $p[3]->isAuthorID) {
        $args['authorid'] = $p[3]->authorID;
        return array('view', $args);
    }
    // articles / by_author / USERID
    if($pc == 3 && $p[0]->isModule && $p[1]->isByAuthor && $p[2]->isAuthorID) {
        $args['authorid'] = $p[3]->authorID;
        return array('view', $args);
    }

    // / articles / c CATID / PUBTYPE /
    if($pc == 3 && $p[0]->isModule && $p[1]->isCategory && $p[2]->isPubType) {
        $args['catid'] = $p[1]->category;
        list($args['cids'], $args['andcids']) = $p[1]->getCategories();
        $args['ptid'] = $p[2]->pubTypeID;
        $args['bycat'] = 1;
        return array('view', $args);
    }

    // / ( PUBTYPE | articles/PUBTYPE ) / c CATID /
    // PUBTYPE / c CATID
    if($pc == 2 && $p[0]->isPubType && $p[1]->isCategory) {
        $args['catid'] = $p[1]->category;
        list($args['cids'], $args['andcids']) = $p[1]->getCategories();
        return array('view', $args);
    }
    // articles / PUBTYPE / c CATID
    if($pc == 3 && $p[0]->isModule && $p[1]->isPubType && $p[2]->isCategory) {
        $args['catid'] = $p[2]->category;
        list($args['cids'], $args['andcids']) = $p[2]->getCategories();
        return array('view', $args);
    }

    // / articles / c CATID /
    if($pc == 2 && $p[0]->isModule && $p[1]->isCategory) {
        $args['catid'] = $p[1]->category;
        list($args['cids'], $args['andcids']) = $p[1]->getCategories();
        return array('view', $args);
    }

    // / ( PUBTYPE | articles/PUBTYPE ] /
    // / articles / PUBTYPE /
    if($pc == 2 && $p[0]->isModule && $p[1]->isPubType) {
        return array('view', $args);
    }
    // / PUBTYPE
    if($pc == 1 && $p[0]->isPubType) {
        return array('view', $args);
    }

    // other features
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / monthview / [ ( all | YEAR / MONTH ) / ]
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / map /
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / search /
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / redirect / AID

    // / ( PUBTYPE | articles/PUBTYPE | articles ) / monthview / [ ( all | YEAR / MONTH ) / ]
    $mv = 0;
    if($pc >= 2 && $p[1]->isMonthView) { $mv = 1; }
    if($pc >= 3 && $p[2]->isMonthView) { $mv = 2; }
    if($mv) {
        if($pc == $mv + 1) {
            return array('monthview', $args);
        } elseif($pc == $mv + 2 && $p[$mv+1]->isMonthViewAll) {
            $args['month'] = 'all';
            return array('monthview', $args);
        } elseif($pc == $mv + 3 && $p[$mv+1]->isMonthViewYear && $p[$mv+2]->isMonthViewMonth) {
            $args['month'] = $p[$mv+1]->monthViewYear.'-'.$p[$mv+2]->monthViewMonth;
            return array('monthview', $args);
        }
    }

    // / ( PUBTYPE | articles/PUBTYPE | articles ) / map /
    if(($pc == 2 || $pc == 3) && $p[$pc-1]->isMap) {
        return array('viewmap', $args);
    }

    // / ( PUBTYPE | articles/PUBTYPE | articles ) / search /
    // / ( PUBTYPE | articles/PUBTYPE | articles ) / search / c CATID
    if($p[$pc-1]->isSearch) {
        return array('search', $args);
    }
    if($p[$pc-1]->isSearchCategory) {
        $args['catid'] = $p[$pc-1]->searchCategory;
        return array('search', $args);
    }

    // / ( PUBTYPE | articles/PUBTYPE | articles ) / redirect / AID
    if(($pc == 3 || $pc == 4) &&  $p[$pc-2]->isRedirect && $p[$pc-1]->isRedirectAid) {
        $args['aid'] = $p[count($p-1)]->redirectAid;
        return array('redirect', $args);
    }


    // display
    // (This list is from encode_shorturl.php)
    // / ( PUBTYPE | articles/PUBTYPE ) / [ c CATID / ] ( Article_Title | AID )
    // / articles / [ c CATID / ] ( Article_Title | AID )

    // / ( PUBTYPE | articles/PUBTYPE ) / [ c CATID / ] ( Article_Title | AID )
    // / PUBTYPE  / Article_Title
    // / PUBTYPE  / Article_Title / appendix
    // / PUBTYPE / AID
    // / PUBTYPE / c CATID / Article_Title
    // / PUBTYPE / c CATID / Article_Title / appendix
    // / PUBTYPE / c CATID / AID
    // / articles / PUBTYPE  / Article_Title
    // / articles / PUBTYPE  / Article_Title / appendix
    // / articles / PUBTYPE / AID
    // / articles / PUBTYPE / c CATID / Article_Title
    // / articles / PUBTYPE / c CATID / Article_Title / appendix
    // / articles / PUBTYPE / c CATID / AID

    // / articles / [ c CATID / ] ( Article_Title | AID )
    // / articles / / Article_Title
    // / articles / / Article_Title / appendix
    // / articles / / AID
    // / articles / c CATID / Article_Title
    // / articles / c CATID / Article_Title / appendix
    // / articles / c CATID / AID

    // Accept category in 1-2
    for($i = 1; $i < 3; $i++) {
        if($pc > $i && $p[$i]->isCategory) {
            $args['catid'] = $p[$i]->category;
            list($args['cids'], $args['andcids']) = $p[$i]->getCategories();
        }
    }
    // Accept article ID in 1-3 and finish
    for($i = 1; $i < 4; $i++) {
        if($pc == $i+1 && $p[$i]->isArticleID) {
            $args['aid'] = $p[$i]->articleID;
            return array('display', $args);
        }
    }

    // Accept article title in 1-3 and use appendix if available
    for($i = 1; $i < 4; $i++) {
        if($pc > $i && $p[$i]->isTitle) {

            // If an ID is appended after title, we are done
            if($pc == $i+2 && $p[$i+1]->isAppendixID) {
                $args['aid'] = $p[$i+1]->appendixID;
                return array('display', $args);
            }

            // Search based on Article Title
            $title = $p[$i]->title;
            $date = '';
            $idxlimit = 1;
            // If date is after title, take that too
            if($pc == $i+2 && $p[$i+1]->isAppendixDate) {
                $date = $p[$i+1]->appendixDate;
                $idxlimit++;
            }
            $aid = articles_decodeAIDUsingTitle($title, $date);
            if($pc == $i+$idxlimit && $aid > 0) {
                $args['aid'] = $aid;
                return array('display', $args);
            }
        }
    }
    return null;
}

/**
 * Find the article ID by its title.
 * @access private
 * @return int aid The article ID
 * @todo bug 5878 Why does a title need higher privileges than the usual aid in a short title?
 */
function articles_decodeAIDUsingTitle( $title, $appendixDate)
{
    $decodedTitle = urldecode($title);

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

    // One result by title, return it
    if( count($articles) == 1 ) {
        return $articles[0]['aid'];
    }
    // Find in matching titles with help of date
    foreach ($articles as $article)
    {
        if( date('Y-m-d H:i',$article['pubdate']) == $appendixDate)
        {
            return $article['aid'];
        }
    }

    // No better, just use the first one that came back
    if (!empty($articles)) {
        return $articles[0]['aid'];
    }

    return 0;
}

?>
