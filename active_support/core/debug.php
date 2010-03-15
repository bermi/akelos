<?php

class AkDebug
{
    /**
    * Trace helper function for development purposes
    *
    * @access public
    * @static
    * @param    string    $text    Helper text
    * @param    string    $line    Helper line
    * @param    string    $file    Helper file
    * @return echoes result to screen
    */
    static function trace($text = null, $line = null, $file = null, $method = null, $escape_html_entities = true) {
        static $counter = 0;
        if(AK_PRODUCTION_MODE){
            return;
        }
        $html_entities_function = $escape_html_entities ? 'htmlentities' : 'trim';
        list($default_file, $default_line, $default_method) = self::getLastFileAndLineAndMethod();
        $default_method = is_bool($text) || empty($text)  ? 'var_dump' : $default_method;
        $line = is_null($line) ? $default_line : $line;
        $file = is_null($file) ? $default_file : $file;
        $method = is_null($method) ? $default_method : $method;

        if(AK_CLI){
            $text = self::dump($text, 'print_r');
        }elseif (!empty($text) && !is_scalar($text)){
            $rand = Ak::randomString();
            $formatted = '';
            $methods = array('print_r', 'var_dump', 'var_export');
            foreach ($methods as $method){
                $pre_style = 'display:none;';
                if(defined('AK_TRACE_DUMP_METHOD')){
                    if(AK_TRACE_DUMP_METHOD == $method){
                        $pre_style = '';
                    }
                }elseif ($method == 'print_r'){
                    $pre_style = '';
                }
                $element_id = $method.'_'.$rand;
                $formatted .= "<div style='margin:10px;'><a href='javascript:void(0);' onclick='e_$element_id = document.getElementById(\"$element_id\"); e_$element_id.style.display = (e_$element_id.style.display == \"none\"?\"block\":\"none\");' title='Set the constant AK_TRACE_DUMP_METHOD to your favourite default method'>$method</a><br />".
                '<pre style="'.$pre_style.'" id="'.$element_id.'">'.$html_entities_function(self::dump($text, $method)).'</pre></div>';
            }
            $text = $formatted;
        }elseif (is_bool($text) || empty($text)){
            $text = '<pre style="margin:10px;">'.$html_entities_function(self::dump($text, $default_method)).'</pre>';
        }elseif (is_scalar($text)){
            $text = '<pre style="margin:10px;">'.$html_entities_function($text).'</pre>';
        }

        if(!isset($text)){
            $counter++;
            $text = '';
        }else {
            $text = AK_CLI?'---> '.$text : ($text);
        }

        $include_file_and_line = strlen(trim($file.$line)) > 0;

        if($include_file_and_line){
            echo AK_CLI?"----------------\n$file ($line):\n $text\n----------------\n":"<div style='background-color:#fff;margin:10px;color:#000;font-family:sans-serif;border:3px solid #fc0;font-size:12px;'><div style='background-color:#ffc;padding:10px;color:#000;font-family:sans-serif;'>$file <span style='font-weight:bold'>$line</span></div>".$text."</div>\n";
        }else{
            echo AK_CLI?"----------------\n $text\n----------------\n":"<div style='background-color:#fff;margin:10px;color:#000;font-family:sans-serif;border:1px solid #ccc;font-size:12px;'>".$text."</div>\n";
        }
    }

    /**
     * Returns a string representation of one of these PHP methods var_dump, var_export, or print_r
     */
    static function dump($var, $method = null, $max_length = null) {
        $method = empty($method) ? (defined('AK_TRACE_DUMP_METHOD') ? AK_TRACE_DUMP_METHOD : 'var_dump') : $method;
        $methods = array('var_dump', 'var_export', 'print_r');
        if(!in_array($method, $methods)){
            trigger_error(Ak::t('Invalid dump method, valid options are %methods', array('%methods'=>join(", ", $methods))), E_USER_ERROR);
            return false;
        }
        ob_start();
        if(is_object($var)){
            !method_exists($var, '__toString') ? $method($var) : print($var);
        }else{
            $method($var);
        }

        $contents = ob_get_contents();
        $max_length = defined('AK_DUMP_MAX_LENGTH') ? AK_DUMP_MAX_LENGTH : 10000000;
        $result = $max_length ? substr($contents, 0, $max_length) : $contents;
        if($contents != $result){
            $result .= ' ...dump truncated at max length of '.$max_length.' chars define AK_DUMP_MAX_LENGTH to false or to a larger number';
        }
        ob_end_clean();
        return $result;
    }

