<?php

/**
                      Combined attributes
====================================================================
*
* The Akelos Framework has a handy way to represent combined fields.
* You can add a new attribute to your models using a printf patter to glue
* multiple parameters in a single one.
*
* For example, If we set...
* $this->addCombinedAttributeConfiguration('name', "%s %s", 'first_name', 'last_name');
* $this->addCombinedAttributeConfiguration('date', "%04d-%02d-%02d", 'year', 'month', 'day');
* $this->setAttributes('first_name=>','John','last_name=>','Smith','year=>',2005,'month=>',9,'day=>',27);
*
* $this->name // will have "John Smith" as value and
* $this->date // will be 2005-09-27
*
* On the other hand if you do
*
*   $this->setAttribute('date', '2008-11-30');
*
*   All the 'year', 'month' and 'day' getters will be fired (if they exist) the following attributes will be set
*
*    $this->year // will be 2008
*    $this->month // will be 11 and
*    $this->day // will be 27
*
* Sometimes you might need a pattern for composing and another for decomposing attributes. In this case you can specify
* an array as the pattern values, where first element will be the composing pattern and second element will be used
* for decomposing.
*
* You can also specify a callback method from this object function instead of a pattern. You can also assign a callback
* for composing and another for decomposing by passing their names as an array like on the patterns.
*
*    <?php
*    class User extends ActiveRecord
*    {
*        public function User()
*        {
*            // You can use a multiple patterns array where "%s, %s" will be used for combining fields and "%[^,], %s" will be used
*            // for decomposing fields. (as you can see you can also use regular expressions on your patterns)
*            $User->addCombinedAttributeConfiguration('name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name');
*
*            //Here we set email_link so compose_email_link() will be triggered for building up the field and parse_email_link will
*            // be used for getting the fields out
*            $User->addCombinedAttributeConfiguration('email_link', array("compose_email_link","parse_email_link"), 'email', 'name');
*
*            // We need to tell the ActiveRecord to load it's magic (see the example below for a simpler solution)
*            $attributes = (array)func_get_args();
*            return $this->init($attributes);
*
*        }
*        public function compose_email_link()
*        {
*            $args = func_get_arg(0);
*            return "<a href=\'mailto:{$args[\'email\']}\'>{$args[\'name\']}</a>";
*        }
*        public function parse_email_link($email_link)
*        {
*            $results = sscanf($email_link, "<a href=\'mailto:%[^\']\'>%[^<]</a>");
*            return array(\'email\'=>$results[0],\'name\'=>$results[1]);
*        }
*
*    }
*   ?>
*
* You can also simplify your live by declaring the combined attributes as a class variable like:
*    <?php
*    class User extends ActiveRecord
*    {
*       public $combined_attributes array(
*       array('name', array("%s, %s","%[^,], %s"), 'last_name', 'first_name')
*       array('email_link', array("compose_email_link","parse_email_link"), 'email', 'name')
*       );
*
*       // ....
*    }
*   ?>
*
*/
class AkActiveRecordCombinedAttributes extends AkActiveRecordExtenssion
{
    /**
    * Returns true if given attribute is a combined attribute for this Model.
    *
    * @param string $attribute
    * @return boolean
    */
    public function isCombinedAttribute ($attribute)
    {
        return !empty($this->_ActiveRecord->_combinedAttributes) && isset($this->_ActiveRecord->_combinedAttributes[$attribute]);
    }

    public function addCombinedAttributeConfiguration($attribute)
    {
        $args = is_array($attribute) ? $attribute : func_get_args();
        $columns = array_slice($args,2);
        $invalid_columns = array();
        foreach ($columns as $colum){
            if(!$this->_ActiveRecord->hasAttribute($colum)){
                $invalid_columns[] = $colum;
            }
        }
        if(!empty($invalid_columns)){
            trigger_error(Ak::t('There was an error while setting the composed field "%field_name", the following mapping column/s "%columns" do not exist',
            array('%field_name'=>$args[0],'%columns'=>join(', ',$invalid_columns))).Ak::getFileAndNumberTextForError(1), E_USER_ERROR);
        }else{
            $attribute = array_shift($args);
            $this->_ActiveRecord->_combinedAttributes[$attribute] = $args;
            $this->composeCombinedAttribute($attribute);
        }
    }

    public function composeCombinedAttributes()
    {

        if(!empty($this->_ActiveRecord->_combinedAttributes)){
            $attributes = array_keys($this->_ActiveRecord->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->composeCombinedAttribute($attribute);
            }
        }
    }

