<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
* AkInflector for pluralize and singularize English nouns.
*
* This AkInflector is a port of Ruby on Rails AkInflector.
*
* It can be really helpful for developers that want to
* create frameworks based on naming conventions rather than
* configurations.
*
* You can find the inflector rules in config/inflector.yml
* To add your own inflector rules, please do so in config/inflector/mydictionary.yml
*
* Using it:
*
* AkInflector::pluralize('inglés',null,'es'); // ingleses, see config/inflector/es.yml
*
* @author Bermi Ferrer Martinez <bermi a.t bermilabs c.om>
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
*/
class AkInflector
{
    // ------ CLASS METHODS ------ //

    // ---- Public methods ---- //

    // {{{ pluralize()
    static function loadConfig($dictionary) {
        static $_loaded = array();
        if (!($return = Ak::getStaticVar('AkInflectorConfig::'.$dictionary))) {
            $return = Ak::getSettings($dictionary, false);

            if ($return !== false) {
                Ak::setStaticVar('AkInflectorConfig::'.$dictionary,$return);
                $_loaded[$dictionary] = true;
            } else {
                trigger_error(Ak::t('Could not load inflector rules file: %file',array('%file'=>'config'.DS.$dictionary.'.yml')),E_USER_ERROR);
            }

        }
        return $return;
    }

    static function inflect($word, $new_value, $type, $dictionary = null) {
        static $_cached;
        static $_loaded;

        if ($dictionary == null || $dictionary=='inflector') {
            $dictionary = 'inflector';
        } else {
            $dictionary = 'inflector/'.$dictionary;
        }
        if (!isset($_loaded[$dictionary])) {

            $_loaded[$dictionary] = true;
            $_cached[$dictionary] = array('singularize'=>array(),'pluralize'=>array());
        }

        $config = AkInflector::loadConfig($dictionary);
        if (!in_array($type,array('singularize','pluralize'))) {
            return $word;
        }
        if(isset($new_value)){
            $_cached[$dictionary][$type][$word] = $new_value;
            return;
        }
        $_original_word = $word;
        if(!isset($_cached[$dictionary][$type][$_original_word])){
            $lowercased_word = strtolower($word);
            if (in_array($lowercased_word,$config[$type]['uncountable'])){
                return $word;
            }
            foreach ($config[$type]['irregular'] as $_plural=> $_singular){
                if ($type == 'singularize') {
                    if (preg_match('/('.$_singular.')$/iu', $word, $arr)) {
                        $_cached[$dictionary][$type][$_original_word] = preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
                        return $_cached[$dictionary][$type][$_original_word];
                    }
                } else {
                    if (preg_match('/('.$_plural.')$/iu', $word, $arr)) {
                        $_cached[$dictionary][$type][$_original_word] = preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
                        return $_cached[$dictionary][$type][$_original_word];
                    }
                }
            }

            $replacements = isset($config[$type]['replacements'])?$config[$type]['replacements']:false;
            if ($replacements!==false) {
                $replacements_keys = array_keys($replacements);
                foreach ($replacements_keys as $idx=>$key) {
                    $replacements_keys[$idx]  = '/'.$key.'/u';
                }
                $replacements_values = array_values($replacements);
            }
            foreach ($config[$type]['rules'] as $rule => $replacement) {
                if (preg_match($rule.'u', $word, $match)) {
                    if(strstr($replacement,'@') && $replacements){
                        foreach ($match as $k=>$v){
                            $replacement = preg_replace("/(@$k)/u",preg_replace($replacements_keys,$replacements_values, $v), $replacement);
                        }
                    }
                    $_cached[$dictionary][$type][$_original_word] = preg_replace($rule.'u', $replacement, $word);
                    return $_cached[$dictionary][$type][$_original_word];
                }
            }
            $_cached[$dictionary][$type][$_original_word] = $word;
            return $_cached[$dictionary][$type][$_original_word];
        }
        return $_cached[$dictionary][$type][$_original_word];
    }


