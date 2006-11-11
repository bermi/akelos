        <div id="header">
          <h1>_{Language settings.}</h1>
        </div>
        
        <div id="main-content">
          <h1>_{Please set your language details}</h1>
        
          <?= $form_tag_helper->start_form_tag(array('controller'=>'framework_setup','action'=>'set_locales')) ?>
   
          <label for='locales'>_{2 letter ISO 639 language codes (separated by commas)}</label>
                    <input type='text' name='locales' id='locales' value='{locales?}' />
                        
                <br />
                <br />
                
                <input type="submit" value="_{Continue}" />

            </form>
            
        </div>
        