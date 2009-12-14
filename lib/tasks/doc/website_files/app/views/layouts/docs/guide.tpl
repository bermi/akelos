<html xmlns="http://www.w3.org/1999/xhtml" xml:lang="en" lang="en">
<head>
<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>

<title>{?page_title}{_page_title}{else}_{Akelos guides}{end}</title>

<link rel="stylesheet" type="text/css" href="/stylesheets/docs/guide/style.css" />
<link rel="stylesheet" type="text/css" href="/stylesheets/docs/guide/syntax.css" />
<link rel="stylesheet" type="text/css" href="/stylesheets/docs/guide/print.css" media="print" />

<script type="text/javascript" src="/javascripts/docs/guide/guides.js"></script>
<script type="text/javascript" src="/javascripts/docs/guide/code_highlighter.js"></script>
<script type="text/javascript" src="/javascripts/docs/guide/highlighters.js"></script>

</head>
<body class="guide">
  <div id="header">
    <div class="wrapper clearfix">
      <h1><a href="/docs/guide/" title="Return to home page">guides.akelos.org</a></h1>
      <p class="hide"><a href="#mainCol">Skip navigation</a>.</p>
      <ul class="nav">
        <li><a href="index.html">Home</a></li>
        <li class="index"><a href="/docs/guide/getting_started.html" onclick="guideMenu(); return false;" id="guidesMenu">Guides Index</a>
          <div id="guides" class="clearfix" style="display: none;">
            <hr />
            <dl class="L">
              <dt>Start Here</dt>
              <dd><a href="/docs/guide/getting_started.html">Getting Started with Akelos</a></dd>
              <dt>Models</dt>
              <dd><a href="/docs/guide/migrations.html">Akelos Database Migrations</a></dd>
              <dd><a href="/docs/guide/activerecord_validations_callbacks.html">Active Record Validations and Callbacks</a></dd>
              <dd><a href="/docs/guide/association_basics.html">Active Record Associations</a></dd>
              <dd><a href="/docs/guide/active_record_querying.html">Active Record Query Interface</a></dd>
              <dt>Views</dt>
              <dd><a href="/docs/guide/layouts_and_rendering.html">Layouts and Rendering in Akelos</a></dd>
              <dd><a href="/docs/guide/form_helpers.html">Action View Form Helpers</a></dd>
              <dt>Controllers</dt>
              <dd><a href="/docs/guide/action_controller_overview.html">Action Controller Overview</a></dd>
              <dd><a href="/docs/guide/routing.html">Akelos Routing from the Outside In</a></dd>
            </dl>
            <dl class="R">
              <dt>Digging Deeper</dt>
              <dd><a href="/docs/guide/i18n.html">Akelos Internationalization API</a></dd>
              <dd><a href="/docs/guide/action_mailer_basics.html">Action Mailer Basics</a></dd>
              <dd><a href="/docs/guide/testing.html">Testing Akelos Applications</a></dd>
              <dd><a href="/docs/guide/security.html">Securing Akelos Applications</a></dd>
              <dd><a href="/docs/guide/debugging_akelos_applications.html">Debugging Akelos Applications</a></dd>
              <dd><a href="/docs/guide/performance_testing.html">Performance Testing Akelos Applications</a></dd>
              <dd><a href="/docs/guide/plugins.html">The Basics of Creating Akelos Plugins</a></dd>
              <dd><a href="/docs/guide/configuring.html">Configuring Akelos Applications</a></dd>
              <dd><a href="/docs/guide/command_line.html">Akelos Command Line Tools and Makelos Tasks</a></dd>
              <dd><a href="/docs/guide/caching_with_akelos.html">Caching with Akelos</a></dd>
              <dd><a href="/docs/guide/contributing_to_akelos.html">Contributing to Akelos</a></dd>
            </dl>
          </div>
        </li>
      </ul>
    </div>
  </div>
  <hr class="hide" />

  <div id="feature">
    <div class="wrapper">
      {header_section?}
      {index_section?}
    </div>
  </div>

  <div id="container">
    <div class="wrapper">
      <div id="mainCol">
        {content_for_layout}
      </div>
    </div>
  </div>

  <hr class="hide" />
  <div id="footer">
    <div class="wrapper">
      <p>This work is licensed under a <a href="http://creativecommons.org/licenses/by-sa/3.0/">Creative Commons Attribution-Share Alike 3.0</a> License</a></p>
    </div>
  </div>
</body>
</html>
