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
 * get array of field formats for publication types
 * @TODO : move this to some common place in Xaraya (base module ?)
 * + replace with dynamic_propertytypes table
 *
 * + extend with other pre-defined formats
 * @return array('static'  => xarML('Static Text'),
                 'textbox' => xarML('Text Box'),
                 ...);
 */
function articles_userapi_getpubfieldformats($args)
{
    $fieldlist=array(
        'static'          => xarML('Static Text'),
        'textbox'         => xarML('Text Box'),
        'textarea_small'  => xarML('Text Area - Small'),
        'textarea_medium' => xarML('Text Area - Medium'),
        'textarea_large'  => xarML('Text Area - Large'),
        'dropdown'        => xarML('Dropdown List'),
        'countrylisting ' => xarML('Dropdown List - Countries'),
        'statelisting '  => xarML('Dropdown List - States'),
        'textupload'      => xarML('Text Upload'),
        'fileupload'      => xarML('File Upload'),
        'url'             => xarML('URL'),
        'urltitle'        => xarML('URL + Title'),
        'urlicon'         => xarML('URL  + Icon'),
        'image'           => xarML('Image'),
        'imagelist'       => xarML('Image List'),
        'username'        => xarML('Username'),
        'userlist'        => xarML('User List'),
        'grouplist'       => xarML('Group List'),
        'integerbox'      => xarML('Number - Integer Box'),
        'floatbox'        => xarML('Number - Float Box'),
        'calendar'        => xarML('Calendar'),
        'webpage'         => xarML('HTML Page'),
        'status'          => xarML('Status'),
        'language'        => xarML('Language List'),
        'email'           => xarML('Email'),
        'checkbox'        => xarML('Checkbox'),
        'checkboxlist'    => xarML('Checkboxlist'),

    // TODO: add more property types after testing
   //other 'text' DD property types won't give significant performance hits
    );

    // Add  'text' dd properites that are dependent on module availability
    $extrafields=array();
    if (xarModIsAvailable('tinymce')) {
        $extrafields=array('xartinymce'=> xarML('xarTinyMCE GUI'));
        $fieldlist=array_merge($fieldlist,$extrafields);
    }
    asort($fieldlist);
    return $fieldlist;
}

?>
