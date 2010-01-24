<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkActiveRecordLocalization extends AkActiveRecordExtenssion
{
    public function getInternationalizedColumns() {
        static $cache;
        $model = $this->_ActiveRecord->getModelName();
        $available_locales = $this->getAvailableLocales();
        if(empty($cache[$model])){
            $cache[$model] = array();
            foreach ($this->_ActiveRecord->getColumnSettings() as $column_name=>$details){
                if(!empty($details['i18n'])){
                    $_tmp_pos = strpos($column_name,'_');
                    $column = substr($column_name,$_tmp_pos+1);
                    $lang = substr($column_name,0,$_tmp_pos);
                    if(in_array($lang, $available_locales)){
                        $cache[$model][$column] = empty($cache[$model][$column]) ? array($lang) :
                        array_merge($cache[$model][$column] ,array($lang));
                    }
                }
            }
        }

        return $cache[$model];
    }

    public function getAvailableLocales() {
        static $available_locales;
        if(empty($available_locales)){
            if(defined('AK_ACTIVE_RECORD_DEFAULT_LOCALES')){
                $available_locales = Ak::stringToArray(AK_ACTIVE_RECORD_DEFAULT_LOCALES);
            }else{
                $available_locales =  Ak::langs();
            }
        }
        return $available_locales;
    }

    public function getCurrentLocale() {
        static $current_locale;
        if(empty($current_locale)){
            $current_locale = Ak::lang();
            $available_locales = $this->getAvailableLocales();
            if(!in_array($current_locale, $available_locales)){
                $current_locale = array_shift($available_locales);
            }
        }
        return $current_locale;
    }


    public function getAttributeByLocale($attribute, $locale) {
        $internationalizable_columns = $this->getInternationalizedColumns();
        if(!empty($internationalizable_columns[$attribute]) && is_array($internationalizable_columns[$attribute]) && in_array($locale, $internationalizable_columns[$attribute])){
            return $this->_ActiveRecord->getAttribute($locale.'_'.$attribute);
        }
    }

    public function getAttributeLocales($attribute) {
        $attribute_locales = array();
        foreach ($this->getAvailableLocales() as $locale){
            if($this->_ActiveRecord->hasColumn($locale.'_'.$attribute)){
                $attribute_locales[$locale] = $this->getAttributeByLocale($attribute, $locale);
            }
        }
        return $attribute_locales;
    }

    public function setAttributeByLocale($attribute, $value, $locale) {
        $internationalizable_columns = $this->getInternationalizedColumns();

        if($this->isInternationalizeCandidate($locale.'_'.$attribute) && !empty($internationalizable_columns[$attribute]) && is_array($internationalizable_columns[$attribute]) && in_array($locale, $internationalizable_columns[$attribute])){
            $this->_ActiveRecord->setAttribute($locale.'_'.$attribute, $value);
        }
    }

    public function setAttributeLocales($attribute, $values = array()) {
        foreach ($values as $locale=>$value){
            $this->setAttributeByLocale($attribute, $value, $locale);
        }
    }

    public function setInternationalizedAttribute($attribute, $value, $inspect_for_callback_child_method = AK_ACTIVE_RECORD_ENABLE_CALLBACK_SETTERS, $compose_after_set = true) {
        if(is_array($value)){
            $this->setAttributeLocales($attribute, $value);
        }elseif(is_string($inspect_for_callback_child_method)){
            $this->setAttributeByLocale($attribute, $value, $inspect_for_callback_child_method);
        }else{
            $this->_groupInternationalizedAttribute($attribute, $value);
        }
    }

    public function addInternationalizedColumn($column_name) {
        $this->_ActiveRecord->_columnsSettings[$column_name]['i18n'] = true;
    }

    public function isInternationalizeCandidate($column_name) {
        $pos = strpos($column_name,'_');
        return $pos === 2 && in_array(substr($column_name, 0, $pos), $this->getAvailableLocales());
    }

    public function delocalizeAttribute($attribute) {
        return $this->isInternationalizeCandidate($attribute) ? substr($attribute, 3) : $attribute;
    }


    /**
     * Adds an internationalized attribute to an array containing other locales for the same column name
     *
     * Example:
     *  es_title and en_title will be available user title = array('es'=>'...', 'en' => '...')
     */
    protected function _groupInternationalizedAttribute($attribute, $value) {
        if($this->_ActiveRecord->internationalize && $this->isInternationalizeCandidate($attribute)){
            if(!empty($this->_ActiveRecord->$attribute)){
                $_tmp_pos = strpos($attribute,'_');
                $column = substr($attribute,$_tmp_pos+1);
                $lang = substr($attribute,0,$_tmp_pos);
                $this->_ActiveRecord->$column = empty($this->_ActiveRecord->$column) ? array() : $this->_ActiveRecord->$column;
                if(empty($this->_ActiveRecord->$column) || (!empty($this->_ActiveRecord->$column) && is_array($this->_ActiveRecord->$column))){
                    $this->_ActiveRecord->$column = empty($this->_ActiveRecord->$column) ? array($lang=>$value) : array_merge($this->_ActiveRecord->$column,array($lang=>$value));
                }
            }
        }
    }

}
