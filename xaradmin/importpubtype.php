<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2007 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * Import an object definition or an object item from XML
 */
function articles_admin_importpubtype($args)
{
    if (!xarSecurityCheck('AdminArticles')) return xarResponseForbidden();

    if(!xarVarFetch('import', 'isset', $import,  NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('xml', 'isset', $xml,  NULL, XARVAR_DONT_SET)) {return;}

    extract($args);

    $data = array();
   //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks');
        
    $data['menutitle'] = xarML('Dynamic Data Utilities');

    $data['warning'] = '';
    $data['options'] = array();

    $basedir = 'modules/articles';
    $filetype = 'xml';
    $files = xarModAPIFunc('dynamicdata','admin','browse',
                           array('basedir' => $basedir,
                                 'filetype' => $filetype));
    if (!isset($files) || count($files) < 1) {
        $files = array();
        $data['warning'] = xarML('There are currently no XML files available for import in "#(1)"',$basedir);
    }

    if (!empty($import) || !empty($xml)) {
        if (!xarSecConfirmAuthKey()) return;

        if (!empty($import)) {
            $found = '';
            foreach ($files as $file) {
                if ($file == $import) {
                    $found = $file;
                    break;
                }
            }
            if (empty($found) || !file_exists($basedir . '/' . $file)) {
                $msg = xarML('File not found');
                 throw new BadParameterException(null,$msg);
            }
            $ptid = xarModAPIFunc('articles','admin','importpubtype',
                                  array('file' => $basedir . '/' . $file));
        } else {
            $ptid = xarModAPIFunc('articles','admin','importpubtype',
                                  array('xml' => $xml));
        }
        if (empty($ptid)) return;

        $data['warning'] = xarML('Publication type #(1) was successfully imported',$ptid);
    }

    natsort($files);
    array_unshift($files,'');
    foreach ($files as $file) {
         $data['options'][] = array('id' => $file,
                                    'name' => $file);
    }

    $data['authid'] = xarSecGenAuthKey();
    return $data;
}

?>
