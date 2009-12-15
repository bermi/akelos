<!DOCTYPE html PUBLIC "-//W3C//DTD XHTML 1.0 Strict//EN" "http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd">
<html xmlns="http://www.w3.org/1999/xhtml">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
<title>Akelos</title>

<link href="<%= url_for :controller => 'virtual_assets', :action => "stylesheets", :id => "akelos", :format => "css" %>" rel="stylesheet" type="text/css" />
</head>
<body>
<!--toc-->
<%= render :partial => 'akelos.org/table_of_content' %>
<!--/toc-->
<div id="wrapper">
  <!--header-->
  <%= render :partial => 'akelos.org/header' %>
  <!--/header-->
  <!--breadcrumb-->
  <div class="breadcrumb-holder">
    <div class="breadcrumb">
      <div><a href="#">Akelos</a> Download</div>
    </div>
  </div>
  <!--/breadcrumb-->
  <!--content-->
  <div id="content">
    <div id="col-3">
      <h1>Download Akelos</h1>
      <h2>Requirements</h2>
      <p>Akelos requires <a href="#">PHP</a> (PHP4 or PHP5) and a <a href="#">webserver</a> to run. We strongly recommend <a href="#">XAMPP</a> webserver stack for Windows and Linux, or MAMP if you're on a Mac.</p>
      <p>You might find these requirements at most web hosting providers. Akelos works with PHP4 and PHP5.</p>
      <span class="separator">&nbsp;</span>
      <h2>Recommended Installation</h2>
      <p>Akelos development trunk is a fast moving place where bugs get fixed in days if not hours. As most of the code is fully unit tested, we recommend you to use this version until we hit version 1.0 if you're willing to <a href="#">keep an eye on the changes</a> that might affect you current application; otherwise get version 0.8.</p>
      <p>You need to have installed <a href="#">subversion</a>.</p>
      <p>You can checkout a working copy of the Akelos source code with the command:</p>
      <div class="svn">svn co http://svn.akelos.org/trunk/ akelos</div>
      <span class="separator">&nbsp;</span>
      <h2>Nightly builds</h2>
      <p>Our Continuous Integration system creates releases from the Akelos trunk on every commit that successfully passes the unit tests. <a href="#">Download the latest stable version</a>.</p>
      <span class="separator-2">&nbsp;</span>
      <h2>Features ported from Ruby on Rails</h2>
      <h3>Active Record</h3>
      <ul class="record-list">
        <li>Associations
          <ul>
            <li>belongs_to</li>
            <li>has_one</li>
            <li>has_many</li>
            <li>has_and_belongs_to_many</li>
            <li>Finders - not so cool as Ruby on Rails but you can still do</li>
            <li>$Project->findFirstBy('language AND start_year:greater', 'PHP', '2004');</li>
            <li>Acts as</li>
            <li>nested_set</li>
            <li>list</li>
          </ul>
        </li>
        <li>Callbacks</li>
        <li>Transactions</li>
        <li>Validators</li>
        <li>Locking</li>
        <li>Observer</li>
        <li>Versioning</li>
        <li>Scaffolds</li>
        <li>Support for <a href="#">MySQL</a>, <a href="#">PostgreSQL</a> and <a href="#">SQLite</a> (might work with other databases supported by <a href="#">ADOdb</a>)</li>
      </ul>
    </div>
    <div id="col-4">
      <div class="download-box-a">
        <div class="download-box-b">
          <h2>Latest stable version</h2>
          <h3>Akelos PHP Framework version 1.0 </h3>
          <span class="release-date">February 8, 2009</span> <a href="#" class="download-button-2"><span class="blue-big-text">Download v1.0</span> Latest stable version <span class="blue-text">.tar.gz</span></a>
          <ul>
            <li><a href="#">akelos_framework-0.9.tar.gz 1.5 MB</a><br />
              md5 490f96519911364d4345c47b1defce3e</li>
            <li><a href="#">akelos_framework-0.9.zip 2.3 MB</a><br />
              md5 9a914649c6dfdb397d9e6baceae63040</li>
          </ul>
          <a href="#">CHANGELOG</a> </div>
      </div>
    </div>
    <span class="separator-3">&nbsp;</span>
    <div id="col-5">
      <h3>Categories</h3>
      <ul>
        <li><a href="#">volutpat</a></li>
        <li><a href="#">elit</a></li>
        <li><a href="#">quisque amet</a></li>
        <li><a href="#">malesuada</a></li>
        <li><a href="#">odio vivamus</a></li>
        <li><a href="#">enim neque</a></li>
        <li><a href="#">suspendisse</a></li>
        <li><a href="#">ipsum</a></li>
        <li><a href="#">commodo</a></li>
        <li><a href="#">ante</a></li>
      </ul>
    </div>
    <div id="col-6">
      <div class="plugin-box-2-a">
        <div class="plugin-box-2-b">
          <h2><a href="#">Google Gears</a></h2>
          <p>Suspendisse vestibulum dignissim quam. Integer vel augue. Phasellus nulla purus, interdum ac, venenatis non, varius rutrum, leo. Pellentesque habitant morbi tristique senectus et netus et malesuada...</p>
          <p class="ratings-2">Category: <a href="#">volutpat</a> &nbsp;&nbsp;Author: <a href="#">volutpat</a> &nbsp;&nbsp;Rating: &nbsp;<img src="<%= url_for :controller => 'virtual_assets', :action => "images", :id => "stars", :format => "gif" %>" width="98" height="19" alt="" /></p>
          <a href="#" class="plugin-download-2"><span>Download</span></a>
          <div class="clear"></div>
        </div>
      </div>
      <div class="plugin-box-2-a">
        <div class="plugin-box-2-b"> <img src="<%= url_for :controller => 'virtual_assets', :action => "images", :id => "official", :format => "gif" %>" width="111" height="42" alt="" class="official-tag" />
          <h2><a href="#">SaleWings</a></h2>
          <p>Donec gravida posuere arcu. Nulla facilisi. Phasellus imperdiet. Vestibulum at metus. Integer euismod. Nullam placerat rhoncus sapien. Ut euismod. Praesent libero. Morbi pellentesque libero sit amet ante....</p>
          <p class="ratings-2">Category: <a href="#">odio vivamus </a> &nbsp;&nbsp;Author: <a href="#">bermi</a> &nbsp;&nbsp;Rating: &nbsp;<img src="<%= url_for :controller => 'virtual_assets', :action => "images", :id => "stars", :format => "gif" %>" width="98" height="19" alt="" /></p>
          <a href="#" class="plugin-download-2"><span>Download</span></a>
          <div class="clear"></div>
        </div>
      </div>
    </div>
    <span class="separator-2">&nbsp;</span>
    <div id="col-7">
      <h1>Screencasts</h1>
      <p>Donec gravida posuere arcu. Nulla facilisi. Phasellus imperdiet. Vestibulum at metus. Integer euismod. Nullam placerat rhoncus sapien. Ut euismod. Praesent libero. Morbi pellentesque libero sit amet ante. Maecenas tellus. Maecenas erat. Pellentesque habitant morbi tristique senectus et netus et malesuada fames.</p>
      <div class="screencast"> <img src="<%= url_for :controller => 'virtual_assets', :action => "images", :id => "screen-1", :format => "gif" %>" width="256" height="156" alt="" />
        <div class="inner-screen">
          <h2>Creating a weblog using the Akelos PHP Framework</h2>
          <p>This is a version of the famous RoR "Creating a blog in 20 minutes" screencast by DHH, but this time using the Akelos PHP Framework , which is a port of Rails for PHP.</p>
          <a href="#" class="screen-button"><span>View screencast</span></a><a href="#" class="screen-button"><span>Download screencast (Quicktime) 41,7MB)</span></a> </div>
        <div class="clear"></div>
      </div>
      <div class="screencast"> <img src="<%= url_for :controller => 'virtual_assets', :action => "images", :id => "screen-2", :format => "gif" %>" width="256" height="156" alt="" />
        <div class="inner-screen">
          <h2>The Akelos Admin Plugin</h2>
          <p>The Akelos admin plugin makes it easy to create management interfaces for your application.</p>
          <a href="#" class="screen-button"><span>View screencast</span></a><a href="#" class="screen-button"><span>Download screencast (Quicktime) 41,7MB)</span></a> </div>
        <div class="clear"></div>
      </div>
      <div class="quick-time-link">In order to watch the screencasts you need to install <a href="#">Quick Time Player</a>.</div>
      <span class="separator-3">&nbsp;</span>
      <h2>Contents of this screencast</h2>
      <ul class="spacing">
        <li>Akelos installation.</li>
        <li>Using generators.</li>
        <li>Using scaffolds.</li>
        <li>Model View Controller in Akelos.</li>
        <li>Using migrations to distribute the changes in your database.</li>
        <li>Ruby syntax on Akelos PHP views using Sintags.</li>
      </ul>
      <ul>
        <li>Database associations.</li>
        <li>Helper functions on views.</li>
        <li>Internationalizing your application.</li>
        <li>Unit testing.</li>
        <li>The log.</li>
        <li>Akelos PHP console.</li>
      </ul>
    </div>
    <div class="clear"></div>
  </div>
  <!--/content-->
  <!--footer-->
  <%= render :partial => 'akelos.org/footer' %>
  <!--/footer-->
</div>
</body>
</html>