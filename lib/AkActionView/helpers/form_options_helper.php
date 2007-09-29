<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_helper.php');

/**
* Provides a number of methods for turning different kinds of containers into a set of option tags.
* == Options
* The <tt>collection_select</tt>, <tt>country_select</tt>, <tt>select</tt>,
* and <tt>time_zone_select</tt> methods take an <tt>options</tt> parameter,
* an array.
*
* * <tt>include_blank</tt> - set to true if the first option element of the select element is a blank. Useful if there is not a default value required for the select element. For example,
*
*   $form_options_helper->select('post','category', $Post->categories, array('include_blank'=>true));
*
* could become:
*
*   <select name="post[category]">
*     <option></option>
*     <option>joke</option>
*     <option>poem</option>
*   </select>
*
* * <tt>prompt</tt> - set to true or a prompt string. When the select element doesn't have a value yet, this prepends an option with a generic prompt -- "Please select" -- or the given prompt string.
*
* Another common case is a select tag for an <tt>belongs_to</tt>-associated object. For example,
*
*   $form_options_helper->select('post', 'person_id', $Person->collect($Person->find(), 'name', 'id'));
*
* could become:
*
*   <select name="post[person_id]">
*     <option value="1">David</option>
*     <option value="2">Sam</option>
*     <option value="3">Tobias</option>
*   </select>
*/
class FormOptionsHelper extends AkActionViewHelper
{


    /**
     * Create a select tag and a series of contained option tags for the provided object and method.
     * The option currently held by the object will be selected, provided that the object is available.
     * See options_for_select for the required format of the choices parameter.
     *
     * Example with $Post->person_id => 1:
     *   $form_options_helper->select('post', 'person_id', $Person->collect($Person->find(), 'name', 'id'), array(), array('include_blank'=>true));
     *
     * could become:
     *
     *   <select name="post[person_id]">
     *     <option></option>
     *     <option value="1" selected="selected">David</option>
     *     <option value="2">Sam</option>
     *     <option value="3">Tobias</option>
     *   </select>
     *
     * This can be used to provide a default set of options in the standard way: before rendering the create form, a
     * new model instance is assigned the default options and bound to $this->model_name. Usually this model is not saved
     * to the database. Instead, a second model object is created when the create request is received.
     * This allows the user to submit a form page more than once with the expected results of creating multiple records.
     * In addition, this allows a single partial to be used to generate form inputs for both edit and create forms.
     *
     * By default, $post.person_id is the selected option.  Specify 'selected' => value to use a different selection
     * or 'selected' => null to leave all options unselected.
     */
    function select($object_name,  $column_name, $choices, $options = array(), $html_options = array())
    {
        $InstanceTag = new AkFormHelperOptionsInstanceTag($object_name,  $column_name, $this, null, $this->_object[$object_name]);
        return $InstanceTag->to_select_tag($choices, Ak::delete($options,'object'), $html_options);
    }

    /**
       * Return select and option tags for the given object and column_name using options_from_collection_for_select to generate the list of option tags.
       */
    function collection_select($object_name,  $column_name, $collection, $value_column_name, $text_column_name, $options = array(), $html_options = array())
    {
        $InstanceTag = new AkFormHelperOptionsInstanceTag($object_name,  $column_name, $this, null, $this->_object[$object_name]);
        return $InstanceTag->to_collection_select_tag($collection, $value_column_name, $text_column_name, Ak::delete($options,'object'), $html_options);
    }

    /**
      * Return select and option tags for the given object and column_name, using country_options_for_select to generate the list of option tags.
      */
    function country_select($object_name,  $column_name, $priority_countries = null, $options = array(), $html_options = array())
    {
        $InstanceTag = new AkFormHelperOptionsInstanceTag($object_name,  $column_name, $this, null, $this->_object[$object_name]);
        return $InstanceTag->to_country_select_tag($priority_countries, Ak::delete($options,'object'), $html_options);
    }

    /**
       * Return select and option tags for the given object and column_name, using
       * #time_zone_options_for_select to generate the list of option tags.
       *
       * In addition to the <tt>include_blank</tt> option documented above,
       * this method also supports a <tt>model</tt> option, which defaults
       * to TimeZone. This may be used by users to specify a different time
       * zone model object. (See #time_zone_options_for_select for more
       * information.)
       */
    function time_zone_select($object_name,  $column_name, $priority_zones = array(), $options = array(), $html_options = array())
    {
        $InstanceTag = new AkFormHelperOptionsInstanceTag($object_name,  $column_name, $this, null, $this->_object[$object_name]);
        return $InstanceTag->to_time_zone_select_tag($priority_zones, Ak::delete($options,'object'), $html_options);
    }

