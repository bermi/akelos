        <div id="header">
          <h1>_{Save Configuration.}</h1>
        </div>
        <div id="main-content">
        
          <h1>_{Final Steps.}</h1>
          <h2>_{You are about to complete the installation process. Please follow the steps bellow.}</h2>
          <ol>
            <li>_{Copy the following configuration file contents to <b>config/config.php</b>.}
                <?= $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'perform_setup')) ?>
                <?=$form_tag_helper->text_area_tag('config',$configuration_file,array('size'=>'45x40'))?>
                </form>
            </li>
            <li>_{Copy the file <b>config/DEFAULT-routes.php</b> to <b>config/routes.php</b>}</li>
            
            <?php if(strlen($FrameworkSetup->getUrlSuffix()) > 1) : ?>
            <li>_{Your application is not on the host main path, so you might need to edit 
            your .htaccess files in order to enable nice URL's. Edit <b>/.htaccess</b> and 
            <b>/public/.htaccess</b> and replace the line <br />}
            <b># RewriteBase /framework</b> <br />
            _{with} <br />
            <b> RewriteBase <?=$FrameworkSetup->getUrlSuffix()?></b> </li>
            <?php endif; ?>
            
            <li>_{Log into a command line prompt and go to the <b>script/</b> directory (cd script). 
            Run the command <b>php prompt.php</b> and once logged into the interactive shell type 
            <b>install framework</b>}</li>
            <li>_{Now you can start generating models and controllers from the interactive shell. Just run
            <b>generate model</b>, <b>generate controller</b> without parameters to get the instructions.}</li>

        </div>