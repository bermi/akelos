<?php

function ak_development_error_handler($error_number, $error_message, $file, $line) {
    $error_number = $error_number & error_reporting();
    if($error_number == 0){
        return false;
    }
    if(AK_WEB_REQUEST){
        echo "<pre>";
    }
    throw new Exception($error_message);
    if(AK_WEB_REQUEST){
        echo "</pre>";
    }
}

include_once(dirname(__FILE__).DS.'error_functions.php');

set_error_handler('ak_development_error_handler');


return;
/**
?>

if(defined('AK_DEBUG') && AK_DEBUG){

    function ak_development_error_handler($error_number, $error_message, $file, $line) {
        static $_sent_errors = array(), $_errors_shown = false;

        $error_number = $error_number & error_reporting();

        if($error_number == 0){
            return false;
        }
        /**
         * resetting content-encoding header to nil,
         * if it was set to gzip before, otherwise we get an encoding error
         * /
        if(AK_WEB_REQUEST) {
            $headers = headers_list();
            if (in_array('Content-Encoding: gzip', $headers) || in_array('Content-Encoding: xgzip', $headers)) {
                header('Content-Encoding: none');
            }
        }

        while (ob_get_level()) {
            ob_end_clean();
        }

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


        if(isset($_sent_errors[$error_type.$error_message])){

            return false;
        }else{
            $_sent_errors[$error_type.$error_message] = true;
        }

        $result = AK_DEBUG_OUTPUT_AS_HTML ? '<pre>' : '';

        //$result = ": <h3>$error_message</h3> in  $file on line $line\n";
        $result .= "<div style='text-align:left;'><h3 style='padding:5px; background-color:#f00;color:#fff;margin-bottom:0px;'>($error_type) $error_message</h3>";
        $result .= "<p style='padding:5px;background-color:#ffc;color:#666;margin-top:0px;'>in <b>$file</b> on line <b>$line</b></p>";
        //$result .= ak_show_source_line($file, $line);
        //ak_show_app_backtrace();
        if(AK_DEBUG_OUTPUT_AS_HTML){
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
        $result .= '</div>';
        
        $result .= !AK_DEBUG_OUTPUT_AS_HTML ? html_entity_decode(strip_tags($result)) : '<div style="background-color:#fff;margin:10px;padding:10px;color:#000;font-family:sans-serif;border-bottom:3px solid #f00;font-size:12px;">'. $result.'</div>';

        $result .= AK_DEBUG_OUTPUT_AS_HTML ? '</pre>' : '';
        
        echo $result;
        //throw Exception($result);

        return false;

    }

    set_error_handler('ak_development_error_handler');

    define('ADODB_OUTP', 'ak_trace_db_query');
    !defined('AK_TRACE_ONLY_APP_DB_QUERIES') && define('AK_TRACE_ONLY_APP_DB_QUERIES', true);
    !defined('AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE') && define('AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE', false);

    function ak_trace_db_query($message, $new_line = true) {
        if(Ak::getStaticVar('ak_trace_db_query') === false){
            return ;
        }
        if(!AK_TRACE_DB_QUERIES_INCLUDES_DB_TYPE){
            $message = preg_replace('/\([a-z0-9]+\): /','', trim($message, "\n-"));
        }
        $details = Ak::getLastFileAndLineAndMethod(AK_TRACE_ONLY_APP_DB_QUERIES);
        if(empty($details)){
            $details = array(null, null, null);
        }
        $message = trim(html_entity_decode(strip_tags($message)));
        if(!AK_DEBUG_OUTPUT_AS_HTML){
            echo $message."\n";
        }else{
            Ak::trace($message, $details[1], $details[0], $details[2]);
        }
    }
}

*/