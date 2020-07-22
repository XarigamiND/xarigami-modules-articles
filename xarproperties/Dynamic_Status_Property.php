<?php
/**
 * Dynamic Data Status Property
 *
 * @copyright (C) 2003 by the Xaraya Development Team.
 * @license GPL {http://www.gnu.org/licenses/gpl.html}
 *
 * @subpackage Xarigami Articles Module
 * @copyright (C) 2008-2012 2skies.com
 * @link http://xarigami.com/project/xarigami_articles
 * @author mikespub
*/
/**
 * Include the base class
 *
 */
sys::import('modules.base.xarproperties.Dynamic_Select_Property');
/**
 * handle the status property
 *
 * @package dynamicdata
 */
class Dynamic_Status_Property extends Dynamic_Select_Property
{

    public $id         = 10;
    public $name       = 'status';
    public $desc       = 'Article status';
    public $reqmodules = 'articles';
    public $xv_override = false;
    public $xv_firstline = '';
    public $xv_size = 1;

    function __construct($args)
    {
        parent::__construct($args);

        $this->filepath   = 'modules/articles/xarproperties';
        if (count($this->options) == 0) {
            $states = xarModAPIFunc('articles','user','getstates');
            $this->options = array();
            foreach ($states as $id => $name) {
                array_push($this->options, array('id' => $id, 'name' => $name));
            }
        }
    }


    /**
     * Show the input form for the property
     * @return mixed. This function calls the template function to show the input form
     */
    public function showInput(Array $args = array())
    {
        extract($args);

        //common data
        $data['value'] = !isset($value) ? $this->value : $value;
        $data['override'] = !isset($override) ? $this->xv_override : $override;


        if (!isset($options) || count($options) == 0) {
            $data['options'] = $this->getOptions();
        } else {
            $data['options'] = $options;
        }
        // check if we need to add the current value to the options
        if (!empty($data['value']) && $this->xv_override) {
            $found = false;
            foreach ($data['options'] as $option) {
                if ($option['id'] == $data['value']) {
                    $found = true;
                    break;
                }
            }
            if (!$found) {
                $data['options'][] = array('id' => $data['value'], 'name' => $data['value']);
            }
        }

        if (!xarVarFetch('aid','isset', $aid, NULL, XARVAR_DONT_SET)) {return;}
        if (isset($aid)) {
            $article = xarModAPIFunc('articles', 'user','get', array('aid' => $aid, 'withcids' => true));
        }
        //check for moderation ability and get out quick if none
        //we don't use it here but in article status property
        $article['mask'] = 'ModerateArticles';
        $data['displayonly'] = false;
        if (!xarModAPIFunc('articles','user','checksecurity',$article)) {
            $data['displayonly'] = true;
        }
        //let's show the the status selector
        $data['name'] = empty($name) ? 'dd_' . $this->id: $name;
        $data['id'] = empty($id) ? $data['name'] : $id;
        $data['onchange']   = isset($onchange) ? $onchange : null; // let tpl decide what to do
        $data['tplmodule']  = !isset($tplmodule) ? $this->tplmodule : $tplmodule;
        $data['tabindex']   = !empty($tabindex) ? $tabindex : 0;
        $data['invalid']    = !empty($this->invalid) ? xarML('Invalid #(1)', $this->invalid) : '';
        $data['layout']     = !isset($layout) ?  $this->layout : $layout;
        $data['class']      = !isset($class) ?  $this->class : $class;
        $data['firstline']  = isset($firstline) ? $firstline:$this->xv_firstline;
        $data['size']       = isset($size) && !empty($size) ?  $size: $this->xv_size;

        unset($article);
        // allow template override by child classes (or in BL tags/API calls)
        $template = (!isset($template) || empty($template)) ? 'dropdown' : $template;
        return xarTplProperty('articles', $template, 'showinput', $data);

    }
     public function getBaseValidationInfo()
    {
        $validation = array();
        return $validation;
    }
    /**
     * Get the base information for this property.
     *
     *
     * @return array base information for this property
     **/
     function getBasePropertyInfo()
     {
         $args = array();
         $validation = $this->getBaseValidationInfo();
         $baseInfo = array(
                            'id'         => 10,
                            'name'       => 'status',
                            'label'      => 'Article status',
                            'format'     => '10',
                            'validation' => serialize($validation),
                            'source'     => '',
                            'dependancies' => '',
                            'requiresmodule' => 'articles',
                            'filepath'   => 'modules/articles/xarproperties',
                            'aliases'        => '',
                            'args'           => serialize($args)
                            // ...
                           );
        return $baseInfo;
     }

}

?>