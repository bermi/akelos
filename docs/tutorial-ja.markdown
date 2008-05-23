Akelos フレームワークを使用して簡単なアプリケーションを作成する
=========================================================

導入
--------------------------

このチュートリアルでは、Akelosフレームワークを使用したアプリケーションを作成方法を説明します。

アプリケーションは書籍や著者を管理し、**booklink**と名づけます。

このチュートリアルに必要なもの
---------------------------

 - MySQL または SQLite データベース
 - Apache ウェブサーバ
 - 実行するサーバにシェルで接続できること
 - PHP4 または PHP5

この設定は多くのLinuxやホスティング事業者で見うけられます。Akelosは、このチュートリアルのおいてはこのように指定された設定上に限定しますが、どのような設定でも動作します。

ダウンロードとインストール
---------------------------
バージョン1.0に到達するまでの間は、Akelosのtrunkバージョンを取得することを強く推奨します。[subversion](http://subversion.tigris.org/)がインストールされていなければなりません。
Akelosのソースコードのコピーをチェックアウトするには、次のコマンドを使用します：

    svn co http://svn.akelos.org/trunk/ akelos

subversionからコードをチェックアウトできない、またはしたくない場合は、[最新の安定版](http://www.akelos.org/akelos_framework-dev_preview.tar.gz)を取得できます。これは継続的な統合システムによって自動的に生成され、次のように実行することでそれを解凍します：

    tar zxvf akelos_framework-dev_preview.tar.gz;mv akelos_framework-dev_preview akelos

さて、akelosが使用しているPHPのバージョンを見つけることができるかを確かめる必要があります。

    /usr/bin/env php -v

もし次のように表示される場合は、

    PHP 5.1.2 (cli) (built: Jan 17 2006 15:00:28)
    Copyright (c) 1997-2006 The PHP Group
    Zend Engine v2.1.0, Copyright (c) 1998-2006 Zend Technologies
    
正しい状態ですので、続いてAkelosアプリケーションを作成することができます。もしそうでない場合は、PHPバイナリへのパスを見つける必要があります。通常は、次のようにします。

    which php

さらに、次のファイル（`script/console`, `script/generate`, `script/migrate`, `script/setup`, `script/test`）の先頭にある `#!/usr/bin/env php` をPHPのバイナリのパスに変更します。

**Windowsユーザへの注意 :** 次のように php.exe ファイルへのフルパスを使用してアプリケーションディレクトリからスクリプトをコールする必要があります：

    C:\Program Files\xampp\php\php.exe ./script/generate scaffold

新規Akelosアプリケーーションのセットアップ
---------------------------------------------

Akelos をダウンロードしたら、コンソールから PH Pスクリプトを実行できるでしょう。（Akelos を実行する必要はありませんが、このチュートリアルでは必要です。）

次のように２つの方法があります：

 1. 異なるフォルダに Akelos アプリケーションを作成し、フレームワークライブラリへアプリケーションをリンクする
 2. セキュリティの観点からこのフォルダからアプリケーションをコーディングし始め、サイトの訪問者に対してアプリケーションのモデル、ビュー、サードパーティライブラリ等を有効にする。


すでに推測されていると思いますが、最初のオプションを使用してリンクされたAkelosアプリケーションを作成します。これは世界への公開フォルダを提供するだけです。フレームワークのパスを変更することは、Akelosでは本当に簡単です。しなければならないことは、各コンポーネントが配置される場所を設定ファイルに定義するだけです。しかし、将来のチュートリアルでは設定ファイルを配布するアプリケーションをデザインすることでこれをやめる予定です。

`HOME_DIR/akelos` にフレームワークをダウンロードし、カレントが`akelos`ディレクトリであると仮定します。次のコマンドを実行して新しいアプリケーションで設定するための有効なオプションをチェックします。

   ./script/setup -h

インストーラで有効なオプションが表示されます。

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

次にこのコマンドを実行します：（`/wwwh/htdocs`はあなたのウェブサーバ公開パスに置き換えてください。共有サーバでは`/home/USERNAME/public_html`が使用されます）

    ./script/setup -d HOMEDIR/booklink -p /www/htdocs/booklink

This will create the following structure for the **booklink** application:
これは次のような構造をした**booklist**アプリケーションを作成します：

    booklink/
        app/ << コントローラ、ビュー、モデル、インストーラを含むアプリケーション
        config/ << 退屈な設定ファイル (ウェブ経由で設定します)
        public/ << これは単なるフォルダで/www/htdocs/bboklist下でソフトリンクとして公開されます
        script/ << コード生成やテスト実行のためのユーティリティ

**Windowsユーザは注意:**　booklink/publicへのソフトリンクは*NIXシステムについてのみ生成されます。そのため、ウェブサーバに`httpd.conf`ファイル上で次のように追加することで**booklink**アプリケーション用のpublicパスを伝える必要があります。

    Alias /booklink "/path/to_your/booklink/public"

    <Directory "/path/to_your/booklink/public">
    	Options Indexes FollowSymLinks
    	AllowOverride All
    	Order allow,deny
        Allow from all
    </Directory>

そしてウェブサーバを再起動します。

### アプリケーション用のデータベースを作成する ###

次に必要なことは、アプリケーション用のデータベースを作成することです。PHP5でSQLiteを使用しようとしている場合はこの章を飛ばしてください。

MySQLデータベースを作成することは、このチュートリアルの範囲外ですので、ご自身のシステム上での作成方法をググルか、またはこの一般的なシナリオを試してください。それぞれの異なる環境について３つの異なるデータベース（production, development, testing）を作成できます。

    mysql -u root -p
    
    mysql> CREATE DATABASE booklink;
    mysql> CREATE DATABASE booklink_dev;
    mysql> CREATE DATABASE booklink_tests;
    
    mysql> GRANT ALL ON booklink.* TO bermi@localhost IDENTIFIED BY "pass";
    mysql> GRANT ALL ON booklink_dev.* TO bermi@localhost IDENTIFIED BY "pass";
    mysql> GRANT ALL ON booklink_tests.* TO bermi@localhost IDENTIFIED BY "pass";
    
    mysql> FLUSH PRIVILEGES;
    mysql> exit

もし共有サーバである場合は、ホスグィング会社の操作パネルから作成する必要があるかもしれません。

### 設定ファイルを生成する ###

#### ウェブインストーラを使用する ####

http://localhost/booklink であなたのアプリケーション設定ウィザードを見ることができます。    

ウィザードの次のステップはデータベース、ロケール、ファイルパーミッションを設定し、設定ファイルを生成します。そうしている間コーヒーでも飲みましょう。そうすると**booklink**アプリケーションを作成できます。

#### 手動で設定ファイルを編集する ####

`config/DEFAULT-config.php` や `config/DEFAULT-routes.php` というファイルは、`config/config.php` や `config/routes.php` として保存し、必要に応じて次のように編集します。

`public/.htaccess`ファイルを編集して、次のようなRewriteBaseを設定することで賢いURLを使用したい場合は、手動でbase rewrite パスを設定する必要があるかもしれません。

    RewriteBase /booklink

アプリケーションを正常にインストールした後で、http://localhost/booklink でウェルカムメッセージが見えるでしょう。そうしたらフレームワークセットアップファイル（`/config/config.php` ファイルが存在する場合はアクセスできないでしょう）を安全に削除することができます。

booklink データベースの構造
---------------------------------

さて、テーブルとカラムを定義する必要があります。そこにアプリケーションが本と著者についての情報を保持します。

他の開発者と作業する際に、データベースが変更されるためそれぞれに異なったものが配布されます。Akelosはこの問題に対する解決法があります。それは*インストーラ*または*マイグレーション*と名づけられています。

それではインストーラを使用してデータベースを作成してみましょう。その時々にbooklinkデータベーススキーマに対して行った変更を配信することができます。
*インストーラ*を使用することで、データベースのテーブルやカラムをデータベースベンダから独立して定義することができます。

では、次のインストーラコードを使用して`app/installers/booklink_installer.php` という名前のファイルを作成します。
 
     <?php
     
     class BooklinkInstaller extends AkInstaller
     {
         function up_1(){
             
             $this->createTable('books',
                'id,'.          // the key
                'title,'.       // the title of the book
                'description,'. // a description of the book
                'author_id,'.   // the author id. This is how Akelos will know how to link
                'published_on'  // the publication date
            );
            
             $this->createTable('authors', 
                'id,'.      // the key
                'name'      // the name of the author
                );
         }
         
         function down_1(){
             $this->dropTables('books','authors');
         }
     }
     
     ?>

これだけでAkelosにとってはデータベーススキーマを作成するには十分です。カラム名を指定する場合は、Akelosはデータベース標準規約に基づいて最もベストなデータ型をデフォルトとします。テーブル設定において完全な制御をしなければならない場合は、[php Adodb データディクショナリ構文](http://phplens.com/lens/adodb/docs-datadict.htm)を使用することができます。

次にコマンドを使用してインストーラを実行する必要があります。

    ./script/migrate Booklink install

それからトリックを使用します。MySQLを使用する場合は、データベースは次のようになるでしょう：

**BOOKS テーブル**

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

**AUTHORS テーブル**
                       
    +--------------+--------------+------+-----+----------------+
    | Field        | Type         | Null | Key | Extra          |
    +--------------+--------------+------+-----+----------------+
    | id           | int(11)      | NO   | PRI | auto_increment |
    | name         | varchar(255) | YES  |     |                |
    | updated_at   | datetime     | YES  |     |                |
    | created_at   | datetime     | YES  |     |                |
    +--------------+--------------+------+-----+----------------+


モデル,ビュー,コントローラ
------------------------------------------------------

Akelosはアプリケーションの形成において[MVC デザインパターン](http://en.wikipedia.org/wiki/Model-view-controller)に基づいています。

![Akelos MVC 図](http://svn.akelos.org/trunk/docs/images/akelos_mvc.png)

### アプリケーションのファイルとAkelos命名規約 ###

Akelosに「設定よりも規約」という哲学を付与する規約があります。

#### モデル ####

 * **パス:** /app/models/
 * **クラス名:** 単数形, キャメルケース *(BankAccount, Person, Book)*
 * **ファイル名:** 単数形, アンダースコア *(bank_account.php, person.php, book.php)*
 * **テーブル名:** 複数形, アンダースコア *(bank_accounts, people, books)*

#### コントローラ ####

 * **パス:** */app/controllers/*
 * **クラス名:** 単数形 または 複数形, キャメルケース, `Controller`で終わる *(AccountController, PersonController)*
 * **ファイル名:** 単数形 または 複数, アンダースコア, `_controller`で終わる *(`account_controller.php`, `person_controller.php`)*

#### ビュー ####

 * **パス:** /app/views/ + *underscored_controller_name/* *(app/views/person/)*
 * **ファイル名:** アクション名, 小文字 *(app/views/person/show.tpl)*


Akelos スキャフォールド
------------------------------------------

Akelosはコードジェネレータを付属しており、完全に機能的なスキャフォールドコードを生成することによって開発時間を短縮することができます。出発点/学習ポイントとして使用することができます。

### スキャフォールドジェネレータを使用する ###

**booklink**データベースを作成する前に対話的に基本となる骨組みを生成します。この骨組みをすばやく取得するために、次のような*スキャフォールドジェネレータ*を使用することができます。

    ./script/generate scaffold Book

と 

    ./script/generate scaffold Author

これは実際に動作するコードを含んだファイルやフォルダを生成します。信じられませんか？自分でやってみてください。ブラウザで[http://localhost/booklink/author](http://localhost/booklink/author) や [http://localhost/booklink/book](http://localhost/booklink/book)を開いて、著者や書籍を追加できます。レコードをいくつか作成し、フードの下に何があるかを説明している部分に戻ってください。

Akelos ワークフロー
------------------------------------------

これは、`http://localhost/booklink/book/show/2`というURLをコールしたときのワークフローの簡単な説明です。

 1. Akelos はリクエストを３つのパラメータに分解します。これは`/config/routes.php`ファイル（この後に詳しく説明します）の内容に従います。
  * controller: book
  * action: show
  * id: 2

 2. 一度Akelosがこのリクエストを処理すると、`/app/controllers/book_controller.php`ファイルを検索します。もし見つかれば、`BookController`クラスをインスタンス化します。

 3. コントローラはリクエストから`controller`変数にマッチするモデルを検索します。この場合、`/app/models/book.php`を検索します。見つかれば、コントローラの`$this->Book`属性にモデルのインスタンスを生成します。`id`がリクエストに存在すれば、データベースからidが2の書籍を検索し、`$this->Book`のままです。

 4. 有効であれば、`BookController`クラスから`show`アクションをコールします。

 5. 一度showアクションが実行されると、コントローラは`/app/views/book/show.tpl`ビューファイルを検索します。結果を描画し`$content_for_layout`変数に格納します。

 6. Akelosは`/app/views/layouts/book.tpl`のようなコントローラと同じ名前のレイアウトを検索します。もし見つかれば、`$content_for_layout`に内容を挿入してレイアウトを描画し、ブラウザに出力を送信します。

これはAkelosがリクエストを処理する方法を理解するのに役立ちます。そのため、ベースアプリケーションを変更します。

Books と Authors の関連
----------------------------

それではauthorsテーブルとbooksテーブルを関連付けてみましょう。これを保管するために`author_id`カラムを使用しますので、データベースに追加します。

テーブルがどのようにお互いに関連しているかをモデルに教える必要があります。

*/app/models/book.php*

    <?php
    
    class Book extends ActiveRecord
    {
        var $belongs_to = 'author'; // <- declaring the association
    }
    
    ?>

*/app/models/author.php*

    <?php
    
    class Author extends ActiveRecord
    {
        var $has_many = 'books'; // <- declaring the association
    }
    
    ?>

モデルはお互いに注意してください。bookコントローラを修正する必要があります。そうすると`author`と`book`モデルインスタンスが導入されます。

*/app/controllers/book_controller.php*

    <?php
    
    class BookController extends ApplicationController
    {
        var $models = 'book, author'; // <- make these models available
        
        // ... more BookController code
        
        function show()
        {
            // Replace "$this->book = $this->Book->find(@$this->params['id']);"
            // with this in order to find related authors.
            $this->book = $this->Book->find(@$this->params['id'], array('include' => 'author'));
        }
        
        // ... more BookController code
    }

次のステップは、本を作成または編集したときに有効な著者テーブルを表示することです。これは`$form_options_helper`を使用して*/app/views/book/_form.tpl* ファイルの `<?=$active_record_helper->error_messages_for('book');?>` の後ろの右に次のようなコードを挿入することでできます。

    <p>
        <label for="author">_{Author}</label><br />
        <?=$form_options_helper->select('book', 'author_id', $Author->collect($Author->find(), 'name', 'id'));?>
    </p>

まだ著者テーブルを追加指定ない場合は、すぐに作成してhttp://locahost/booklink/book/add を開き、選択リストから新しい著者をチェックしてください。リストから著者を選択して新しい本を追加します。

著者が保存されたように思えますが、`app/views/book/show.tpl`ビューには含まれていません。`<? $content_columns = array_keys($Book->getContentColumns()); ?>`の後の右にこのコードを追加してください。

    <label>_{Author}:</label> <span class="static">{book.author.name?}</span><br />

めったにない`_{Author}`や`{book.author.name?}`構文についてカナキリ声をあげたにちがいありません。それは実際に[Sintags](http://www.bermi.org/projects/sintags)のルールであり、キレイにビューを記述するのに役立ちます。また、標準のPHPにコンパイルされます。


Colophon
--------------------

これがすべてです。徐々にこのチュートリアルを改良していて、足りない特徴を追加します。他の文書は次のようなものです：

 * validations
 * routes
 * filters
 * callbacks
 * transactions
 * console
 * AJAX
 * helpers
 * web services
 * testing
 * distributing
 * and many more...

------------

Translated by: bobchin