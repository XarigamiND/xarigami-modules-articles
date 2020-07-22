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
 * @author mikespub
 */
/**
 * show some predefined form field in a template
 *
 * @param $args array containing the definition of the field (type, name, value, ...)
 * @return string containing the HTML (or other) text to output in the BL template
 * @TODO move this to some common place in Xaraya (base module ?)
 */
function articles_userapi_showfield($args)
{
    if (empty($args['type']) || $args['type'] != 'fieldtype') {
        // let DynamicData handle it
        return xarModAPIFunc('dynamicdata','admin','showinput',$args);
    }

    extract($args);
    if (empty($name)) {
        return xarML('Missing \'name\' attribute in field tag or definition');
    }
    if (!isset($type)) {
        $type = 'text';
    }
    if (!isset($value)) {
        $value = '';
    }
    if (!isset($id)) {
        $id = '';
    } else {
        $id = ' id="'.$id.'"';
    }
    if (!isset($tabindex)) {
        $tabindex = '';
    } else {
        $tabindex = ' tabindex="'.$tabindex.'"';
    }

    // Note: if we want to re-use this for dynamic data, types are numeric there
    if (is_numeric($type)) {
        $fieldformatnums = xarModAPIFunc('articles','user','getfieldformatnums');
        foreach ($fieldformatnums as $fname => $fid) {
            if ($fid == $type) {
                $type = $fname;
                break;
            }
        }
    }

    $output = '';
    switch ($type) {
    // yes, we auto-declare the allowed field types here too :-)
        case 'fieldtype':
            // Get the list of defined field formats
            $pubfieldformats = xarModAPIFunc('articles','user','getpubfieldformats');

        // Note: if we want to re-use this for dynamic data, id's need to be numeric
            if (!empty($numeric_id)) {
                $fieldformatnums = xarModAPIFunc('articles','user','getfieldformatnums');
            }

            $output .= '<select name="'.$name.'"'.$id.$tabindex.'>';
            foreach ($pubfieldformats as $fid => $fname) {
                if (!empty($numeric_id)) {
                    $numid = $fieldformatnums[$fid];
                    $output .= '<option value="'.$numid.'"';
                    if ($numid == $value) {
                        $output .= ' selected="selected"';
                    }
                } else {
                    $output .= '<option value="'.$fid.'"';
                    if ($fid == $value) {
                        $output .= ' selected="selected"';
                    }
                }
                $output .= '>'.$fname.'</option>';
            }
            $output .= '</select>';
            break;
        default:
            $output .= 'Unknown type '.$type;
            break;
    }
    return $output;
}

?>
