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
<%= render :partial => 'akelos_panel/table_of_contents' %>
<div id="wrapper">
  <%= render :partial => 'akelos_panel/header' %>
  <div id="content">
    {content_for_layout}
    <div class="clear"></div>
  </div>
  <%= render :partial => 'akelos_panel/footer' %>
</div>
</body>
</html>