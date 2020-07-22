<?php
/**
 * Articles module
 *
 * @package modules
 * @copyright (C) 2002-2008 The Digital Development Foundation
 * @license GPL {@link http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2007-2011 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
 */
/**
 * View articles for an administrator. This function shows a page from which articles can be managed
 *
 * @param int startnum Defaults to 1
 * @param int ptid OPTIONAL
 * @param status OPTIONAL
 * @param int itemtype OPTIONAL
 * @param catid OPTIONAL
 * @param int authorid OPTIONAL
 * @param lang OPTIONAL
 * @param pubdate OPTIONAL
 * @return mixed. Calls the template function to show the article listing.
 */
function articles_admin_view($args)
{
    // Get parameters
    if(!xarVarFetch('startnum', 'isset', $startnum, 1,    XARVAR_NOT_REQUIRED)) {return;}
    if(!xarVarFetch('ptid',     'isset', $ptid,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('ptidselect','isset', $ptidselect,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('status',   'isset', $status,   NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('itemtype', 'isset', $itemtype, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('catid',    'isset', $catid,    NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('authorid', 'isset', $authorid, NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('lang',     'isset', $lang,     NULL, XARVAR_DONT_SET)) {return;}
    if(!xarVarFetch('pubdate',  'str:1', $pubdate,  NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('editor',  'id', $editor,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('checkout','int:0', $checkout,   NULL, XARVAR_NOT_REQUIRED)) {return;}
    if (!xarVarFetch('order',   'pre:trim:alpha:lower:enum:asc:desc', $order, NULL,  XARVAR_DONT_SET)) return;
    if(!xarVarFetch('sort',     'isset',  $sort,     '', XARVAR_DONT_SET)) {return;}
    if (isset($ptidselect)) $ptid = $ptidselect;
    extract($args);

    $pubtypes = xarModAPIFunc('articles','user','getpubtypes');
    $oldlastview = @unserialize(xarSessionGetVar('Articles.LastView'));

    $sort  = isset($sort) && !empty($sort) ?$sort : 'pubdate' ;
    $order = isset($sort) && !empty($order) ? $order :'DESC';

    //we can come from a sort or pager
    $sortorder = $sort.' '.strtoupper($order);

    // Default parameters
    if (!isset($ptid)) {
        if (!empty($itemtype) && is_numeric($itemtype)) {
            // when we use some categories filter
            $ptid = $itemtype;
        } else {
            // we default to this for convenience
            $default = xarModGetVar('articles','defaultpubtype');
            if (!empty($default) && !xarSecurityCheck('EditArticles',0,'Article',"$default:All:All:All")) {
                // try to find some alternate starting pubtype if necessary
                foreach ($pubtypes as $id => $pubtype) {
                    if (xarSecurityCheck('EditArticles',0,'Article',"$id:All:All:All")) {
                        $ptid = $id;
                        break;
                    }
                }
            } else {
                $ptid = $default;
            }
        }
    }
    if (empty($ptid)) {
        $ptid = null;
    }
    $data = array();
    $data['ptid'] = $ptid;
    $data['authorid'] = $authorid;
    $data['language'] = $lang;
    $data['pubdate'] = $pubdate;

    if (!empty($catid)) {
        if (strpos($catid,' ')) {
            $cids = explode(' ',$catid);
            $andcids = true;
        } elseif (strpos($catid,'+')) {
            $cids = explode('+',$catid);
            $andcids = true;
        } else {
            $cids = explode('-',$catid);
            $andcids = false;
        }
    } else {
        $cids = array();
        $andcids = false;
    }
    $data['catid'] = $catid;

    if (empty($ptid)) {
        if (!xarSecurityCheck('EditArticles',0,'Article',"All:All:All:All")) {
            $msg = xarML('You do not have access levels to this  #(1) publication edit page',
                         'Articles');
            return xarResponseForbidden($msg);
        }
    } elseif (!is_numeric($ptid) || !isset($pubtypes[$ptid])) {
        $msg = xarML('Invalid publication type');
        throw new BadParameterException(null,$msg);
    } elseif (!xarSecurityCheck('EditArticles',0,'Article',"$ptid:All:All:All")) {
        $msg = xarML('You have no permission to edit #(1)',
                     $pubtypes[$ptid]['descr']);
        return xarResponseForbidden($msg);
    }

    if (!empty($ptid)) {
        $settings = unserialize(xarModGetVar('articles', 'settings.'.$ptid));
    } else {
        $string = xarModGetVar('articles', 'settings');
        if (!empty($string)) {
            $settings = unserialize($string);
        }
    }
    if (isset($settings['adminitemsperpage'])) {
        $numitems = $settings['adminitemsperpage'];
    } else {
        $numitems = 30;
    }
   //checkout functionality is off by default
    if (!isset($settings['usecheckoutin'])) $settings['usecheckoutin'] = 0;
    $usecheckoutin = $settings['usecheckoutin'];
    $data['usecheckoutin'] = $usecheckoutin; //pass through to template


    $labels = array();
    if (!empty($ptid)) {
        foreach ($pubtypes[$ptid]['config'] as $field => $value) {
            $labels[$field] = isset($value['label'])?$value['label']:'';
        }
    } else {
        $pubfields = xarModAPIFunc('articles','user','getpubfields');
        foreach ($pubfields as $field => $value) {
            $labels[$field] = $value['label'];
        }
    }
    $data['labels'] = $labels;

    // only show the date if this publication type has one
    $showdate = !empty($labels['pubdate']);
    if (!$showdate && ($sort == 'pubdate')) $sort = 'title';
    $data['showdate'] = $showdate;

    // only show the status if this publication type has one
    $showstatus = !empty($labels['status']);
                  // and if we're not selecting on it already
                  //&& (!is_array($status) || !isset($status[0]));
    if (!$showstatus && ($sort == 'status')) $sort = $title;
    $data['showstatus'] = $showstatus;

    $data['states'] = xarModAPIFunc('articles','user','getstates');

     //don't show monthview filter or tabs unless we are using archive
    $usearchiving = xarModGetVar('articles','usearchiving');
    //unset the 'archived' status filter
    if (!$usearchiving) {
        if (isset($data['states'][5])) unset($data['states'][5]);
    }


    $data['sort'] = $sort;
    $data['order'] = $order;

    // Get item information
    $articles = xarModAPIFunc('articles', 'user', 'getall',
                             array('startnum' => $startnum,
                                   'numitems' => $numitems,
                                   'ptid'     => $ptid,
                                   'authorid' => $authorid,
                                   'editor'   => $editor,
                                   'checkout' => $checkout,
                                   'language' => $lang,
                                   'pubdate'  => $pubdate,
                                   'cids'     => $cids,
                                   'andcids'  => $andcids,
                                   'sort'     => $sortorder,
                                   'status'   => $status));

    // Save the current admin view, so that we can return to it after update
    $lastview = array('ptid' => $ptid,
                      'authorid' => $authorid,
                      'editor' =>$editor,
                      'checkout' => $checkout,
                      'language' => $lang,
                      'catid' => $catid,
                      'status' => $status,
                      'pubdate' => $pubdate,
                      'order'   => $order,
                      'sort'=>  $sort,
                      'sortorder'=>$sortorder,
                      'startnum' => $startnum > 1 ? $startnum : null);
    xarSessionSetVar('Articles.LastView',serialize($lastview));

    //now in case of checkin check out and user is
    //get the current user
    if (xarUserIsLoggedIn()) {
       $thisuser=xarUserGetVar('uid');
    } else {
        $thisuser=_XAR_ID_UNREGISTERED;
    }
    //canmod - used to toggle the status checkboxes and status change drop down display on/off depending on user
    $data['canmod'] = xarSecurityCheck('ModerateArticles',0,'Article',$ptid.':All:All:All');
    $items = array();
    if ($articles != false) {
        foreach ($articles as $article) {

            $item = array();


            // Title and pubdate
            $item['title'] = $article['title'];
            $item['aid'] = $article['aid'];
            $item['checkouttime'] = $article['checkouttime'];
            $item['editor'] = $article['editor'];
            $item['checkout'] = $article['checkout'];

            if ($showdate) {
                $item['pubdate'] = $article['pubdate']; //strftime('%x %X %z', $article['pubdate']);
            }

            $hasstatus =  empty($pubtypes[$article['pubtypeid']]['config']['status']['label'])?FALSE:TRUE;

            if ($showstatus  && $hasstatus){
                //careful - archiving might be unset - set it manually but we dont want it in drop downs
                $item['status'] = isset($data['states'][$article['status']])?$data['states'][$article['status']]:xarML('ARCHIVED');
                if ( !isset($data['states'][$article['status']])) {
                $msg = xarML('Archived documents exist but Use Archiving is turned OFF in Modify Config');
                    xarSessionSetVar('statusmsg', $msg);
                }
                $item['numericstatus']= $article['status'];
                // pre-select all submitted items
                if ($article['status'] == 0) {
                    $item['selected'] = 'checked';
                } else {
                    $item['selected'] = '';
                }

            } elseif (isset($usecheckoutin) && ($usecheckoutin == 1))  {
               if ($article['checkout'] == 1) {
                   $item['selected'] = 'checked';
               }else {
                   $item['selected'] = '';
               }
            } else {
                $item['selected'] = '';
            }
            if (!isset($item['status'])) $item['status'] = xarML('--');
            if (!isset($item['numericstatus'])) $item['numericstatus'] = '';

            // Security check - do checkout in editor check here too
            $input = array();
            $input['article'] = $article;
            $input['mask'] = 'DeleteArticles';
            if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                if (isset($usecheckoutin) && ($usecheckoutin == 1) && ($article['checkout']==1) && ($thisuser != $item['editor'])) {
                    $item['deleteurl'] ='';
                    $item['editurl'] ='';
                    $item['cloneurl'] ='';
                } else {
                    $item['deleteurl'] = xarModURL('articles', 'admin','delete',
                                              array('aid' => $article['aid'],
                                                    'ptid'=>$article['pubtypeid'],
                                                    'authid'=> xarSecGenAuthKey()));
                    if ($showstatus && $hasstatus) {
                        $item['cloneurl'] = xarModURL('articles', 'admin','clone',
                                                      array('aid' => $article['aid'],
                                                            'ptid'=>$article['pubtypeid'],
                                                            'authid'=> xarSecGenAuthKey()));
                    }
                $item['editurl'] = xarModURL('articles', 'admin','modify',
                                            array('aid' => $article['aid'],
                                                  'ptid'=>$article['pubtypeid']));
                }
                $item['viewurl'] = xarModURL('articles', 'user','display',
                                            array('aid' => $article['aid'],
                                                  'ptid' => $article['pubtypeid']));
            } else {
                $item['deleteurl'] = '';
                $input['mask'] = 'AddArticles';

                if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                    if (isset($usecheckoutin) && ($usecheckoutin == 1) && ($article['checkout']==1) && ($thisuser != $item['editor'])) {
                        $item['cloneurl'] ='';
                    } else {
                        if ($showstatus && $hasstatus) {
                            $item['cloneurl'] = xarModURL('articles', 'admin','clone',
                                                      array('aid' => $article['aid'],
                                                            'ptid'=>$article['pubtypeid'],
                                                            'authid'=> xarSecGenAuthKey()));
                        }
                        $item['editurl'] = xarModURL('articles', 'admin','modify',
                                            array('aid' => $article['aid'],
                                                  'ptid'=>$article['pubtypeid']));
                    }

                     $item['viewurl'] = xarModURL('articles', 'user','display',
                                            array('aid' => $article['aid'],
                                                  'ptid' => $article['pubtypeid']));

                }else {
                    $item['cloneurl'] = '';
                    $input['mask'] = 'ModerateArticles';
                    if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                         if (isset($usecheckoutin) && ($usecheckoutin == 1) && ($article['checkout']==1) && ($thisuser != $item['editor'])) {
                            $item['cloneurl'] ='';
                        } else {
                        if ($showstatus && $hasstatus) {
                            $item['cloneurl'] = xarModURL('articles', 'admin','clone',
                                                      array('aid' => $article['aid'],
                                                            'ptid'=>$article['pubtypeid'],
                                                            'authid'=> xarSecGenAuthKey()));
                        }
                        $item['editurl'] = xarModURL('articles', 'admin','modify',
                                            array('aid' => $article['aid'],
                                                  'ptid'=>$article['pubtypeid']));
                        }
                        if (isset($usecheckoutin) && $usecheckoutin == 1 && $article['checkout']==1 && ($thisuser != $item['editor'])) {
                           $item['editurl'] = '';
                        } else {
                            $item['editurl'] = xarModURL('articles','admin','modify',
                                                    array('aid' => $article['aid'],'ptid'=>$article['pubtypeid']));
                        }
                        $item['viewurl'] = xarModURL('articles','user','display',
                                                    array('aid' => $article['aid'],
                                                          'ptid' => $article['pubtypeid']));
                    } else {
                        $item['editurl'] = '';
                        $input['mask'] = 'EditArticles';

                        if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                            if (isset($usecheckoutin) && $usecheckoutin == 1 && $article['checkout']==1 && ($thisuser != $item['editor'])) {
                                $item['editurl'] = '';
                            } else {
                                $item['editurl'] = xarModURL('articles','admin','modify',
                                                    array('aid' => $article['aid'],'ptid'=>$article['pubtypeid']));
                            }

                            $item['viewurl'] = xarModURL('articles','user','display',
                                                    array('aid' => $article['aid'],
                                                          'ptid' => $article['pubtypeid']));
                        }
                         /*
                         else {

                            $input['mask'] = 'ReadArticles';
                            if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                                $item['viewurl'] = xarModURL('articles', 'user', 'display',
                                                        array('aid' => $article['aid'],
                                                              'ptid' => $article['pubtypeid']));
                            } else {
                                $item['viewurl'] = '';
                            }

                        }*/
                    }
                }
            }
            $item['deletetitle'] = xarML('Delete');
            $item['edittitle'] = xarML('Edit');
            $item['viewtitle'] = xarML('View');
            $item['clonetitle'] = xarML('Clone');
            $input['mask'] = 'EditArticles';
            if (xarModAPIFunc('articles','user','checksecurity',$input)) {
                $items[] = $item;
            }
        }
    }
    $data['items'] = $items;

    // Add pager
    $data['pager'] = xarTplGetPager($startnum,
                            xarModAPIFunc('articles', 'user', 'countitems',
                                          array('ptid' => $ptid,
                                                'authorid' => $authorid,
                                                'language' => $lang,
                                                'pubdate' => $pubdate,
                                                'cids' => $cids,
                                                'andcids' => $andcids,
                                                'sort'  =>$sort,
                                                'order'=>$order,
                                                'status' => $status)),
                            xarModURL('articles', 'admin', 'view',
                                      array('startnum' => '%%',
                                            'ptid' => $ptid,
                                            'authorid' => $authorid,
                                            'language' => $lang,
                                            'pubdate' => $pubdate,
                                            'sort'  =>$sort,
                                            'order'=>$order,
                                            'catid' => $catid,
                                            'status' => $status)),
                            $numitems);

    // Create filters based on publication type
    $pubfilters = array();
    $publist = array();
    $publist['0']=xarML('All');
    foreach ($pubtypes as $id => $pubtype) {
        if (!xarSecurityCheck('EditArticles',0,'Article',"$id:All:All:All")) {
            continue;
        }
        $pubitem = array();
        if ($id == $ptid) {
            $pubitem['plink'] = '';
        } else {
            $pubitem['plink'] = xarModURL('articles','admin','view',
                                         array('ptid' => $id));
        }
        $pubitem['ptitle'] = $pubtype['descr'];
        $publist[$pubtype['ptid']] = ucfirst($pubtype['name']);
        $pubfilters[] = $pubitem;
    }
    $data['pubfilters'] = $pubfilters;
    $data['publist'] = $publist;
    // Create filters based on article status
    $statusfilters = array();
    if (!empty($labels['status'])) {
        $statusfilters[] = array('stitle' => xarML('All'),
                                 'slink' => !is_array($status) ? '' :
                                                xarModURL('articles','admin','view',
                                                          array('ptid' => $ptid,
                                                                'catid' => $catid)));
        foreach ($data['states'] as $id => $name) {
            $statusfilters[] = array('stitle' => $name,
                                     'slink' => (is_array($status) && $status[0] == $id) ? '' :
                                                    xarModURL('articles','admin','view',
                                                              array('ptid' => $ptid,
                                                                    'catid' => $catid,
                                                                    'status' => array($id))));
        }
    }

    $data['statusfilters'] = $statusfilters;


    $data['changestatuslabel'] = xarML('Confirm Status Change');
    // Add link to create new article
    if (xarSecurityCheck('SubmitArticles',0,'Article',"$ptid:All:All:All")) {
        $newurl = xarModURL('articles','admin','new',
                           array('ptid' => $ptid));
        $data['shownewlink'] = true;
    } else {
        $newurl = '';
        $data['shownewlink'] = false;
    }
   $data['newurl'] = $newurl;
// TODO: Hook category block someday ?
    xarVarSetCached('Blocks.categories','module','articles');
    xarVarSetCached('Blocks.categories','type','admin');
    xarVarSetCached('Blocks.categories','func','view');
    xarVarSetCached('Blocks.categories','itemtype',$ptid);
    if (!empty($ptid) && !empty($pubtypes[$ptid]['descr'])) {
        xarVarSetCached('Blocks.categories','title',$pubtypes[$ptid]['descr']);
    }
    xarVarSetCached('Blocks.categories','cids',$cids);

    if (!empty($ptid)) {
        $template = $pubtypes[$ptid]['name'];
        xarTplSetPageTitle(xarML('Listing #(1)', $pubtypes[$ptid]['descr']));
    } else {
// TODO: allow templates per category ?
       $template = null;
       xarTplSetPageTitle(xarML('Article Listing'));
    }

    //prepare images etc for sort
    $data['orderimgclass'] = '';
    $data['orderimglabel'] = '';
    if ($order == 'asc') {
        $data['orderimgclass'] = 'esprite xs-sorted-asc';
        $data['orderimglabel'] = xarML('Sorted ascending');
    } else {
        $data['orderimgclass'] = 'esprite xs-sorted-desc';
         $data['orderimglabel'] = xarML('Sorted descending');
    }
    $data['order'] = strtolower($order);
    //decide what image goes where
    $sortimage = array();
    $dorder = array();
    $headerarray= array('title','pubdate','status');
    foreach ($headerarray as $headername) {
        $orderimage[$headername] = false;
        if ($sort == $headername) {
            $orderimage[$headername] = true;
        }
    }

    $data['orderimage'] = $orderimage;
    $data['dorder'] = ($data['order'] == 'asc') ? 'desc' : 'asc';
    $data['sort'] = $sort;

  //common menu link
    $data['menulinks'] = xarModAPIFunc('articles','admin','getmenulinks',$data);

    $data['dummyimage'] = xarTplGetImage('blank.gif','base');
    return xarTplModule('articles', 'admin', 'view', $data, $template);
}

?>
