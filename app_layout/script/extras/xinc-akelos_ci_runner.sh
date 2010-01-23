#!/usr/bin/env sh

akelos_base_path="/media/shared/bermilabs/akelos_cache";
php_bin=/usr/bin/php;
install_path="/media/shared/bermilabs/akelos_frameworktests"
rm -Rf $akelos_base_path/tests

clear;

$akelos_base_path/akelos -d $install_path/tests -deps --force;
chmod 777 $install_path/tests -Rf
chgrp www-data $install_path/tests -Rf
rm -Rf $install_path/tests/log
mkdir $install_path/tests/log
chgrp -Rf www-data $install_path/tests/
chmod -Rf g+rwt,o-rwx $install_path/tests/

touch $install_path/tests/log/testing.log
chgrp -Rf www-data $install_path/tests/log
chown -Rf arno $install_path/tests/log
chmod -Rf g+rwts,o-rwxs $install_path/tests/log/


cp $akelos_base_path/script/extras/xinc-ci_tests.php         $install_path/tests/script/extras/xinc-ci_tests.php;
cp $akelos_base_path/script/extras/ci-config.yaml         $install_path/tests/config/ci-config.yaml;
cp $akelos_base_path/script/extras/caching.yml         $install_path/tests/config/caching.yml;
cp $akelos_base_path/script/extras/sessions.yml         $install_path/tests/config/sessions.yml;
cp $akelos_base_path/script/extras/fix_htaccess.php       $install_path/tests/config/fix_htaccess.php;
cp $akelos_base_path/script/extras/mysql-testing.php      $install_path/tests/config/mysql-testing.php;
cp $akelos_base_path/script/extras/postgres-testing.php   $install_path/tests/config/postgres-testing.php;
cp $akelos_base_path/script/extras/routes.php             $install_path/tests/config/routes.php;
cp $akelos_base_path/script/extras/sqlite-testing.php     $install_path/tests/config/sqlite-testing.php;


cd $install_path/tests/test;
$php_bin  ../script/extras/xinc-ci_tests.php;

