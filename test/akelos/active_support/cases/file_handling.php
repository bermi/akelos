<?php

require_once(dirname(__FILE__).'/../config.php');

class FileHandling_TestCase extends ActiveSupportUnitTest
{
    public function Test_file_put_contents() {
        $file_name = AkConfig::getDir('tmp').DS.'test_file_1.txt';
        $content = 'This is the content of file 1';
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        $file_name = '/cache'.DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        AkFileSystem::file_delete($file_name);

        $file_name = AkConfig::getDir('tmp').DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        $file_name = 'cache/test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));

        $file_name = 'ak_test_folder/new_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertFalse(!AkFileSystem::file_put_contents($file_name, $content));
    }

    public function Test_file_get_contents() {
        $file_name = AkConfig::getDir('tmp').DS.'test_file_1.txt';
        $content = 'This is the NEW content for file 1';
        $this->assertFalse(!AkFileSystem::file_get_contents($file_name) === $content);

        $file_name = AkConfig::getDir('tmp').DS.'test_file_2.txt';
        $content = "\n\rThis is the content of file 2\n";
        $this->assertFalse(!AkFileSystem::file_get_contents($file_name) === $content);

        $file_name = 'cache'.DS.'test_file_3.txt';
        $content = "\rThis is the content of file 3\r\n";
        $this->assertFalse(!AkFileSystem::file_get_contents($file_name) === $content);

        $file_name = 'cache/test_file_4.txt';
        $content = "\rThis is the content of file 4\r\n";
        $this->assertFalse(!AkFileSystem::file_get_contents($file_name) === $content);

    }

    public function Test_copy_files() {
        $original_path = AkConfig::getDir('tmp').DS.'test_file_1.txt';
        $copy_path = $original_path.'.copy';
        $this->assertTrue(AkFileSystem::copy($original_path, $copy_path));
        $this->assertEqual(AkFileSystem::file_get_contents($original_path), AkFileSystem::file_get_contents($copy_path));
        $this->assertTrue(AkFileSystem::file_delete($copy_path, array('debug'=>true)));
    }

    public function Test_copy_directories() {
        $original_path = 'ak_test_folder';
        $copy_path = $original_path.'_copy';
        $this->assertTrue(AkFileSystem::copy($original_path,$copy_path));

        $file_name = $copy_path.'/new_folder/test_file.txt';
        $content = "\rThis is the content of the test file";
        $this->assertTrue(AkFileSystem::file_get_contents($file_name) === $content);
    }

    public function Test_file_delete() {
        $this->assertFalse(!AkFileSystem::file_delete(AkConfig::getDir('tmp').DS.'test_file_1.txt'));
        $this->assertFalse(!AkFileSystem::file_delete(AkConfig::getDir('tmp').DS.'test_file_2.txt'));
        $this->assertFalse(!AkFileSystem::file_delete('cache/test_file_3.txt'));
        $this->assertFalse(!AkFileSystem::file_delete('cache/test_file_4.txt'));
        $this->assertFalse(!AkFileSystem::file_delete('ak_test_folder/new_folder/test_file.txt'));
    }

    public function Test_directory_delete() {
        $this->assertFalse(!AkFileSystem::directory_delete('ak_test_folder'));
        $this->assertFalse(!AkFileSystem::directory_delete('ak_test_folder_copy'));
        $this->assertFalse(AkFileSystem::directory_delete('../../'));
        $this->assertFalse(AkFileSystem::directory_delete('..\..\\'));
        $this->assertFalse(AkFileSystem::directory_delete(' '));
        $this->assertFalse(AkFileSystem::directory_delete('/'));
        $this->assertFalse(AkFileSystem::directory_delete('./'));

        clearstatcache();
        $this->assertFalse(is_dir(AK_BASE_DIR.DS.'ak_test_folder'), 'Did not remove empty dir ./ak_test_folder');
        $this->assertFalse(is_dir(AK_BASE_DIR.DS.'ak_test_folder_copy'), 'Did not remove nested dir ./ak_test_folder_copy');
    }

    public function test_mime_type_detection() {
        // png is not in any RFC so we might want to check if it has a /x- preffix for non standard values
        $this->assertTrue(in_array(Ak::mime_content_type(AK_PUBLIC_DIR.DS.'images'.DS.'akelos_framework_logo.png'),array('image/png','image/x-png')));
        $this->assertEqual(Ak::mime_content_type('C:\Folder\image.png'),'image/png');
    }

    public function test_should_read_files_using_scoped_file_get_contents_function() {
        $this->assertEqual(AkFileSystem::file_get_contents(__FILE__, array('base_path' => dirname(__FILE__))), file_get_contents(__FILE__));
    }

    public function test_dir_should_not_recurse_when_set_to_false() {
        $files_and_dirs = AkFileSystem::dir(AK_FRAMEWORK_DIR, array('dirs' => true, 'recurse' => false));
        foreach ($files_and_dirs as $k=>$file_or_dir){
            if(is_array($file_or_dir)){
                $this->assertEqual(count($files_and_dirs[$k]), 1);
            }
        }
    }

    public function test_should_delete_nested_directories_when_include_hidden_files() {
        $tmp_dir = AkConfig::getDir('tmp').DS.Ak::randomString();
        $hidden_tmp_dir = $tmp_dir.DS.'.hidden';
        AkFileSystem::make_dir($tmp_dir, array('base_path'=>AkConfig::getDir('tmp')));
        AkFileSystem::make_dir($tmp_dir.DS.'.hidden', array('base_path'=>AkConfig::getDir('tmp')));
        $this->assertTrue(is_dir($hidden_tmp_dir), 'Could not create test directory '.$hidden_tmp_dir);
        $this->assertTrue(AkFileSystem::directory_delete($tmp_dir, array('base_path'=>AkConfig::getDir('tmp'))));
        clearstatcache();
        $this->assertFalse(is_dir($tmp_dir));
    }

    public function test_should_create_base_path_ticket_148() {
        $tmp_dir = AkConfig::getDir('tmp').DS.Ak::randomString();
        $base_path = AkConfig::getDir('tmp').'new_dir_'.time();
        AkFileSystem::make_dir($base_path, array('base_path'=>$base_path));

        $this->assertTrue(is_dir($base_path), 'Could base_path directory '.$base_path);
        clearstatcache();
    }
}

ak_test_case('FileHandling_TestCase');

