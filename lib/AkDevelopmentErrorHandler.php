<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009 Bermi Ferrer                                      |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @author Bermi Ferrer <bermi at bermilabs com>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 * @package ActiveSupport
 */

if(defined('AK_DEBUG') && AK_DEBUG){

    function ak_backtrace($only_app = false)
    {
        $result = '';
        $bt = debug_backtrace();
        $result .= ("\nBacktrace (most recent call first):\n");
        for($i = 0; $i <= count($bt) - 1; $i++)
        {
            if($bt[$i]["function"]!='ak_backtrace' && $bt[$i]["function"]!='ak_development_error_handler'){
                if(isset($bt[$i]["line"])){
                    if(strstr($bt[$i]["file"], AK_COMPILED_VIEWS_DIR) || strstr($bt[$i]["file"], AK_APP_DIR)){
                        $result .= '<div style="background-color:#ededed;padding:10px;border:1px solid #ccc;">'.ak_show_source_line($bt[$i]["file"],$bt[$i]["line"], $bt[$i]["function"], (array)@$bt[$i]['args']).'</div>';
                    }elseif(!$only_app){
                        $result .= '<div style="margin:20px 0;padding:10px;border:1px solid #ccc;">'.ak_show_source_line($bt[$i]["file"],$bt[$i]["line"], $bt[$i]["function"], (array)@$bt[$i]['args']).'</div>';
                    }
                }

            }
        }
        return $result;
    }

    function ak_show_app_backtrace()
    {
        $result = '';
        $bt = debug_backtrace();
        $result .= ("\n\Where in the application space the error occured?:\n\n\n");
        for($i = 0; $i <= count($bt) - 1; $i++)
        {
            if($bt[$i]["function"]!='ak_show_app_backtrace' && $bt[$i]["function"]!='ak_show_app_backtrace'){
                if(!isset($bt[$i]["file"])){
                    $result .= ("[PHP core called function]\n");
                }else{
                    $result .= ("File: ".$bt[$i]["file"]."\n");
                    if(empty($file[$bt[$i]["file"]])){
                        $file[$bt[$i]["file"]] = explode("\n", file_get_contents($bt[$i]["file"]));
                    }
                }
                $result .= ("    function called: ".$bt[$i]["function"])."\n";
                if(isset($bt[$i]["line"])){
                    $result .= ("    line: ".$bt[$i]["line"]."\n");
                    $result .=  "    code: ".highlight_string((trim($file[$bt[$i]["file"]][$bt[$i]["line"]-1])),true);
                }
                $result .= ("\n\n");
            }
        }
        return $result;
    }

    function ak_development_error_handler($error_number, $error_message, $file, $line)
    {
        static $_errors_shown = false;

        $error_number = $error_number & error_reporting();
        if($error_number == 0){
            return;
        }
        /**
         * resetting content-encoding header to nil,
         * if it was set to gzip before, otherwise we get an encoding error
         */
        if(AK_WEB_REQUEST) {
            $headers = headers_list();
            if (in_array('Content-Encoding: gzip', $headers) || in_array('Content-Encoding: xgzip', $headers)) {
                header('Content-Encoding: none');
            }
        }
        while (ob_get_level()) {
            ob_end_clean();
        }

        AK_WEB_REQUEST ? print('<pre>') : null;

        if(!defined('E_STRICT')) define('E_STRICT', 2048);
        if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

        switch($error_number){
            case E_ERROR:               $error_type = "Error";                  break;
            case E_WARNING:             $error_type = "Warning";                break;
            case E_PARSE:               $error_type = "Parse Error";            break;
            case E_NOTICE:              $error_type = "Notice";                 break;
            case E_CORE_ERROR:          $error_type = "Core Error";             break;
            case E_CORE_WARNING:        $error_type = "Core Warning";           break;
            case E_COMPILE_ERROR:       $error_type = "Compile Error";          break;
            case E_COMPILE_WARNING:     $error_type = "Compile Warning";        break;
            case E_USER_ERROR:          $error_type = "User Error";             break;
            case E_USER_WARNING:        $error_type = "User Warning";           break;
            case E_USER_NOTICE:         $error_type = "User Notice";            break;
            case E_STRICT:              $error_type = "Strict Notice";          break;
            case E_RECOVERABLE_ERROR:   $error_type = "Recoverable Error";      break;
            default:                    $error_type = "Unknown error ($error_number)"; break;
        }

        //$result = ": <h3>$error_message</h3> in  $file on line $line\n";
        $result = "<h3 style='padding:5px; background-color:#f00;color:#fff'>($error_type) $error_message</h3>";
        //$result .= ak_show_source_line($file, $line);
        //ak_show_app_backtrace();
        if(AK_WEB_REQUEST){
            $result .= " <a href='javascript:void(0);' onclick='ak_lib_backtrace = document.getElementById(\"ak_lib_backtrace\").style.display = \"block\";document.getElementById(\"ak_app_backtrace\").style.display = \"none\";this.style.display = \"none\"'>show full trace</a>";
            $result .= '<div id="ak_app_backtrace">'.ak_backtrace(true).'</div>';
        }

        $result .= '<div id="ak_lib_backtrace" style="display:none">'.ak_backtrace().'</div>';

        if(!$_errors_shown && $line > 0){
            $_app_files_shown = true;
            foreach (ak_get_application_included_files($file) as $type => $files){
                $result .= "<h2>$type</h2>";
                $result .= "<ul>";
                foreach ($files as $k => $file){
                    $result .= "<li style='margin:0;padding:0;'>".($file['path']).
                    (empty($file['original_path'])?'':
                    " <a href='#".md5($file['original_path']).'-'.$line."' onclick='element_$k = document.getElementById(\"ak_debug_$k\"); element_$k.style.display = (element_$k.style.display == \"none\"?\"block\":\"none\");'>show source</a>
                <div id='ak_debug_$k' style='display:none;'>".ak_highlight_file($file['original_path'], $line)."</div>").
                "</li>";
                }
                $result .= "</ul><div style='clear:both;'></div>";
            }
        }

        echo !AK_WEB_REQUEST ? html_entity_decode(strip_tags($result)) : '<div style="background-color:#fff;margin:10px;padding:10px;color:#000;font-family:sans-serif;border-bottom:3px solid #f00;font-size:12px;">'. $result.'</div>';

        AK_WEB_REQUEST ? print('</pre>') : null;

    }

    function ak_show_source_line($file, $line, $highlight = '', $params = array())
    {
        $result = ("File: ".$file."\n");
        $file = explode("\n", file_get_contents($file));
        $code = (trim($file[$line-1]));
        $code = strstr($code, '<?') ? $code : "<? $code";
        $result .= ("    line: ".$line."\n");
        $colored = preg_replace("/".('<span style="color: #0000BB">&lt;\?&nbsp;<\/span>')."(.*)/", "$1", highlight_string($code, true));
        if(!empty($highlight) && strstr($colored, $highlight)){
            $result .=  "    code: ".str_replace($highlight, '<strong style="border:1px solid red;padding:3px;background-color:#ffc;">'.$highlight."</strong>", $colored);
        }else{
            if(!empty($highlight)){
                $result .=  "    Variable function called: ".'<strong style="border:1px solid red;padding:3px;background-color:#ffc;">'.$highlight."</strong>\n";
            }
            $result .=  "    code: ".$colored;
        }


        if(!empty($params)){
            $result .=  "\n    <span style='color:#ccc;'>params:</span> \n".'<div style="background-color:#cff;margin:10px;padding:10px;color:#000;font-family:sans-serif;border:1px solid #0ff;font-size:12px;">'.ak_show_params($params).'</div>';;
        }

        $result .=  "\n\n";
        return $result;
    }

    function ak_show_params($params, $number_of_recursions = 0, $currently_inspecting = 'Array')
    {

        $preffix = (str_repeat('        ',$number_of_recursions));
        if($number_of_recursions == 10){
            return $preffix.$currently_inspecting.' [recusive limit reached]';
        }
        $number_of_recursions++;
        $result = '';
        if(!empty($params)){
            foreach ((array)$params as $k => $param){

                $result .=  $preffix."(".gettype($param).'): ';
                if(is_scalar($param)){
                    $result .=  $param;
                }elseif (is_object($param)){
                    $result .=  trim(get_class($param));
                }else{
                    $result .=  " => (\n        $preffix$k => ".trim(ak_show_params($param, $number_of_recursions))."\n$preffix)";
                }
                $result .=  $preffix." \n";
            }
        }
        return $result;
    }

    function ak_highlight_file($file, $line_number = 0)
    {
        $highlighted = highlight_file($file, true);
        $highlighted = str_replace(array('<br /></span>',"<code><span style=\"color: #000000\">\n","\n</code>"), array('</span><br />','<span style="color: #000000">','',), $highlighted);

        $lines = explode("<br />", $highlighted);


        if($line_number > 0){
            $lines[$line_number-1] = "<div style='border:1px solid red'><a name='".md5($file)."-$line_number' />".$lines[$line_number-1]."</div>";
        }
        $active_line_number=$line_number-1;

        $result = "<html><head><style media='screen'>
        tr#ak_code_line_$active_line_number{
        border:1px solid red;
        background-color:yellow;
        }
.ak_code_list {
float:left;
color:#000;
background-color:#fff;
width:700px;
}
.ak_line_numbers{
border-right:1px solid #ccc;
color:#000;
background-color:#fff;
width:30px;
float:left;
}
        </style></head><body>";
        /*
        <tr><td class='line-no'>1</td><td rowspan='".count($lines)."'><code>".
        join("<br />", $lines)."</code></td></tr><tr><td>".
        join("</td></tr><tr><td class='line-no'>", range(2,count($lines))).
        "</td></tr></table></body></html>";
        */

        $result .= "<div class='ak_line_numbers'><div>".join('</div><div>', range(1, count($lines)))."</div></div>";
        $result .= '<div class="ak_code_list" onclick="this.select()">';
        foreach ($lines as $i=>$line){
            $line = trim($line);
            $result .= '<div>'.(empty($line)?'&nbsp;':$line).'</div>';
        }
        $result .= '</div>';

        $result .= "</body></html>";

        return $result;

    }

    function ak_get_application_included_files($source_for = '')
    {
        $app_files = array();
        foreach (get_included_files() as $k => $file){
            $short_path = str_replace(AK_BASE_DIR, '', $file);
            if(strstr($file, AK_MODELS_DIR)){
                $app_files['Models'][$k]['path'] = $short_path;
                if($file == $source_for)
                $app_files['Models'][$k]['original_path'] = ($file);
            }elseif(strstr($file, AK_COMPILED_VIEWS_DIR)){
                $app_files['Views'][$k]['path'] = array_shift(explode('.tpl.', str_replace(array(AK_COMPILED_VIEWS_DIR,'/compiled'),'', $file))).'.tpl';
                if($file == $source_for)
                $app_files['Views'][$k]['original_path'] = ($file);
            }elseif(strstr($file, AK_CONTROLLERS_DIR)){
                $app_files['Controllers'][$k]['path'] = $short_path;
                if($file == $source_for)
                $app_files['Controllers'][$k]['original_path'] = ($file);
            }elseif(strstr($file, AK_HELPERS_DIR)){
                $app_files['Helpers'][$k]['path'] = $short_path;
                if($file == $source_for)
                $app_files['Helpers'][$k]['original_path'] = ($file);
            }
        }
        return $app_files;
    }

    set_error_handler('ak_development_error_handler');

    define('ADODB_OUTP', 'ak_trace_db_query');
    !defined('AK_TRACE_ONLY_APP_DB_QUERIES') && define('AK_TRACE_ONLY_APP_DB_QUERIES', true);
    !defined('AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE') && define('AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE', false);

    function ak_trace_db_query($message, $new_line = true)
    {
        if(!AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE){
            $message = preg_replace('/\([a-z0-9]+\): /','', trim($message, "\n-"));
        }
        $details = Ak::getLastFileAndLineAndMethod(AK_TRACE_ONLY_APP_DB_QUERIES);
        if(empty($details)){
            $details = array(null, null, null);
        }
        $message = trim(html_entity_decode(strip_tags($message)));
        if(AK_CLI){
            echo $message."\n";
        }else{
            Ak::trace($message, $details[1], $details[0], $details[2]);
        }
    }


}

?>
