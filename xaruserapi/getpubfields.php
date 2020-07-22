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
 * get array of configurable fields for publication types
 * @TODO : add dynamic fields here for .81+
 * @return array('title'   => array('label'  => xarML('...'),
                                    'format' => '...',
                                    'input'  => 1),
                 'summary' => array('label'  => xarML('...'),
                                    'format' => '...',
                                    'input'  => 1),
                 ...);
 */
function articles_userapi_getpubfields($args)
{
    return array(
        'title'    => array('label'  => xarML('Title'),
                            'format' => 'textbox',
                            'input'  => 1),
        'summary'  => array('label'  => xarML('Summary'),
                            'format' => 'textarea_medium',
                            'input'  => 1),
        'body' => array('label'  => xarML('Body'),
                            'format' => 'textarea_large',
                            'input'  => 1),
        'notes'    => array('label'  => xarML('Notes'),
                            'format' => 'textarea_small',
                            'input'  => 0),
        'authorid' => array('label'  => xarML('Author'),
                            'format' => 'username',
                            'input'  => 0),
        'pubdate'  => array('label'  => xarML('Publication Date'),
                            'format' => 'calendar',
                            'input'  => 0),
        'status'   => array('label'  => xarML('Status'),
                            'format' => 'status',
                            'input'  => 0)
                            /* not configurable at this stage
        'checkout'   => array('label'  => xarML('Checked Out'),
                            'format' => 'textbox',
                            'input'  => 0),
        'checkouttime'   => array('label'  => xarML('Check Out Time'),
                            'format' => 'calendar',
                            'input'  => 0),
        'editor'   => array('label'  => xarML('Editor'),
                            'format' => 'username',
                            'input'  => 0)*/
    );
}

?>
