        <div id="header">
          <h1>_{Welcome aboard}</h1>
          <h2>_{You&rsquo;re using The Akelos Framework!}</h2>
        </div>

        <div id="main-content">
          <h1>_{Getting started}</h1>
          <ol>
            <li>
              <h2>_{Configure your environment}</h2>
              <p><?=$text_helper->translate('<a href="%url">Run a step by step wizard for creating a configuration file</a> or read INSTALL.txt instead.',array('%url'=>$url_helper->url_for(array('action'=>'select_database'))))?></p>
            </li>
            
          </ol>
        </div>
        <div id="next-step">
            <p>
                <a href="<?=$url_helper->url_for(array('action'=>'select_database'))?>">_{Start the configuration wizard}</a>
            </p>
        </div>