    /**
       * Accepts a container array and returns a string of option tags. Given a container where the elements respond to first and 
       * last (such as a two-element array), the "lasts" serve as option values and the "firsts" as option text. Arrays are turned 
       * into this form automatically, so the keys become "firsts" and values become lasts. If +selected+ is specified, the matching 
       * "last" or element will get the selected option-tag.  +Selected+ may also be an array of values to be selected when using 
       * a multiple select.
       *
       * Examples (call, result):
       *   $form_options_helper->options_for_select(array('Dollar'=>'$', 'Kroner'=>'DKK'));
       *     <option value="$">Dollar</option><option value="DKK">Kroner</option>
       *
       *   $form_options_helper->options_for_select(array('VISA', 'MasterCard'), 'MasterCard');
       *     <option value="VISA">VISA</option><option selected="selected" value="MasterCard">MasterCard</option>
       *
       *   $form_options_helper->options_for_select(array('Basic'=>'$20','Plus'=>'$40'), '$40');
       *     <option value="$20">Basic</option><option selected="selected" value="$40">Plus</option>
       *
       *   $form_options_helper->options_for_select(array('VISA','MasterCard','Discover'), array('VISA','Discover'));
       *     <option selected="selected" value="VISA">VISA</option>
       *     <option value="MasterCard">MasterCard</option>
       *     <option selected="selected" value="Discover">Discover</option>
       *
       * NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
       */
    function options_for_select($container, $selected = array(), $options = array())
    {
        $container = (array)$container;
        if (empty($container)) {
            return '';
        }
        $container = array_map('strval',$container);

        $text_is_value = count(array_diff(range(0,count($container)-1),array_keys($container))) == 0;

        $selected = (array)$selected;
        $options_for_select = '';
        
        $compare_captions = !empty($selected) ? is_string(key(array_slice($selected,0,1))) : false;
       
        foreach ($container as $text=>$value){
            $options_for_select .= TagHelper::content_tag('option',$text_is_value ? $value : $text,
            array_merge($options, ($compare_captions ? 
            (isset($selected[$text]) && $selected[$text] == $value) : 
            in_array($value, $selected)) ? array('value'=>$value,'selected'=>'selected') : array('value'=>$value))
            )."\n";
        }
        return $options_for_select;
    }

    /**
       * Returns a string of option tags that have been compiled by iterating over the +collection+ and assigning the
       * the result of a call to the +value_column_name+ as the option value and the +text_column_name+ as the option text.
       * If +$selected_value+ is specified, the element returning a match on +value_column_name+ will get the selected option tag.
       *
       * Example (call, result). Imagine a loop iterating over each +person+ in <tt>$Project->People</tt> to generate an input tag:
       *   $form_options_helper->options_from_collection_for_select($Project->People,'id','name');
       *     <option value="{$Person->id}">{$Person->name}</option>
       *
       * NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
       */
    function options_from_collection_for_select($collection, $value_column_name, $text_column_name = null, $selected_value = array(), $options = array())
    {
        $text_column_name = is_null($text_column_name) ? $value_column_name : $text_column_name;
        $collection_options = array();
        if($value_column_name[0] == '_' || $text_column_name[0] == '_'){
            trigger_error(Ak::t('You cannot call private methods from %helper_name helper',array('%helper_name'=>'options_from_collection_for_select'),'helpers/form'), E_USER_ERROR);
            return '';
        }else{
            foreach ($collection as $item){
                $name = method_exists($item,$text_column_name) ? $item->$text_column_name() : $item->get($text_column_name);
                $collection_options[$name] = method_exists($item,$value_column_name) ? $item->$value_column_name() : $item->get($value_column_name);
            }
            return $this->options_for_select($collection_options,$selected_value,$options);
        }
    }

