        <div id="header">
          <h1><?php echo $text_helper->translate('Database Configuration.', array()); ?></h1>
        </div>

        <div id="main-content">
          <h1><?php echo $text_helper->translate('Please select a database type', array()); ?></h1>
          <h2><?php echo $text_helper->translate('The list below only includes databases we found you had support for under your current PHP settings', array()); ?></h2>
          <ol>
          <?php 
 empty($databases) ? null : $database_loop_counter = 0;
 empty($databases) ? null : $databases_available = count($databases);
 if(!empty($databases))
     foreach ($databases as $database_loop_key=>$database){
         $database_loop_counter++;
         $database_is_first = $database_loop_counter === 1;
         $database_is_last = $database_loop_counter === $databases_available;
         $database_odd_position = $database_loop_counter%2;
?>
            <li>
              <h2><a href="<?php echo $url_helper->url_for(array('action'=>'set_database_details','database_type'=>$database['type']))?>"><?php 
 echo $database['name'];
?></h2>
            </li>
          <?php } ?>  
          </ol>
        </div>
        
        <?php if(empty($databases)) : ?>
        
        <div>
        <p><?php echo $text_helper->translate('Could not find any database driver on your current setup. You might need to enable MySQL or PostgreSQL on your 
        PHP settings', array()); ?> </p>
        </div>
        
        <?php endif; ?>
        
