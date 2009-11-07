Introduction.
---------------------------------------

The Akelos Framework is an open-source port of Ruby on Rails to the 
PHP programming language.

The main goal of the Akelos Framework its to help programmers to build
multilingual database-backed web applications according to the
Model-View-Control pattern. It lets you write less code by favoring
conventions over configuration.

You can find more information at the Akelos Framework website at
http://www.akelos.org 


The tutorial
---------------------------------------
Perhaps the easiest way to lear about Akelos is to get your hands on the tutorials 
you can find on the docs folder.


Setting up the framework.
---------------------------------------
Once you checkout the code you'll need to make available the folder ./public 
to your webserver with a command like:

    ln -s  /home/bermi/akelos_framework/public /usr/htdocs/akelos 

Then just point your browser to that url and follow the steps.

You will also need to make sure that mod_rewrite is loaded into Apache,
and that it can be controlled from .htaccess files, to do this make sure that
the Apache configuration directive AllowOverride is set to 'All' (you may allow only the specific directives for mod_rewrite),
for the directory your project will be accessed from.


If you have problems with the web setup you can copy and edit 
config/DEFAULT-config.php and config/DEFAULT-routes.php. You might also need
to edit the  .htaccess files in ./ and ./public/  and un-comment/edit the 
"# RewriteBase" directive so it matches to your url path.

All the configuration params are on /lib/constants.php If you define any of them
in your /config/config.php, /config/development.php, /config/production.php 
or /config/testing.php the default setting will be overwritten.


Accessing the Command Line interface
---------------------------------------
In order to access the command line interface run 
     
    ./script/console

Then you can run any PHP code interactively.

Example:

	>>> generate
	
	// Will show a list of available generators
	
	>>> test app/models/post.php
	
	// Will run the unit tests for the framework the Post model
	
You can also use the commands generate, migrate, setup ... by calling directly
   
     ./script/generate


Differences from Ruby on Rails.
---------------------------------------
I've tried to adhere as much as I could to the original interfaces, but some 
general changes apply:

- PHP doesn't have name spaces so on the controller you must access to
$this->params, $this->ModelName, $this->Request, $this->Response

- Templates are ended in .tpl (there is only one render on the framework, but
more can be added)

- Views work using PHP, but some like file functions, static method calls, 
object instantiation.... will be disallowed for helping in keeping in the
view just presentation logic. If you need extra logic for your views you can
always create a helper "./app/helpers" so your views will be easier to
maintain.

- Helpers are made available automatically for your views under the naming 
convention $name_helper were "name" is the name of the desired helper.

    $url_helper->url_for(array('action'=>'add'));

- All the methods (but helpers) use PEAR like naming conventions so instead of 
AkActionController::url_for() you need to call AkActionController::urlFor()

- Helpers are located at /lib/AkActionView/helpers (it's worth having a look 
at them)

- In order to expose data from your controllers to the views, you'll simply 
need to assign them as attributes of the controller that is handling the
action so:

    class PostController extends ApplicationController
    {
          function index()
          {
               $this->message = 'Hello World';
          }
    }

Will expose  into ./app/views/post/index.tpl $message variable so you can use 
it like:
 
    <?php echo $message; ?> 
    
or the same using SinTags

    {message}


i18n and l10n the Akelos way
---------------------------------------

Locale files are located at:

    ./config/locales/  # Akelos Framework locales
    ./app/locales/NAMESPACE/ # Your application locales where NAMESPACE is
     replaced by your model/controller/view name

In order to change the language of your application can prefix your request 
with the locale name so:

    http://example.com/es/post/add # will load ./config/locales/es.php
and
    http://example.com/en/post/add # will load ./config/locales/en.php


All the functions for writing multilingual code rely on the Ak::t() method.
Based on the Ak::t() function you can find:

    $PostController->t() # controller
    $Post->t() # model
    $text_helper->translate() # for the view
    _{ hello world }  # for the view (SinTags)

All these four will save new locales onto their corresponding namespace in 
the example above "./app/locales/post/en.php"

If you want to use your own namespace for storing locales you can do it like:
    
    translate('Hello world', null, 'shared_posts');
 
In this case it will store it at "./app/locales/shared_posts/en.php"


Deal with Compound Messages

As you can see the Framework has been designed with l10n and i18n in mind. One
nice and flexible feature common to all these functions but the sintags one is
the ability to add compounded messages, you might already realized this but
here is a small example:

Ak::t('Hello %title %last_name,',
array('%title'=>$title,'%last_name'=>$last_name,'%first_name'=>$first_name));

    Ak::t('Today is %date', array('%date'=>Ak::getDate()));
    // You can use Ak::t or any of its derived methods

The SinTags way to deal with compounded messages is
    
    _{Today is %date}
    // which will be converted to
    // <?=$text_helper->translate('Today is %date', array('%date'=>$date));?>
    // note that $date is selected by replacing the % from the needle

Internationalizing Models. 

You can have multilingual database columns by adding the locale prefix plus
and underscore to the column name. This way when you do 

    $Article->get('title')

you'll get the information on the "en_title" column if "en" is your current
locale. 

The same way you can set posted attributes like 

    $_POST = array('title'=>array('en'=>'Tech details',
     'es'=>'Detalles técnicos')); 
    $Article->setAttributes($_POST); 

and the attributes will be mapped to their corresponding columns. 

In order to make this work you need to add to your config/config.php

    define('AK_ACTIVE_RECORD_DEFAULT_LOCALES', 'en,es');


In order to convert between charsets you can use Ak::recode() and 
Ak::utf8('My  ISO Text', 'ISO-8859-1').
