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
 * Import an object definition or an object item from XML
 */
function articles_adminapi_importpubtype($args)
{
    // Security check - we require ADMIN rights here
    if (!xarSecurityCheck('AdminArticles')) return xarResponseForbidden();

    extract($args);

    if (empty($xml) && empty($file)) {
        $msg = xarML('Missing import file or XML content');
         throw new BadParameterException(null,$msg);
    } elseif (!empty($file) && (!file_exists($file) || !preg_match('/\.xml$/',$file)) ) {
        $msg = xarML('Invalid import file');
         throw new BadParameterException(null,$msg);
    }

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');

    $proptypes = xarModAPIFunc('dynamicdata','user','getproptypes');
    $name2id = array();
    foreach ($proptypes as $propid => $proptype) {
        $name2id[$proptype['name']] = $propid;
    }

    $prefix = xarDBGetSystemTablePrefix();
    $prefix .= '_';

    if (!empty($file)) {
        $fp = @fopen($file, 'r');
        if (!$fp) {
            $msg = xarML('Unable to open import file');
             throw new BadParameterException(null,$msg);
        }
    } else {
        $lines = preg_split("/\r?\n/", $xml);
        $maxcount = count($lines);
    }

    $what = '';
    $count = 0;
    $ptid = 0;
    $objectname2objectid = array();
    $objectcache = array();
    $objectmaxid = array();
    while ( (!empty($file) && !feof($fp)) || (!empty($xml) && $count < $maxcount) ) {
        if (!empty($file)) {
            $line = fgets($fp, 4096);
        } else {
            $line = $lines[$count];
        }
        $count++;
        if (empty($what)) {
            if (preg_match('#<object name="(\w+)">#',$line,$matches)) { // in case we import the object definition
                $object = array();
                $object['name'] = $matches[1];
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) { // in case we only import data
                $what = 'item';
            }

         } elseif ($what == 'object') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($object[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object',xarVarPrepForDisplay($key),$count);
                    if (!empty($file)) fclose($fp);
                     throw new DuplicateException(null,$msg);
                    return;
                }
                $object[$key] = $value;
            } elseif (preg_match('#<config>#',$line)) {
                if (isset($object['config'])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','object','config',$count);
                    if (!empty($file)) fclose($fp);
                     throw new DuplicateException(null,$msg);
                    return;
                }
                $config = array();
                $what = 'config';
            } elseif (preg_match('#<properties>#',$line)) {
                if (empty($object['name']) || empty($object['moduleid'])) {
                    $msg = xarML('Missing keys in object definition');
                    if (!empty($file)) fclose($fp);
                    throw new DuplicateException(null,$msg);
                    return;
                }
                // make sure we drop the object id, because it might already exist here
                unset($object['objectid']);

                $properties = array();
                $what = 'property';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'config') {
            if (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($config[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','config',xarVarPrepForDisplay($key),$count);
                    if (!empty($file)) fclose($fp);
                    throw new DuplicateException(null,$msg);
                    return;
                }
                $config[$key] = $value;
            } elseif (preg_match('#</config>#',$line)) {
                // override default view if necessary
                $config['defaultview'] = 1;

                $object['config'] = serialize($config);
                $config = array();
                $what = 'object';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'property') {
            if (preg_match('#<property name="(\w+)">#',$line,$matches)) {
                $property = array();
                $property['name'] = $matches[1];
            } elseif (preg_match('#</property>#',$line)) {
                if (empty($property['name']) || empty($property['type'])) {
                    $msg = xarML('Missing keys in property definition');
                    if (!empty($file)) fclose($fp);
                     throw new DuplicateException(null,$msg);
                    return;
                }
                // make sure we drop the property id, because it might already exist here
                unset($property['id']);

            // TODO: watch out for multi-sites
                // replace default xar_* table prefix with local one
                $property['source'] = preg_replace("/^xar_/",$prefix,$property['source']);

                // add this property to the list
                $properties[] = $property;

            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($property[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','property',xarVarPrepForDisplay($key),$count);
                    if (!empty($file)) fclose($fp);
                     throw new DuplicateException(null,$msg);
                    return;
                }
                $property[$key] = $value;
            } elseif (preg_match('#</properties>#',$line)) {

                // 1. make sure we have a unique pubtype name
                foreach ($pubtypes as $pubid => $pubtype) {
                    if ($object['name'] == $pubtype['name']) {
                        $object['name'] .= '_' . time();
                        break;
                    }
                }

                // 2. fill in the pubtype field config
                $fields = array();
                $extra = array();
                foreach ($properties as $property) {
                    $field = $property['name'];
                    switch($field) {
                        case 'aid':
                        case 'pubtypeid':
                            // skip these
                            break;

                        case 'title':
                        case 'summary':
                        case 'body':
                        case 'notes':
                        case 'authorid':
                        case 'pubdate':
                        case 'checkouttime':
                        case 'checkout':
                        case 'editor':
                        case 'status':
                            // convert property type to string if necessary
                            if (is_numeric($property['type'])) {
                                if (isset($proptypes[$property['type']])) {
                                    $property['type'] = $proptypes[$property['type']]['name'];
                                } else {
                                    $property['type'] = 'static';
                                }
                            }
                            // reset disabled field labels to empty
                            if (empty($property['status'])) {
                                $property['label'] = '';
                            }
                            if (!isset($property['validation'])) {
                                $property['validation'] = '';
                            }
                            $fields[$field] = array('label' => $property['label'],
                                                    'format' => $property['type'],
                                                    'input' => $property['input'],
                                                    'validation' => $property['validation'],
                                                    );
                            break;

                        default:
                            // convert property type to numeric if necessary
                            if (!is_numeric($property['type'])) {
                                if (isset($name2id[$property['type']])) {
                                    $property['type'] = $name2id[$property['type']];
                                } else {
                                    $property['type'] = 1;
                                }
                            }
                            $extra[] = $property;
                            break;
                    }
                }

                // 3. create the pubtype
                $ptid = xarModAPIFunc('articles','admin','createpubtype',
                                      array('name' => $object['name'],
                                            'descr' => $object['label'],
                                            'config' => $fields));
                if (empty($ptid)) return;

                // 4. set the module variables
                xarModSetVar('articles', 'settings.'.$ptid, $object['config']);
                xarModSetVar('articles', 'number_of_categories.'.$ptid, 0);
                xarModSetVar('articles', 'mastercids.'.$ptid, '');

                // 5. create a dynamic object if necessary
                if (count($extra) > 0) {
                    $object['itemtype'] = $ptid;
                    $object['config'] = '';
                    $object['isalias'] = 0;
                    $objectid = xarModAPIFunc('dynamicdata','admin','createobject',
                                              $object);
                    if (!isset($objectid)) {
                        if (!empty($file)) fclose($fp);
                        return;
                    }

                    // 6. create the dynamic properties
                    foreach ($extra as $property) {
                        $property['objectid'] = $objectid;
                        $property['moduleid'] = $object['moduleid'];
                        $property['itemtype'] = $object['itemtype'];

                        $prop_id = xarModAPIFunc('dynamicdata','admin','createproperty',
                                                 $property);
                        if (!isset($prop_id)) {
                            if (!empty($file)) fclose($fp);
                            return;
                        }
                    }

                    // 7. check if we need to enable DD hooks for this pubtype
                    if (!xarModIsHooked('dynamicdata','articles')) {
                        xarModAPIFunc('modules','admin','enablehooks',
                                      array('callerModName' => 'articles',
                                            'callerItemType' => $ptid,
                                            'hookModName' => 'dynamicdata'));
                    }
                }

                $properties = array();
                $what = 'object';
            } elseif (preg_match('#<items>#',$line)) {
                $what = 'item';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
                // multi-line entries not relevant here
            }

        } elseif ($what == 'item') {
/* skip this for articles
            if (preg_match('#<([^> ]+) itemid="(\d+)">#',$line,$matches)) {
                // find out what kind of item we're dealing with
                $objectname = $matches[1];
                $itemid = $matches[2];
                if (empty($objectname2objectid[$objectname])) {
                    $objectinfo = Dynamic_Object_Master::getObjectInfo(array('name' => $objectname));
                    if (isset($objectinfo) && !empty($objectinfo['objectid'])) {
                        $objectname2objectid[$objectname] = $objectinfo['objectid'];
                    } else {
                        $msg = xarML('Unknown #(1) "#(2)" on line #(3)','object',xarVarPrepForDisplay($objectname),$count);
                        if (!empty($file)) fclose($fp);
                        throw new BadParameterException(null,$msg);
                        return;
                    }
                }
                $objectid = $objectname2objectid[$objectname];
                $item = array();
                // don't save the item id for now...
            // TODO: keep the item id if we set some flag
                //$item['itemid'] = $itemid;
                $closeitem = $objectname;
                $closetag = 'N/A';
            } elseif (preg_match("#</$closeitem>#",$line)) {
                // let's create the item now...
                if (!isset($objectcache[$objectid])) {
                    $objectcache[$objectid] = new Dynamic_Object(array('objectid' => $objectid));
                }
                // set the item id to 0
            // TODO: keep the item id if we set some flag
                $item['itemid'] = 0;
                // create the item
                $itemid = $objectcache[$objectid]->createItem($item);
                if (empty($itemid)) {
                    if (!empty($file)) fclose($fp);
                    return;
                }
                // keep track of the highest item id
                if (empty($objectmaxid[$objectid]) || $objectmaxid[$objectid] < $itemid) {
                    $objectmaxid[$objectid] = $itemid;
                }
                $closeitem = 'N/A';
                $closetag = 'N/A';
            } elseif (preg_match('#<([^>]+)>(.*)</\1>#',$line,$matches)) {
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2) on line #(3)','item',xarVarPrepForDisplay($key),$count);
                    if (!empty($file)) fclose($fp);
                    throw new DuplicateException(null,$msg);
                    return;
                }
                $item[$key] = $value;
                $closetag = 'N/A';
            } elseif (preg_match('#<([^/>]+)>(.*)#',$line,$matches)) {
                // multi-line entries *are* relevant here
                $key = $matches[1];
                $value = $matches[2];
                if (isset($item[$key])) {
                    $msg = xarML('Duplicate definition for #(1) key #(2)','item',xarVarPrepForDisplay($key));
                    if (!empty($file)) fclose($fp);
                     throw new DuplicateException(null,$msg);
                    return;
                }
                $item[$key] = $value;
                $closetag = $key;
            } elseif (preg_match("#(.*)</$closetag>#",$line,$matches)) {
                // multi-line entries *are* relevant here
                $value = $matches[1];
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    if (!empty($file)) fclose($fp);
                    return;
                }
                $item[$closetag] .= $value;
                $closetag = 'N/A';
            } elseif ($closetag != 'N/A') {
                // multi-line entries *are* relevant here
                if (!isset($item[$closetag])) {
                    $msg = xarML('Undefined #(1) key #(2)','item',xarVarPrepForDisplay($closetag));
                    if (!empty($file)) fclose($fp);
                    throw new BadParameterException(null,$msg);;
                    return;
                }
                $item[$closetag] .= $line;
            } elseif (preg_match('#</items>#',$line)) {
skip this for articles */
            if (preg_match('#</items>#',$line)) {
                $what = 'object';
            } elseif (preg_match('#</object>#',$line)) {
                $what = '';
            } else {
            }
        } else {
        }
    }
    if (!empty($file)) {
        fclose($fp);
    }
     xarLogMessage('ARTICLES: pubtype '. $ptid.' imported by '.xarSession::getVar('uid'),XARLOG_LEVEL_AUDIT);
    return $ptid;
}

?>
