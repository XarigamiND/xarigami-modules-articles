<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2008-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 */
/**
 * get array of field formats numbers for publication types
 * @TODO : move this to some common place in Xaraya (base module ?)
 * + replace with dynamic_propertytypes table
 * + extend with other pre-defined formats
 * @return array('static'  => 1,
                 'textbox' => 2,
                 ...);
 */
function articles_userapi_getfieldformatnums($args)
{
    $fieldnames= array(
        'static'          => 1,
        'textbox'         => 2,
        'textarea_small'  => 3,
        'textarea_medium' => 4,
        'textarea_large'  => 5,
        'dropdown'        => 6,
        'countrylisting'  => 42,
        'statelisting'    => 43,
        'textupload'      => 38,
        'fileupload'      => 9,
        'url'             => 11,
        'urltitle'        => 41,
        'urlicon'         => 27,
        'image'           => 12,
        'imagelist'       => 35,
        'username'        => 7,
        'userlist'        => 37,
        'grouplist'       => 45,
        'integerbox'      => 15,
        'floatbox'        => 17,
        'calendar'        => 8,
        'webpage'         => 13,
        'status'          => 10,
        'language'        => 36,
        'email'           => 26,
        'checkbox'        => 14,
        'checkboxlist'    => 1115

    // TODO: add more property types after testing
    //other 'text' DD property types won't give significant performance hits
    );
    // Add  'text' dd properites that are dependent on module availability
    $fielditem=array();

    if (xarModIsAvailable('tinymce')) {
        $fielditems=array('xartinymce' => 205);
        $fieldnames=array_merge($fieldnames,$fielditems);
    }


return $fieldnames;

}

?>