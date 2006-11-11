        <div id="header">
          <h1><?php echo $text_helper->translate('Language settings.', array()); ?></h1>
        </div>
        
        <div id="main-content">
          <h1><?php echo $text_helper->translate('Please set your language details', array()); ?></h1>
        
          <?= $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'set_locales')) ?>
   
          <label for='locales'><?php echo $text_helper->translate('2 letter ISO 639 language codes (separated by commas)', array()); ?></label>
                    <input type='text' name='locales' id='locales' value='<?php 
 echo isset($locales) ? $locales : '';
?>' />
                        
                <br />
                <br />
                
                <input type="submit" value="<?php echo $text_helper->translate('Continue', array()); ?>" />

            </form>
            
        </div>
        