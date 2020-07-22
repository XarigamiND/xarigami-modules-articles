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
 * get array of (fixed) field types for publication types
 * @TODO : add dynamic fields here for .81+
 * @return array('title'   => 'string',
                 'summary' => 'text',
                 ...);
 */
function articles_userapi_getpubfieldtypes($args)
{
    return array(
        'title'    => 'string',
        'summary'  => 'text',
        'notes'    => 'text',
        'body'     => 'text',
        'authorid' => 'integer',
        'pubdate'  => 'integer',
        'status'   => 'integer'/*,
        'checkout' => 'integer',
        'checkouttime' =>'integer',
        'editor'   => 'integer'*/
    );
}

?>
