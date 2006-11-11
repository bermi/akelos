        <div id="header">
          <h1><?php echo $text_helper->translate('Welcome aboard', array()); ?></h1>
          <h2><?php echo $text_helper->translate('You&rsquo;re using The Akelos Framework!', array()); ?></h2>
        </div>

        <div id="main-content">
          <h1><?php echo $text_helper->translate('Getting started', array()); ?></h1>
          <ol>
            <li>
              <h2><?php echo $text_helper->translate('Configure your environment', array()); ?></h2>
              <p><?=$text_helper->translate('<a href="%url">Run a step by step wizard for creating a configuration file</a> or read INSTALL.txt instead.',array('%url'=>$url_helper->url_for(array('action'=>'select_database'))))?></p>
            </li>
            
          </ol>
        </div>
        <div id="next-step">
            <p>
                <a href="<?=$url_helper->url_for(array('action'=>'select_database'))?>"><?php echo $text_helper->translate('Start the configuration wizard', array()); ?></a>
            </p>
        </div>