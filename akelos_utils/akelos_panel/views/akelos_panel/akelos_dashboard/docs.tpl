<div class="main-content main_content_left">
<h1>_{Documentation}</h1>
<h2>_{Akelos Framework documentation}</h2>
<ul>
    <li><%= link_to _('API'), :controller => 'akelos_dashboard', :action => 'api', :module => 'akelos_panel' %></li>
    <li><%= link_to _('Guides'), :controller => 'akelos_dashboard', :action => 'guide', :module => 'akelos_panel' %></li>
</ul>

<h2>_{<strong>%application_name</strong>} documentation</h2>
<ul>
    <li><%= link_to _('API'), :controller => 'docs', :action => 'app_api', :module => 'akelos_panel' %></li>
</ul>

</div>