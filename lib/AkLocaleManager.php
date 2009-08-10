<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage I18n-L10n
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!defined('AK_AVAILABLE_LOCALES')){
    define('AK_AVAILABLE_LOCALES',AK_FRAMEWORK_LANGUAGE);
}

require_once(AK_LIB_DIR.DS.'AkObject.php');

defined('AK_FRAMEWORK_LANGUAGE') ? null : define('AK_FRAMEWORK_LANGUAGE', 'en');

class AkLocaleManager extends AkObject
{

    var $available_locales = array(AK_FRAMEWORK_LANGUAGE=>array(AK_FRAMEWORK_LANGUAGE));
    var $browser_lang = array(AK_FRAMEWORK_LANGUAGE);

    function init()
    {
        if(AK_AVAILABLE_LOCALES == 'auto'){
            $this->available_locales = $this->_getAvailableLocales();
        }elseif(AK_AVAILABLE_LOCALES != 'en'){
            $this->available_locales = $this->_parseLocaleConfigString(AK_AVAILABLE_LOCALES);
        }
    }

    function _getAvailableLocales()
    {
        static $available_locales;

        if(empty($available_locales)){
            $available_locales = array();
            $d = dir(AK_CONFIG_DIR.DS.'locales');
            while (false !== ($entry = $d->read())) {
                if (preg_match('/\\.php$/', $entry)){
                    $locale = str_replace('.php','',$entry);
                    $available_locales[$locale] = array($locale);
                }
            }
            $d->close();
        }

        return $available_locales;
    }

    function _parseLocaleConfigString($locale_settings)
    {
        $locale_settings = trim(str_replace(' ','',$locale_settings));
        $locale_settings = str_replace(array(';','(',')'), array(',','~','',''),$locale_settings);
        $available_locales = strstr($locale_settings,',') ? explode(',',$locale_settings) : array($locale_settings);
        $locales = array();
        foreach ($available_locales as $locale_string){
            if(strstr($locale_string,'~')){
                $tmp_arr = explode('~',$locale_string);
                $locale_string = $tmp_arr[0];
                $locale_alias = array($locale_string);
                if(strstr($tmp_arr[1],'|')){
                    $locale_alias = array_merge($locale_alias, explode('|',$tmp_arr[1]));
                }else{
                    $locale_alias = array_merge($locale_alias, array($tmp_arr[1]));
                }
            }else {
                $locale_alias = array($locale_string);
            }


            $locales[trim($locale_string)] = $locale_alias;
        }

        return $locales;
    }

    function getBrowserLanguages()
    {
        $browser_accepted_languages = str_replace('-','_', strtolower(preg_replace('/q=[0-9\.]+,*/','',@$_SERVER['HTTP_ACCEPT_LANGUAGE'])));
        $browser_languages = (array_diff(split(';|,',$browser_accepted_languages.','), array('')));
        if(empty($browser_languages)){
            return array($this->_getDefaultLocale());
        }
        return array_unique($browser_languages);
    }


    function getDefaultLanguageForUser()
    {
        $browser_languages = $this->getBrowserLanguages();

        // First run for full locale (en_us, en_uk)
        foreach ($browser_languages as $browser_language){
            if(isset($this->available_locales[$browser_language])){
                return $browser_language;
            }
        }

        // Second run for only language code (en, es)
        foreach ($browser_languages as $browser_language){
            if($pos = strpos($browser_language,'_')){
                $browser_language = substr($browser_language,0, $pos);
                if(isset($this->available_locales[$browser_language])){
                    return $browser_language;
                }
            }
        }
        return $this->_getDefaultLocale();
    }

    function _getDefaultLocale()
    {
        $available_locales = $this->available_locales;
        $default_locale = array_shift($available_locales);
        return is_array($default_locale) ? $default_locale[0] : $default_locale;
    }