    /**
    * Pluralizes English nouns.
    *
    * @access public
    * @static
    * @param    string    $word    English noun to pluralize
    * @return string Plural noun
    */
    static function pluralize($word, $new_plural = null, $dictionary = null) {
        return AkInflector::inflect($word,$new_plural,'pluralize',$dictionary);
    }

    // }}}
    // {{{ singularize()

    /**
    * Singularizes English nouns.
    *
    * @access public
    * @static
    * @param    string    $word    English noun to singularize
    * @return string Singular noun.
    */
    static function singularize($word, $new_singular = null, $dictionary = null) {
        return AkInflector::inflect($word,$new_singular,'singularize',$dictionary);
    }

    // }}}
    // {{{ conditionalPlural()

    /**
     * Get the plural form of a word if first parameter is greater than 1
     *
     * @param integer $numer_of_records
     * @param string $word
     * @return string Pluralized string when number of items is greater than 1
     */
    static function conditionalPlural($numer_of_records, $word) {
        return $numer_of_records > 1 ? AkInflector::pluralize($word) : $word;
    }

    // }}}
    // {{{ titleize()

    /**
    * Converts an underscored or CamelCase word into a English
    * sentence.
    *
    * The titleize function converts text like "WelcomePage",
    * "welcome_page" or  "welcome page" to this "Welcome
    * Page".
    * If second parameter is set to 'first' it will only
    * capitalize the first character of the title.
    *
    * @access public
    * @static
    * @param    string    $word    Word to format as tile
    * @param    string    $uppercase    If set to 'first' it will only uppercase the
    * first character. Otherwise it will uppercase all
    * the words in the title.
    * @return string Text formatted as title
    */
    static function titleize($word, $uppercase = '') {
        $uppercase = $uppercase == 'first' ? 'ucfirst' : 'ucwords';
        return $uppercase(AkInflector::humanize(AkInflector::underscore($word)));
    }

    // }}}
    // {{{ camelize()

    /**
    * Returns given word as CamelCased
    *
    * Converts a word like "send_email" to "SendEmail". It
    * will remove non alphanumeric character from the word, so
    * "who's online" will be converted to "WhoSOnline"
    *
    * @access public
    * @static
    * @see variablize
    * @param    string    $word    Word to convert to camel case
    * @return string UpperCamelCasedWord
    */
    static function camelize($word) {
        static $_cached;
        if(!isset($_cached[$word])){
            if(preg_match_all('/\/(.?)/',$word,$got)){
                foreach ($got[1] as $k=>$v){
                    $got[1][$k] = '::'.strtoupper($v);
                }
                $word = str_replace($got[0],$got[1],$word);
            }
            $_cached[$word] = str_replace(' ','',ucwords(preg_replace('/[^A-Z^a-z^0-9^:]+/',' ',$word)));
        }
        return $_cached[$word];
    }

    // }}}
    // {{{ underscore()

    /**
    * Converts a word "into_it_s_underscored_version"
    *
    * Convert any "CamelCased" or "ordinary Word" into an
    * "underscored_word".
    *
    * This can be really useful for creating friendly URLs.
    *
    * @access public
    * @static
    * @param    string    $word    Word to underscore
    * @return string Underscored word
    */
    static function underscore($word) {
        static $_cached;
        if(!isset($_cached[$word])){
            $_cached[$word] = strtolower(preg_replace(
            array('/[^A-Z^a-z^0-9^\/]+/','/([a-z\d])([A-Z])/','/([A-Z]+)([A-Z][a-z])/'),
            array('_','\1_\2','\1_\2'), $word));
        }
        return $_cached[$word];
    }


    // }}}
    // {{{ humanize()

