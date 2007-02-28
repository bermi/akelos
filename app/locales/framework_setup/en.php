<?php

$dictionary = array();

$dictionary['Welcome aboard'] = 'Welcome aboard';
$dictionary['You&rsquo;re using The Akelos Framework!'] = 'You&rsquo;re using The Akelos Framework!';
$dictionary['Getting started'] = 'Getting started';
$dictionary['Configure your environment'] = 'Configure your environment';
$dictionary['Use <tt>script/generate</tt> to create your models and controllers'] = 'Use <tt>script/generate</tt> to create your models and controllers';
$dictionary['To see all available options, run it without parameters.'] = 'To see all available options, run it without parameters.';
$dictionary['Start the configuration wizard'] = 'Start the configuration wizard';
$dictionary['Akelos Framework'] = 'Akelos Framework';

$dictionary['Database Configuration.'] = 'Database Configuration.';
$dictionary['Please select a database type'] = 'Please select a database type';
$dictionary['The list below only includes databases we found you had support for under your current PHP settings'] = 'The list below only includes databases we found you had support for under your current PHP settings';

$dictionary['The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.'] = 'The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.';
$dictionary['
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				'] = '
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				';
$dictionary['Please set your database details'] = 'Please set your database details';
$dictionary['Development'] = 'Development';
$dictionary['Database name'] = 'Database name';
$dictionary['Production'] = 'Production';
$dictionary['Testing'] = 'Testing';
$dictionary['Continue'] = 'Continue';


$dictionary['File handling settings.'] = 'File handling settings.';
$dictionary['The Akelos Framework makes an extensive use of the file system for handling locales, cache, compiled templates...'] = 'The Akelos Framework makes an extensive use of the file system for handling locales, cache, compiled templates...';
$dictionary['The installer could not create a test file at <b>config/test_file.txt</b>, so you should check if the user that is running the web server has enough privileges to write files inside the installation directory.'] = 'The installer could not create a test file at <b>config/test_file.txt</b>, so you should check if the user that is running the web server has enough privileges to write files inside the installation directory.';
$dictionary['If you have made changes to the filesystem or web server, <a href="%ftp_url">click here to continue</a> or 
<a href="%url_skip">here to skip the filesystem setting</a></p>'] = 'If you have made changes to the filesystem or web server, <a href="%ftp_url">click here to continue</a> or 
<a href="%url_skip">here to skip the filesystem setting</a></p>';
$dictionary['You don\'t have enabled FTP support into your PHP settings. When enabled 
            you can perform file handling functions using specified FTP account. 
            In order to use FTP functions with your PHP configuration, you should add the 
            --enable-ftp option when installing PHP.'] = 'You don\'t have enabled FTP support into your PHP settings. When enabled 
            you can perform file handling functions using specified FTP account. 
            In order to use FTP functions with your PHP configuration, you should add the 
            --enable-ftp option when installing PHP.';


$dictionary['Bad file permission. Please change file system privileges or set up a FTP account below'] = 'Bad file permission. Please change file system privileges or set up a FTP account below';

$dictionary['Language settings.'] = 'Language settings.';
$dictionary['Please set your language details'] = 'Please set your language details';
$dictionary['2 letter ISO 639 language codes (separated by commas)'] = '2 letter ISO 639 language codes (separated by commas)';

$dictionary['Database Host'] = 'Database Host';
$dictionary['User'] = 'User';
$dictionary['Password'] = 'Password';
$dictionary['(optional) Try to create databases using the following privileged account:'] = '(optional) Try to create databases using the following privileged account:';
$dictionary['DB admin user name'] = 'DB admin user name';
$dictionary['DB admin password'] = 'DB admin password';
$dictionary['Could not connect to %database database'] = 'Could not connect to %database database';


$dictionary['If you can\'t change the web server or file system permissions the Akelos Framework has an alternate way to access the file system by using an FTP account that points to your application path.'] = 'If you can\'t change the web server or file system permissions the Akelos Framework has an alternate way to access the file system by using an FTP account that points to your application path.';
$dictionary['This is possible because the Framework uses a special version of file_get_contents and file_put_contents functions that are located under the class Ak, which acts as a namespace for some PHP functions. If you are concerned about distributing applications done using the Akelos Framework, you should use Ak::file_get_contents() and Ak::file_put_contents() and this functions will automatically select the best way to handle files. Additional methods like LDAP might be added in a future.'] = 'This is possible because the Framework uses a special version of file_get_contents and file_put_contents functions that are located under the class Ak, which acts as a namespace for some PHP functions. If you are concerned about distributing applications done using the Akelos Framework, you should use Ak::file_get_contents() and Ak::file_put_contents() and this functions will automatically select the best way to handle files. Additional methods like LDAP might be added in a future.';
$dictionary['Please set your ftp connection details'] = 'Please set your ftp connection details';
$dictionary['FTP Host'] = 'FTP Host';
$dictionary['Application path from FTP initial path'] = 'Application path from FTP initial path';
$dictionary['Could not connect to selected ftp server'] = 'Could not connect to selected ftp server';
$dictionary['Could not change to the FTP base directory %directory'] = 
'Could not change to the FTP base directory %directory';

$dictionary['<a href="%url">Run a step by step wizard for creating a configuration file</a> or read README.txt instead.'] = '<a href="%url">Run a step by step wizard for creating a configuration file</a> or read README.txt instead.';
$dictionary['The framework_setup.php found that you already have a configuration file at config/config.php. You need to remove that file first in order to run the setup.'] = 'The framework_setup.php found that you already have a configuration file at config/config.php. You need to remove that file first in order to run the setup.';

$dictionary['Save Configuration.'] = 'Save Configuration.';
$dictionary['Final Steps.'] = 'Final Steps.';
$dictionary['You are about to complete the installation process. Please follow the steps bellow.'] = 'You are about to complete the installation process. Please follow the steps bellow.';
$dictionary['Copy the following configuration file contents to <b>config/config.php</b>.'] = 'Copy the following configuration file contents to <b>config/config.php</b>.';
$dictionary['Copy the file <b>config/DEFAULT-routes.php</b> to <b>config/routes.php</b>'] = 'Copy the file <b>config/DEFAULT-routes.php</b> to <b>config/routes.php</b>';
$dictionary['Your application is not on the host main path, so you might need to edit 
    your .htaccess files in order to enable nice URL\'s. Edit <b>/.htaccess</b> and 
    <b>/public/.htaccess</b> and replace the line <br />'] = 'Your application is not on the host main path, so you might need to edit 
    your .htaccess files in order to enable nice URL\'s. Edit <b>/.htaccess</b> and 
    <b>/public/.htaccess</b> and replace the line <br />';
$dictionary['with'] = 'with';
$dictionary['Now you can start generating models and controllers by running <b>./script/generate model</b>, <b>./script/generate controller</b> and , <b>./script/generate scaffold</b>. Run them without parameters to get the instructions.'] = 'Now you can start generating models and controllers by running <b>./script/generate model</b>, <b>./script/generate controller</b> and , <b>./script/generate scaffold</b>. Run them without parameters to get the instructions.';

?>