    function getUsedLanguageEntries($lang_entry = null, $controller = null)
    {
        static $_locale_entries = array();
        
        if(isset($controller)){
            $_locale_entries[$controller][$lang_entry] = $lang_entry;
        } else if(isset($lang_entry)) {
            $_locale_entries[$lang_entry] = $lang_entry;
        }
        if(!isset($lang_entry)){
            return $_locale_entries;
        }
    }
    function _getNewEntries($array,$existing = array())
    {
        foreach($array as $key => $value) {
            $value=trim($value);
            if(empty($value) || isset($existing[$key])) unset($array[$key]);
        }
        return $array;
    }
    /**
     * @todo Refactor this method
     */
    function updateLocaleFiles()
    {
        if(defined('AK_LOCALE_MANAGER') && class_exists(AK_LOCALE_MANAGER) && in_array('AkLocaleManager',class_parents(AK_LOCALE_MANAGER))) {
            return;
        }
        
        $new_core_entries = array();
        $new_controller_entries = array();
        $new_controller_files = array();
        $used_entries = AkLocaleManager::getUsedLanguageEntries();
        list($core_locale,$core_dictionary) = self::getCoreDictionary(AK_FRAMEWORK_LANGUAGE);
        $controllers_dictionaries = array();

        foreach ($used_entries as $k=>$v){
            // This is a controller file
            if(is_array($v)){
                if(!isset($controllers_dictionaries[$k])){
                    $controller = $k;
                    
                    $controllers_dictionaries[$controller]=self::getDictionary(AK_FRAMEWORK_LANGUAGE,$controller);
                    if(!empty($controllers_dictionaries[$controller])) {
                        $existing_controllers_dictionaries[$controller] =$controllers_dictionaries[$controller];
                    } else {
                        $new_controller_files[$controller] = true;
                    }
                    $controllers_dictionaries[$controller]=array_merge($controllers_dictionaries[$controller], (array)$v);
                    
                }
            }else {
                if(!isset($core_dictionary[$k])){
                    $new_core_entries[$k] = $k;
                }
            }
        }

        foreach ($new_controller_files as $controller=>$true){
            self::setDictionary($controllers_dictionaries[$controller],AK_FRAMEWORK_LANGUAGE,$controller,"File created on: ".date("Y-m-d G:i:s",Ak::time()));
            foreach (Ak::langs() as $lang){
                if($lang != AK_FRAMEWORK_LANGUAGE){
                    $dictionary  = self::getDictionary($lang,$controller);
                    self::setDictionary(array_merge($controllers_dictionaries[$controller],$dictionary),$lang,$controller);
                }
            }
            unset($controllers_dictionaries[$controller]);
        }

        // Module files
        foreach ((array)$controllers_dictionaries as $controller => $controller_entries){
            $controller_entries=self::_getNewEntries($controller_entries,(array)@$existing_controllers_dictionaries[$controller]);
            if(!empty($controller_entries)) {
                $dictionary  = self::getDictionary(AK_FRAMEWORK_LANGUAGE,$controller);
                self::setDictionary(array_merge($dictionary,$controller_entries),AK_FRAMEWORK_LANGUAGE,$controller);
                foreach (Ak::langs() as $lang){
                    if($lang != AK_FRAMEWORK_LANGUAGE){
                        $dictionary  = self::getDictionary($lang,$controller);
                        self::setDictionary(array_merge($dictionary,$controller_entries),$lang,$controller);
                    }
                }
            }
        }

        // Core locale files
        $new_core_entries=self::_getNewEntries($new_core_entries);
        if(!empty($new_core_entries)) {
            self::setCoreDictionary($core_locale,array_merge($core_dictionary,$new_core_entries),AK_FRAMEWORK_LANGUAGE);
            foreach (Ak::langs() as $lang){
                if($lang != AK_FRAMEWORK_LANGUAGE){
                    list($l,$dictionary)  = self::getCoreDictionary($lang);
                    if(empty($l)) {
                        $l=$core_locale;
                        $l['description']=$lang;
                        $l['locale_description']=$lang;
                    }
                    if(empty($dictionary)) {
                        $dictionary=$core_dictionary;
                    }
                    self::setCoreDictionary($l,array_merge($dictionary,$new_core_entries),$lang);
                }
            }
        }
    }



    /**
     * The following functions are for handling i18n when using url based interfaces
     */


    function initApplicationInternationalization(&$Request)
    {
        if(!defined('AK_APP_LOCALES')){
            define('AK_APP_LOCALES',join(',',array_keys($this->available_locales)));
        }
        $lang = $this->_getLocaleForRequest($Request);
        $previous_lang = $this->getNavigationLanguage();

        $this->rememberNavigationLanguage($lang);

        $Request->_request['lang'] = $lang;
        $Request->lang = $lang;
        $Request->previous_lang = $previous_lang;
    }

