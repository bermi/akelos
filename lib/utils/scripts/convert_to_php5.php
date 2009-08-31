<?php /* © 2009 Akelos PHP Framework (LGPL'd licensed) - http://www.akelos.org/copyright */

array_shift($argv);
$options = $argv;

$path = array_shift($options);

if(is_file($path)){
    $files = array($path);
}else{
    $files = ak_dir($path, array('recurse' => true));
}

if(empty($files)){
    die("\nNo files found!\n");
}

if(ak_prompt("These files will be converted to PHP5:\n\n".join("\n", $files)."\n\nDid you backup these files?", array('default'=>'yes')) == 'yes'){
    foreach ($files as $file){
        $php4_contents = file_get_contents($file);
        $php5_contents = convert_to_php5($php4_contents);

        $php4_lines = (array_diff(explode("\n", $php4_contents), explode("\n", $php5_contents)));
        $php5_lines = (array_diff(explode("\n", $php5_contents), explode("\n", $php4_contents)));

        if(!empty($php5_lines)){
            echo "\n\n--------\nPHP5 version for $file\n--------\n\n".$php5_contents."\n\n";
            echo "\n\n--------\n\nModified lines for $file:\n";
            foreach ($php4_lines as $line_number => $php4_line){
                echo "\n- $php4_line";
                echo "\n+ ".$php5_lines[$line_number];
            }

            echo "\n--------\n\n";

            if(ak_prompt("Do you want to replace $file with the above contents?", array('default'=>'yes')) == 'yes'){
                file_put_contents($file, $php5_contents);
            }else {
                echo "\nSkiping $file\n\n";
            }
        }else{
            echo "\nNo changes for $file\n\n";
        }
    }
}else {
    echo "Take your time to backup your files as something might go wrong while converting to PHP5";
}


echo "\n";


function convert_to_php5($code){
    $replacements = array(
    '/&(\s?)new /' => '$1new ',
    '/(\s{4,})function /' => '$1public function ',
    '/(?<!array)([\(,])(\s?)&(\s?)\$/' => '$1$2$3$',
    '/(\s{4,})var(\s+)\$/' =>   '$1public$2$');

    foreach ($replacements as $k => $v){
        if(preg_match_all($k, $code, $matches)){
            $code = preg_replace($k, $v, $code);
        }
    }

    return $code;
}

function ak_dir($path, $options = array())
{
    $result = array();

    $path = rtrim($path, '/\\');
    $default_options = array(
    'files' => true,
    'dirs' => true,
    'recurse' => false,
    );

    $options = array_merge($default_options, $options);

    if(is_file($path)){
        $result = array($path);
    }elseif(is_dir($path)){
        if ($id_dir = opendir($path)){
            while (false !== ($file = readdir($id_dir))){
                if ($file != "." && $file != ".." && $file != '.svn'){
                    if(!empty($options['files']) && !is_dir($path.DS.$file)){
                        $result[] = $path.DS.$file;
                    }elseif(!empty($options['dirs'])){
                        if(!empty($options['recurse'])){
                            $result = array_merge($result, ak_dir($path.DS.$file, $options));
                        }else{
                            $result[] = $path.DS.$file;
                        }
                    }
                }
            }
            closedir($id_dir);
        }
    }

    return array_reverse($result);
}


function ak_prompt($message, $options = array())
{
    $f = fopen("php://stdin","r");
    $default_options = array(
    'default' => null,
    'optional' => false,
    );

    $options = array_merge($default_options, $options);

    echo "\n".$message.(empty($options['default'])?'': ' ['.$options['default'].']').': ';
    $user_input = fgets($f, 25600);
    $value = trim($user_input,"\n\r\t ");
    $value = empty($value) ? $options['default'] : $value;
    if(empty($value) && empty($options['optional'])){
        echo "\n\nThis setting is not optional.";
        fclose($f);
        return ak_prompt($message, $options);
    }
    fclose($f);
    return empty($value) ? $options['default'] : $value;
}

?>
