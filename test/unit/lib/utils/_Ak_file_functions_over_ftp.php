<?php

//
// In order to pass these tests you'll neet to have an ftp server configured at ftptesting.akelos.org

if(defined('AK_UPLOAD_FILES_USING_FTP')){
    die('Fatal error: This test needs to run on its own and not as part of any suite in '.__FILE__);
}

if(!function_exists('ftp_connect')){
    echo "PHP is not compiled with FTP support\n";
    return;
}

define('AK_UPLOAD_FILES_USING_FTP', true);
define('AK_READ_FILES_USING_FTP', true);
define('AK_DELETE_FILES_USING_FTP', true);
define('AK_FTP_PATH', 'ftp://tests:tests@ftptesting.akelos.org');
define('AK_FTP_AUTO_DISCONNECT', true);

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');

class Ak_file_functions_over_ftp_TestCase extends AkUnitTest
{
    public function Test_file_put_contents()
    {
        $file_name = 'cache'.DS.'test_file_1.txt';
        $content = 'This is the content of file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = '/cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache'.DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache\test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/new_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));


        $file_name = 'ak_test_folder/folder with space/test file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

    }

    public function Test_file_get_contents()
    {
        $file_name = 'cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';

        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache'.DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache\test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'ak_test_folder/folder with space/test file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertEqual(Ak::file_get_contents($file_name), $content);

    }

    public function Test_file_delete()
    {
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_1.txt'));
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_2.txt'));
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_3.txt'));
        $this->assertTrue(Ak::file_delete('cache'.'\test_file_4.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/new_folder/test_file.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/folder with space/test file.txt'));

    }

    public function Test_directory_delete()
    {
        $this->assertTrue(Ak::directory_delete('ak_test_folder'));
        $this->assertFalse(Ak::directory_delete('../../'));
        $this->assertFalse(Ak::directory_delete('..\..\\'));
        $this->assertFalse(Ak::directory_delete(' '));
        $this->assertFalse(Ak::directory_delete('/'));
        $this->assertFalse(Ak::directory_delete('./'));
    }

}


class Ak_file_static_functions_over_ftp_TestCase extends AkUnitTest
{
    public function Test_connect()
    {
        $connection = AkFtp::connect();
        $this->assertTrue($connection);
        $this->assertTrue(is_resource($connection) && get_resource_type($connection) == 'FTP Buffer');
        $this->assertIdentical($connection, AkFtp::connect());
    }

    public function Test_disconnect()
    {
        $this->assertTrue(AkFtp::disconnect());
    }


    public function Test_file_put_contents()
    {
        $file_name = 'cache'.DS.'test_file_1.txt';
        $content = 'This is the content of file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = '/cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache'.DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'cache\test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/new_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(Ak::file_put_contents($file_name, $content));
    }

    public function Test_file_get_contents()
    {
        $file_name = 'cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache'.DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

        $file_name = 'cache\test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);

    }

    public function Test_file_delete()
    {
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_1.txt'));
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_2.txt'));
        $this->assertTrue(Ak::file_delete('cache'.DS.'test_file_3.txt'));
        $this->assertTrue(Ak::file_delete('cache'.'\test_file_4.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/new_folder/test_file.txt'));

    }

    public function Test_directory_delete()
    {
        $this->assertTrue(Ak::directory_delete('ak_test_folder'));
        $this->assertFalse(Ak::directory_delete('../../'));
        $this->assertFalse(Ak::directory_delete('..\..\\'));
        $this->assertFalse(Ak::directory_delete(' '));
        $this->assertFalse(Ak::directory_delete('/'));
        $this->assertFalse(Ak::directory_delete('./'));
    }


    public function Test_make_dir()
    {
        $this->assertTrue(AkFtp::make_dir('new_dir_1'));
        $this->assertTrue(AkFtp::make_dir('/new_dir_2'));
        $this->assertTrue(AkFtp::make_dir('new_dir_3/'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_4'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_5'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_6/'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_7/'));

        $this->assertTrue(AkFtp::make_dir('new_dir_1/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('/new_dir_2/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('new_dir_3/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_4/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_5/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_6/subdir_1'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_7/subdir_1'));

        $this->assertTrue(AkFtp::make_dir('new_dir_1/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('/new_dir_2/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('new_dir_3/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_4/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_5/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('./new_dir_6/subdir_2/subsubdir2'));
        $this->assertTrue(AkFtp::make_dir('../new_dir_7/subdir_2/subsubdir2'));

        $this->assertTrue(AkFtp::make_dir('new_dir_8/subdir_1/subsubdir1'));

    }

    public function Test_delete()
    {
        $this->assertTrue(AkFtp::delete('new_dir_8/*'));

        $this->assertTrue(Ak::file_put_contents('prueba.txt', ''));
        $this->assertTrue(AkFtp::delete('prueba.txt'));

        $this->assertTrue(Ak::file_put_contents('new_dir_1/prueba.txt', ''));
        $this->assertTrue(AkFtp::delete('new_dir_1/prueba.txt'));

        $this->assertTrue(AkFtp::delete('new_dir_2/subdir_1'));

        $this->expectError();
        AkFtp::delete('new_dir_2/subdir');

        $this->expectError();
        AkFtp::delete('new_dir_2/subdir_1');

        $this->assertTrue(AkFtp::delete('new_dir_1'));
        $this->assertTrue(AkFtp::delete('new_dir_2'));
        $this->assertTrue(AkFtp::delete('../new_dir_3'));
        $this->assertTrue(AkFtp::delete('./new_dir_4'));
        $this->assertTrue(AkFtp::delete('./new_dir_5/'));
        $this->assertTrue(AkFtp::delete('new_dir_6/'));
        $this->assertTrue(AkFtp::delete('/new_dir_7'));
        $this->assertTrue(AkFtp::delete('/new_dir_8/'));
    }

    public function Test_is_dir()
    {
        $path = 'invalid path';
        $this->assertFalse(AkFtp::is_dir($path));

        $path = 'this_is_a_file.txt';
        Ak::file_put_contents('this_is_a_file.txt', '');

        $this->assertFalse(AkFtp::is_dir($path));

        AkFtp::make_dir('tmp_test_dir');
        Ak::file_put_contents('tmp_test_dir/file_inside.txt', '');

        $path = 'tmp_test_dir/file_inside.txt';
        $this->assertFalse(AkFtp::is_dir($path));


        AkFtp::make_dir('real_dir/another/dir');

        $path = 'real_dir';
        $this->assertTrue(AkFtp::is_dir($path));

        $path = 'real_dir/another/dir';
        $this->assertTrue(AkFtp::is_dir($path));

        AkFtp::delete('real_dir');
        AkFtp::delete('this_is_a_file.txt');
        AkFtp::delete('tmp_test_dir');
        AkFtp::delete('cache');
    }
}

ak_test_run_case_if_executed('Ak_file_functions_over_ftp_TestCase');
ak_test_run_case_if_executed('Ak_file_static_functions_over_ftp_TestCase');

