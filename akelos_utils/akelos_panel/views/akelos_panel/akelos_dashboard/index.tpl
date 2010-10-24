<div class="main-content main_content_left">
    {?new_install}
        <h1>_{It works!}</h1>
        <h2>_{Congratulations on your first Akelos-powered page.}</h2>
        <p>_{Of course you haven't coded your app yet. Continue reading to learn what you need to do to create your Akelos application.}</p>       
    {else}
        <h1>_{Akelos Panel for %application_name}</h1>
    {end}

    <?php if(!$has_configuration || !$has_routes) : ?>
    <h2>_{Some files are missing at config/}</h2>
    {!has_configuration}
        <%= flash_warning _("Configuration file #{base_dir}/config/config.php not found") %>
    {end}
    {!has_routes}
        <%= flash_warning _("Routes file #{base_dir}/config/routes.php not found.") %>
    {end}
    
    <?php endif; ?>
        
        {?new_install}
            <h2>_{Quickstart}</h2>
            <div class="important-item-list quickstart">
                <ul>
                    <li>_{Create the config/database.yml file by running} 
                    <%= format_snippet "./script/configure", 'shell' %> 
                    _{you can also rename and edit config/DEFAULT-database.yml}
                    </li>
                    <li>_{Edit your config/routes.php to disable this panel and setup the default controller.}</li>
                    <li>_{Check available generators by running} 
                    <%= format_snippet "./makelos generate", 'shell' %> 
                    _{and start coding}
                    </li>
                </ul>
            </div>

            <h3>_{Must read documentation}</h3>
            <div class="important-item-list">
                <ul>
                    <li><%= link_to_guide _'Getting Started with Akelos', 'getting_started' %></li>
                    <li><%= link_to_guide _'Akelos Routing from the Outside In', 'routing' %></li>
                </ul>
            </div>
            
           <hr />
          
           <h2>_{Why I'm seeing this screen?}</h2>
            <p>_{This dashboard is only available on fresh installs when browsing from the localhost.}</p>
            <p>_{You need to edit your config/routes.php file and disable or change the base path for the Akelos Panel panel by editing the folowing route:}</p>
            <%= capture_snippet 'php' %>
            $Map->connect('/:controller/:action/:id', array(
                          'controller' => 'akelos_dashboard', 
                          'action' => 'index', 
                          'module' => 'akelos_panel',
                          'rebase' => AK_AKELOS_UTILS_DIR.DS.'akelos_panel'
                        ));
            <%= format_snippet %>
            
            
           <hr />
        {end}
        

        <h2>_{%application_name information}</h2>
        <div class="text-block radius_5">
            <dl>
                <dt>_{Application name}</dt><dd>{application_name}</dd>            
                <dt>_{Host}</dt> <dd><?php echo AK_HOST; ?></dd>
                <dt>_{Environment}</dt> <dd><strong>{environment}</strong></dd>
                <dt>_{Available locales}</dt><dd><?php echo join(', ', $langs); ?></dd>
                <dt>_{Application path}</dt> <dd>{base_dir}</dd>
                <dt>_{Akelos path}</dt> <dd>{akelos_dir}</dd>
                <dt>_{Akelos version}</dt> <dd><?php echo AKELOS_VERSION; ?></dd>
                <dt>_{PHP version}</dt> <dd><?php echo PHP_VERSION; ?></dd>
                <dt>_{Memcached}</dt> <dd>{?memcached_on}_{On}{else}_{Off}{end}</dd>           
                <dt>_{Database}</dt><dd>{?database_settings-name}{database_settings-name}{else}_{none}{end}</dd>
                <dt>_{Server user}</dt><dd>{server_user}</dd>
            </dl>
        
        </div>
    
        
        <h2>_{Who can access the Akelos Panel?}</h2>
        <div class="text-block">
            <p>_{By default it can <strong>only</strong> be accessed from when the environment is set to <tt>development</tt> from the localhost machine. You can edit the file:}</p>
            <%= format_snippet 'config/environment.php' %>
            <p>_{And set which IPs that are allowed to access:}</p>
            <%= format_snippet "AkConfig::setOption('local_ips', array('1.2.3.4', '2.4.3.2'));", 'php' %>
            <p>_{These are the IP addresses that can currently access the Akelos Panel:} <strong><?php echo join(', ', $local_ips);?></strong></p>
        </div>
</div>

<div id="col-4">

<div class="important-item-list">
<h3>_{Akelos Framework}</h3>
<ul>
    <li><%= link_to _('API'), :controller => 'docs', :action => 'api', :module => 'akelos_panel' %></li>
    <li><%= link_to _('Guides'), :controller => 'docs', :action => 'guide', :module => 'akelos_panel' %></li>
</ul>
</div>

<div class="important-item-list">
<h3>{application_name}</h3>
<ul>
    <li><%= link_to _('API'), :controller => 'docs', :action => 'app_api', :module => 'akelos_panel' %></li>
</ul>
</div>



<div class="tweets">
    <h3>Latest Tweets <span class="icon"></span></h3>
    <?php $tweets = $akelos_dashboard_helper->get_twitter_feeds(); ?>
    {loop tweets}
    <div class="tweet">
        <p class="message"><strong>{tweet.user.screen_name}:</strong> <%= auto_link tweet.text %> </p>

    </div>
    {end}
</div>

</div>

