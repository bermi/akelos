<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

if(!empty($options['help'])){
    die(<<<HELP
Creating releases for Akelos applications.

This build script will generate a release for your application using

    "git archive --format=? --prefix=? HEAD | gzip > ./releases/?.tar.gz"

Valid options are:

    --app_name  Application name. (defaults to AK_APP_NAME)
    --version   Version number for your release (by default it will use the
                version file version.txt on your application base). Setting
                the version number will disable (--status,--minor,--major)
    --status    When using the default version convention it will add the
                status number on the third position of the version
                number #.#.# <- , valid options are:
                alpha (0), beta (1), rc (2), pr (3)
    --commit    Include commit hash in the version name. (default false)
    --minor     When using the default version convention it will add the
                minor version number on the second position of the version
                number #.# <-
    --major     When using the default version convention it will add the
                major version number on the first position of the version
                number # <-
    --tag       Tag for the file being generated. (Ie: nighly, ci...)
    --version_file
                Version file path. Defaults to (version.txt)
    --skip_version_update
                The version file will not be updated.
                Disables --commit_version
    --skip_commit_version
                Commits version file before archiving.
    --path      Where shall we put the release (./releases/ by default)
    --format    Comma separated list of release file formats. By default
                it will use all formats git supports "git archive -l"
    --skip_gzip Tarfiles will be gzipped unless this option is set
    --revision  Repositiory revision, default HEAD
    --checksum  Generate checksum files

HELP
);
}
$default_app_name = (AK_APP_NAME == 'Application') ? basename(MAKELOS_BASE_DIR) : AK_APP_NAME;
$is_akelos_core = empty($options['app_name']) && $default_app_name=='Application' && !file_exists(AkConfig::getDir('config').DS.'config.php');

$available_formats = array_diff(explode("\n", @`git archive -l`), array(''));
if(empty($available_formats)){
    die("Could not find archive formats when running 'git archive -l'\n");
}
$options['format'] = !empty($options['format']) ? Ak::stringToArray($options['format']) : $available_formats;

foreach ($options['format'] as $format){
    if(!in_array($format, $available_formats)){
        die("Format ".$format." not supported by git. 'git archive -l' reports these available formats: ".join(",", $available_formats)."\n");
    }
}

$options['app_name'] = AkInflector::underscore(empty($options['app_name']) ? $is_akelos_core?'akelos':$default_app_name:$options['app_name']);
$options['revision'] = empty($options['revision']) ? 'HEAD' : $options['revision'];

if($options['revision'] == 'HEAD' && preg_match('/commit (.+)/', `git log --no-color --abbrev-commit -n 1`, $matches)){
    $options['revision'] = $matches[1];
}

$options['revision'] = trim($options['revision'], '. ');

$options['commit'] = isset($options['commit']) ? $is_akelos_core : false;

$version_file = empty($options['version_file']) ? MAKELOS_BASE_DIR.DS.'version.txt' : $options['version_file'];

if(!isset($options['version'])){
    $version_candidate = @file_get_contents($version_file);
    if(empty($version_candidate)){
        die("You need to provide a valid version number or create a version.txt file\n");
    }

    list($major, $minor, $status) = explode('.', str_replace('-','.', $version_candidate).'...');

    if(!empty($options['status'])){
        $status = is_numeric($options['status']) ? $options['status'] : array_search($options['status'], array('alpha', 'beta', 'rc', 'pr'));
    }
    if(!empty($options['minor']) && $minor != $options['minor']){
        $minor = $options['minor'];
    }
    if(!empty($options['major']) && $major != $options['major']){
        $major = $options['major'];
    }
    $options['version'] = ((int)$major).'.'.((int)$minor).'.'.((int)$status);

}


$options['tag'] = empty($options['tag']) ? '' : '-'.$options['tag'];

$skip_updating_version_number = !empty($options['skip_version_update']) || $options['tag'] == 'ci' || $options['tag'] == 'nighly';

$options['path'] = empty($options['path']) ? MAKELOS_BASE_DIR.DS.'releases' : $options['path'];
strstr($options['path'], MAKELOS_BASE_DIR) && AkFileSystem::make_dir($options['path'], array('base_path'=>MAKELOS_BASE_DIR));

echo "Building version ".$options['version'].$options['tag']."\n";
if(!$skip_updating_version_number){
    file_put_contents($version_file, $options['version']);
    if(false && !empty($options['skip_commit_version'])){
        @`git add $version_file`;
        @`git commit $version_file -m "Updating version"`;
    }
}

$preffix = $options['app_name'].'-'.$options['version'].($options['commit']?'-'.$options['revision']:'').$options['tag'];

foreach ($options['format'] as $format){
    $file_path = "{$options['path']}/$preffix";
    if($format == 'tar' && empty($options['skip_gzip'])){
        $file_name = $file_path.'.tar.gz';
        $command = "git archive --format=$format --prefix=$preffix/ {$options['revision']} | gzip > $file_name";
    }else{
        $file_name = $file_path.'.'.$format;
        $command = "git archive --format=$format --prefix=$preffix/ {$options['revision']} > $file_name";
    }
    if(!empty($options['checksum'])){
        file_put_contents($file_path.'.checkum', md5_file($file_name));
    }

    echo "running $command\n";
    echo `$command`;
}
echo "done\n";