    static function getLastFileAndLineAndMethod($only_app = false, $start_level = 1) {
        $backtrace = debug_backtrace();
        if(!$only_app){
            return array(@$backtrace[$start_level]['file'], @$backtrace[$start_level]['line'], @$backtrace[$start_level]['function']);
        }else{
            for($i = $start_level-1; $i <= count($backtrace) - 1; $i++){
                if(isset($backtrace[$i]["line"])){
                    if(strstr($backtrace[$i]["file"], AK_COMPILED_VIEWS_DIR) || strstr($backtrace[$i]["file"], AkConfig::getDir('app'))){
                        return array($backtrace[$i]["file"], $backtrace[$i]["line"], $backtrace[$i]["function"]);
                    }
                }
            }
        }
    }

    static function getFileAndNumberTextForError($levels = 0) {
        list($file,$line,$method) = self::getLastFileAndLineAndMethod(false, $levels+1);
        return Ak::t('In %file line %line', array('%file' => $file, '%line' => $line));
    }

    /**
    * Outputs debug info given a PHP resource (vars, objects,
    * arrays...)
    *
    * @access public
    * @static
    * @param    mixed    $data    Data to debug. It can be an object, array,
    * resource..
    * @return void Prints debug info.
    */
    static function debug ($data, $_functions=0) {
        if(!AK_DEBUG && !AK_DEV_MODE){
            return;
        }

        if($_functions!=0) {
            $sf=1;
        } else {
            $sf=0 ;
        }
        if(is_object($data) && method_exists($data, 'debug')){
            echo AK_CLI ?
            "\n------------------------------------\nEntering on ".get_class($data)." debug() method\n\n":
            "<hr /><h2>Entering on ".get_class($data)." debug() method</h2>";
            if(!empty($data->__activeRecordObject)){
                $data->toString(true);
            }
            $data->debug();
            return ;
        }
        if (isset ($data)) {
            if (is_array($data) || is_object($data)) {

                if (count ($data)) {
                    echo AK_CLI ? "/--\n" : "<ol>\n";
                    while (list ($key,$value) = each ($data)) {
                        $type=gettype($value);
                        if ($type=="array" || $type == "object") {
                            ob_start();
                            self::debug ($value,$sf);
                            $lines = explode("\n",ob_get_clean()."\n");
                            foreach ($lines as $line){
                                echo "\t".$line."\n";
                            }
                        } elseif (stristr($type, "function")) {
                            if ($sf) {
                                AK_CLI ? printf ("\t* (%s) %s:\n",$type, $key, $value) :
                                printf ("<li>(%s) <b>%s</b> </li>\n",$type, $key, $value);
                            }
                        } else {
                            if (!$value) {
                                $value="(none)";
                            }
                            AK_CLI ? printf ("\t* (%s) %s = %s\n",$type, $key, $value) :
                            printf ("<li>(%s) <b>%s</b> = %s</li>\n",$type, $key, $value);
                        }
                    }
                    echo AK_CLI ? "\n--/\n" : "</ol>fin.\n";
                } else {
                    echo "(empty)";
                }
            }
        }
    }

