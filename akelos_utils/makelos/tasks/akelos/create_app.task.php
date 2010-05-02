<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

if(count($options) == 1 && array_keys($options) === array_values($options)){
    $options['d'] = array_shift($options);
}

if(!empty($options['v']) || !empty($options['version'])){
    echo AKELOS_VERSION."\n";
    exit(0);
}
if(!empty($options['base_dir'])){
    echo AK_BASE_DIR."\n";
    exit(0);
}

if(empty($options) || !empty($options['h']) || !empty($options['help'])){
    die(<<<HELP
Description:
    The 'akelos' command creates a new Akelos application with a default
    directory structure and configuration at the path you specify.

Example:
    akelos ~/Code/PHP/weblog

    This generates a skeletal Akelos installation in ~/Code/PHP/weblog.
    See the README in the newly created application to get going.

Usage: akelos [-vsqhf] <-pd> 

    -d --directory=<value>    Destination directory for installing 
                              the application.
    -p --public_html=<value>  Location where the application will be 
                              accessed by the web server.
    -f --force                Overwrite files that already exist. (false)
    -q --quiet                Suppress normal output. (false)
    -s --skip                 Skip files that already exist. (false)
    --prompt                  Prompts before performing install. (true)
    -h --help                 Show this help message.
    -v --version              Print the akelos version.
    --base_dir                Print the path where akelos resides.


HELP
);
}

class AkelosInstaller
{
    public $options = array();
    public $errors = array();

    public function __construct($options) {
        $default_options = array(
        'source' => $this->getAbsolutePath(dirname(__FILE__).DIRECTORY_SEPARATOR.str_repeat(DIRECTORY_SEPARATOR.'..',4).DIRECTORY_SEPARATOR.'app_layout'),
        'force' => false,
        'skip' => false,
        'quiet' => false,
        'public_html' => false
        );

        $this->options = array_merge($default_options, $options);

        $this->options['directory'] = $this->getAbsolutePath(@$this->options['directory']);

        if(empty($this->options['directory'])){
            trigger_error('You must supply a valid destination path', E_USER_ERROR);
        }

        $this->source_tree = AkFileSystem::dir($this->options['source'],array('dirs'=>true,'recurse'=>true));
        $this->destination_tree = AkFileSystem::dir($this->options['directory'],array('dirs'=>true,'recurse'=>true));
    }

    public function install() {
        if(empty($this->destination_tree) || !empty($this->options['force'])){
            if(!is_dir($this->options['directory'])){
                if(!$this->_makeDir($this->options['directory'])){
                    $this->addError("Can't create directory: " . $this->options['directory']);
                    return false;
                }
            }

            $this->_copyApplicationFiles($this->source_tree, $this->options['source']);

            $this->_linkPublicHtmlFolder();
            $this->_copyAkelosToVendors();
            $this->_cleanUpAndCreateEmptyFolders();
            $this->runEvironmentSpecificTasks();


        }else{
            $this->addError('Installation directory is not empty. Add --force if you want to override existing files');
        }
    }

    public function yield($message) {
        if(empty($this->options['quiet'])){
            echo $message."\n";
        }
    }

    public function addError($error) {
        $this->errors[$error] = '';
    }

    public function getErrors() {
        return array_keys($this->errors);
    }

    public function hasErrors() {
        return !empty($this->errors);
    }


    public function runEvironmentSpecificTasks() {
        if($evironment = $this->guessEnvironment()){
            $method_name = 'run'.$evironment.'Tasks';
            if(method_exists($this, $method_name)){
                $this->$method_name();
            }
        }
    }

    // Environment specific tasks
    public function guessEnvironment() {
        if(AK_WIN){
            if(file_exists('C:/xampp/apache/conf/httpd.conf')){
                return 'DefaultXamppOnWindows';
            }
        }
        return false;
    }

    public function runDefaultXamppOnWindowsTasks() {
        // XAMPP has mod_rewrite disabled by default so we will try to enable it.
        $http_conf = file_get_contents('C:/xampp/apache/conf/httpd.conf');
        if(strstr($http_conf, '#LoadModule rewrite_module')){
            $this->yield('Enabling mod_rewrite');
            file_put_contents('C:/xampp/apache/conf/httpd.conf.akelos', $http_conf);
            file_put_contents('C:/xampp/apache/conf/httpd.conf',
            str_replace(
            '#LoadModule rewrite_module',
            'LoadModule rewrite_module',
            $http_conf
            ));

            $this->yield('Restarting Apache');
            // Stop apache
            exec('C:\xampp\apache\bin\pv -f -k apache.exe -q');
            exec('rm C:\xampp\apache\logs\httpd.pid');

            // Start Apache in the background
            $shell = new COM('WScript.Shell');
            $shell->Run('C:\xampp\apache\bin\apache.exe', 0, false);
        }

        $my_cnf = @file_get_contents('C:/xampp/mysql/bin/my.cnf');
        // InnoDB engine is not enabled by default on XAMPP we need it enabled in order to use transactions
        if(strstr($my_cnf, '#innodb_')){
            $this->yield('Enabling InnoDB MySQL engine.');
            file_put_contents('C:/xampp/mysql/bin/my.cnf.akelos', $my_cnf);
            file_put_contents('C:/xampp/mysql/bin/my.cnf',
            str_replace(
            array('skip-innodb', '#innodb_', '#set-variable = innodb'),
            array('#skip-innodb', 'innodb_', 'set-variable = innodb')
            ,$my_cnf));

            $this->yield('Restarting MySQL server.');
            $shell = new COM('WScript.Shell');
            $shell->Run('C:\xampp\mysql\bin\mysqladmin --user=pma --password= shutdown', 0, false);
            $shell = new COM('WScript.Shell');
            $shell->Run('C:\xampp\mysql\bin\mysqld --defaults-file=C:\xampp\mysql\bin\my.cnf --standalone --console', 0, false);
        }
    }


