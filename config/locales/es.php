<?php

$locale = array();
$locale['description'] = 'Spanish';
$locale['charset'] = 'UTF-8';
$locale['date_time_format'] = 'd/m/Y H:i:s';
$locale['date_format'] = 'd/m/Y';
$locale['long_date_format'] = 'd/m/Y';
$locale['time_format'] = 'H:i';
$locale['long_time_format'] = 'H:i:s';

$locale['currency'] = array(
'precision'=>2,
'unit' => '€',
'unit_position' => 'right',
'separator'=> ',',
'delimiter' =>  '.'
);

$dictionary = array();
$dictionary['Akelos Framework'] = 'Akelos Framework';
$dictionary['Object <b>%object_name</b> information:<hr> <b>object Vars:</b><br>%var_desc <hr> <b>object Methods:</b><br><ul><li>%methods</li></ul>'] = 'Información del objeto <b>%object_name</b>:<hr> <b>Variables del Objeto:</b><br>%var_desc <hr> <b>Métodos del Objeto:</b><br><ul><li>%methods</li></ul>';
$dictionary['Controller <i>%controller_name</i> does not exist'] = 'El controlador <i>%controller_name</i> no existe';
$dictionary['Action <i>%action</i> does not exist for controller <i>%controller_name</i>'] = 'La acción <i>%action</i> no está definida en el controlador <i>%controller_name</i>';
$dictionary['View file <i>%file</i> does not exist.'] = 'El fichero de la vista <i>%file</i> no existe.';
$dictionary['%controller requires a missing model %model_class, exiting.'] = 'El controlador %controller necesita el modelo %model_class, saliendo.';
$dictionary['Could not find the file /app/<i>%controller_file_name</i> for the controller %controller_class_name'] = 'No se ha encontrado el fichero /app/<i>%controller_file_name</i> correspondiente al controlador %controller_class_name';
$dictionary['Invalid class name in AkPatterns::singleton()'] = 'AkPatterns::singleton() ha recibido un nombre de clase inválido';
$dictionary['Connection to the database failed'] = 'La conexión a la base de datos a fallado';
$dictionary['The Akelos Framework could not automatically configure your model name. This might be caused because your model file is not located on %path. Please call $this->setModelName("YourModelName"); in your model constructor in order to make this work.'] = 'El Akelos Framework no ha podido configurar automáticamente el nombre del modelo. Esto puede deberse a que el modelo no se ha podido hayar en %path. Si lo desea puede utilizar $this->setModelName("MiModelo"); en el constructor para asignar un nombre manualmente.';
$dictionary['Unable to fetch current model name'] = 'No se ha podido averiguar el nombre del modelo actual';
$dictionary['Unable to set "%table_name" table for the model "%model".  There is no "%table_name" available into current database layout. Set AK_ACTIVE_CONTROLLER_VALIDATE_TABLE_NAMES constant to false in order to avoid table name validation'] = 'No se ha podido relacionar la tabla "%table_name" con el modelo "%model". No se ha encontrado ninguna tabla llamada "%table_name" entre las tablas de la base de datos. Defina la constante AK_ACTIVE_CONTROLLER_VALIDATE_TABLE_NAMES como falsa en config/config.php para desactivar la auto-averiguación de los nombres de tablas';
$dictionary['You are calling recursively AkActiveRecord::setAttribute by placing parent::setAttribute() or  parent::set() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::setAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_SET_RECURSION and set it to false'] = 'You are calling recursivelly AkActiveRecord::setAttribute by placing parent::setAttribute() or  parent::set() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::setAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_SET_RECURSION and set it to false';
$dictionary['You are calling recursively AkActiveRecord::getAttribute by placing parent::getAttribute() or  parent::get() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::getAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_GET_RECURSION and set it to false'] = 'You are calling recursivelly AkActiveRecord::getAttribute by placing parent::getAttribute() or  parent::get() on your model "%method" method. In order to avoid this, set the 3rd paramenter of parent::getAttribute to FALSE. If this was the behaviour you expected, please define the constant AK_ACTIVE_RECORD_PROTECT_GET_RECURSION and set it to false';
$dictionary['Error'] = 'Error';
$dictionary['There was an error while setting the composed field "%field_name", the following mapping column/s "%columns" do not exist'] = 'There was an error while setting the composed field "%field_name", the following mapping column/s "%columns" do not exist';
$dictionary['Unable to set "%table_name" table for the model "%model".  There is no "%table_name" available into current database layout. Set AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES constant to false in order to avoid table name validation'] = 'Unable to set "%table_name" table for the model "%model".  There is no "%table_name" available into current database layout. Set AK_ACTIVE_RECORD_VALIDATE_TABLE_NAMES constant to false in order to avoid table name validation';
$dictionary['The mysqli extension is designed to work with the version 4.1.3 or above of MySQL. Please use mysql: database driver instead'] = 'The mysqli extension is designed to work with the version 4.1.3 or above of MySQL. Please use mysql: database driver instead';
$dictionary['The mysqli extension is designed to work with the version 4.1.3 or above of MySQL. Please use mysql: database driver instead of mysqli'] = 'The mysqli extension is designed to work with the version 4.1.3 or above of MySQL. Please use mysql: database driver instead of mysqli';
$dictionary['Could not set %column_name as the inheritance column as this column is not available on the database.'] = 'Could not set %column_name as the inheritance column as this column is not available on the database.';
$dictionary['Could not set %column_name as the inheritance column as this column type is %column_type instead of "string".'] = 'Could not set %column_name as the inheritance column as this column type is %column_type instead of "string".';
$dictionary['Could not set %column_name as the inheritance column as this column type is "%column_type" instead of "string".'] = 'Could not set %column_name as the inheritance column as this column type is "%column_type" instead of "string".';
$dictionary['Could not set "%column_name" as the inheritance column as this column is not available on the database.'] = 'Could not set "%column_name" as the inheritance column as this column is not available on the database.';
$dictionary['The Akelos Framework could not automatically configure your model name. This might be caused because your model file is not located on %path. Please call $this->setParentModelName("YourParentModelName"); in your model constructor in order to make this work.'] = 'The Akelos Framework could not automatically configure your model name. This might be caused because your model file is not located on %path. Please call $this->setParentModelName("YourParentModelName"); in your model constructor in order to make this work.';
$dictionary['Unable to fetch parent model name'] = 'Unable to fetch parent model name';
$dictionary['Too many range options specified.  Choose only one.'] = 'Too many range options specified.  Choose only one.';
$dictionary['%option must be a nonnegative Integer'] = '%option must be a nonnegative Integer';
$dictionary['Range unspecified.  Specify the "within", "maximum", "minimum, or "is" option.'] = 'Range unspecified.  Specify the "within", "maximum", "minimum, or "is" option.';
$dictionary['Attempted to update a stale object'] = 'Attempted to update a stale object';
$dictionary['Could not find the column %column into the table %table. This column is needed in order to make the %model act as a list.'] = 'Could not find the column %column into the table %table. This column is needed in order to make the %model act as a list.';
$dictionary['Could not find the column "%column" into the table "%table". This column is needed in order to make "%model" act as a list.'] = 'Could not find the column "%column" into the table "%table". This column is needed in order to make "%model" act as a list.';
$dictionary['You are trying to set an object that is not an active record or that is already acting as a list, or nested set. Please provide a valid Active Record Object or call disableActsLike() in your active record in order to solve this conflict.'] = 'You are trying to set an object that is not an active record or that is already acting as a list, or nested set. Please provide a valid Active Record Object or call disableActsLike() in your active record in order to solve this conflict.';
$dictionary['You are trying to set an object that is not an active record.'] = 'You are trying to set an object that is not an active record.';
$dictionary['The following columns are required in the table "%table" for the model "%model" to act as a Nested Set: "%columns".'] = 'The following columns are required in the table "%table" for the model "%model" to act as a Nested Set: "%columns".';
$dictionary['Moving nodes isn\'t currently supported'] = 'Moving nodes isn\'t currently supported';
$dictionary['Could not add hasOne association. Foreign key %fk does not exit on table %table.'] = 'Could not add hasOne association. Foreign key %fk does not exit on table %table.';
$dictionary['Association type mismatch %association_class expected, got %record_class'] = 'Association type mismatch %association_class expected, got %record_class';
$dictionary['Could not write to temporary directory for generating compressed file using Ak::compress(). Please provide write access to %dirname'] = 'Could not write to temporary directory for generating compressed file using Ak::compress(). Please provide write access to %dirname';
$dictionary['Invalid ISO date. You must supply date in one of the following formats: "year-month-day hour:min:sec", "year-month-day", "hour:min:sec"'] = 'Invalid ISO date. You must supply date in one of the following formats: "year-month-day hour:min:sec", "year-month-day", "hour:min:sec"';
$dictionary['Adding sub-tree isn\'t currently supported'] = 'Adding sub-tree isn\'t currently supported';
$dictionary['Argument list did not match expected set. Requested arguments are:'] = 'Argument list did not match expected set. Requested arguments are:';
$dictionary['Filters need to be a method name, or class implementing a static filter method'] = 'Filters need to be a method name, or class implementing a static filter method';
$dictionary['Filter object must respond to both before and after'] = 'Filter object must respond to both before and after';
$dictionary['Missing %template_type %full_template_path'] = 'Missing %template_type %full_template_path';
$dictionary['Can only render or redirect once per action'] = 'Can only render or redirect once per action';
$dictionary['variables'] = 'variables';
$dictionary['You can\'t use the following %type within templates:'] = 'You can\'t use the following %type within templates:';
$dictionary['functions'] = 'functions';
$dictionary['classes'] = 'classes';
$dictionary['Template %template_file compilation error'] = 'Template %template_file compilation error';
$dictionary['Showing template source from %file:'] = 'Showing template source from %file:';
$dictionary['Showing compiled template source:'] = 'Showing compiled template source:';
$dictionary['Template %template_file security error'] = 'Template %template_file security error';
$dictionary['Edit %file_name in order to change this page.'] = 'Edit %file_name in order to change this page.';
$dictionary['No tpl.php, js.php or delegate template found for %template_path'] = 'No tpl.php, js.php or delegate template found for %template_path';
$dictionary['You can\'t instantiate classes within templates'] = 'You can\'t instantiate classes within templates';
$dictionary['Render and/or redirect were called multiple times in this action. Please note that you may only call render OR redirect, and only once per action. Also note that neither redirect nor render terminate execution of the action, so if you want to exit an action after redirecting, you need to do something like "redirectTo(...); return;". Finally, note that to cause a before filter to halt execution of the rest of the filter chain, the filter must return false, explicitly, so "render(...); return; false".'] = 'Render and/or redirect were called multiple times in this action. Please note that you may only call render OR redirect, and only once per action. Also note that neither redirect nor render terminate execution of the action, so if you want to exit an action after redirecting, you need to do something like "redirectTo(...); return;". Finally, note that to cause a before filter to halt execution of the rest of the filter chain, the filter must return false, explicitly, so "render(...); return; false".';
$dictionary['%option must be a Range (array(min, max))'] = '%option must be a Range (array(min, max))';
$dictionary['No tpl.php, js or delegate template found for %template_path'] = 'No tpl.php, js or delegate template found for %template_path';
$dictionary['No tpl.php, js.tpl or delegate template found for %template_path'] = 'No tpl.php, js.tpl or delegate template found for %template_path';
$dictionary['Default Router has not been set'] = 'Default Router has not been set';
$dictionary['The following files have been created:'] = 'The following files have been created:';
$dictionary['Could not find %generator_name generator'] = 'Could not find %generator_name generator';
$dictionary['There where collisions when attempting to generate the %type.'] = 'There where collisions when attempting to generate the %type.';
$dictionary['%file_name file already exists'] = '%file_name file already exists';
$dictionary['Find me in %path'] = 'Find me in %path';
$dictionary['Tag <code>%previous</code> may not contain raw character data'] = 'Tag <code>%previous</code> may not contain raw character data';
$dictionary['Ooops! There are some errors on current XHTML page'] = 'Ooops! There are some errors on current XHTML page';
$dictionary['Showing rendered XHTML'] = 'Showing rendered XHTML';
$dictionary['Tag <code>%tag</code> must occur inside another tag'] = 'Tag <code>%tag</code> must occur inside another tag';
$dictionary['%previous tag is not a content tag. close it like this \'<%previous />\''] = '%previous tag is not a content tag. close it like this \'<%previous />\'';
$dictionary['Tag <code>%tag</code> is not allowed within tag <code>%previous</code>'] = 'Tag <code>%tag</code> is not allowed within tag <code>%previous</code>';
$dictionary['XHTML is not well-formed'] = 'XHTML is not well-formed';
$dictionary['In order to disable XHTML validation, set the <b>AK_ENABLE_STRICT_XHTML_VALIDATION</b> constant to false on your config/development.php file'] = 'In order to disable XHTML validation, set the <b>AK_ENABLE_STRICT_XHTML_VALIDATION</b> constant to false on your config/development.php file';
$dictionary['Tag &lt;code&gt;%tag&lt;/code&gt; must occur inside another tag'] = 'Tag &lt;code&gt;%tag&lt;/code&gt; must occur inside another tag';
$dictionary['Tag &lt;code&gt;%tag&lt;/code&gt; is not allowed within tag &lt;code&gt;%previous&lt;/code&gt;'] = 'Tag &lt;code&gt;%tag&lt;/code&gt; is not allowed within tag &lt;code&gt;%previous&lt;/code&gt;';
$dictionary['%previous tag is not a content tag. close it like this \'&lt;%previous /&gt;\''] = '%previous tag is not a content tag. close it like this \'&lt;%previous /&gt;\'';
$dictionary['Invalid value on &lt;%tag %attribute="%value"'] = 'Invalid value on &lt;%tag %attribute="%value"';
$dictionary['Attribute %attribute can\'t be used inside &lt;%tag> tags'] = 'Attribute %attribute can\'t be used inside &lt;%tag> tags';
$dictionary['Invalid value on &lt;%tag %attribute="%value"... Valid values must match the pattern %pattern'] = 'Invalid value on &lt;%tag %attribute="%value"... Valid values must match the pattern %pattern';
$dictionary['Invalid value on &lt;%tag %attribute="%value"... Valid values must match the pattern "%pattern"'] = 'Invalid value on &lt;%tag %attribute="%value"... Valid values must match the pattern "%pattern"';
$dictionary['Showing XHTML code'] = 'Showing XHTML code';
$dictionary['You have repeated the id %id %count times on your xhtml code. Duplicated Ids found on %tags'] = 'You have repeated the id %id %count times on your xhtml code. Duplicated Ids found on %tags';
$dictionary['Tag %tag requires %attributes to be defined'] = 'La etiqueta %tag require que %attributes debe estar definido';
$dictionary['Tag <%tag> is not allowed within tag <%previous<>'] = 'Tag <%tag> is not allowed within tag <%previous<>';
$dictionary['Tag %tag is not allowed within tag %previous'] = 'Tag %tag is not allowed within tag %previous';
$dictionary['Missing required attribute %attribute on &lt;%tag&gt;'] = 'Missing required attribute %attribute on &lt;%tag&gt;';
$dictionary['Repeating id %id'] = 'Repeating id %id';
$dictionary['duplicate attribute'] = 'duplicate attribute';
$dictionary['XHTML is not well-formed.'] = 'XHTML is not well-formed.';
$dictionary['Illegal tag: <code>%tag</code>'] = 'Illegal tag: <code>%tag</code>';
$dictionary['first page'] = 'primera página';
$dictionary['previous page'] = 'página anterior';
$dictionary['next page'] = 'página siguiente';
$dictionary['last page'] = 'última página';
$dictionary['page'] = 'página';
$dictionary['show all'] = 'mostrar todas';
$dictionary['previous'] = 'anterior';
$dictionary['next'] = 'siguiente';
$dictionary['Showing page %page of %number_of_pages'] = 'Mostrando página %page de %number_of_pages';
$dictionary['first'] = 'primera';
$dictionary['last'] = 'última';
$dictionary['You can\'t use ${ within templates'] = 'No es posible utilizar ${ en las vistas/plantillas';
$dictionary['You must set the settings for current locale first by calling Ak::locale(null, $locale, $settings)'] = 'Tiene que establecer la configuración de la local llamando Ak::locale(null, $locale, $settings) antes de nada';
$dictionary['Akelos'] = 'Akelos';
$dictionary['Could not load %converter_class_name converter class'] = 'No se ha podido cargar la clase del conversor %converter_class_name';
$dictionary['Could not locate %from to %to converter on %file_name'] = 'No se ha encontrado el conversor de %from a %to en %file_name';
$dictionary['Xdoc2Text is a windows only application. Please use wvWare instead'] = 'Xdoc2Text is a windows only application. Please use wvWare instead';
$dictionary['Could not find xdoc2txt.exe on %path. Please download it from http://www31.ocn.ne.jp/~h_ishida/xdoc2txt.html'] = 'Could not find xdoc2txt.exe on %path. Please download it from http://www31.ocn.ne.jp/~h_ishida/xdoc2txt.html';
$dictionary['Loading...'] = 'Cargando...';
$dictionary['Sorry but you can\'t view configuration files.'] = 'Lo sentimos pero no puede ver los ficheros de configuración.';
$dictionary['Opsss! File highlighting is only available on development mode.'] = '¡Uysss!. La vista del código sólo está habilitada en modo de desarrollo';
$dictionary['%file_name is not available for showing its source code'] = 'El fichero %file_name no está disponible para mostrar su código fuente';
$dictionary['Your current PHP settings do not have support for %database_type databases.'] = 'Su configuración actual de PHP no tiene habilitado el soporte para conexiones con bases de datos %database_type databases.';