    /**
    * Gets information about given object
    *
    * @access public
    * @static
    * @uses AkDebug::get_this_object_methods
    * @uses AkDebug::get_this_object_attributes
    * @param    object    &$object    Object to get info from
    * @param    boolean    $include_inherited_info    By setting this to true, parent Object properties
    * and methods will be included.
    * @return string html output with Object info
    */
    static function get_object_info($object, $include_inherited_info = false) {
        $object_name = get_class($object);
        $methods = $include_inherited_info ? get_class_methods($object) : self::get_this_object_methods($object);
        $vars = $include_inherited_info ? get_class_vars($object_name) : self::get_this_object_attributes($object);
        $var_desc = '';
        if(is_array($vars)){
            $var_desc = '<ul>';
            foreach ($vars as $varname=>$var_value){
                $var_desc .= "<li>$varname = $var_value (". gettype($var_value) .")</li>\n";
            }
            $var_desc .= "</ul>";
        }
        return Ak::t('Object <b>%object_name</b> information:<hr> <b>object Vars:</b><br>%var_desc <hr> <b>object Methods:</b><br><ul><li>%methods</li></ul>',array('%object_name'=>$object_name,'%var_desc'=>$var_desc,'%methods'=>join("();</li>\n<li>",$methods) .'();'));
    }


    /**
    * Gets selected object methods.
    *
    * WARNING: Inherited methods are not returned by this
    * function. You can fetch them by using PHP native function
    * get_class_methods
    *
    * @access public
    * @static
    * @see get_this_object_attributes
    * @see get_object_info
    * @param    object    &$object    Object to inspect
    * @return array Returns an array with selected object methods. It
    * does not return inherited methods
    */
    static function get_this_object_methods($object) {
        $array1 = get_class_methods($object);
        if($parent_object = get_parent_class($object)){
            $array2 = get_class_methods($parent_object);
            $array3 = array_diff($array1, $array2);
        }else{
            $array3 = $array1;
        }
        return array_values((array)$array3);
    }



    /**
    * Get selected objects default attributes
    *
    * WARNING: Inherited attributes are not returned by this
    * function. You can fetch them by using PHP native function
    * get_class_vars
    *
    * @access public
    * @static
    * @see get_this_object_methods
    * @see get_object_info
    * @param    object    &$object    Object to inspect
    * @return void Returns an array with selected object attributes.
    * It does not return inherited attributes
    */
    static function get_this_object_attributes($object) {
        $object = get_class($object);
        $array1 = get_class_vars($object);
        if($parent_object = get_parent_class($object)){
            $array2 = get_class_vars($parent_object);
            $array3 = array_diff_assoc($array1, $array2);
        }else{
            $array3 = $array1;
        }
        return (array)$array3;
    }


    static function get_constants() {
        $constants = get_defined_constants();
        $keys = array_keys($constants);
        foreach ($keys as $k){
            if(substr($k,0,3) != 'AK_'){
                unset($constants[$k]);
            }
        }
        return $constants;
    }



    /**
     * Add a profile message that can be displayed after executing the script
     *
     * You can add benchmark markers by calling
     *
     *    Ak::profile('Searching for books');
     *
     * To display the results you need to call
     *
     *     Ak::profile(true);
     *
     * You might also find handy adding this to your application controller.
     *
     *     class ApplicationController extends BaseActionController
     *     {
     *         static function __construct(){
     *             $this->afterFilter('_displayBenchmark');
     *             parent::__construct();
     *         }
     *         static function _displayBenchmark(){
     *             Ak::profile(true);
     *         }
     *     }
     *
     * IMPORTANT NOTE: You must define AK_ENABLE_PROFILER to true for this to work.
    */
    static function profile($message = '') {
        if(AK_ENABLE_PROFILER){
            if(!$ProfileTimer = $Timer = Ak::getStaticVar('ProfileTimer')){
                require_once 'Benchmark/Timer.php';
                $ProfileTimer = new Benchmark_Timer();
                $ProfileTimer->start();
                Ak::setStaticVar('ProfileTimer', $ProfileTimer);
            }elseif($message === true){
                $ProfileTimer->display();
            }else {
                $ProfileTimer->setMarker($message);
            }
        }
    }

}