    // Protected methods


    protected function _linkPublicHtmlFolder() {
        if(!empty($this->options['public_html'])){
            if(function_exists('symlink')){
                $this->options['public_html'] = $this->getAbsolutePath($this->options['public_html']);
                $link_info = @linkinfo($this->options['public_html']);
                $target = $this->options['directory'].DS.'public';

                if($target == $this->options['public_html']){
                    // No need to symlink, same path on target
                    return true;
                }
                if(!is_numeric($link_info) || $link_info < 0){
                    $this->yield("\n    Adding symbolic link ".$this->options['public_html'].' to the public web server.');
                    if(@symlink($target, $this->options['public_html'])){
                        return true;
                    }
                }
            }
            $this->yield("\n    Could not create a symbolic link of ".$this->options['directory'].DS.'public'.' at '.$this->options['public_html']);

        }
        return false;
    }

    protected function _copyAkelosToVendors() {
        $this->yield("\nCopying akelos framework from ".AK_FRAMEWORK_DIR." to ".$this->options['directory'].DS.'vendor'.DS.'akelos');

        self::copyRecursivelly(AK_FRAMEWORK_DIR, $this->options['directory'].DS.'vendor'.DS.'akelos');
    }

    protected function _copyApplicationFiles($directory_structure, $base_path = '.') {
        foreach ($directory_structure as $k=>$node){

            $path = $base_path.DS.$node;
            if(is_dir($path)){
                $this->_makeDir($path);
            }elseif(is_file($path)){
                $this->_copyFile($path);
            }elseif(is_array($node)){
                foreach ($node as $dir=>$items){
                    $path = $base_path.DS.$dir;
                    if(is_dir($path)){
                        $this->_makeDir($path);
                        $this->_copyApplicationFiles($items, $path);
                    }
                }
            }

        }
    }

    protected function _makeDir($path) {
        $dir = $this->_getDestinationPath($path);

        if($this->_canUsePath($dir)){
            if(!is_dir($dir)){
                $this->yield("    Creating directory: ".$dir);
                if(!@mkdir($dir))
                return false;
            }
        }
        return true;
    }

    protected function _copyFile($path) {
        $destination_file = $this->_getDestinationPath($path);

        if($this->_canUsePath($destination_file)){
            if(!file_exists($destination_file)){
                $this->yield("    Creating file: ".$destination_file);
                copy($path, $destination_file);
            }elseif(md5_file($path) != md5_file($destination_file)){
                $this->yield("    Modifying file: ".$destination_file);
                copy($path, $destination_file);
            }

            $source_file_mode =  fileperms($path);
            $target_file_mode =  fileperms($destination_file);
            if($source_file_mode != $target_file_mode){
                $this->yield("    Setting $destination_file permissions to: ".(sprintf("%o",$source_file_mode)));
                chmod($destination_file,$source_file_mode);
            }
        }
    }

    static function copyRecursivelly($source, $destination) {
        $dir = opendir($source);

        @mkdir($destination);
        @self::equalPermissions($source, $destination);
        while(false !== ($file = readdir($dir))) {
            if ($file != '.' && $file != '..' && $file != '.git' && $file != '.svn' && $file != '.empty_directory') {
                if (is_dir($source.DS.$file)) {
                    self::copyRecursivelly($source.DS.$file,$destination.DS.$file);
                } else {
                    if(!@copy($source.DS.$file, $destination.DS.$file)){
                        if(!file_put_contents($destination.DS.$file, file_get_contents($source.DS.$file))){
                            throw new Exception('Error While copying file: '.$source.DS.$file.' to '.$destination.DS.$file);
                        }
                        @self::equalPermissions($source.DS.$file, $destination.DS.$file);
                    }
                }
            }
        }
        closedir($dir);
    }

    static function equalPermissions($source, $destination){
        $source_file_mode =  fileperms($source);
        $target_file_mode =  fileperms($destination);
        if($source_file_mode != $target_file_mode){
            chmod($destination, $source_file_mode);
        }
    }

