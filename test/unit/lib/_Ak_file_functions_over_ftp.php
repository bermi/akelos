<?php

define('AK_UPLOAD_FILES_USING_FTP', true);
define('AK_READ_FILES_USING_FTP', true);
define('AK_DELETE_FILES_USING_FTP', true);
define('AK_FTP_PATH', 'ftp://tests:tests@tests.akelos.com');
define('AK_FTP_AUTO_DISCONNECT', true);

require_once(dirname(__FILE__).'/../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'Ak.php');

class test_AkFileFunctionsUsingFtp extends  UnitTestCase
{
    function Test_file_put_contents()
    {
        $file_name = AK_CACHE_DIR.DS.'test_file_1.txt';
        $content = 'This is the content of file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));
        
        $file_name = '/cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));
        
        $file_name = AK_CACHE_DIR.DS.'test_file_2.txt';
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

    function Test_file_get_contents()
    {
        $file_name = AK_CACHE_DIR.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);
        
        $file_name = AK_CACHE_DIR.DS.'test_file_2.txt';
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
    
    function Test_file_delete()
    {
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_1.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_2.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_3.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.'\test_file_4.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/new_folder/test_file.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/folder with space/test file.txt'));

    }
    
    function Test_directory_delete()
    {
        $this->assertTrue(Ak::directory_delete('ak_test_folder'));
        $this->assertFalse(Ak::directory_delete('../../'));
        $this->assertFalse(Ak::directory_delete('..\..\\'));
        $this->assertFalse(Ak::directory_delete(' '));
        $this->assertFalse(Ak::directory_delete('/'));
        $this->assertFalse(Ak::directory_delete('./'));
    }
    
}

class Test_of_Ak_static_file_functions_using_ftp extends  UnitTestCase
{
    function Test_connect()
    {
        $connection = AkFtp::connect();
        $this->assertTrue($connection);
        $this->assertTrue(is_resource($connection) && get_resource_type($connection) == 'FTP Buffer');
        $this->assertIdentical($connection, AkFtp::connect());
    }

    function Test_disconnect()
    {
        $this->assertTrue(AkFtp::disconnect());
    }
    

    function Test_file_put_contents()
    {
        $file_name = AK_CACHE_DIR.DS.'test_file_1.txt';
        $content = 'This is the content of file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));
        
        $file_name = '/cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_put_contents($file_name, $content));
        
        $file_name = AK_CACHE_DIR.DS.'test_file_2.txt';
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

    function Test_file_get_contents()
    {
        $file_name = AK_CACHE_DIR.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);
        
        $file_name = AK_CACHE_DIR.DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);
        
        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);
        
        $file_name = 'cache\test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertTrue(Ak::file_get_contents($file_name) === $content);
        
    }
    
    function Test_file_delete()
    {
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_1.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_2.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.DS.'test_file_3.txt'));
        $this->assertTrue(Ak::file_delete(AK_CACHE_DIR.'\test_file_4.txt'));
        $this->assertTrue(Ak::file_delete('ak_test_folder/new_folder/test_file.txt'));

    }
    
    function Test_directory_delete()
    {
        $this->assertTrue(Ak::directory_delete('ak_test_folder'));
        $this->assertFalse(Ak::directory_delete('../../'));
        $this->assertFalse(Ak::directory_delete('..\..\\'));
        $this->assertFalse(Ak::directory_delete(' '));
        $this->assertFalse(Ak::directory_delete('/'));
        $this->assertFalse(Ak::directory_delete('./'));
    }

    
    function Test_make_dir()
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
    
    function Test_delete()
    {
        $this->assertTrue(AkFtp::delete('new_dir_8/*'));
        
        $this->assertTrue(Ak::file_put_contents('prueba.txt', ''));
        $this->assertTrue(AkFtp::delete('prueba.txt'));
        
        $this->assertTrue(Ak::file_put_contents('new_dir_1/prueba.txt', ''));
        $this->assertTrue(AkFtp::delete('new_dir_1/prueba.txt'));

        $this->assertTrue(AkFtp::delete('new_dir_2/subdir_1'));

        $this->assertError(AkFtp::delete('new_dir_2/subdir'));
        $this->assertError(AkFtp::delete('new_dir_2/subdir_1'));
        
        $this->assertTrue(AkFtp::delete('new_dir_1'));
        $this->assertTrue(AkFtp::delete('new_dir_2'));
        $this->assertTrue(AkFtp::delete('../new_dir_3'));
        $this->assertTrue(AkFtp::delete('./new_dir_4'));
        $this->assertTrue(AkFtp::delete('./new_dir_5/'));
        $this->assertTrue(AkFtp::delete('new_dir_6/'));
        $this->assertTrue(AkFtp::delete('/new_dir_7'));
        $this->assertTrue(AkFtp::delete('/new_dir_8/'));
    }
    
    function Test_is_dir()
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
    }
}


Ak::test('test_AkFileFunctionsUsingFtp');


?>