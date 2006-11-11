<?php

$dictionary = array();

$dictionary['Welcome aboard'] = 'Bienvenidos a bordo';
$dictionary['You&rsquo;re using The Akelos Framework!'] = 'Estás utilizando el Akelos Framework!';
$dictionary['Getting started'] = 'Comenzando a usar el Framework';
$dictionary['Configure your environment'] = 'Configuración del entorno';
$dictionary['<a href="%url">Run a step by step wizard for creating a configuration file</a> or read INSTALL.txt instead.'] = '<a href="%url">Ejecuta el asistente paso a paso para generar el fichero de configuración</a> o realiza la instalación siguiendo los pasos que figuran en el fichero INSTALL.txt.';
$dictionary['Start the configuration wizard'] = 'Iniciar el asistente de configuración';
$dictionary['Akelos Framework'] = 'Akelos Framework';

$dictionary['Database Configuration.'] = 'Configuración de la base de datos.';
$dictionary['Please select a database type'] = 'Seleccione un tipo de base de datos';
$dictionary['The list below only includes databases we found you had support for under your current PHP settings'] = 'La siguiente lista sólo muestra las bases de datos que su instalación de PHP tiene habilitadas.';


$dictionary['The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.'] = 'El Akelos Framework tiene 3 entornos de ejecución distintos y cada uno de ellos tiene una base de datos separada. Nuestra recomendación es que se programe la aplicación en mode "development", se testee en modo "testing" y se lance a producción en modo "production".';
$dictionary['
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				'] = '
                <p>Le recomendamos que cree las siguientes bases de datos:</p>
                <ul>
                    <li><em>nombre_base_datos</em><b>_dev</b> para mode desarrollo (por defecto)</li>
                    <li><em>nombre_base_datos</em> para modo producción</li>
                    <li><em>nombre_base_datos</em><b>_tests</b> para realizar tests</li>
                </ul>
				';
$dictionary['Please set your database details'] = 'Por favor introduzca los detalles de su base de datos';
$dictionary['Development'] = 'Desarrollo';
$dictionary['Database name'] = 'Nombre de la base de datos name';
$dictionary['Production'] = 'Producción';
$dictionary['Testing'] = 'Testeo';
$dictionary['Continue'] = 'Continuar';


$dictionary['File handling settings.'] = 'Configuración de la gestión de ficheros.';
$dictionary['The Akelos Framework makes an extensive use of the file system for handling locales, cache, compiled templates...'] = 'El Akelos Framework hace un uso intensivo del sistema de ficheros para gestionar y mantener la internacionalización, la caché, compilación de plantillas...';
$dictionary['The installer could not create a test file at <b>config/test_file.txt</b>, so you should check if the user that is running the web server has enough privileges to write files inside the installation directory.'] = 'El instalador no pudo crear el fichero de prueba <b>config/test_file.txt</b>, por lo que debería comprobar si el usuario que ejecuta el proceso del servidor web tiene los permisos necesarios para escribir ficheros en el directorio donde está instalado el framework.';
$dictionary['If you have made changes to the filesystem or web server, <a href="%ftp_url">click here to continue</a> or 
<a href="%url_skip">here to skip the filesystem setting</a></p>'] = 'Si ya ha realizado cambios en el sistema de ficheros del servidor, <a href="%ftp_url">pulse aquí para continuar</a> o 
<a href="%url_skip">aquí para saltarse este paso.</a></p>';
$dictionary['You don\'t have enabled FTP support into your PHP settings. When enabled 
            you can perform file handling functions using specified FTP account. 
            In order to use FTP functions with your PHP configuration, you should add the 
            --enable-ftp option when installing PHP.'] = 'No tiene habilitada la compatibilidad con FTP en su configuración de PHP. Si esta configuración estuviera habilitada podría realizar las operaciones de ficheros usando una cuenta FTP. 
            Para activar la compatibilidad con FTP deberá añadir la opción --enable-ftp cuando instale PHP.';


$dictionary['Bad file permission. Please change file system privileges or set up a FTP account below'] = 'Privilegios insuficientes para gestionar ficheros. Por favor cambie los permisos de sus carpetas o habilite la gestión de privilegios mediante FTP.';


$dictionary['Language settings.'] = 'Configuración del idioma.';
$dictionary['Please set your language details'] = 'Introduzca los detalles de idioma';
$dictionary['2 letter ISO 639 language codes (separated by commas)'] = 'Código ISO 639 de 2 letras (separados por comas)';

$dictionary['Database Host'] = 'Servidor de base de datos (Host)';
$dictionary['User'] = 'Usuario';
$dictionary['Password'] = 'Contraseña';
$dictionary['(optional) Try to create databases using the following privileged account:'] = '(opcional) Intenta crear las bases de datos usando la siguiente cuenta con privilegios:';
$dictionary['DB admin user name'] = 'Nombre de usuario (administrador base de datos)';
$dictionary['DB admin password'] = 'Contraseña (administrador base de datos)';
$dictionary['Could not connect to %database database'] = 'No se ha podido conectar con la base de datos %database database';



$dictionary['If you can\'t change the web server or file system permissions the Akelos Framework has an alternate way to access the file system by using an FTP account that points to your application path.'] = 'Si no puede modificar los privilegios del servidor o del sistema de ficheros el Akelos Framework ofrece la posibilidad de utilizar una cuenta FTP que apunte a la aplicación para realizar las operaciones con ficheros.';
$dictionary['This is possible because the Framework uses a special version of file_get_contents and file_put_contents functions that are located under the class Ak, which acts as a namespace for some PHP functions. If you are concerned about distributing applications done using the Akelos Framework, you should use Ak::file_get_contents() and Ak::file_put_contents() and this functions will automatically select the best way to handle files. Additional methods like LDAP might be added in a future.'] = '
Esto es posible gracias a la utilización de de una versión especial de las funciones de PHP "file_get_contents" y "file_put_contents" que están definidas como un método dentro del la clase Ak; lacual actual como un espacio de nombre para algunas funciones de PHP. Si va a distribuir aplicaciones realizadas con el Akelos Framework debería utilizar Ak::file_get_contents() y Ak::file_put_contents() en sus aplicaciones ya que estas determinarán la mejor manera de trabajar con los ficheros. Otros métodos como LDAP se podrán implementar en el fututo.';
$dictionary['Please set your ftp connection details'] = 'Especifique los detalles de su conexión FTP';
$dictionary['FTP Host'] = 'Servidor/Host FTP';
$dictionary['Could not connect to selected ftp server'] = 'No se ha podido conectar con el servidor FTP';
$dictionary['Application path from FTP initial path'] = 'Ruta de la aplicación desde el punto de entrada en el servidor FTP';

$dictionary['Could not change to the FTP base directory %directory'] = 
'No se ha podido cambiar al direcitorio base de la aplicación %directory';


?>