    public function composeCombinedAttribute($combined_attribute)
    {
        if($this->isCombinedAttribute($combined_attribute)){
            $config = $this->_ActiveRecord->_combinedAttributes[$combined_attribute];
            $pattern = array_shift($config);

            $pattern = is_array($pattern) ? $pattern[0] : $pattern;
            $got = array();

            foreach ($config as $attribute){
                if(isset($this->_ActiveRecord->$attribute)){
                    $got[$attribute] = $this->_ActiveRecord->getAttribute($attribute);
                }
            }
            if(count($got) === count($config)){
                $this->_ActiveRecord->$combined_attribute = method_exists($this->_ActiveRecord, $pattern) ? $this->_ActiveRecord->{$pattern}($got) : vsprintf($pattern, $got);
            }
        }
    }

    public function getCombinedAttributesWhereThisAttributeIsUsed($attribute)
    {
        $result = array();
        foreach ($this->_ActiveRecord->_combinedAttributes as $combined_attribute=>$settings){
            if(in_array($attribute,$settings)){
                $result[] = $combined_attribute;
            }
        }
        return $result;
    }


    public function requiredForCombination($attribute)
    {
        foreach ($this->_ActiveRecord->_combinedAttributes as $settings){
            if(in_array($attribute,$settings)){
                return true;
            }
        }
        return false;
    }

    public function hasCombinedAttributes()
    {
        return count($this->getCombinedSubattributes()) === 0 ? false :true;
    }

    public function getCombinedSubattributes($attribute)
    {
        $result = array();
        if(is_array($this->_ActiveRecord->_combinedAttributes[$attribute])){
            $attributes = $this->_ActiveRecord->_combinedAttributes[$attribute];
            array_shift($attributes);
            foreach ($attributes as $attribute_to_check){
                if(isset($this->_ActiveRecord->_combinedAttributes[$attribute_to_check])){
                    $result[] = $attribute_to_check;
                }
            }
        }
        return $result;
    }

    public function decomposeCombinedAttributes()
    {
        if(!empty($this->_ActiveRecord->_combinedAttributes)){
            $attributes = array_keys($this->_ActiveRecord->_combinedAttributes);
            foreach ($attributes as $attribute){
                $this->decomposeCombinedAttribute($attribute);
            }
        }
    }

    public function decomposeCombinedAttribute($combined_attribute, $used_on_combined_fields = false)
    {
        if(isset($this->_ActiveRecord->$combined_attribute) && $this->isCombinedAttribute($combined_attribute)){
            $config = $this->_ActiveRecord->_combinedAttributes[$combined_attribute];
            $pattern = array_shift($config);
            $pattern = is_array($pattern) ? $pattern[1] : $pattern;

            if(method_exists($this->_ActiveRecord, $pattern)){
                $pieces = $this->_ActiveRecord->{$pattern}($this->_ActiveRecord->$combined_attribute);
                if(is_array($pieces)){
                    foreach ($pieces as $k=>$v){
                        $is_combined = $this->isCombinedAttribute($k);
                        if($is_combined){
                            $this->decomposeCombinedAttribute($k);
                        }
                        $this->_ActiveRecord->setAttribute($k, $v, true, !$is_combined);
                    }
                    if($is_combined && !$used_on_combined_fields){
                        $combined_attributes_contained_on_this_attribute = $this->getCombinedSubattributes($combined_attribute);
                        if(count($combined_attributes_contained_on_this_attribute)){
                            $this->decomposeCombinedAttribute($combined_attribute, true);
                        }
                    }
                }
            }else{
                $got = sscanf($this->_ActiveRecord->$combined_attribute, $pattern);
                for ($x=0; $x<count($got); $x++){
                    $attribute = $config[$x];
                    $is_combined = $this->isCombinedAttribute($attribute);
                    if($is_combined){
                        $this->decomposeCombinedAttribute($attribute);
                    }
                    $this->_ActiveRecord->setAttribute($attribute, $got[$x], true, !$is_combined);
                }
            }
        }
    }

    public function getAvailableCombinedAttributes()
    {
        $combined_attributes = array();
        foreach ($this->_ActiveRecord->_combinedAttributes as $attribute=>$details){
            $combined_attributes[$attribute] = array('name'=>$attribute, 'type'=>'string', 'path' => array_shift($details), 'uses'=>$details);
        }
        return !empty($this->_ActiveRecord->_combinedAttributes) && is_array($this->_ActiveRecord->_combinedAttributes) ? $combined_attributes : array();
    }
}