    protected function _cleanUpAndCreateEmptyFolders(){
        # empty log files

        if(!AK_WIN){
            $writable_files = array(
            'log',
            'log/development.log',
            'log/production.log',
            'log/testing.log',
            'config/locales',
            'app/locales',
            );

            foreach ($writable_files as $file){
                $file = $this->options['directory'].DS.$file;
                `chmod -R 777 $file`;
            }
        }
        
        $files_and_replacements = array(
        'config/environment.php' => array('[SECRET]' => Ak::uuid())
        );
        
        foreach ($files_and_replacements as $file => $replacements) {
                $file = $this->options['directory'].DS.$file;
        	file_put_contents($file, str_replace(array_keys($replacements), array_values($replacements), file_get_contents($file)));
        }

        // Copy docs
        self::copyRecursivelly($this->options['directory'].DS.'vendor'.DS.'akelos'.DS.'docs', $this->options['directory'].DS.'docs');

    }

    /**
     * Computes the destination path
     *
     * Giving /path/to/the_framework/lib/Ak.php will rerturn /my/project/path/lib/Ak.php
     */
    protected function _getDestinationPath($path) {
        return str_replace($this->options['source'].DS, $this->options['directory'].DS, $path);
    }

    /**
     * Returns false if operating on the path is not allowed
     */
    protected function _canUsePath($path) {
        if(strstr($path, '.empty_directory') || strstr($path, '.git')){
            return false;
        }
        if(is_file($path) || is_dir($path)){
            return !empty($this->options['skip']) ? false : !empty($this->options['force']);
        }
        return true;
    }

    static function getAbsolutePath($path) {
        $_path = $path;
        if (!preg_match((AK_WIN ? "/^\w+:/" : "/^\//"), $path )) {
            $current_dir = AK_WIN ? str_replace("\\", DS, realpath('.').DS) : realpath('.').DS;
            $_path = $current_dir . $_path;
        }
        $start = '';
        if(AK_WIN){
            list($start, $_path) = explode(':', $_path, 2);
            $start .= ':';
        }
        $real_parts = array();
        $parts = explode(DS, $_path);
        for ($i = 0; $i < count($parts); $i++ ) {
            if (strlen($parts[$i]) == 0 || $parts[$i] == "."){
                continue;
            }
            if ($parts[$i] == '..'){
                if(count($real_parts) > 0){
                    array_pop($real_parts);
                }
            }else{
                array_push($real_parts, $parts[$i]);
            }
        }
        return $start.DS.implode(DS,$real_parts );
    }
}


function get_command_value($options, $short, $long, $default = null, $error_if_unset = null, $value_if_isset = null){
    $isset = isset($options[$long]) || isset($options[$short]);
    $value = isset($options[$short]) ?
    $options[$short] :
    (isset($options[$long])?$options[$long]:$default);

    if(is_null($value) && !empty($error_if_unset)){
        echo Ak::t($error_if_unset)."\n";
        exit(0);
    }
    if(!is_null($value_if_isset) && $isset){
        return $isset ? $value_if_isset : $value;
    }
    return is_null($value) ? false : $value;
}

$directory_candidate   = get_command_value($options, 'd', 'directory', false);

foreach ($options as $k => $v){
    if(!$directory_candidate && !preg_match('/^(d|directory|p|public_html||f|force|q|quiet|s|skip|prompt)$/', $v)){
        $directory_candidate = $v;
    }
}


$directory   = AkelosInstaller::getAbsolutePath(
get_command_value($options, 'd', 'directory',   $directory_candidate,
'Destination directory can\'t be blank'));
$public_html = get_command_value($options, 'p', 'public_html', false);
$public_html = empty($public_html) ? false : AkelosInstaller::getAbsolutePath($public_html);
$force       = get_command_value($options, 'f', 'force', false);
$quiet       = get_command_value($options, 'q', 'quiet', false);
$skip        = get_command_value($options, 's', 'skip', false);
$prompt      = get_command_value($options, 'prompt', 'prompt', true);

if($prompt){
    echo "\nCreate an Akelos ".AKELOS_VERSION." application in $directory\n";

    if($public_html)
    echo "symlink the public directory to $public_html\n";

    if($force)
    "OVERWRITE EXISTING FILES in $directory\n";

    AkConsole::promptUserVar("Shall web proceed installing? \nPress enter to continue", array('optional' => true));
}


$Installer = new AkelosInstaller(array(
'directory'     =>$directory,
'public_html'   =>$public_html,
'public_html'   =>$public_html,
'force'         =>$force,
'quiet'         =>$quiet,
'skip'          =>$skip,
'prompt'        =>$prompt,
));

$Installer->install();

if(!$quiet){
    if($Installer->hasErrors()){
        echo "\nThere where some errors during the installation process:\n";
        echo "\n * ".join("\n    * ",$Installer->getErrors());
    }elseif(empty($Installer->options['force'])){

        echo "\n    In order to create the config.php file and setup a database...\n\n".
        " change to \n\n    $directory\n".
        " and run \n\n    ./script/configure \n\nto configure the database details\n";
    }
    echo "\n";
}

