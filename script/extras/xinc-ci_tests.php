<?php
defined('DS')          ? null : define('DS',DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR') ? null : define('AK_BASE_DIR',preg_replace('@\\'.DS.'(test|script)($|\\'.DS.'.*)@','',getcwd()));
define('AK_CI_CONFIG_FILE',AK_BASE_DIR.DS.'config'.DS.'ci-config.yaml');

class CI_Tests
{
    var $options = array(
    'break_on_errors'=>false,
    'test_mode'      =>false,
    'repeat'         =>1
    );

    var $settings;

    var $target_files;
    var $target_executables;
    var $target_environments;

    static function main($args=array())
    {
        if (empty($args)){
            global $argv;
            $args = $argv;
        }

        $self = new CI_Tests($args);
        $self->run();
        $self->hadError() ? exit(1) : exit(0);
    }

    function __construct($args)
    {
        //if (!is_file($this->config_file())) die('Not sure where I am and where config/config.php is ('.$this->config_file().'). Run from inside the test/* folders.');

        $this->loadSettings();
        $this->parseArgs($args);
    }

    function loadSettings($filename=AK_CI_CONFIG_FILE)
    {
        require dirname(__FILE__).DS.'..'.DS.'..'.DS.'vendor'.DS.'TextParsers'.DS.'spyc.php';

        if (!is_file($filename)){
            die ('Could not find ci configuration file in '.AK_CI_CONFIG_FILE.'.');
        }
        $yaml = file_get_contents($filename);
        $this->settings = Spyc::YAMLLoad($yaml);
    }

    function parseArgs($args)
    {
        array_shift($args);
        
        while (count($args) > 0){
            $arg = array_shift($args);
            $arg = strtolower($arg);
            if (in_array($arg,array('postgresql','postgressql','pgsql','pg'))) {
                $arg = 'postgres';
            }
            if (array_key_exists(strtolower($arg),$this->settings['executables'])){
                $this->target_executables[] = $arg;
            }elseif (array_key_exists(strtolower($arg),$this->settings['environments'])){
                $this->target_environments[] = $arg;
            }elseif ($filename = $this->constructTestFilename($arg)){
                $this->target_files[] = $filename;
            }else{
                switch ($arg){
                    case '-b':
                        $this->options['break_on_errors'] = true;
                        break;
                    case '-t':
                        $this->options['test_mode'] = true;
                        break;
                    case '-?':
                    case '?':
                        $this->drawHelp();
                        break;
                    case '-n':
                        $timesToRepeat = array_shift($args);
                        $this->options['repeat'] = $timesToRepeat;
                        break;
                }
            }
        }

        $this->setDefaults();
    }

    function setDefaults()
    {
        if (!$this->target_executables)  $this->target_executables  = $this->settings['default_executables'];
        if (!$this->target_files)        $this->target_files[]      = AK_BASE_DIR.DS.'test'.DS.'xinc-unit.php';
        if (!$this->target_environments) $this->target_environments = array_keys($this->settings['environments']);
    }

    function constructTestFilename($filename)
    {
        if (is_file($filename)) return $filename;

        $target_file = getcwd().DIRECTORY_SEPARATOR.$filename;
        if (is_file($target_file)) return $target_file;

        return false;
    }


    function config_file()
    {
        return AK_BASE_DIR.DS.'config'.DS.'config.php';
    }

    function config_backup_file()
    {
        return AK_BASE_DIR.DS.'config'.DS.'config-backup.php';
    }

    function config_file_for($environment)
    {
        return AK_BASE_DIR.DS.'config'.DS.$this->settings['environments'][$environment].'.php';
    }

    function run()
    {
        $this->drawHeader();

        $this->beforeRun();
        for ($i=1; $i <= $this->timesToRun(); $i++){
            $this->drawRepeatIndicator($i);
            foreach ($this->filesToRun() as $file){
                foreach ($this->executablesToRun() as $php_version){
                    foreach ($this->environmentsToRun() as $environment){
                        if ($this->isValidCombination($environment,$php_version)){
                            $return_value = $this->runCommand($php_version,$file,$environment);
                            if ($return_value !== 0) {
                                $this->markError();
                                if ($this->options['break_on_errors']) break 4;
                            }
                        }
                    }
                }
            }
        }
        $this->afterRun();

        $this->drawFooter();
    }

    function markError()
    {
        $this->errors = true;
    }

    function hadError()
    {
        return isset($this->errors);
    }

    function filesToRun()
    {
        return $this->target_files;
    }

    function executablesToRun()
    {
        return $this->target_executables;
    }

    function environmentsToRun()
    {
        return $this->target_environments;
    }

    function timesToRun()
    {
        return $this->options['repeat'];
    }

    function isValidCombination($environment,$php_version)
    {
        return in_array($environment,$this->settings['valid_combinations'][$php_version]);
    }

    function beforeRun()
    {
        //return copy($this->config_file(),$this->config_backup_file());
        return true;
    }

    function afterRun()
    {
        //if (copy($this->config_backup_file(),$this->config_file())){
        //    return unlink($this->config_backup_file());
        //}
        //return false;
        return true;
    }

    function prepareEnvironment($environment)
    {
        if (!is_file($this->config_file_for($environment))){
            echo "Can't find environment settings for $environment. Skipping...\n\r";
            return false;
        }
        return copy($this->config_file_for($environment),$this->config_file());
    }

    function runCommand($php,$filename,$environment)
    {
        $this->drawBox(array($filename,strtoupper($environment),$php));

        if ($this->prepareEnvironment($environment)){
            $command = $this->settings['executables'][$php].' '.$filename.' --xml ./test-results-'.$php.'-'.str_replace(' ','-',$environment).'.xml "'.$php.'" "'.$environment.'"';
            if ($this->options['test_mode']){
                echo "Executing: ".$command."\n\r";
                $return_value = 0;
            }else{
                echo "Executing: ".$command."\n\r";
                passthru($command,$return_value);
            }
            return $return_value;
        }
    }

    function drawBox($message)
    {
        $this->drawNewline();
        $this->drawLine();
        $this->drawNewline();
        echo " TARGET: ".join(', ',$message)."\n\r";
        $this->drawLine();
        $this->drawNewline();
    }

    function drawHeader()
    {
        #$this->drawLine('+');
    }

    function drawFooter()
    {
        $this->drawNewline();
        $this->drawLine('+');
        $this->drawNewline();
        echo "FINISHED. ";
        $this->drawNewline();
        if (!$this->hadError()) echo " All fine.";
    }

    function drawRepeatIndicator($actual)
    {
        if ($this->timesToRun() == 1) return;

        $this->drawNewline(2);
        echo str_pad('# '.$actual.'. ',80,'#');
    }

    function drawLine($char='-',$num=80)
    {
        echo str_pad('',$num,$char);
    }

    function drawNewline($multiplier=1)
    {
        echo str_repeat("\n\r",$multiplier);
    }

    function drawHelp()
    {
        echo <<<BANNER
Usage:

ci_tests [php4|php5] [mysql|postgres|sqlite] [-b] [test-files]
   -b   break on first error
   -t   test-mode, don't run the commands actually
   -n x repeat tests x times
   -?   this help

Examples:
> ci_tests 
run all unit tests in any combination.

> ci_tests php5 postgres mysql AkHasMany AkBelongsTo
run AkHasMany and AkBelongsTo on PHP5 using the postgres and mysql-db.

Setup:
1.  Copy DEFAULT-ci-config.yaml to config/ci-config.yaml and set it up  

2.  Copy config/config.php to config/mysql-testing.php, config/postgres-testing.php [...] and modify the database settings at least for the testing environment. You can configure the filename for these config-files in the script directly if you must.

3.  Expects to be run from inside the test folder structure. So to speak your current directory must be */test or a subdir. The script itself can be placed whereever you want. You can define a (shell-)macro and quickly swap between different installations and test again. ;-) 

This script backups config/config.php to config-backup.php (and restores it after run).

BANNER;
        exit;
    }
}
$test_args = array(
'Myself_will_be_thrown_away',
"all",
#"-b",
#"-?",
"-t",
#"-n","2",
#'AkHasMany.php',
#'postgres'
);
#CI_Tests::main($test_args);
CI_Tests::main();

?>