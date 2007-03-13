Comment créer une application simple grâce au framework Akelos
=========================================================

Introduction
--------------------------

Ce tutoriel va vous permettre de créer une application à l'aide du framework Akelos.
Nous allons donc créer **booklink**, une application de gestion de livres et de leurs auteurs.

Configuration nécessaire
---------------------------

 - Une base de données de type MySQL ou SQLite
 - Un serveur web Apache
 - Un accès shell à votre serveur ('cmd' sur Windows)
 - PHP4 or PHP5

Cette configuration est plutôt commune et se retrouve sur la plupart des serveurs web ou machines *NIX.
Bien sûr, Akelos fonctionne sur plusieurs types de configuration, mais pour ce tutoriel, nous nous concentrerons sur celle-ci.

Téléchargement et installation
---------------------------

Tant qu'Akelos n'est pas sorti dans sa version finale (1.0), nous vous recommandons de toujours utiliser la dernière révision SVN.
Pour cela, il vous faudra posséder un client [subversion](http://subversion.tigris.org).

Pour récupérer la dernière révision d'Akelos, tapez la commande :

    svn co http://akelosframework.googlecode.com/svn/trunk/ akelos

Si jamais vous ne pouvez ou ne voulez pas utiliser subversion, vous pouvez toujours télécharger la [dernière version stable](http://www.akelos.org/akelos_framework-dev_preview.tar.gz).
Vous pouvez ensuite l'extraire en tapant :

    tar zxvf akelos_framework-dev_preview.tar.gz ; mv akelos_framework-dev_preview akelos

Il faut maintenant s'assurer qu'Akelos sera capable d'utiliser PHP sur votre système. Tapez donc :

    /usr/bin/env php -v

Si vous voyez quelque chose de ce genre :
    
    PHP 5.2.1 (cli) (built: Jan 17 2006 15:00:28)
    Copyright (c) 1997-2006 The PHP Group
    Zend Engine v2.1.0, Copyright (c) 1998-2006 Zend Technologies
    
alors vous êtes prêts à utiliser Akelos, vous pouvez donc passer au paragraphe suivant.

Cependant, si ce n'est pas le cas, il vous faudra trouver le chemin complet vers votre binaire PHP. En général, il suffit de taper :

    which php

Ensuite, changez le chemin dans le shebang `#!/usr/bin/env php` par le votre, et ce, au début de chacun de ces fichiers :

 * script/console
 * script/generate
 * script/setup
 * script/migrate
 * script/setup
 * script/test

**Pour les utilisateurs de Windows :** Les shebang ne sont pas pris en compte sur Windows. Il vous faudra donc appeler les scripts directement avec le binaire php :

    C:\wamp\php\php.exe ./script/generate scaffold

Mise en place d'une nouvelle application Akelos
---------------------------------------------

A ce point, vous devez avoir Akelos mis en place, et devez être capable de lancer les scripts PHP depuis une console. Bien que ces scripts ne soient pas absolument nécessaires au fonctionnement d'Akelos, ils le seront pour ce tutoriel.

Vous avez maintenant deux possibilités :

 1. Créer une application Akelos dans un dossier différent et lier ce dernier aux librairies du Framework.
 2. Commencer à travailler directement depuis le dossier téléchargé, avec la sécurité que cela implique : il n'est jamais recommandé de rendre visibles les sources de votre application.

Vous l'aurez sûrement deviné, nous utiliserons la première méthode qui consiste à créer un lien (symbolique par exemple) vers le dossier `public` de notre application. Il est aussi très simple de configurer les dossiers du framework, puisqu'il suffit de définir l'emplacement de chacun des composants. Cependant, ce n'est pas le sujet de cette explication, et laissons cette partie à un prochain tutoriel expliquant la mise en place et en production d'une application.

Nous supposerons que vous avez téléchargé Akelos dans le dossier `HOME_DIR/akelos` et que vous vous situez à la racine du dossier `akelos`.
D'ici, vous pouvez obtenir les différentes options d'installation du framework en tapant :

    ./script/setup -h

Vous devriez obtenir l'affichage suivant :

    Usage: setup [-sqphf --dependencies] <-d> 

    -deps --dependencies      Includes a copy of the framework into the application
                              directory. (true)
    -d --directory=<value>    Destination directory for installing the application.
    -f --force                Overwrite files that already exist. (false)
    -h --help                 Show this help message.
    -p --public_html=<value>  Location where the application will be accesed by the
                              webserver. ()
    -q --quiet                Suppress normal output. (false)
    -s --skip                 Skip files that already exist. (false)

Dont voici la traduction :

    Utilisation: setup [-sqphf --dependencies] <-d> 

    -deps --dependencies      Inclut une copie du framework dans le répertoire de
                              l'application. (true)
    -d --directory=<value>    Dossier d'installation de l'application.
    -f --force                Écraser les fichiers existants. (false)
    -h --help                 Affiche cette aide.
    -p --public_html=<value>  Dossier par lequel le serveur web va accéder à
                              l'application.
    -q --quiet                N'affiche rien. (false)
    -s --skip                 Ne copie pas les fichiers déjà existants. (false)

Voici un exemple de commande d'installation : (remplacez `/www/htdocs` par le chemin vers votre serveur web)

    ./script/setup -d ~/booklink -p /www/htdocs/booklink

Cela va générer l'architecture suivante pour l'application **booklink** :

    booklink/
        app/ << L'application (modèles, vues, contrôleurs, et installeurs)
        config/ << Des machins de configuration, mais tout sera fait via navigateur.
        public/ << Le seul dossier rendu public
        script/ << Outils de génération de code, de lancement de tests, etc.

**Pour les utilisateurs de Windows :** Les liens symboliques ne fonctionnent pas non plus sous Windows. Il va donc falloir renseigner Apache sur le chemin vers votre application. Éditez le fichier `httpd.conf` et rajoutez ceci (en modifiant, bien entendu, au préalable selon votre configuration) :

    Alias /booklink "/chemin/vers/booklink/public"

    <Directory "/chemin/vers/booklink/public">
    	Options Indexes FollowSymLinks
    	AllowOverride All
    	Order allow,deny
        Allow from all
    </Directory>

N'oubliez pas de redémarrer le serveur Apache.

### Création de la base de données (MySQL) ###

**/!\ Si vous comptez utiliser SQLite, sautez cette étape /!\\**

La prochaine étape consiste à créer la base de données relative à votre application.

Le but de ce tutoriel n'est bien évidemment pas de vous apprendre à créer une base de données. Si vous ne savez pas comment faire, faites des recherches sur Google, vous trouverez sûrement quelquechose :).

Cependant, vous pouvez tout simplement essayer de créer 3 bases différentes, pour chacun des 3 environnements (production, développement, tests)

    $> mysql -u root -p
    
    mysql> CREATE DATABASE booklink;
    mysql> CREATE DATABASE booklink_dev;
    mysql> CREATE DATABASE booklink_tests;
    
    mysql> FLUSH PRIVILEGES;
    mysql> exit

Vous pouvez bien évidemment passer par une interface graphique, telle phpMyAdmin, pour créer ces tables.

### Création des fichiers de configuration ###

#### À l'aide de l'installeur ####

Vous pouvez ouvrir votre navigateur et vous rendre sur le script d'installation en allant à l'adresse `http://localhost/booklink`.

Vous allez donc pouvoir configurer votre base de données, vos différents langages, et les permissions de vos fichiers. Le fichier de configuration sera enfin généré. Pendant que bermi s'occupe de prendre un café en attendant que les Anglais et les Espagnols configurent leur application **booklink**, je pencherais plutôt pour un p'tit chocolat chaud.

#### Configuration manuelle (non, pas le prénom) ####

Copiez les fichiers `config/DEFAULT-config.php` et `config/DEFAULT-routes.php` en tant que `config/config.php` et `config/routes.php`, respectivement, et éditez-les à vos soins.

Il vous faudra probablement aussi définir le dossier à partir duquel s'effectue la ré-écriture d'URL (afin de pouvoir utiliser des URL propres). Éditez donc le fichier `public/.htaccess`, et changez la valeur de RewriteBase :

    RewriteBase /booklink

Une fois votre application installée, vous pouvez ouvrir un navigateur et aller sur `http://localhost/booklink`. Un message d'accueil s'affichera, et vous pourrez alors supprimer les fichiers d'installation du framework.

Structure de la base de données
---------------------------------

Il va maintenant falloir définir les tables que **booklink** va utiliser pour stocker les informations sur les livres et leurs auteurs.

La plupart du temps, lorsque l'on travaille avec d'autres développeurs, le schéma de la base de données est susceptible de changer. Il devient alors compliqué de maintenir cette base identique pour chaque personne du projet. Akelos propose donc une solution à ce problème, appelée *installer*, ou *migration*.

Grâce à cet outil de migration, vous allez non seulement pouvoir créer vos bases de données, mais aussi générer un installeur, qui pourra être utilisé pour enregistrer tous les différents changements que vous effectuerez sur la base.

Pour ce tutoriel, créez le fichier `app/installers/booklink_installer.php`, et copiez-y le contenu suivant :
 
     <?php
     
     class BooklinkInstaller extends AkInstaller
     {
         function up_1(){
             
             $this->createTable('books',
                'id,'.          // La clé primaire
                'title,'.       // Le titre du livre
                'description,'. // La description du livre
                'author_id,'.   // L'identifiant de l'auteur. C'est grâce à cela qu'Akelos va pouvoir faire le lien entre les deux.
                'published_on'  // La date de publication
            );
            
             $this->createTable('authors', 
                'id,'.      // La clé primaire
                'name'      // Le nom de l'auteur
                );
         }
         
         function down_1(){
             $this->dropTables('books','authors');
         }
     }
     
     ?>

Ce peu de données suffit à Akelos pour créer la base de données. En ne spécifiant que le nom des colonnes, Akelos choisira lui-même leur type en se basant sur les conventions de nommage des tables SQL. Cependant, vous avez bien évidemment la possibilité de définir vous-même le typages des colonnes grâce à la [syntaxe de PHP Adodb](http://phplens.com/lens/adodb/docs-datadict.htm)

Maintenant que nous avons défini les tables, il ne reste plus qu'à les installer. Tapez la commande :

    ./script/migrate Booklink install

Et pouf ! Les tables sont installées automagiquement ! Avec MySQL, vous devriez obtenir quelque chose du genre :

**TABLE "BOOKS"**

    +--------------+--------------+------+-----+----------------+
    | Field        | Type         | Null | Key | Extra          |
    +--------------+--------------+------+-----+----------------+
    | id           | int(11)      | NO   | PRI | auto_increment |
    | title        | varchar(255) | YES  |     |                |
    | description  | longtext     | YES  |     |                |
    | author_id    | int(11)      | YES  | MUL |                |
    | published_on | date         | YES  |     |                |
    +--------------+--------------+------+-----+----------------+ 

**TABLE "AUTHORS"**
                       
    +-------+--------------+------+-----+----------------+
    | Field | Type         | Null | Key | Extra          |
    +-------+--------------+------+-----+----------------+
    | id    | int(11)      | NO   | PRI | auto_increment |
    | name  | varchar(255) | YES  |     |                |
    +-------+--------------+------+-----+----------------+


Modèles, Vues, et Controlleurs
------------------------------------------------------

Pour faire fonctionner vos applications, Akelos utilise le [motif de conception appelé MVC](http://fr.wikipedia.org/wiki/Motif_de_conception).

### Les conventions de nommage dans Akelos ###

Le nommage de chaque objet dans Akelos est très important, puisqu'il permet l'automatisation de son fonctionnement.

#### Modèles ####

 * **Dossier :** /app/models/
 * **Nom des classes :** au singulier, au format [CamelCase](http://fr.wikipedia.org/wiki/CamelCase) *(BankAccount, User, etc.)*
 * **Nom des fichiers :** au singulier, séparé par des underscore *(bank_account.php.php, user.php, etc.)*
 * **Nom des tables :** au pluriel, séparé par des underscore *(bank_accounts, users)*

#### Contrôleurs ####

 * **Dossier :** */app/controllers/*
 * **Nom des classes :** Au singulier ou au pluriel, au format CamelCase, fini par `Controller` *(AccountController, UserController)*
 * **Nom des fichiers :** Au singulier ou au pluriel, séparé par des underscore, fini par `_controller` *(account_controller.php, user_controller.php)*

#### Vues ####

 * **Dossier :** /app/views/ + *nom_du_controller_avec_underscore/* *(app/views/account, app/views/super_user/)*
 * **Nom des fichiers :** Nom de l'action, en minuscules *(app/views/user/show.tpl)*


Utilisation du scaffolding dans Akelos
------------------------------------------

Akelos fournit une méthode de **scaffold**, à savoir une générateur de code qui vous fera non seulement gagner du temps, mais pourra aussi servir de point de départ à la construction de votre application, ou à votre apprentissage.

### La magie du scaffold ###

À l'aide du scaffolding, vous allez générer le squelette d'une interface d'administration pour **booklink**, ce qui va vous permettre d'ajouter/éditer/supprimer des entrées dans la base de données.
Tapez ces deux commandes :

    ./script/generate scaffold Book
    ./script/generate scaffold Author

Cela va créer une multitude de fichiers là où il le faut, et le tout va fonctionner directement ! Sceptique ?
Allez donc sur [http://localhost/booklink/author](http://localhost/booklink/author) et sur [http://localhost/booklink/books](http://localhost/booklink/books), et vous pourrez d'ores et déjà gérer les livres et les auteurs dans votre base de données.
Allez, je vous laisse un peu de temps pour vous amuser, et revenez me voir dès que vous êtes prêts à continuer.

Le fonctionnement d'Akelos
------------------------------------------

Voici une description rapide de comment Akelos réagi lorsqu'il répond à l'adresse : `http://localhost/booklink/book/show/2`
  
 1. Akelos va récupérer trois paramètres, en fonction de ce que vous avez défini dans le fichier `/config/routes.php` (tutoriel est à venir) :

  * contrôleur : *book*
  * action : *show*
  * id : 2

 2. Il va ensuite chercher le fichier `/app/controllers/book_controller.php`. S'il existe, il instanciera la classe `BookController`.

 3. Le contrôleur instancié va chercher le modèle lui correspondant, ici `/app/models/book.php`. Si le modèle existe, il en crée une instance, disponible ici via l'attribut `$this->Book`. Il va ensuite chercher dans la base de données Books l'entrée avec un `id = 2` qui écrasera l'attribut `$this->Book`.

 4. Akelos appelle enfin l'action `show` de la classe `BookController`.

 5. A la fin de l'action, Akelos chercher le fichier de vue `/app/views/book/show.tpl` et crée le rendu de ce fichier, ce dernier étant aussi disponible dans la variable `$content_for_layout` dans les layouts.

 6. Akelos va enfin chercher le fichier layout appelé `/app/views/layouts/book.tpl`. Si ce fichier est trouvé, Akelos crée le rendu du layout, et assigne le contenu de la vue dans `$content_for_layout`. Le tout est enfin envoyé au navigateur.

Si vous avez compris ce fonctionnement, je pense que vous pouvez d'ores et déjà commencer à modifier votre application.

Faire la relation entre *Books* et *Authors*
----------------------------

Il va maintenant falloir créer le lien entre la classe *Book* et la classe *Author*. Pour cela, il vous faudra utiliser la colonne `author_id` dans la base *books*

Pour renseigner chacun des modèles sur la relation entre *books* et *authors*, il vous suffit de faire :

`/app/models/book.php`

    <?php
    
    class Book extends ActiveRecord
    {
        var $belongs_to = 'author'; // Un livre correspond à un auteur
    }
    
    ?>

`/app/models/author.php`

    <?php
    
    class Author extends ActiveRecord
    {
        var $has_many = 'books'; // Un auteur peut posséder plusieurs livres
    }
    
    ?>

Les modèles savent maintenant comment ils sont liés, mais il faut que le contrôleur `BookController` puisse charger les deux modèles, `author` et `book`.

`/app/models/author.php`

    <?php
    
    class BookController extends ApplicationController
    {
        var $models = 'book, author'; // Cette ligne suffit à indiquer quels modèles utiliser
        
        // ... code du controlleur
    }

La prochaine étape consiste à afficher les auteurs disponibles dans la base lors de l'ajout/édition d'un livre. Il suffit pour cela d'utiliser, dans la vue,
la variable `$form_options_helper`.

Juste après `<?=$active_record_helper->error_messages_for('book');?>`, dans le fichier */app/views/book/_form.tpl*, rajoutez le code suivant :

`/app/views/book/_form.tpl`

    <p>
        <label for="author">_{Author}</label><br />
        <?=$form_options_helper->select('book', 'author_id', $Author->collect($Author->find(), 'name', 'id'));?>
    </p>

Si vous n'avez pas encore ajouté d'auteurs dans votre base de données (vilain garnement), c'est le moment de le faire.

Vous pouvez donc désormais choisir l'auteur de chaque livre. C'est magnifique ! Mais vous avez sûrement remarqué que vous ne voyez pas l'auteur des livres dans la liste des livres.
Ouvrez donc le fichier `app/views/book/show.tpl`, et juste après `<? $content_columns = array_keys($Book->getContentColumns()); ?>`, rajoutez :

    <label>_{Author}:</label> <span class="static">{book.author.name?}</span><br />

Vous vous demandez sûrement ce que ces `_{Author}` ou autre `{book.author.name?}`. C'est en fait la syntaxe utilisée par [Sintags](http://www.bermi.org/projects/sintags) dans les templates d'Akelos.


Petite conclusion
--------------------

C'est tout pour le moment. Ce tutoriel continuera bien sûr d'évoluer, et il y en aura d'autres, car ce ne sont pas là les seules fonctionnalités d'Akelos !
Si vous voyez une faute de frappe ou de français, n'hésitez pas à me le faire savoir !