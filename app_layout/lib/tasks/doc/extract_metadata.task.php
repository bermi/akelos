<?php 
// Command line options are accesible via $options

if(!empty($options['help'])){
    die(<<<HELP
Describe your task.

Valid options are:

    --base_path     Comma sepparated paths to start looking for source code 
                    documents. Accepts wildcards.
    --destination   Where to store class metada. (default docs/)
    --recurse       Recurse into base_path directories in search for more files
    --extenssions   Comma separated list of extenssions considered source code.
                    (by default just .php)
    --skip          Comma separated list of patterns to exclude from the 
                    file list. You can use regular expressions.
                    (by default /locales\/.+\.php/)
    --package_file  File name for documenting the package on the source dir
                    (default README)
    --help          Shows this message

HELP
);
}

if(empty($options['base_path'])){
    $options['base_path'] = AK_FRAMEWORK_DIR.DS.'a*';
}
$paths = array_diff(array_map('trim', explode(',', $options['base_path'].',')), array(''));

if(empty($options['destination'])){
    $options['destination'] = AK_DOCS_DIR;
}

if(empty($options['extenssions'])){
    $options['extenssions'] = 'php,markdown';
}
$extenssions = array_diff(array_map('trim', explode(',', $options['extenssions'].',')), array(''));

if(empty($options['package_file'])){
    $options['package_file'] = 'README.markdown';
}

if(empty($options['skip'])){
    $options['skip'] = '/locales\/.+\.php/,akelos_utils';
}
$skip = array_diff(array_map('trim', explode(',', $options['skip'].',')), array(''));

if(empty($options['recurse'])){
    $options['recurse'] = true;
}

$all_files = array();
foreach ($paths as $path){

    foreach ($extenssions as $extenssion){
        $extenssion = trim($extenssion, ' .');
        $extra_paths = array(
        $path.'*.'.$extenssion,
        $path.'*'.DS.'*.'.$extenssion,
        $path.'*'.DS.'**'.DS.'*.'.$extenssion,
        $path.'*'.DS.'**'.DS.'**'.DS.'*.'.$extenssion,
        $path.'*'.DS.'**'.DS.'**'.DS.'**'.DS.'*.'.$extenssion,
        $path.'*'.DS.'**'.DS.'**'.DS.'**'.DS.'**'.DS.'*.'.$extenssion
        );
        foreach ($extra_paths as $extra_path){
            $all_files = array_merge($all_files, glob($extra_path));
        }
    }
}

sort($all_files);
$all_files = array_diff(array_unique($all_files), array(''));

$filtered_files = array();
$has_skip = !empty($skip);

foreach ($all_files as $file){
    if(!$has_skip){
        $filtered_files[] = $file;
    }else{
        $should_skip = false;
        foreach ($skip as $pattern){
            if($pattern[0] == '/'){
                $should_skip = preg_match($pattern, $file);
            }else{
                $should_skip = strstr($file, $pattern);
            }
            if($should_skip){
                break;
            }
        }
        if(!$should_skip){
            $filtered_files[] = $file;
        }
    }
}

$packages = array();
foreach ($filtered_files as $k => $filtered_file){
    if(basename($filtered_file) == $options['package_file']){
        unset($filtered_files[$k]);
        $dirname = dirname($filtered_file);
        $base_package_dir = realpath($dirname.DS.'..'.DS);
        $package_name = trim(str_replace($base_package_dir, '', $dirname), DS.'/');
        $packages[$package_name] = $dirname;
    }
}

$package_files = array();

foreach ($packages as $package_name => $file_path){
    foreach ($filtered_files as $filtered_file){
        if(strstr($filtered_file, $file_path)){
            $package_files[$package_name][trim(str_replace($file_path, '', $filtered_file), '/')] = $filtered_file;
        }
    }
}

$destination = rtrim($options['destination'], DS).DS.'metadata';
foreach ($package_files as $package_name => $files){
    Ak::file_put_contents($destination.DS.'files'.DS.$package_name.'.php',
    '<?php $metadata = '.var_export(array('files' => $files), true). '; return $metadata;');
}

$destination = rtrim($options['destination'], DS).DS.'refactored';
foreach ($package_files as $package_name => $files){
    foreach ($files as $base_file_path => $file){
        Ak::file_put_contents(
        $destination.DS.$package_name.DS.$base_file_path,
        file_get_contents($file));
    }
}

$destination = rtrim($options['destination'], DS).DS.'textile';
foreach ($package_files as $package_name => $files){
    foreach ($files as $base_file_path => $file){
        Ak::file_put_contents(
        $destination.DS.$package_name.DS.str_replace('.php', '.textile', $base_file_path),
        file_get_contents($file));
    }
}


//print_r($package_files);