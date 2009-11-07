<?php

$dictionary = array();

$dictionary['Welcome aboard'] = 'ようこそ';
$dictionary['You&rsquo;re using The Akelos Framework!'] = '今まさに Akelos フレームワークを使用しています!';
$dictionary['Getting started'] = 'まず初めに';
$dictionary['Configure your environment'] = '環境を設定します';
$dictionary['Use <tt>script/generate</tt> to create your models and controllers'] = 'モデルとコントローラを作成するには <tt>script/generate</tt> を使用してください';
$dictionary['To see all available options, run it without parameters.'] = '利用可能なすべてのオプションをみるには、パラメータを指定せずに実行してください。';
$dictionary['Start the configuration wizard'] = '設定ウィザードを開始します';
$dictionary['Akelos Framework'] = 'Akelos フレームワーク';

$dictionary['Database Configuration.'] = 'データベース設定';
$dictionary['Please select a database type'] = 'データベースを選択してください';
$dictionary['The list below only includes databases we found you had support for under your current PHP settings'] = '以下のリストはあなたの現在の PHP 設定でサポートされているデータベースのみを表示しています。';

$dictionary['The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.'] = 'Akelos フレームワークには３つの異なる実行環境があり、それぞれ別のデータベースになっています。
                開発モードでアプリケーションを開発し、
                テストモードでテストし、製品モードでリリースすることを推奨しています。';
