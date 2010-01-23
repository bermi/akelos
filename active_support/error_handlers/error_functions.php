<?php

if(!defined('AK_DEBUG_OUTPUT_AS_HTML')){
    if(AK_WEB_REQUEST){
        $is_ajax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strstr(strtolower($_SERVER['HTTP_X_REQUESTED_WITH']),'xmlhttprequest');
        define('AK_DEBUG_OUTPUT_AS_HTML', !$is_ajax);
    }else{
        define('AK_DEBUG_OUTPUT_AS_HTML', false);
    }
}

function ak_show_source_line($file, $line, $highlight = '', $params = array()) {
    $result = ("File: ".$file."\n");

    $file = explode("\n", file_get_contents($file));
    $code = (trim($file[$line-1]));

    $code = AK_DEBUG_OUTPUT_AS_HTML ? (strstr($code, '<?') ? $code : "<? $code") : $code;
    $result .= ("    line: ".$line."\n");
    $colored = AK_DEBUG_OUTPUT_AS_HTML ? (preg_replace("/".('<span style="color: #0000BB">&lt;\?&nbsp;<\/span>')."(.*)/", "$1", highlight_string($code, true))) : $code;
    if(AK_DEBUG_OUTPUT_AS_HTML && !empty($highlight) && strstr($colored, $highlight)){
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

function ak_show_params($params, $number_of_recursions = 0, $currently_inspecting = 'Array') {

    $preffix = (str_repeat('        ',$number_of_recursions));
    if($number_of_recursions == 10){
        return $preffix.$currently_inspecting.' [recursion limit reached]';
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
                $result .=  " => (\n        $preffix".(!is_numeric($k)?"$k => ":'').trim(ak_show_params($param, $number_of_recursions))."\n$preffix)";
            }
            $result .=  $preffix." \n";
        }
    }
    if(strlen($result) > 400){
        return substr($result,0,400);
    }
    return $result;
}

function ak_highlight_file($file, $line_number = 0) {
    $highlighted = highlight_file($file, true);
    $highlighted = str_replace(array('<br /></span>',"<code><span style=\"color: #000000\">\n","\n</code>"), array('</span><br />','<span style="color: #000000">','',), $highlighted);

    $lines = explode("<br />", $highlighted);


    if($line_number > 0){
        $lines[$line_number-1] = "<div style='border:1px solid red'><a name='".md5($file)."-$line_number' />".$lines[$line_number-1]."</div>";
    }
    $active_line_number=$line_number-1;

    $result = "<html><head><style media='screen'>tr#ak_code_line_$active_line_number{ border:1px solid red;background-color:yellow;} .ak_code_list {float:left;color:#000;background-color:#fff;width:700px;text-align:left;} .ak_line_numbers{border-right:1px solid #ccc;color:#000;background-color:#fff;width:30px;float:left;}</style></head><body>";
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

function ak_get_application_included_files($source_for = '') {
    $app_files = array();
    foreach (get_included_files() as $k => $file){
        if(strstr($file, AK_FRAMEWORK_DIR)) continue;
        $short_path = str_replace(AK_BASE_DIR, '', $file);
        if(strstr($file, AK_MODELS_DIR)){
            $app_files['Models'][$k]['path'] = $short_path;
            if($file == $source_for)
            $app_files['Models'][$k]['original_path'] = ($file);
        }elseif(strstr($file, AK_COMPILED_VIEWS_DIR)){
            $path = Ak::first(explode('.tpl.', str_replace(array(AK_COMPILED_VIEWS_DIR,'/compiled'),'', $file))).'.tpl';
            if(!in_array($path, array('/app/views/exception.tpl', '/app/views/_trace.tpl'))){
                $app_files['Views'][$k]['path'] = $path;
                if($file == $source_for)
                $app_files['Views'][$k]['original_path'] = ($file);
            }
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