    /**
    * Returns a human-readable string from $word
    *
    * Returns a human-readable string from $word, by replacing
    * underscores with a space, and by upper-casing the initial
    * character by default.
    *
    * If you need to uppercase all the words you just have to
    * pass 'all' as a second parameter.
    *
    * @access public
    * @static
    * @param    string    $word    String to "humanize"
    * @param    string    $uppercase    If set to 'all' it will uppercase all the words
    * instead of just the first one.
    * @return string Human-readable word
    */
    static function humanize($word, $uppercase = '') {
        $uppercase = $uppercase == 'all' ? 'ucwords' : 'ucfirst';
        return $uppercase(str_replace('_',' ',preg_replace('/_id$/', '',$word)));
    }

    // }}}
    // {{{ variablize()

    /**
    * Same as camelize but first char is lowercased
    *
    * Converts a word like "send_email" to "sendEmail". It
    * will remove non alphanumeric character from the word, so
    * "who's online" will be converted to "whoSOnline"
    *
    * @access public
    * @static
    * @see camelize
    * @param    string    $word    Word to lowerCamelCase
    * @return string Returns a lowerCamelCasedWord
    */
    static function variablize($word) {
        $word = AkInflector::camelize($word);
        return strtolower($word[0]).substr($word,1);
    }

    // }}}
    // {{{ tableize()

    /**
    * Converts a class name to its table name according to rails
    * naming conventions.
    *
    * Converts "Person" to "people"
    *
    * @access public
    * @static
    * @see classify
    * @param    string    $class_name    Class name for getting related table_name.
    * @return string plural_table_name
    */
    static function tableize($class_name) {
        return AkInflector::pluralize(AkInflector::underscore($class_name));
    }

    // }}}
    // {{{ classify()

    /**
    * Converts a table name to its class name according to Akelos
    * naming conventions.
    *
    * Converts "people" to "Person"
    *
    * @access public
    * @static
    * @see tableize
    * @param    string    $table_name    Table name for getting related ClassName.
    * @return string SingularClassName
    */
    static function classify($table_name) {
        return AkInflector::camelize(AkInflector::singularize($table_name));
    }

    // }}}
    // {{{ ordinalize()

    /**
    * Converts number to its ordinal English form.
    *
    * This method converts 13 to 13th, 2 to 2nd ...
    *
    * @access public
    * @static
    * @param    integer    $number    Number to get its ordinal value
    * @return string Ordinal representation of given string.
    */
    static function ordinalize($number) {
        if (in_array(($number % 100),range(11,13))){
            return $number.'th';
        }else{
            switch (($number % 10)) {
                case 1:
                    return $number.'st';
                    break;
                case 2:
                    return $number.'nd';
                    break;
                case 3:
                    return $number.'rd';
                default:
                    return $number.'th';
                    break;
            }
        }
    }

    // }}}

    /**
    * Removes the module name from a module/path, Module::name or Module_ControllerClassName.
    *
    *   Example:    AkInflector::demodulize('admin/dashboard_controller');  //=> dashboard_controller
    *               AkInflector::demodulize('Admin_DashboardController');  //=> DashboardController
    *               AkInflector::demodulize('Admin::Dashboard');  //=> Dashboard
    */
    static function demodulize($module_name) {
        $module_name = str_replace('::', '/', $module_name);
        return (strstr($module_name, '/') ? preg_replace('/^.*\//', '', $module_name) : (strstr($module_name, '_') ? substr($module_name, 1+strrpos($module_name,'_')) : $module_name));
    }