$dictionary['
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				'] = '
                <p>次のようなデータベースを作成することを強く推奨します:</p>
                <ul>
                    <li><em>データベース名</em><b>_dev</b> ： 開発モード用 (デフォルトのモード)</li>
                    <li><em>データベース名</em> ： 製品モード用</li>
                    <li><em>データベース名</em><b>_tests</b> ： テスト用</li>
                </ul>
				';
$dictionary['Please set your database details'] = 'データベースの詳細を設定してください';
$dictionary['Development'] = '開発';
$dictionary['Database name'] = 'データベース名';
$dictionary['Production'] = '製品';
$dictionary['Testing'] = 'テスト';
$dictionary['Continue'] = '次へ';


$dictionary['File handling settings.'] = 'ファイル処理設定';
$dictionary['The Akelos Framework makes an extensive use of the file system for handling locales, cache, compiled templates...'] = 'Akelos フレームワークはロケール、キャッシュ、テンプレートのコンパイル等のように広範囲にわたってファイルシステムを利用します。';
$dictionary['The installer could not create a test file at <b>config/test_file.txt</b>, so you should check if the user that is running the web server has enough privileges to write files inside the installation directory.'] = 'インストーラはテストファイル <b>config/test_file.txt</b> を作成できませんでした。そのためウェブサーバを実行しているユーザがインストールディレクトリ内にファイルを書き込む権限があるかどうかをチェックすべきです。';
$dictionary['If you have made changes to the filesystem or web server, <a href="%ftp_url">click here to continue</a> or 
<a href="%url_skip">here to skip the filesystem setting</a></p>'] = 'もしファイルシステムやウェブサーバを変更できない場合、 <a href="%ftp_url">こちらをクリックして続けるか</a> または 
<a href="%url_skip">こちらをクリックしてファイルシステム設定をスキップします</a></p>';
$dictionary['You don\'t have enabled FTP support into your PHP settings. When enabled 
            you can perform file handling functions using specified FTP account. 
            In order to use FTP functions with your PHP configuration, you should add the 
            --enable-ftp option when installing PHP.'] = 'PHP 設定で FTP がサポートされていません。有効であれば、 
            特定の FTP アカウントを使用してファイル処理関数を実行できます。
            PHP 設定で FTP 関数を使用するには、PHP をインストールする際に 
            --enable-ftp オプションを追加する必要があります。';


$dictionary['Bad file permission. Please change file system privileges or set up a FTP account below'] = 'ファイルパーミッションが不正です。ファイルシステムの権限を変更するか、以下の FTP アカウントを設定してください。';

$dictionary['Language settings.'] = '言語設定';
$dictionary['Please set your language details'] = '使用する言語の詳細を設定してください';
$dictionary['2 letter ISO 639 language codes (separated by commas)'] = '２文字の ISO 639 言語コードで指定してください (カンマ区切りで指定します)';

$dictionary['Database Host'] = 'データベースホスト';
$dictionary['User'] = 'ユーザ';
$dictionary['Password'] = 'パスワード';
$dictionary['(optional) Try to create databases using the following privileged account:'] = '(オプション) 次のような権限のあるアカウントを使用してデータベースを作成してみてください:';
$dictionary['DB admin user name'] = 'DB 管理者ユーザ名';
$dictionary['DB admin password'] = 'DB 管理者パスワード';
$dictionary['Could not connect to %database database'] = '%database データベースに接続できません';


$dictionary['If you can\'t change the web server or file system permissions the Akelos Framework has an alternate way to access the file system by using an FTP account that points to your application path.'] = 'もしウェブサーバやファイルシステムのパーミッションを変更できない場合は、Akelos フレームワークは FTP アカウントを使用してアプリケーションのパスに割り当てられているファイルシステムにアクセスするという選択肢があります。';
$dictionary['This is possible because the Framework uses a special version of file_get_contents and file_put_contents functions that are located under the class Ak, which acts as a namespace for some PHP functions. If you are concerned about distributing applications done using the Akelos Framework, you should use Ak::file_get_contents() and Ak::file_put_contents() and this functions will automatically select the best way to handle files. Additional methods like LDAP might be added in a future.'] = 'これはフレームワークが file_get_contents や file_put_contents 関数の特別なバージョンを使用していて、この関数は Ak クラスにあります。このクラスは いくつかの PHP 関数の名前空間として振る舞います。もし Akelos フレームワークを使用したアプリケーションの配布について関心がある場合は、Ak::file_get_contents() や Ak::file_put_contents() を使用すべきです。この関数はファイルを処理する最良の方法を自動的に選択してくれます。LDAP のような付随的なメソッドは将来的に追加されるかもしれません。';
$dictionary['Please set your ftp connection details'] = 'FTP 接続の詳細を設定してください';
$dictionary['FTP Host'] = 'FTP ホスト';
$dictionary['Application path from FTP initial path'] = 'FTP 初期パスからのアプリケーションのパス';
$dictionary['Could not connect to selected ftp server'] = '選択した FTP サーバに接続できませんでした';
$dictionary['Could not change to the FTP base directory %directory'] = 
'FTP ベースディレクトリ %directory に移動できません';

$dictionary['<a href="%url">Run a step by step wizard for creating a configuration file</a> or read README.txt instead.'] = '<a href="%url">設定ファイルを作成するために設定ウィザードを実行します。</a> または README.txt を読んでください。';
$dictionary['The framework_setup.php found that you already have a configuration file at config/config.php. You need to remove that file first in order to run the setup.'] = 'framework_setup.php は設定ファイル config/config.php をみつけました。セットアップを実行するためにはまずこのファイルを削除することが必要です。';

$dictionary['Save Configuration.'] = '保存設定';
$dictionary['Final Steps.'] = '最後の設定';
$dictionary['You are about to complete the installation process. Please follow the steps bellow.'] = 'インストール作業を完了しました。続けて以下のようにしてください。';
$dictionary['Copy the following configuration file contents to <b>config/config.php</b>.'] = '次の設定ファイルを <b>config/config.php</b> にコピーします。';
$dictionary['Copy the file <b>config/DEFAULT-routes.php</b> to <b>config/routes.php</b>'] = 'ファイル <b>config/DEFAULT-routes.php</b> を <b>config/routes.php</b> にコピーします。';
$dictionary['Your application is not on the host main path, so you might need to edit 
    your .htaccess files in order to enable nice URL\'s. Edit <b>/.htaccess</b> and 
    <b>/public/.htaccess</b> and replace the line <br />'] = 'アプリケーションはホストのメインパスにありません。そのため 
    キレイな URL を有効にするために .htaccess ファイルを編集する必要があります。<b>/.htaccess</b> と 
    <b>/public/.htaccess</b> を編集し、次の行を置換してください。 <br />';
$dictionary['with'] = 'を次のようにします';
$dictionary['Now you can start generating models and controllers by running <b>./script/generate model</b>, <b>./script/generate controller</b> and , <b>./script/generate scaffold</b>. Run them without parameters to get the instructions.'] = 'これで <b>./script/generate model</b>, <b>./script/generate controller</b>, <b>./script/generate scaffold</b> を実行してモデルやコントローラを生成することができます。使用方法については引数を指定せずに実行してください。';

?>