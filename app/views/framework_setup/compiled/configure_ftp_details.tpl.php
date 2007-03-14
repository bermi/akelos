        <div id="header">
          <h1><?php echo $text_helper->translate('File handling settings.', array()); ?></h1>
        </div>
        
        <p><?php echo $text_helper->translate('The Akelos Framework makes an extensive use of the file system for handling locales, cache, compiled templates...', array()); ?></p>
        <p><?php echo $text_helper->translate('The installer could not create a test file at <b>config/test_file.txt</b>, so you should check if the user that is running the web server has enough privileges to write files inside the installation directory.', array()); ?></p>
        
        <p><?php echo $text_helper->translate('If you have made changes to the filesystem or web server, <a href="%ftp_url">click here to continue</a> or 
<a href="%url_skip">here to skip the filesystem setting</a></p>',
array(
	'%ftp_url'=>$url_helper->url_for(array('controller'=>'framework_setup','action'=>'configure_ftp_details','check'=>true)),
	'%url_skip'=>$url_helper->url_for(array('controller'=>'framework_setup','action'=>'configure_ftp_details','skip'=>true))
)); ?>



        <?php if($FrameworkSetup->canUseFtpFileHandling()) : ?>
        
        <p><?php echo $text_helper->translate('If you can\'t change the web server or file system permissions the Akelos Framework has an alternate way to access the file system by using an FTP account that points to your application path.', array()); ?></p>
        
        <p><?php echo $text_helper->translate('This is possible because the Framework uses a special version of file_get_contents and file_put_contents functions that are located under the class Ak, which acts as a namespace for some PHP functions. If you are concerned about distributing applications done using the Akelos Framework, you should use Ak::file_get_contents() and Ak::file_put_contents() and this functions will automatically select the best way to handle files. Additional methods like LDAP might be added in a future.', array()); ?></p>
        
        <div id="main-content">
          <h1><?php echo $text_helper->translate('Please set your ftp connection details', array()); ?></h1>
        
          <?php echo  $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'configure_ftp_details')) ?>
   
          <label for='ftp_host'><?php echo $text_helper->translate('FTP Host', array()); ?></label>
                    <input type='text' name='ftp_host' id='ftp_host' value='<?php 
 echo isset($FrameworkSetup->ftp_host) ? $FrameworkSetup->ftp_host : '';
?>' />
                    
                <label for='ftp_path'><?php echo $text_helper->translate('Application path from FTP initial path', array()); ?></label>
                    <input type='text' name='ftp_path' id='ftp_path' value='<?php 
 echo isset($FrameworkSetup->ftp_path) ? $FrameworkSetup->ftp_path : '';
?>' />
                    
                <label for='ftp_user'><?php echo $text_helper->translate('User', array()); ?></label>
                    <input type='text' name='ftp_user' id='ftp_user' value='<?php 
 echo isset($FrameworkSetup->ftp_user) ? $FrameworkSetup->ftp_user : '';
?>' />
                    
                <label for='ftp_password'><?php echo $text_helper->translate('Password', array()); ?></label>
                    <input type='password' name='ftp_password' id='ftp_password' value='<?php 
 echo isset($FrameworkSetup->ftp_password) ? $FrameworkSetup->ftp_password : '';
?>' />
                    
                        
                <br />
                <br />
                
                <input type="submit" value="<?php echo $text_helper->translate('Continue', array()); ?>" />

            </form>
            
        </div>
        
        <?php else : ?>
        
        <div class="important">
            <p><?php echo $text_helper->translate('You don\'t have enabled FTP support into your PHP settings. When enabled 
            you can perform file handling functions using specified FTP account. 
            In order to use FTP functions with your PHP configuration, you should add the 
            --enable-ftp option when installing PHP.', array()); ?>
            </p>
        </div>
        
        <?php endif; ?>