    /**
      * Returns a string of option tags, like options_from_collection_for_select, but surrounds them with <optgroup> tags.
      *
      * An array of group objects are passed. Each group should return an array of options when calling group_method
      * Each group should return its name when calling group_label_method.
      *
      * $form_options_helper->option_groups_from_collection_for_select($continents, 'getCountries', 'getContinentName', 'getCountryId', 'getCountryName', $selected_country->id);
      *
      * Could become:
      *     <optgroup label="Africa">
      *         <option value="EGP">Egipt</option>
      *         <option value="RWD">Rwanda</option>
      *         ....
      *     </optgroup>
      *     
      *     <optgroup label="Asia">
      *         <option value="ZHN">China</option>
      *         <option value="IND">India</option>
      *         <option selected="selected" value="JPN">Japan</option>
      *         .....
      *     </optgroup>
      *
      * with objects of the following classes:
      * class Continent{
      *   function Continent($p_name, $p_countries){ $this->continent_name = $p_name; $this->countries = $p_countries;}
      *   function getContinentName(){ return $this->continent_name; }
      *   function getCountries(){ return $this->countries; }
      * }
      * class Country {
      *   function Country($id, $name){ $this->id = $id; $this->name = $name; }
      *   function getCountryId(){ return $this->id; }
      *   function getCountryName(){ return $this->name;}
      * }
      *
      * NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
      */
    function option_groups_from_collection_for_select($collection, $group_method, $group_label_method, $option_key_method, $option_value_method, $selected_key = null)
    {

        if($group_label_method[0] == '_' || $group_label_method[0] == '_' || $option_key_method[0] == '_' || $option_value_method[0] == '_'){
            trigger_error(Ak::t('You cannot call private methods from %helper_name helper',array('%helper_name'=>'option_groups_from_collection_for_select'),'helpers/form'), E_USER_ERROR);
        }else{
            $options_for_select = '';
            foreach ($collection as $group){

                if(method_exists($group, $group_method)){
                    $options_group = $group->{$group_method}();
                }elseif(isset($group->$group_method)){
                    $options_group =& $group->$group_method;
                }

                $options_for_select .= TagHelper::content_tag('optgroup',
                $this->options_from_collection_for_select($options_group, $option_key_method, $option_value_method, $selected_key),
                array('label'=>
                method_exists($group, $group_label_method) ?
                $group->{$group_label_method}() :
                $group->get($group_label_method)
                ));
            }
            return $options_for_select;
        }
    }


    /**
       * Returns a string of option tags for pretty much any country in the world. Supply a country name as +selected+ to
       * have it marked as the selected option tag. You can also supply an array of countries as +priority_countries+, so
       * that they will be listed above the rest of the (long) list.
       *
       * NOTE: Only the option tags are returned, you have to wrap this call in a regular HTML select tag.
       */
    function country_options_for_select($selected = null, $priority_countries = array(), $model = 'AkCountries', $options = array())
    {
        $country_options = '';

        if($model == 'AkCountries'){
            require_once(AK_LIB_DIR.DS.'AkLocalize'.DS.'AkCountries.php');
            $countries_form_method = AkCountries::all();
        }else{
            $countries_form_method = $model->all();
        }

        if (!empty($priority_countries)){
            $country_options .= $this->options_for_select($priority_countries, $selected, $options);
            $country_options .= '<option value="">-------------</option>'."\n";
        }

        if (!empty($priority_countries) && in_array($selected,$priority_countries)){
            $country_options .= $this->options_for_select(array_diff($countries_form_method, $priority_countries), $selected, $options);
        }else{
            $country_options .= $this->options_for_select($countries_form_method, $selected, $options);
        }
        return $country_options;
    }

    /**
       * Returns a string of option tags for pretty much any time zone in the
       * world. Supply a TimeZone name as +selected+ to have it marked as the
       * selected option tag. You can also supply an array of TimeZones
       * as +$priority_zones+, so that they will be listed above the rest of the
       * (long) list. 
       * 
       * The +selected+ parameter must be either +null+, or a string that names
       * a TimeZone.
       *
       * By default, +model+ is an AkTimeZone instance. The only requirement is that the
       * +model+ parameter be an object that responds to #all, and returns
       * an object with a toString() method and an utc_offset attribute.
       *
       * NOTE: Only the option tags are returned, you have to wrap this call in
       * a regular HTML select tag.
       */
    function time_zone_options_for_select($selected = null, $priority_zones = array(), $model = 'AkTimeZone')
    {
        $zone_options = '';
        if($model == 'AkTimeZone'){
            require_once(AK_LIB_DIR.DS.'AkLocalize'.DS.'AkTimeZone.php');
            $Zones = AkTimeZone::all();
        }else{
            $Zones = $model->all();
        }

        $zones_for_options = array();
        foreach (array_keys($Zones) as $k){
            $zones_for_options[$Zones[$k]->toString()] = $Zones[$k]->utc_offset;
        }

        if (!empty($priority_zones)){
            $zone_options .= $this->options_for_select($priority_zones, $selected);
            $zone_options .= '<option value="">-------------</option>'."\n";
        }
        
        $zone_options .= $this->options_for_select(array_diff_assoc($zones_for_options,$priority_zones), $selected);
        return $zone_options;

    }

}

