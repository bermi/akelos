Creando una aplicación simple usando el Akelos Framework
=========================================================

Introducción
--------------------------

Este tutorial te muestra cómo crear una aplicación con el Akelos Framework. 

Esta aplicación sirve para manejar libros y sus autores y la llamaremos **booklink**

Requisitos para este tutorial
---------------------------

 - Una Base de Datos MySQl o SQLite
 - Servidor web Apache
 - Acceso Shell a tu servidor
 - PHP4 ó PHP5

Esta configuración se puede encontrar en la mayoría de los servidores y proveedores de hosting Linux. Akelos funciona bajo múltiples configuraciones, pero para este tutorial nos centraremos en los requisitos anteriores.

Descarga e Instalación
---------------------------
Hasta que no esté disponible la versión 1.0, te recomendamos que te descargues la última versión trunk (troncal). Para ello necesitas tener instalado [subversion](http://subversion.tigris.org/).
Puedes retirar una "copia de trabajo" del código fuente de Akelos con el comando:

    svn co http://svn.akelos.org/trunk/ akelos

Si no puedes o no quieres descargar el código desde subversion, puedes obtener la [última versión estable](http://www.akelos.org/akelos_framework-dev_preview.tar.gz) generada de forma automática por el sistema de Integración Contínua, y descomprimirlo con el comando:

    tar zxvf akelos_framework-dev_preview.tar.gz;mv akelos_framework-dev_preview akelos

Ahora comprobaremos que Akelos puede encontrar tu versión de PHP, ejecutando:

    /usr/bin/env php -v

Si ves algo como:

    PHP 5.1.2 (cli) (built: Jan 17 2006 15:00:28)
    Copyright (c) 1997-2006 The PHP Group
    Zend Engine v2.1.0, Copyright (c) 1998-2006 Zend Technologies
	
vas por buen camino y puedes continuar creando una aplicación con Akelos; de lo contrario necesitarás averiguar la ruta a tu binario de PHP, habitualmente con

    which php

Y cambiar el `#!/usr/bin/env php` al inicio de estos archivos  `script/console`, `script/generate`, `script/migrate`, `script/setup` y `script/test` por la ruta a tu binario de php.

**Nota para los usuarios de Windows:** Necesitarás llamar a los scripts desde el directorio de tu aplicación, usando la ruta completa a tu archivo php.exe, por ejemplo:

    C:\Archivos de Programa\xampp\php\php.exe ./script/generate scaffold

o alternativamente configurando el path para evitar escribir la ruta completa al binario de PHP siguiendo los pasos dell Mini HOW-TO [Añadiendo PHP al path de Windows](http://svn.akelos.org/trunk/docs/windows_set_php_path-es.markdown).

Instalando una nueva aplicación con Akelos
---------------------------------------------

Ya te has descargado Akelos y te has asegurado de que puedes ejecutar scripts PHP desde tu linea de comandos (no es necesario para ejecutar aplicaciones realizadas con Akelos, pero sí que es necesario para seguir este tutorial)

Ahora se pueden seguir dos vías:

 1. Crear una aplicación con Akelos y enlazarla a las librerías del Framework.
 2. Comenzar a escribir el código de tu aplicación desde esta carpeta, con el riesgo que conlleva que los _visitantes_ de tu sitio puedan dirigirse con su navegador directamente a cualquier fichero del Framework o de tu aplicación sin pasar por el controlador principal.	
 
Si ya te has descargado Akelos en `HOME_DIR/akelos` y estás dentro del directorio `akelos`, puedes comprobar las opciones disponibles para instalar tu nueva aplicación con el comando:

   ./script/setup -h
   
lo que nos muestra las opciones disponibles para el instalador

    Uso: setup [-sqphf --dependencies] <-d> 

    -deps --dependencies      Incluye una copia del framework dentro del directorio de la aplicación. (true)
    -d --directory=<value>    Directorio de destino para instalar la aplicación.
    -f --force                Sobrescribe archivos que ya existen. (false)
    -h --help                 Muestra la ayuda que ahora mismo estás leyendo.
    -p --public_html=<value>  Ubicación que el servidor web utilizará para iniciar la aplicación. ()
    -q --quiet                Suprime la salida por pantalla normal. (false)
    -s --skip                 Ignora archivos que ya existen en el directorio. (false)
	
Así que ejecutando el siguiente comando: (reemplazar `/www/htdocs` con tu  ruta al directorio público del servidor web. En algunos Hosting Compartidos es `/home/USUARIO/public_html`)

    ./script/setup -d HOMEDIR/booklink -p /www/htdocs/booklink

Esto creará la siguiente estructura para la aplicación **booklink**:

    booklink/
        app/ << La aplicación, incluyendo controladores, vistas, modelos e instaladores
        config/ << Los aburridos archivos de configuración (haremos la configuración vía web)
        public/ << Ésta es la única carpeta pública dentro del enlace simbólico /www/htdocs/booklink 
        script/ << Utilidades para generación de código y ejecución de pruebas unitarias (tests)

**Nota para los usuarios de Windows:** Un enlace simbólico al directorio booklink/public se crea solamente en sistemas *NIX, así que necesitarás indicarle a tu servidor web cómo encontrar la ruta pública para la aplicación **booklink** en tu archivo `httpd.conf` agregando algo así como:

    Alias /booklink "/path/to_your/booklink/public"

    <Directory "/path/to_your/booklink/public">
    	Options Indexes FollowSymLinks
    	AllowOverride All
    	Order allow,deny
        Allow from all
    </Directory>

una vez agregado esto, reinicia el servidor.

### Creando una base de datos para la aplicación ###

El siguiente paso es crear una base de datos para la aplicación. Si tienes planeado usar SQLite sobre PHP5 puedes saltarte este paso.

 En este tutorial no podemos  explicar cómo crear una base de datos MySQL pero quizá te sirva de ayuda tomar como ejemplo este caso común donde creamos tres bases de datos diferentes, una para cada entorno (producción, desarrollo y testing).

    mysql -u root -p
    
    mysql> CREATE DATABASE booklink;
    mysql> CREATE DATABASE booklink_dev;
    mysql> CREATE DATABASE booklink_tests;
    
    mysql> GRANT ALL ON booklink.* TO bermi@localhost IDENTIFIED BY "pass";
    mysql> GRANT ALL ON booklink_dev.* TO bermi@localhost IDENTIFIED BY "pass";
    mysql> GRANT ALL ON booklink_tests.* TO bermi@localhost IDENTIFIED BY "pass";
    
    mysql> FLUSH PRIVILEGES;
    mysql> exit

Si estás en un servidor de hosting compartido, seguramente deberás crearlas desde el panel de control de tu proveedor de alojamiento web.

### Generando el archivo de configuración ###

#### Usando el instalador web ####

Ahora ya puedes acceder al asistente de configuración de tu aplicacion en http://localhost/booklink    

Sigue los pasos del asistente para configurar el acceso a tu base de datos, configuraciones regionales y permisos de archivo, para así generar un archivo de configuración. Yo mientras vas creando la aplicación **booklink** tranquilamente, iré a buscar un café.

#### Configuración manual de la aplicación ####

Guarda los archivos `config/DEFAULT-config.php` y `config/DEFAULT-routes.php` como `config/config.php` y `config/routes.php` y modifica lo que consideres necesario siguiendo las indicaciones del fichero.

Si deseas usar URLs bonitas necesitas definir la ruta base para la reescritura de URLs, editando el archivo `public/.htaccess` y definiendo RewriteBase como:

    RewriteBase /booklink

Una vez hayas instalado correctamente tu aplicación, podrás ver un mensaje de bienvenida en http://localhost/booklink. Ahora puedes eliminar los archivos de instalación del framework con toda seguridad (no serán accesibles mientras exista el archivo  `/config/config.php`)

La estructura de la base de datos de booklink
---------------------------------------------

Ahora necesitas definir las tablas y columnas donde la aplicación almacenará la información de los libros y los autores. 

Cuando se trabaja con otros desarrolladores, los cambios en las bases de datos pueden ser difíciles de distribuir entre todos ellos. Akelos ofrece una solución a este problema llamada *instalador* o *migración*.

Para crear la estructura de la base de datos utilizarás un installer para distribuir las modificaciones que realices en ella.
El uso de *instaladores* te permitirá definir las tablas y columnas de tu base de datos independientemente de si usas MySQL, SQLite u otro.

Ahora crearás un archivo llamado `app/installers/booklink_installer.php` con el siguiente código correspondiente al Instalador de Booklink:

     <?php
     
     class BooklinkInstaller extends AkInstaller
     {
         function up_1(){
             
             $this->createTable('books',
                'id,'.          // la clave principal
                'title,'.       // el título del libro
                'description,'. // la descripción del libro
                'author_id,'.   // el identificador del author. Esto lo utilizará Akelos para vincular libros con autores.
                'published_on'  // fecha de publicación
            );
            
             $this->createTable('authors', 
                'id,'.      // la clave principal
                'name'      // el nombre del autor
                );
         }
         
         function down_1(){
             $this->dropTables('books','authors');
         }
     }
     
     ?>

Eso es suficiente para que Akelos cree la estructura de tu base de datos. Si sólo especificas el nombre de la columna, Akelos determinará el tipo de datos basándose en convenciones de normalización de base de datos. Si deseas tener total control de la configuración de tus tablas, puedes usar [sintaxis Datadict de php Adodb](http://phplens.com/lens/adodb/docs-datadict.htm)

Ahora ejecuta el instalador, con el comando:

    ./script/migrate Booklink install

y eso creará las tablas definidas en el instalador. Si estás usando una base de datos MySQL, creará las siguientes estructuras:

**TABLA BOOKS**

    +--------------+--------------+------+-----+----------------+
    | Field        | Type         | Null | Key | Extra          |
    +--------------+--------------+------+-----+----------------+
    | id           | int(11)      | NO   | PRI | auto_increment |
    | title        | varchar(255) | YES  |     |                |
    | description  | longtext     | YES  |     |                |
    | author_id    | int(11)      | YES  | MUL |                |
    | published_on | date         | YES  |     |                |
    | updated_at   | datetime     | YES  |     |                |
    | created_at   | datetime     | YES  |     |                |
    +--------------+--------------+------+-----+----------------+ 

**TABLA AUTHORS**
                       
    +--------------+--------------+------+-----+----------------+
    | Field        | Type         | Null | Key | Extra          |
    +--------------+--------------+------+-----+----------------+
    | id           | int(11)      | NO   | PRI | auto_increment |
    | name         | varchar(255) | YES  |     |                |
    | updated_at   | datetime     | YES  |     |                |
    | created_at   | datetime     | YES  |     |                |
    +--------------+--------------+------+-----+----------------+

Modelos, Vistas y Controladores
------------------------------------------------------

Akelos sigue el [patrón de diseño MVC](http://en.wikipedia.org/wiki/Model-view-controller) para organizar tu aplicación.

![Diagrama MVC de Akelos.](http://svn.akelos.org/trunk/docs/images/akelos_mvc-es.png)

### Los archivos de tu aplicación y las convenciones de nomenclatura de Akelos ###

Éstas son las convenciones que permiten mantener la filosofía de convención sobre configuración de Akelos.

#### Modelos ####

 * **Ruta:** /app/models/
 * **Nombre de la clase:** singular, separando palabras sin espacios y con mayúsculas (CamelCase) *(BankAccount, Person, Book)*
 * **Nombre del fichero:** singular, separando palabras con guiones bajos  *(bank_account.php, person.php, book.php)*
 * **Nombre de la tabla:** plural,  separando palabras con guiones bajos *(bank_accounts, people, books)*

#### Controladores ####

 * **Ruta:** */app/controllers/*
 * **Nombre dela clase:** singular o plural, separando palabras sin espacios y con mayúsculas (CamelCase), termina en `Controller` *(AccountController, PersonController)*
 * **Nombre del fichero:** singular o pural, separando palabras con guiones bajos, termina en `_controller` *(`account_controller.php`, `person_controller.php`)*

#### Vistas ####

 * **Ruta:** /app/views/ + *nombre_del_controlador_con_guiones_bajos/* *(app/views/person/)*
 * **Nombre del fichero:** nombre de la acción, minusculas *(app/views/person/show.tpl)*


Scaffolding (Andamiaje) en Akelos
------------------------------------------

Akelos incluye generadores de código que pueden reducir el tiempo de desarrollo al crear código *scaffold* (andamio) que puedes utilizar como punto de partida para tu aplicación. El proceso de scaffolding genera automáticamente el código necesario para realizar tareas habituales de **altas**, **bajas** y **modificación** de registros en las tablas de la base de datos de la aplicación.

### Intimando con el generador de Scaffolds  ###

Ahora generarás el esqueleto base para interactuar con la base de datos **booklink** creada en el paso anterior. Para generar este esqueleto de forma rápida puedes usar el *generador de scaffolds* de esta manera:

    ./script/generate scaffold Book

y 

    ./script/generate scaffold Author

Esto generará un montón de archivos y carpetas que ¡funcionan de verdad!. ¿No me crees? Pruébalo tu mismo. Dirige tu navegador a  [http://localhost/booklink/author](http://localhost/booklink/author) y [http://localhost/booklink/book](http://localhost/booklink/book) para empezar a agregar autores y libros. Crea algunos registros y vuelve aquí para saber que es lo que ocurre internamente.


El flujo de trabajo de Akelos
------------------------------------------

Esta es una pequeña explicación del flujo de trabajo subyacente para una llamada a la siguiente URL  `http://localhost/booklink/book/show/2`

 1. Akelos dividirá la petición en tres parámetros  de acuerdo a tu archivo `/config/routes.php` (más sobre este tema en un instante)
  * controlador: book
  * acción: show
  * id: 2

 2. Una vez que Akelos conoce las partes de la petición buscará el archivo `/app/controllers/book_controller.php` y si lo encuentra instanciará la clase `BookController`

 3. El controlador buscará un modelo que coincida con el parámetro `controlador` de la petición. En este caso buscará `/app/models/book.php`. Si lo encuentra, creará una instancia del modelo en el atributo del controlador  `$this->Book`. si hay un `id` en la petición, buscará en la tabla Books de la base de datos el registro con id 2 y eso permanecerá en `$this->Book`

 4. Ahora llamará a la acción `show` de la clase `BookController` si estuviese disponible.

 5. Una vez que la acción `show` se ejecuta, el controlador buscará el archivo de la vista en `/app/views/book/show.tpl` y renderizará el resultado en la variable `$content_for_layout`.

 6. Ahora Akelos buscará un layout con el mismo nombre que el controlador en `/app/views/layouts/book.tpl`. Si lo encuentra renderizará el mismo, insertando el contenido de `$content_for_layout` y enviando la salida al navegador.

Esto podría ayudarte a comprender la forma en la que Akelos maneja tus peticiones, así que estamos listos para modificar la aplicación base.


Relacionando libros y autores
----------------------------

Ahora vamos a enlazar autores y libros (trataremos asociaciones más complejas en futuros tutoriales). Para conseguir esto, usarás la columna `author_id` que definiste en la base de datos.

Ahora necesitarás indicar a tus modelos cómo se relacionan unos con otros, de esta forma 

*/app/models/book.php*

    <?php
    
    class Book extends ActiveRecord
    {
        var $belongs_to = 'author'; // <- declarando la asociación
    }
    
    ?>

*/app/models/author.php*

    <?php
    
    class Author extends ActiveRecord
    {
        var $has_many = 'books'; // <- declarando la asociación
    }
    
    ?>

Ahora que los modelos son conscientes de la existencia del otro necesitas modificar el controlador book, para que incluya las instancias de los modelos `author` y `book`

*/app/controllers/book_controller.php*

    <?php
    
    class BookController extends ApplicationController
    {
        var $models = 'book, author'; // <- hace que estos modelos estén dispobibles
        
        // ... más código del BookController
 
        function show()
        {
            // Reemplaza "$this->book = $this->Book->find(@$this->params['id']);"
            // con esto, para encontrar a los autores relacionados
            $this->book = $this->Book->find(@$this->params['id'], array('include' => 'author'));
        }
        
        // ... más código del BookController
    }

El próximo paso es mostrar los autores disponibles en el momento de crear o editar un libro. Esto puede lograrse mediante el uso de `$form_options_helper` insertando el siguiente código 
justo después de `<?=$active_record_helper->error_messages_for('book');?>` en el archivo  */app/views/book/_form.tpl* 

    <p>
        <label for="author">_{Author}</label><br />
        <?=$form_options_helper->select('book', 'author_id', $Author->collect($Author->find(), 'name', 'id'));?>
    </p>

Si aún no has creado ningún autor necesitarás crear algunos ahora mismo para ver de lo que hablamos. Luego visita http://locahost/booklink/book/add para comprobar que nos muestra una lista para seleccionar autores. Adelante, crea un libro seleccionando un autor de la lista.

Parece ser que el autor se ha guardado, pero no se muestra en la vista `app/views/book/show.tpl`. deberás añadir este código después de `<? $content_columns = array_keys($Book->getContentColumns()); ?>`

    <label>_{Author}:</label> <span class="static">{book.author.name?}</span><br />

Ahora seguramente estarás poniendo el grito en el cielo sobre la extraña sintaxis de `_{Author}` y `{book.author.name?}` syntax. Eso, en realidad es  [Sintags](http://www.bermi.org/projects/sintags), un pequeño grupo de reglas que ayudan a escribir vistas más limpias y que será compilado como PHP standard.

Comentarios Finales
--------------------

Esto es todo por ahora, iré mejorando este tutorial de vez en cuando, para agregar algunas funcionalidades que faltan, como:

 * validaciones
 * rutas (routes)
 * filtros
 * callbacks
 * transacciones
 * consola
 * AJAX
 * helpers
 * servicios web
 * pruebas unitarias (testing)
 * distribución
 * y mucho más...


------------

Traducción realizada por: Matias Quaglia