    /**
     * Transforms a string to its unaccented version.
     * This might be useful for generating "friendly" URLs
     */
    static function unaccent($text) {
        $map = array(
        'À'=>'A', 'Á'=>'A', 'Â'=>'A', 'Ã'=>'A', 'Ä'=>'A', 'Å'=>'A', 'Æ'=>'A', 'Ç'=>'C',
        'È'=>'E', 'É'=>'E', 'Ê'=>'E', 'Ë'=>'E', 'Ì'=>'I', 'Í'=>'I', 'Î'=>'I', 'Ï'=>'I',
        'Ð'=>'D', 'Ñ'=>'N', 'Ò'=>'O', 'Ó'=>'O', 'Ô'=>'O', 'Õ'=>'O', 'Ö'=>'O', 'Ø'=>'O',
        'Ù'=>'U', 'Ú'=>'U', 'Û'=>'U', 'Ü'=>'U', 'Ý'=>'Y', 'Þ'=>'T', 'ß'=>'s', 'à'=>'a',
        'á'=>'a', 'â'=>'a', 'ã'=>'a', 'ä'=>'a', 'å'=>'a', 'æ'=>'a', 'ç'=>'c', 'è'=>'e',
        'é'=>'e', 'ê'=>'e', 'ë'=>'e', 'ì'=>'i', 'í'=>'i', 'î'=>'i', 'ï'=>'i', 'ð'=>'e',
        'ñ'=>'n', 'ò'=>'o', 'ó'=>'o', 'ô'=>'o', 'õ'=>'o', 'ö'=>'o', 'ø'=>'o', 'ù'=>'u',
        'ú'=>'u', 'û'=>'u', 'ü'=>'u', 'ý'=>'y', 'þ'=>'t', 'ÿ'=>'y');
        return str_replace(array_keys($map), array_values($map), $text);
    }


    static function urlize($text) {
        return trim(AkInflector::underscore(AkInflector::unaccent($text)),'_');
    }


    static function slugize($text) {
        return str_replace('_','-', AkInflector::urlize($text));
    }

    /**
    * Returns $class_name in underscored form, with "_id" tacked on at the end.
    * This is for use in dealing with the database.
    *
    * @param string $class_name
    * @return string
    */
    static function foreignKey($class_name, $separate_class_name_and_id_with_underscore = true) {
        return AkInflector::underscore(AkInflector::humanize(AkInflector::underscore($class_name))).($separate_class_name_and_id_with_underscore ? "_id" : "id");
    }

    static function toControllerFilename($name) {
        $name = str_replace('::', '/', $name);
        return AkConfig::getDir('controllers').DS.join(DS, array_map(array('AkInflector','underscore'), strstr($name, '/') ? explode('/', $name) : array($name))).(stristr($name,'controller')?'':'_controller').'.php';
    }

    static function toModelFilename($name) {
        return AkConfig::getDir('models').DS.AkInflector::underscore($name).'.php';
    }

    static function toHelperFilename($name) {
        return AkConfig::getDir('app').DS.'helpers'.DS.AkInflector::underscore($name).'_helper.php';
    }

    static function toFullName($name, $correct) {
        if (strstr($name, '_') && (strtolower($name) == $name)){
            return AkInflector::camelize($name);
        }

        if (preg_match("/^(.    * )({$correct})$/i", $name, $reg)){
            if ($reg[2] == $correct){
                return $name;
            }else{
                return ucfirst($reg[1].$correct);
            }
        }else{
            return ucfirst($name.$correct);
        }
    }

    static function is_singular($singular) {
        return AkInflector::singularize(AkInflector::pluralize($singular)) == $singular;
    }

    static function is_plural($plural) {
        return AkInflector::pluralize(AkInflector::singularize($plural)) == $plural;
    }

    /**
     * Simply checks if $collection_name is the plural of $child_name
     *
     * @param string $child_name
     * @param string $collection_name
     * @return boolean
     */
    static public function isCollectionOf($child_name,$collection_name)
    {
        return AkInflector::pluralize($child_name) == $collection_name;
    }
    
    /**
     * @deprecated
     */
    static function _inflect($word, $new_value, $type, $dictionary = null) {
        Ak::deprecateWarning('AkInflector::_inflect is deprecated and will be removed in future Akelos versions. Please use AkInflector::inflect() instead.');
        return AkInflector::inflect($word, $new_value, $type, $dictionary);
    }
    /**
     * @deprecated
     */
    static function _loadConfig($dictionary) {
        Ak::deprecateWarning('AkInflector::_loadConfig is deprecated and will be removed in future Akelos versions. Please use AkInflector::loadConfig() instead.');
        return AkInflector::loadConfig($dictionary);
    }
}

