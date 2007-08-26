        <div id="header">
          <h1>_{Database Configuration.}</h1>
        </div>

                <p>_{The Akelos Framework has 3 different runtime environments, each of these
                has a separated database. Our recommendation is to develop your application in 
                development mode, test it on testing mode and release it on production mode.}
                </p>
                _{
                <p>We strongly recommend you to create the following databases:</p>
                <ul>
                    <li><em>database_name</em><b>_dev</b> for development mode (default mode)</li>
                    <li><em>database_name</em> for production mode</li>
                    <li><em>database_name</em><b>_tests</b> for testing purposes</li>
                </ul>
				}
                
        <div id="main-content">
          <h1>_{Please set your database details}</h1>
        
          <?= $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'set_database_details')) ?>

          <? foreach (array('development','production','testing') as $mode) : ?>
          
              <fieldset>
              <legend><?=$text_helper->translate(ucfirst($mode))?> Database Details</legend>
              
              <? if($FrameworkSetup->database_type != 'sqlite') : ?>
       
              <label for='{mode}_database_host'>_{Database Host}</label>
                        <input type='text' name='{mode}_database_host' id='{mode}_database_host' 
                        value='<?=$FrameworkSetup->getDatabaseHost($mode)?>' />
                        
                    <label for='{mode}_database_name'>_{Database name}</label>
                        <input type='text' name='{mode}_database_name' id='{mode}_database_name' 
                        value='<?=$FrameworkSetup->getDatabaseName($mode)?>' />
                        
                    <label for='{mode}_database_user'>_{User}</label>
                        <input type='text' name='{mode}_database_user' id='{mode}_database_user' 
                        value='<?=$FrameworkSetup->getDatabaseUser($mode)?>' />
                        
                    <label for='{mode}_database_password'>_{Password}</label>
                        <input type='password' name='{mode}_database_password' id='{mode}_database_password' 
                        value='<?=$FrameworkSetup->getDatabasePassword($mode)?>' />
                        
            <? else : ?>
           
              <label for='{mode}_database_name'>_{Database name}</label>
              <b>config/</b><input class="sqlite_database_name" type='text' 
                        name='{mode}_database_name' id='{mode}_database_name' 
                        value='<?=$FrameworkSetup->getDatabaseName($mode)?>' /><b>-<?=$FrameworkSetup->random?>.sqlite</b>
            
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
            <legend>_{(optional) Try to create databases using the following privileged account:}</legend>
                <label for='admin_database_user'>_{DB admin user name}</label>
                    <input type='text' name='admin_database_user' id='admin_database_user' 
                    value='<?=$FrameworkSetup->getDatabaseAdminUser()?>' />
                    
                <label for='admin_database_password'>_{DB admin password}</label>
                    <input type='password' name='admin_database_password' id='admin_database_password' 
                    value='<?=$FrameworkSetup->getDatabaseAdminPassword()?>' />
        </fieldset>
        <br />
        <br />
        
        <? endif; ?>
                
                <input type="submit" value="_{Continue}" />

            </form>
            
        </div>
