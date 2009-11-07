<div id="header">
  <h1><?php echo $text_helper->translate('Save Configuration.', array()); ?></h1>
</div>
<div id="main-content">

  <h1><?php echo $text_helper->translate('Final Steps.', array()); ?></h1>
  <h2><?php echo $text_helper->translate('You are about to complete the installation process. Please follow the steps bellow.', array()); ?></h2>
  <ol>
    <li><?php echo $text_helper->translate('Copy the following configuration file contents to <b>config/config.php</b>.', array()); ?>
        <?php echo  $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'perform_setup')) ?>
        <?php echo $form_tag_helper->text_area_tag('config',$configuration_file,array('size'=>'45x40'))?>
        </form>
    </li>
    <li><?php echo $text_helper->translate('Copy the file <b>config/DEFAULT-routes.php</b> to <b>config/routes.php</b>', array()); ?></li>
    
    <?php if(strlen($FrameworkSetup->getUrlSuffix()) > 1) : ?>
    <li><?php echo $text_helper->translate('Your application is not on the host main path, so you might need to edit 
    your .htaccess files in order to enable nice URL\'s. Edit <b>/.htaccess</b> and 
    <b>/public/.htaccess</b> and replace the line <br />', array()); ?>
    <b># RewriteBase /framework</b> <br />
    <?php echo $text_helper->translate('with', array()); ?> <br />
    <b> RewriteBase <?php echo $FrameworkSetup->getUrlSuffix()?></b> </li>
    <?php endif; ?>
    
    <li><?php echo $text_helper->translate('Now you can start generating models and controllers by running <b>./script/generate model</b>, <b>./script/generate controller</b> and , <b>./script/generate scaffold</b>. Run them without parameters to get the instructions.', array()); ?></li>

</div>