$dictionary['Could not connect to the FTP server'] = 'No se ha podido conectar con el servidor FTP';
$dictionary['Could not change to the FTP base directory %directory'] = 'Error de FTP. No se ha podido cambiar al directorio base %directory al iniciar sesión en el servidor.';
$dictionary['Could not change to the FTP directory %directory'] = 'Error de FTP. No se ha podido cambiar al directorio %directory';
$dictionary['Ooops! Could not fetch details for the table %table_name.'] = '¡Uysss!. No hemos encontrado los detalles de la tabla %table_name.';

$dictionary['Upgrading'] = 'Actualizando';
$dictionary['Could not find the file /app/controllers/<i>%controller_file_name</i> for the controller %controller_class_name'] = 'No se ha encontrado el fichero /app/controllers/<i>%controller_file_name</i> del controlador %controller_class_name';

$dictionary['No controller was specified.'] = 'No se ha especificado ningún controlador.';

// 2007-10-05 23:28:22


$dictionary['Please add force=true to the argument list in order to overwrite existing files.'] = 'Please add force=true to the argument list in order to overwrite existing files.';

// 2007-10-06 3:15:57


$dictionary['Could not find a helper to handle the method "%method" you called in your view'] = 'Could not find a helper to handle the method "%method" you called in your view';


?>
