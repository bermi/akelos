Mini HOW-TO. Añadiendo PHP al path de Windows
---------------------------------------------

Para usar con comodidad el ejecutable php.exe y no tener que indicar su ruta compelta todas las veces que se ejecuten scripts de [Akelos](http://www.akelos.org/) desde la consola de comandos se debe cambiar la variable de entorno `path`. La misma contiene la ruta de acceso de los programas que se usan más frecuentemente en el sistema, así parta poder ejecutarlo bastara escribir su nombre en la consola y Windows lo buscará sin necesidad de escribir la ruta completa todas las veces que necesitemos acceder a dicho programa. 

### Ejemplo antes y después del seteo del path de php.exe ###

**Antes:**

    C:\Archivos de Programa\xampp\php\php.exe ./script/generate scaffold

**Después:**

    php ./script/generate scaffold

Cambiando el path
-----------------

Para conocer los valores de la variable de entorno path ejecutamos en la consola:

    path

Lo que devolverá una lista de los valores de esta variable separados por ';'. 
Bastará con agregar la ruta a nuestro propio php.exe, en este ejemplo `C:\Archivos de Programa\xampp\php\php.exe`. A dicha lista. ¿Cómo hacemos esto?
Copiamos el texto que nos devuelve la ejecucón de `path` en la consola. Por ejemplo:
`PATH=C:\WINDOWS\system32;C:\WINDOWS;` y agregamos la ruta anuestro `php.exe` al final de dicha lista:
`PATH=C:\WINDOWS\system32;C:\WINDOWS;C:\Archivos de Programa\xampp\php\php.exe;`.
Para llevar a cabo dichos cambios deberíamos ejecutar en la consola

    set path=C:\WINDOWS\system32;C:\WINDOWS;C:\Archivos de Programa\xampp\php\php.exe;

Para terminar reiniciamos el equipo. Con esto logramos definir la variable path cada vez que se ejecuta el archivo Autoexec.bat, es decir cada vez que se inicia Windows.

*Nota:
Este mini How-To está inspirado y toma como base el tutorial [¿Qué es el Path y el Classpath de java?](http://www.webtaller.com/construccion/lenguajes/java/lecciones/que-es-path-classpath-java.php)*