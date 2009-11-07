        <div id="header">
          <h1>_{Database Configuration.}</h1>
        </div>

        <div id="main-content">
          <h1>_{Please select a database type}</h1>
          <h2>_{The list below only includes databases we found you had support for under your current PHP settings}</h2>
          <ol>
          {loop databases?}
            <li>
              <h2><a href="<?=$url_helper->url_for(array('action'=>'set_database_details','database_type'=>$database['type']))?>">{database-name}</h2>
            </li>
          {end}  
          </ol>
        </div>
        
        <?php if(empty($databases)) : ?>
        
        <div>
        <p>_{Could not find any database driver on your current setup. You might need to enable MySQL or PostgreSQL on your 
        PHP settings} </p>
        </div>
        
        <?php endif; ?>
        
