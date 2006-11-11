        <div id="header">
          <h1><?php echo $text_helper->translate('Database Configuration.', array()); ?></h1>
        </div>

                <p><?php echo $text_helper->translate('The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.', array()); ?>
                </p>
                <?php echo $text_helper->translate('
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				', array()); ?>
                
        <div id="main-content">
          <h1><?php echo $text_helper->translate('Please set your database details', array()); ?></h1>
        
          <?= $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'set_database_details')) ?>

          <? foreach (array('development','production','testing') as $mode) : ?>
          
              <fieldset>
              <legend><?=$text_helper->translate(ucfirst($mode))?> Database Details</legend>
              
              <? if($FrameworkSetup->database_type != 'sqlite') : ?>
       
              <label for='<?php 
 echo $mode;
?>_database_host'><?php echo $text_helper->translate('Database Host', array()); ?></label>
                        <input type='text' name='<?php 
 echo $mode;
?>_database_host' id='<?php 
 echo $mode;
?>_database_host' 
                        value='<?=$FrameworkSetup->getDatabaseHost($mode)?>' />
                        
                    <label for='<?php 
 echo $mode;
?>_database_name'><?php echo $text_helper->translate('Database name', array()); ?></label>
                        <input type='text' name='<?php 
 echo $mode;
?>_database_name' id='<?php 
 echo $mode;
?>_database_name' 
                        value='<?=$FrameworkSetup->getDatabaseName($mode)?>' />
                        
                    <label for='<?php 
 echo $mode;
?>_database_user'><?php echo $text_helper->translate('User', array()); ?></label>
                        <input type='text' name='<?php 
 echo $mode;
?>_database_user' id='<?php 
 echo $mode;
?>_database_user' 
                        value='<?=$FrameworkSetup->getDatabaseUser($mode)?>' />
                        
                    <label for='<?php 
 echo $mode;
?>_database_password'><?php echo $text_helper->translate('Password', array()); ?></label>
                        <input type='password' name='<?php 
 echo $mode;
?>_database_password' id='<?php 
 echo $mode;
?>_database_password' 
                        value='<?=$FrameworkSetup->getDatabasePassword($mode)?>' />
                        
            <? else : ?>
           
              <label for='<?php 
 echo $mode;
?>_database_name'><?php echo $text_helper->translate('Database name', array()); ?></label>
              <b>config/.ht-</b><input class="sqlite_database_name" type='text' 
                        name='<?php 
 echo $mode;
?>_database_name' id='<?php 
 echo $mode;
?>_database_name' 
                        value='<?=$FrameworkSetup->getDatabaseName($mode)?>' /><b>.sqlite</b>
            
            <? endif; ?>
                
            </fieldset>           
            <br />
            <br />
                
        <? endforeach; ?>
        
        
        <?php
        /**
         * @todo Database creation form. Requires extensive testing before 
         * making it into the setup process
         */
        if(false && $FrameworkSetup->database_type != 'sqlite') : ?>
        
        <fieldset>
            <legend><?php echo $text_helper->translate('(optional) Try to create databases using the following privileged account:', array()); ?></legend>
                <label for='admin_database_user'><?php echo $text_helper->translate('DB admin user name', array()); ?></label>
                    <input type='text' name='admin_database_user' id='admin_database_user' 
                    value='<?=$FrameworkSetup->getDatabaseAdminUser()?>' />
                    
                <label for='admin_database_password'><?php echo $text_helper->translate('DB admin password', array()); ?></label>
                    <input type='password' name='admin_database_password' id='admin_database_password' 
                    value='<?=$FrameworkSetup->getDatabaseAdminPassword()?>' />
        </fieldset>
        <br />
        <br />
        
        <? endif; ?>
                
                <input type="submit" value="<?php echo $text_helper->translate('Continue', array()); ?>" />

            </form>
            
        </div>
