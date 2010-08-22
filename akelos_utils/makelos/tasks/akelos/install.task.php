<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

if(!empty($options['h']) || !empty($options['help'])){
    die(<<<HELP
Description:
    The 'akelos:install' command install the Akelos Framework in your system.

Example:
    akelos:install

    This will isntall a copy of Akelos in your system and will add the
    akelos and makelos commands to the binary path.

Usage: akelos:install [-db]

    -d --directory=<value>    Destination directory for installing 
                              the framework. (/var/src)
    -b --binary_path=<value>  Binary path for the commands akelos and
                              makelos. (/urs/local/bin)
    -q                        Do not display verbose output

HELP
);
}


class AkelosFrameworkInstaller
{
    public $options = array();
    
    public function __construct($options) {
        $this->options = $options;
    }
    
    public function install() {
        $core_dir = AK_CORE_DIR;
        $src_path = $this->getSrcPath().'/akelos';
        $bin_path = $this->getBinaryPath();

        if(is_dir($src_path)){
            if(AkConsole::promptUserVar("The directory $src_path is not empty. Do you want to override its contents? (y/n)", 'n') != 'y'){
                die("Aborted.\n");
            }
        }
        
        
        $this->ensureCanWriteOnDirectory($src_path);
        $this->ensureCanWriteOnDirectory($bin_path.'/akelos');
        $this->log("Copying souce files from $core_dir to $src_path.");
        $this->run("cp -R $core_dir/ $src_path/");
        $this->log("Linking binaries");
        $this->run(array('rm '.$bin_path.'/akelos',"ln -s $src_path/akelos $bin_path/akelos"));
        $this->run(array('rm '.$bin_path.'/makelos',"ln -s $src_path/makelos $bin_path/makelos"));
        $this->log("Done.");
    }
    
    public function getBinaryPath() {
        return (empty($this->options['b']) ? (empty($this->options['binary']) ? ('/usr/local/bin') : $this->options['binary']) : $this->options['b']);
    }
    
    public function getSrcPath() {
        return (empty($this->options['d']) ? (empty($this->options['directory']) ? ('/var/src') : $this->options['directory']) : $this->options['d']);
    }
    
    static public function ensureCanWriteOnDirectory($dir) {
        if(!is_writable(dirname($dir))){
            echo "$dir: Permission denied.\n";
            die();
        }
    }
    
    public function run($cmds) {
        $cmds = is_array($cmds) ? $cmds : array($cmds);
        foreach($cmds as $cmd){
            $this->log($cmd);
            $this->log(`$cmd`);
        }
    }
    
    public function log($message = null) {
        if(empty($this->options['q'])){
            echo $message ? $message."\n" : '';
        }
    }
}

$Installer = new AkelosFrameworkInstaller($options);
$Installer->install();