    /**
     * Returns an array which locales enabled on the public website.
     * In order to define available languages you must define AK_PUBLIC_LOCALES
     * which a comma-separated list of locales
     *
     * @return array
     */
    function getPublicLocales()
    {
        static $public_locales;
        if(empty($public_locales)){
            $public_locales = defined('AK_PUBLIC_LOCALES') ?
            Ak::toArray(AK_PUBLIC_LOCALES) :
            array_keys($this->available_locales);
        }
        return $public_locales;
    }
    function getCoreDictionary($language,$set=false,$set_data=null)
    {
            static $dictionaries=array();
            $path = AK_CONFIG_DIR.DS.'locales'.DS.basename($language).'.php';
            if($set===true && is_array($set_data)) {
                $dictionaries[$path]=$set_data;
                return;
            }
            if(!isset($dictionaries[$path])) {
                if(is_file($path)) {
                    
                    require($path);
                    
                    $dictionaries[$path]=array((array)@$locale,(array)@$dictionary);
                } else {
                    $dictionaries[$path]=array(array(),array());
                }
                
            }
            return $dictionaries[$path];
           
    }
    
    
    function getDictionary($language,$namespace=false,$set=false,$set_data=null)
    {
        static $dictionaries=array();
        $path = AK_APP_DIR.DS.'locales'.DS.($namespace?trim(Ak::sanitize_include($namespace,'high'),DS).DS:'').basename($language).'.php';
        
        if($set===true && is_array($set_data)) {
            $dictionaries[$path]=$set_data;
            return;
        }
        if(empty($dictionaries[$path])) {
            if(is_file($path)) {
                require($path);
                $dictionaries[$path]=(array)$dictionary;
                return $dictionaries[$path];
            } 
            $dictionaries[$path]=array();
        }
        return $dictionaries[$path];
    }
    function setCoreDictionary($locale,$dictionary, $language,$comment=null)
    {

        $path = AK_CONFIG_DIR.DS.'locales'.DS.basename($language).'.php';
        AkLocaleManager::getCoreDictionary($language,true,array($locale,$dictionary));
        return Ak::file_put_contents($path,"<?php\n/** $comment */\n\n\$locale=".var_export((array)$locale,true).";\n\n\$dictionary=".var_export((array)$dictionary,true).";\n");
    }
    function deleteDictionary($language,$namespace)
    {
        $path = AK_APP_DIR.DS.'locales'.DS.($namespace?trim(Ak::sanitize_include($namespace,'high'),DS).DS:'').basename($language).'.php';
        AkLocaleManager::getDictionary($language,$namespace,true,array());
        clearstatcache();
        return (file_exists($path)?@unlink($path):false);
    }
    function deleteCoreDictionary($language)
    {
        $path = AK_CONFIG_DIR.DS.'locales'.DS.basename($language).'.php';
        AkLocaleManager::getCoreDictionary($language,true,array(array(),array()));
        clearstatcache();
        return (file_exists($path)?@unlink($path):false);
    }
    function setDictionary($dictionary,$language,$namespace=false,$comment=null)
    {
        $path = AK_APP_DIR.DS.'locales'.DS.($namespace?trim(Ak::sanitize_include($namespace,'high'),DS).DS:'').basename($language).'.php';
        AkLocaleManager::getDictionary($language,$namespace,true,$dictionary);
        return Ak::file_put_contents($path,"<?php\n/** $comment */\n\n\$dictionary=".var_export((array)$dictionary,true).";\n");
    }
    function _getLocaleForRequest(&$Request)
    {
        $lang = $this->getNavigationLanguage();

        if($url_locale = $this->getLangFromUrl($Request)){
            $lang = $this->getLocaleFromAlias($url_locale);
        }

        if(!$this->_canUseLocaleOnCurrentRequest($lang, $Request)){
            $lang = array_shift($this->getPublicLocales());
        }elseif (empty($lang)){
            $lang = array_shift($this->getPublicLocales());
        }

        // This way we store on get_url_locale and on lang the value of $lang on
        // a static variable for accessing it application wide
        empty($url_locale) ? null : Ak::get_url_locale($url_locale);
        Ak::lang($lang);

        return $lang;
    }

    function _canUseLocaleOnCurrentRequest($lang, &$Request)
    {
        return in_array($lang, $this->getPublicLocales());
    }


    function getLangFromUrl(&$Request)
    {
        $lang = false;

        if(isset($Request->lang)){
            return $Request->lang;
        }

        if(isset($Request->ak)){
            $regex_arr = array();
            $match = false;

            foreach ($this->available_locales as $lang=>$aliases){
                foreach ($aliases as $alias){
                    $regex_arr[] = '('.$alias.')(\/){1}';
                }
            }
            $regex = '/^('.join('|',$regex_arr).'){1}/';

            if (preg_match($regex, trim($Request->ak,'/').'/', $match)){
                $lang = trim($match[0],'/');
                if(empty($lang)){
                    unset($Request->_request['ak'], $Request->ak);
                }else{
                    $Request->ak = $Request->_request['ak'] = ltrim(substr_replace(trim($Request->ak,'/'),'',0,strlen($lang)), '/');
                }
            }else {
                return false;
            }
        }

        $lang = isset($Request->lang) ? $Request->lang : $lang;

        return $lang;
    }

    function rememberNavigationLanguage($lang)
    {
        if(isset($_SESSION) && !empty($lang)){
            $_SESSION['lang'] = $lang;
        }
    }

    function getNavigationLanguage()
    {
        if(!isset($_SESSION['lang'])){
            $this->browser_lang = $this->getDefaultLanguageForUser();
            return $this->getDefaultLanguageForUser();
        }else{
            return $_SESSION['lang'];
        }
    }

    function getLocaleFromAlias($alias)
    {
        foreach ($this->available_locales  as $locale=>$locale_arr){
            if(in_array($alias,$locale_arr)){
                return $locale;
            }
        }

        return false;
    }

}

?>