class AkFormHelperOptionsInstanceTag extends AkFormHelperInstanceTag
{
    function AkFormHelperOptionsInstanceTag($object_name, $column_name, &$template_object, $local_binding = null, &$object)
    {
        $this->AkFormHelperInstanceTag($object_name, $column_name, $template_object, $local_binding, $object);
    }

    function to_select_tag($choices, $options=array(), $html_options = array())
    {
        $this->add_default_name_and_id($html_options);
        $selected_value = !empty($options['selected']) ? $options['selected'] : $this->getValue();

        return TagHelper::content_tag('select', $this->_addOptions($this->_template_object->options_for_select($choices, $selected_value, $options),
        $html_options, $this->getValue()), Ak::delete($html_options,'prompt','include_blank'));
    }

    function to_collection_select_tag($collection, $value_column_name, $text_column_name = null, $options = array(), $html_options = array())
    {
        $this->add_default_name_and_id($html_options);

        return TagHelper::content_tag('select', $this->_addOptions(
        $this->_template_object->options_from_collection_for_select($collection, $value_column_name, $text_column_name, $this->getValue(), array_diff($options,array('prompt'=>true))),
        $options, $this->getValue()), $html_options);
    }

    function to_country_select_tag($priority_countries = array(), $options = array(), $html_options = array())
    {
        $this->add_default_name_and_id($html_options);
        return TagHelper::content_tag('select', $this->_addOptions($this->_template_object->country_options_for_select($this->getValue(), $priority_countries, 'AkCountries', $options),
        $options, $this->getValue()), $html_options);
    }

    function to_time_zone_select_tag($priority_zones = array(), $options = array(), $html_options = array())
    {
        $this->add_default_name_and_id($html_options);

        return TagHelper::content_tag('select',
        $this->_addOptions($this->_template_object->time_zone_options_for_select(
        $this->getValue(),$priority_zones,(empty($options['model'])?'AkTimeZone':$options['model'])),$options,$this->getValue()),$html_options);
    }

    function _addOptions($option_tags, $options, $value = null)
    {
        $option_tags = (!empty($options['include_blank']) ? "<option value=\"\"></option>\n" : '').$option_tags;
        if(empty($value) && !empty($options['prompt'])){
            return '<option value="">'.(is_string($options['prompt']) ? $options['prompt'] : Ak::t('Please select',array(),'helpers/form'))."</option>\n".$option_tags;
        }else{
            return $option_tags;
        }
    }
}

class AkFormOptionsHelperBuilder extends FormOptionsHelper
{

    function AkFormOptionsHelperBuilder($object_name, $object, &$template)
    {
        $this->object_name = $object_name;
        $this->object = $object;
        $this->template =& $template;
        $this->proccessing = $object_name;
        $this->template->_remove_object_from_options = true;
    }

    function select($column_name, $choices, $options = array(), $html_options = array())
    {
        return $this->template->select($this->object_name, $column_name, $choices, array_merge($options, array('object' => $this->object)), $html_options);
    }

    function collection_select($column_name, $collection, $value_column_name, $text_column_name, $options = array(), $html_options = array())
    {
        return $this->template->collection_select($this->object_name, $column_name, $collection, $value_column_name, $text_column_name, array_merge($options, array('object' => $this->object)), $html_options);
    }

    function country_select($column_name, $priority_countries = null, $options = array(), $html_options = array())
    {
        return $this->template->country_select($this->object_name, $column_name, $priority_countries, array_merge($options, array('object' => $this->object)), $html_options);
    }

    function time_zone_select($column_name, $priority_zones = null, $options = array(), $html_options = array())
    {
        return $this->template->time_zone_select($this->object_name, $column_name, $priority_zones, array_merge($options, array('object' => $this->object)), $html_options);
    }
}

?>
