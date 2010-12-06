<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>{!title}{application_name}, Akelos Panel{else}{title}{end}</title>
<%= javascript_include_tag %>

<link href="<%= url_for :controller => 'virtual_assets', :action => "stylesheets", :id => "akelos", :format => "css" %>" rel="stylesheet" type="text/css" media="screen" />
<link href="<%= url_for :controller => 'virtual_assets', :action => "stylesheets", :id => "syntax", :format => "css" %>" rel="stylesheet" type="text/css" />
<link href="<%= url_for :controller => 'virtual_assets', :action => "stylesheets", :id => "print", :format => "css" %>" rel="stylesheet" type="text/css" media="print" />

<%= javascript_include_tag url_for(:controller => 'virtual_assets', :action => "javascripts", :id => "guides") %>
<%= javascript_include_tag url_for(:controller => 'virtual_assets', :action => "javascripts", :id => "code_highlighter") %>
<%= javascript_include_tag url_for(:controller => 'virtual_assets', :action => "javascripts", :id => "highlighters") %>

</head>
<body>
<div id="styled-content" style="display:none;">
    <%= render :partial => 'akelos_panel/table_of_contents' %>
    <div id="wrapper">
      <%= render :partial => 'akelos_panel/header' %>
      <div id="content">
        {content_for_layout}
        <div class="clear"></div>
      </div>
      <%= render :partial => 'akelos_panel/footer' %>
    </div>
</div>

<div id="unstyled-content">
    <h1>The server is not resolving nice URLs.</h1>
    
    <p>Please check the following items.</p>
    
    <ol>
        <li>Is mod_rewrite is enabled in your web server?</li>
        <li>Is your web server applying the configuration directives in your .htaccess? <br />
            You might want to check this host settings in your main Apache configuration file. <br />
            A common Directory setting looks like:
            <pre>
                &lt;Directory &quot;/path/to/public&quot;&gt;
                        Options Indexes FollowSymLinks
                        AllowOverride All
                        Order allow,deny
                        Allow from all
                &lt;/Directory&gt;
            </pre>
        </li>
        <li>If the above did not fixed the issue, try setting the RewriteBase directive by replacing:
        
            <pre>
                # RewriteBase /public
            </pre>
        
            with
        
            <pre>
                RewriteBase /
            </pre>
            
            in your .htaccess files
        </li>
    </ol>
</div>

</body>
</html>