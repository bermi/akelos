<?php

define('AK_URL_REWRITE_ENABLED', true);
define('AK_SITE_URL_SUFFIX', '/');

require_once(AK_LIB_DIR.DS.'AkInstaller.php');

@ini_set("include_path",(AK_BASE_DIR.DS.'vendor'.DS.'pear'.PATH_SEPARATOR.ini_get("include_path")));


Ak::import('FrameworkSetup');


set_time_limit(0);
error_reporting(E_ALL);

require_once(AK_CONTRIB_DIR.DS.'adodb'.DS.'adodb.inc.php');
require_once (AK_VENDOR_DIR.DS.'pear'.DS.'Console'.DS.'Getargs.php');


function prompt_var($question, $default_value = null, $cli_value = null)
{
    global $options;
    if(empty($options['interactive']) && isset($cli_value)){
        return $cli_value;
    }else{
        return AkInstaller::promptUserVar($question, array('default'=>$default_value, 'optional'=>true));
    }
}

function set_db_user_and_pass(&$FrameworkSetup, &$db_user, &$db_pass, &$db_type, $defaults = true){
    global $options;
    $db_type = prompt_var('Database type', 'mysql', $defaults?@$options['database']:null);
    $db_user = prompt_var('Database user', $FrameworkSetup->suggestUserName(), $defaults?@$options['user']:null);
    $db_pass = prompt_var('Database password', '', $defaults?@$options['password']:null);
    
    
    if(!@NewADOConnection("$db_type://$db_user:$db_pass@localhost")){
        echo Ak::t('Could not connect to the database'."\n");
        set_db_user_and_pass($FrameworkSetup, $db_user, $db_pass, $db_type, false);
    }

}





$config =  array(

'user' => array(
'short'   => 'u',
'max'     => 1,
'min'     => 1,
'default' => 'root',
'desc'    => 'Database user'),

'password' => array(
'short'   => 'p',
'max'     => 1,
'min'     => 1,
'default' => '',
'desc'    => 'Database password'),

'database' => array(
'short'   => 'd',
'max'     => 1,
'min'     => 1,
'default' => 'mysql',
'desc'    => 'Database type'),

'name' => array(
'short'   => 'n',
'max'     => 1,
'min'     => 1,
'default'     => AkInflector::underscore(basename(AK_BASE_DIR)),
'desc'    => 'Database name. This is the name of your database. It will be prefixed with _dev and _tests for non production environments.'),

'languages' => array(
'short'   => 'l',
'max'     => 1,
'min'     => 1,
'default' => 'en',
'desc'    => 'Language codes for this application.'),

'interactive' => array(
'short'   => 'i',
'max'     => 0,
'min'     => 0,
'default' => false,
'desc'    => 'Interactive mode.'),

);


$args =& Console_Getargs::factory($config);

if (PEAR::isError($args)) {
    if ($args->getCode() === CONSOLE_GETARGS_ERROR_USER) {
        echo Console_Getargs::getHelp($config, null, $args->getMessage())."\n";
    } else if ($args->getCode() === CONSOLE_GETARGS_HELP) {
        echo @Console_Getargs::getHelp($config)."\n";
    }
    exit;
}

$options = $args->getValues();




$FrameworkSetup = new FrameworkSetup();
$FrameworkSetup->setDefaultOptions();
$FrameworkSetup->getAvailableDatabases();

$app_name = empty($options['interactive']) ?
 $options['name'] :
 prompt_var('Database name. This is the name of your database. It will be prefixed with _dev and _tests for non production environments.', $options['name']);

$db_user = $db_pass = $db_type = '';




set_db_user_and_pass($FrameworkSetup, $db_user, $db_pass, $db_type);


$FrameworkSetup->setDatabaseUser($db_user, 'admin');
$FrameworkSetup->setDatabasePassword($db_pass, 'admin');
$FrameworkSetup->setDatabaseType($db_type);

foreach (array('development','production','testing') as $mode){
    $db_postfix = ($mode=='production'?'':
    ($mode=='development'?'_dev':
    ($mode=='testing'?'_tests':'_'.$mode)));

    $FrameworkSetup->setDatabaseName(
    prompt_var(ucfirst($mode).' database name',
    $app_name.$db_postfix, @$options['name'].$db_postfix), $mode);

    if($FrameworkSetup->getDatabaseType($mode) != 'sqlite'){
        $FrameworkSetup->setDatabaseHost('localhost', $mode);
        $FrameworkSetup->setDatabaseUser($db_user, $mode);
        $FrameworkSetup->setDatabasePassword($db_pass, $mode);
        $FrameworkSetup->createDatabase($mode);
    }
}

$FrameworkSetup->setLocales(prompt_var('Application Locales', 'en', @$options['languages']));

$configuration_file = $FrameworkSetup->getConfigurationFile();
$db_configuration_file = $FrameworkSetup->getDatabaseConfigurationFile();


if( $FrameworkSetup->canWriteConfigurationFile() &&
$FrameworkSetup->canWriteDbConfigurationFile()){
    $FrameworkSetup->writeConfigurationFile($configuration_file) &&
    $FrameworkSetup->writeDatabaseConfigurationFile($db_configuration_file) &&
    $FrameworkSetup->writeRoutesFile() &&
    $FrameworkSetup->runFrameworkInstaller();
    echo "\nYour application has been confirured correctly\n";
    echo "\nSee config/config.php and config/database.yml\n";
}

?>