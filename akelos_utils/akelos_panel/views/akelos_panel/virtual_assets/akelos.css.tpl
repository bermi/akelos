/* Show content when CSS is enabled */
#styled-content{
    display: block !important;
}
/* Hide CSS warning */
#unstyled-content{
    display: none !important;
}

/* Main CSS Start */
* {
	padding: 0;
	margin: 0;
}
html, body, #wrapper, #wrapper-2 {
	margin: 0;
	padding: 0;
	height: 100%;
	min-height: 100%;
}
html>body, html>body #wrapper, #content, #wrapper-2 {
	height: auto;
}
div#wrapper {
	position: absolute;
}
* html #footer {
	position: absolute;
}
* html #content {
	padding-bottom: 0;
}
/* \*/
head:first-child+body div#footer {
	position: absolute;
	z-index:0;
}
head:first-child+body div#content {
	padding-bottom: 0;
}
}
 @media all and (min-width:0px) {
head~body {
height:100%;
}
}
body {
	background: #f8f8f8;
	font-family: "Lucida Grande", "Lucida Sans Unicode", Arial, Verdana, sans-serif;
}

table {
	margin: 0 0 1.5em;
	border: 2px solid #CCC;
	background: #FFF;
	border-collapse: collapse;
}
 
table th, table td {
	padding: 0.25em 1em;
	border: 1px solid #CCC;
	border-collapse: collapse;
}

table th {
	border-bottom: 2px solid #CCC;
	background: #EEE;
	font-weight: bold;
	padding: 0.5em 1em;
}


#wrapper {
	width: 100%;
	background: url('<%= url_for :action => "images", :id => "body-bg", :format => "gif" %>') repeat-x top #f8f8f8;
}
a, :focus {
	outline: none;
}
.clear {
	clear: both;
	line-height: 0;
	font-size: 0;
}
img {
	border: 0;
}

hr{
  height:0;
  border:0;
  margin-top:40px;
  border-bottom:1px solid #eee;
}

/* Main CSS End */


/* Header CSS Start */
#header {
	width: 893px;
	height: 139px;
	padding: 0 15px 0 16px;
	margin: 0 auto;
}
.top-buttons {
	height: 28px;
	overflow: hidden;
}
.table-content-button {
	height: 28px;
	display: inline-block;
	float: left;
	background: url('<%= url_for :action => "images", :id => "content-button-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 18px;
	font-size: 11px;
	text-decoration: none;
	color: #fff;
}
.table-content-button span {
	height: 22px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "content-button-right", :format => "gif" %>') no-repeat right;
	padding: 6px 18px 0 0;
	cursor: pointer;
}
.user-tabs {
	float: right;
	list-style: none;
}
.user-tabs li {
	float: left;
	padding-left: 11px;
}
.user-tabs li a {
	height: 28px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "user-tab-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 19px;
	font-size: 11px;
	text-decoration: none;
	color: #565a5c;
}
.user-tabs li a span {
	height: 22px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "user-tab-right", :format => "gif" %>') no-repeat right;
	padding: 6px 19px 0 0;
	cursor: pointer;
}
#search {
	height: 25px;
	padding: 25px 0 10px 639px;
}
.search-field {
	display: inline-block;
	width: 171px;
	height: 25px;
	background: url('<%= url_for :action => "images", :id => "search-field-bg", :format => "gif" %>') no-repeat;
	text-align: center;
	float: left;
}
.search-field input {
	width: 160px;
	border: 0;
	background: none;
	margin: 4px 0 0 0;
}
.search-button {
	height: 25px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "search-button-left", :format => "gif" %>') no-repeat left;
	color: #007c92;
	text-decoration: none;
	font-size: 11px;
	font-weight: bold;
	float: left;
	padding: 0 0 0 21px;
	letter-spacing: -1px;
	margin: 0 0 0 4px;
}
.search-button span {
	height: 21px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "search-button-right", :format => "gif" %>') no-repeat right;
	cursor: pointer;
	padding: 4px 21px 0 0;
}
.logo {
	width: 140px;
	height: 31px;
	float: left;
	padding: 0 24px 0 0;
}
.logo a {
	width: 140px;
	height: 31px;
	display: block;
	background: url('<%= url_for :action => "images", :id => "logo", :format => "gif" %>') no-repeat;
	line-height: 0;
	font-size: 0;
	text-indent: -9000px;
}
#top-nav {
	height: 51px;
	float: left;
}
#top-nav ul {
	list-style: none;
}
#top-nav ul li {
	float: left;
}
#top-nav ul li a {
	display: inline-block;
	height: 51px;
	padding: 0 0 0 15px;
	font-size: 12px;
	text-decoration: none;
	font-weight: bold;
	color: #007c92;
	letter-spacing: -1px;
}
#top-nav ul li a span {
	display: inline-block;
	height: 36px;
	padding: 15px 15px 0 0;
	cursor: pointer;
}
#top-nav ul li a:hover, #top-nav ul li a.active {
	background: url('<%= url_for :action => "images", :id => "top-nav-left", :format => "gif" %>') no-repeat left;
	color: #565a5c;
}
#top-nav ul li a:hover span, #top-nav ul li a.active span {
	background: url('<%= url_for :action => "images", :id => "top-nav-right", :format => "gif" %>') no-repeat right;
}
/* Header CSS Endt */


/* Banner CSS Start */
#main-banner {
	width: 886px;
	height: 410px;
	background: url('<%= url_for :action => "images", :id => "main-banner", :format => "jpg" %>') no-repeat;
	margin: 26px auto 0 auto;
	padding: 0 0 5px 38px;
}
.banner-nav {
	list-style: none;
	font-size: 14px;
	font-weight: bold;
	display: block;
	height: 44px;
}
.banner-nav li {
	float: left;
	padding: 0 20px 0 0;
}
.banner-nav li a {
	display: inline-block;
	height: 44px;
	padding: 0 0 0 12px;
	text-decoration: none;
	color: #007c92;
	background: url('<%= url_for :action => "images", :id => "banner-tab-left", :format => "gif" %>') no-repeat left top;
}
.banner-nav li a span {
	display: inline-block;
	height: 36px;
	padding: 8px 12px 0 0;
	background: url('<%= url_for :action => "images", :id => "banner-tab-right", :format => "gif" %>') no-repeat right top;
	cursor: pointer;
}
.banner-nav li a span span {
	display: inline-block;
	height: 36px;
	padding: 0 0 0 0;
	cursor: pointer;
	background: none;
}
.banner-nav li a:hover, .banner-nav li a.active {
	background-position: bottom left;
	color: #565a5c;
}
.banner-nav li a:hover span, .banner-nav li a.active span {
	background-position: bottom right;
}
.banner-nav li a:hover span span, .banner-nav li a.active span span {
	display: inline-block;
	height: 36px;
	padding: 0 0 0 0;
	background: url('<%= url_for :action => "images", :id => "down-arrow", :format => "gif" %>') no-repeat bottom center;
	cursor: pointer;
}
.tab-banner-content {
	width: 844px;
	margin: 65px 0 0 0;
	color: #fff;
	position: relative;
}
.tab-banner-content h1 {
	font-weight: normal;
	font-size: 30px;
	color: #fff;
	padding-bottom: 20px;
}
.tab-banner-content p {
	font-size: 12px;
	padding-bottom: 15px;
}
.learn-more {
	font-weight: bold;
	color: #fff;
	font-size: 12px;
}
.learn-more:hover {
	text-decoration: none;
}
.download-button {
	width: 168px;
	height: 53px;
	background: url('<%= url_for :action => "images", :id => "download-button", :format => "gif" %>') no-repeat;
	display: block;
	position: absolute;
	right: 0;
	bottom: -20px;
	padding: 9px 0 0 95px;
	font-size: 11px;
	text-decoration: none;
	color: #565a5c;
}
.download-button span.blue-big-text {
	font-size: 18px;
	color: #00b0ca;
	display: block;
}
.download-button span.blue-text {
	color: #007c92;
}
/* Banner CSS End */


/* Content CSS Start */
#content {
	width: 909px;
	padding: 30px 0 0 15px;
	margin: 0 auto;
}
#content-2 {
	width: 924px;
	padding: 0 0 30px 0;
	margin: 0 auto;
}
.latest-bar {
	width: 890px;
	height: 33px;
	background: url('<%= url_for :action => "images", :id => "latest-bar", :format => "gif" %>') no-repeat;
	padding: 17px 21px 0 0;
}
.latest-bar h2 {
	font-size: 14px;
	color: #fff;
	float: left;
	width: 315px;
	padding: 0 0 0 19px;
}
.latest-bar p {
	color: #565a5c;
	font-size: 12px;
}
.latest-bar a {
	color: #007c92;
	text-decoration: none;
}
.latest-bar a:hover {
	text-decoration: underline;
}
#col-1 {
	width: 273px;
	float: left;
	padding: 22px 0 0 0;
}
.rss-link-1 {
	float: right;
	background: url('<%= url_for :action => "images", :id => "rss-icon", :format => "gif" %>') no-repeat left;
	color: #007c92;
	font-size: 12px;
	padding: 0 0 0 18px;
	text-decoration: none;
}
.rss-link-1:hover {
	text-decoration: underline;
}
.title-1 {
	padding: 0 17px 15px 19px;
}
.title-1 h2 {
	color: #565a5c;
	font-size: 12px;
}
.plugin-box-a {
	width: 273px;
	background: url('<%= url_for :action => "images", :id => "plugin-box-top", :format => "gif" %>') no-repeat top;
	margin: 0 0 23px 0;
}
.plugin-box-b {
	width: 200px;
	background: url('<%= url_for :action => "images", :id => "plugin-box-bottom", :format => "gif" %>') no-repeat bottom;
	padding: 24px 43px 55px 30px;
}
.plugin-box-b a {
	color: #007c92;
	text-decoration: none;
}
.plugin-box-b a:hover {
	text-decoration: underline;
}
.plugin-box-b h2 {
	font-size: 14px;
	color: #007c92;
	padding: 0 0 10px 0;
}
.plugin-box-b p {
	font-size: 11px;
	color: #565a5c;
	padding: 0 0 12px 0;
}
.ratings {
	position: relative;
}
.ratings img {
	position: absolute;
	top: -3px;
}
.twitter-icon {
	position: relative;
	top: 2px;
	left: 9px;
}
.twitter-icon-2 {
	position: relative;
	top: 0px;
	left: 9px;
}
.plugin-download {
	height: 31px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 23px;
	font-size: 11px;
	font-weight: bold;
	color: #007c92;
	letter-spacing: -1px;
	margin: 3px 0 0 0;
}
.plugin-download span {
	height: 24px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-right", :format => "gif" %>') no-repeat right;
	padding: 7px 23px 0 0;
	cursor: pointer;
}
.plugin-download:hover {
	text-decoration: none !important;
}
.help-us-a {
	width: 272px;
	background: url('<%= url_for :action => "images", :id => "help-us-top", :format => "gif" %>') no-repeat top #00afc9;
}
.help-us-b {
	width: 251px;
	background: url('<%= url_for :action => "images", :id => "help-us-bottom", :format => "gif" %>') no-repeat bottom;
	padding: 15px 0 18px 21px;
}
.help-us-b h2 {
	font-size: 14px;
	color: #fff;
	padding: 0 0 14px 0;
}
.help-us-b ul {
	list-style: none;
	font-size: 12px;
	line-height: 16px;
}
.help-us-b ul li {
	background: url('<%= url_for :action => "images", :id => "donation-bullet", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 21px;
}
.help-us-b ul li a {
	text-decoration: none;
	color: #fff;
}
.help-us-b ul li a:hover {
	text-decoration: underline;
}
#col-2 {
	width: 599px;
	float: right;
	padding: 24px 0 0 0;
}
.latest-news {
	padding: 0 0 30px 0;
}
.forum-activity {
	padding: 0 0 30px 0;
}
.entry {
	width: 555px;
	background: url('<%= url_for :action => "images", :id => "entry-bg", :format => "gif" %>') no-repeat bottom;
	padding: 29px 20px 25px 24px;
	margin-bottom: 15px;
}
.entry h2 {
	font-size: 16px;
	font-weight: normal;
	color: #007c92;
}
.entry p {
	font-size: 12px;
	color: #565a5c;
}
.entry a {
	color: #007c92;
	text-decoration: none;
}
.entry a:hover {
	text-decoration: underline;
}
.load {
	display: inline-block;
	height: 14px;
	background: url('<%= url_for :action => "images", :id => "reloader", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 21px;
	color: #007c92;
	font-size: 12px;
	text-decoration: none;
	line-height: 12px;
	margin: 0 0 0 22px;
}
.load:hover {
	text-decoration: underline;
}
.breadcrumb-holder {
	height: 51px;
	padding: 22px 0 0 15px;
	width: 909px;
	margin: 0 auto;
}
.breadcrumb {
	height: 30px;
	background: url('<%= url_for :action => "images", :id => "breadcrumb-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 18px;
	float:left;
	font-size: 11px;
	color: #565a5c;
	position: relative;
	left: -17px;
}
.breadcrumb div {
	height: 23px;
	background: url('<%= url_for :action => "images", :id => "breadcrumb-right", :format => "gif" %>') no-repeat right;
	padding: 7px 18px 0 0;
	float: left;
}
.breadcrumb a {
	color: #007c92;
	text-decoration: none;
	background: url('<%= url_for :action => "images", :id => "breadcrumb-arrow", :format => "gif" %>') no-repeat right 4px;
	padding: 0 13px 0 0;
	margin: 0 5px 0 0;
}
.breadcrumb a:hover {
	text-decoration: underline;
}

.main_content_left {
	width: 574px;
	float: left;
	color: #333;
	padding: 10px 0 0 0;
}
.main_content_left h1 {
	font-size: 24px;
	padding: 0 0 15px 0;
}
.main_content_left h2 {
	font-size: 16px;
	padding: 0 0 12px 0;
}
.main_content_left h3 {
	font-size: 12px;
	padding: 0 0 12px 0;
}
.main_content_left p {
	font-size: 12px;
	padding: 0 0 13px 0;
}
.main_content_left a {
	color: #007c92;
	text-decoration: none;
}
.main_content_left a:hover {
	text-decoration: underline;
}
.record-list {
	list-style: none;
	color: #565a5c;
	font-size: 12px;
	line-height: 18px;
}
.record-list li {
	background: url('<%= url_for :action => "images", :id => "record-bullet", :format => "gif" %>') no-repeat left 3px;
	padding: 0 0 0 21px;
}
.record-list li ul {
	list-style: none;
}
.record-list li ul li {
	background: url('<%= url_for :action => "images", :id => "record-bullet-2", :format => "gif" %>') no-repeat 9px 7px;
}
.svn {
	background: #e6f7f9;
	border: #cceff4 1px solid;
	height: 26px;
	font-family: "Courier New", Courier, monospace;
	font-size: 14px;
	padding: 6px 0 0 12px;
	margin: 4px 0 13px 0;
}
.cls {
	clear: both;
}
.separator {
	height: 15px;
	clear: both;
	line-height: 0;
	font-size: 0;
	display: block;
}
.separator-2 {
	height: 30px;
	clear: both;
	line-height: 0;
	font-size: 0;
	display: block;
}
.separator-3 {
	height: 50px;
	clear: both;
	line-height: 0;
	font-size: 0;
	display: block;
}
.separator-4 {
	height: 75px;
	clear: both;
	line-height: 0;
	font-size: 0;
	display: block;
}
#col-4 {
	width: 293px;
	float: right;
}
.download-box-a {
	width: 293px;
	background: url('<%= url_for :action => "images", :id => "download-box-a", :format => "gif" %>') no-repeat top;
	color: #565a5c;
}
.download-box-b {
	width: 273px;
	background: url('<%= url_for :action => "images", :id => "download-box-b", :format => "gif" %>') no-repeat bottom;
	padding: 0 0 19px 20px;
	font-size: 11px;
}
.download-box-b h2 {
	color: #565a5c;
	font-size: 12px;
	line-height: 31px;
	padding: 0 0 18px 0;
}
.download-box-b h3 {
	font-size: 14px;
	letter-spacing: -1px;
}
.download-box-b ul {
	font-size: 11px;
	list-style: none;
	padding: 14px 0 0 0;
}
.download-box-b ul li {
	padding: 0 0 12px 0;
}
.download-box-b a {
	color: #007c92;
	text-decoration: none;
}
.download-box-b a:hover {
	text-decoration: underline;
}
.release-date {
	font-size: 12px;
	display: block;
	padding: 0 0 15px 0;
}
.download-button-2 {
	width: 168px;
	height: 53px;
	background: url('<%= url_for :action => "images", :id => "download-button-2", :format => "gif" %>') no-repeat;
	display: block;
	padding: 9px 0 0 95px;
	font-size: 11px;
	text-decoration: none;
	color: #565a5c;
}
.download-button-2 span.blue-big-text {
	font-size: 18px;
	color: #00b0ca;
	display: block;
}
.download-button-2 span.blue-text {
	color: #007c92;
}
.download-button-2:hover {
	text-decoration: none !important;
}


#col-6 {
	width: 667px;
	float: right;
}
.plugin-box-2-a {
	width: 667px;
	background: url('<%= url_for :action => "images", :id => "plugin-box-2-top", :format => "gif" %>') no-repeat top;
	margin: 0 0 10px 0;
	position: relative;
}
.plugin-box-2-b {
	width: 576px;
	background: url('<%= url_for :action => "images", :id => "plugin-box-2-bottom", :format => "gif" %>') no-repeat bottom;
	padding: 25px 30px 35px 61px;
}
.plugin-box-2-b a {
	color: #007c92;
	text-decoration: none;
}
.plugin-box-2-b a:hover {
	text-decoration: underline;
}
.plugin-box-2-b h2 {
	font-size: 14px;
	color: #007c92;
	padding: 0 0 10px 0;
}
.plugin-box-2-b p {
	font-size: 11px;
	color: #565a5c;
	padding: 0 0 20px 0;
}
.plugin-download-2 {
	height: 31px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 23px;
	font-size: 11px;
	font-weight: bold;
	color: #007c92;
	letter-spacing: -1px;
	margin: -3px 0 0 0;
	float: right;
}
.plugin-download-2 span {
	height: 24px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-right", :format => "gif" %>') no-repeat right;
	padding: 7px 23px 0 0;
	cursor: pointer;
}
.plugin-download-2:hover {
	text-decoration: none !important;
}
.ratings-2 {
	float: left;
	padding: 0 0 0 0 !important;
}
.ratings-2 img {
	position: relative;
	top: 4px;
}
.official-tag {
	position: absolute;
	top: 0;
	right: 0;
}
#col-7 {
	padding: 0 55px 60px 0;
	overflow: auto;
}
#col-7 h1 {
	font-size: 24px;
	padding: 0 0 15px 0;
}
#col-7 h2 {
	font-size: 16px;
	padding: 0 0 12px 0;
}
#col-7 p {
	color: #565a5c;
	font-size: 12px;
}
#col-7 ul {
	list-style: none;
	line-height: 18px;
	font-size: 12px;
	color: #565a5c;
	float: left;
}
#col-7 ul.spacing {
	margin: 0 100px 0 0;
}
#col-7 ul li {
	background: url('<%= url_for :action => "images", :id => "record-bullet", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 21px;
}
.screencast {
	padding: 24px 0 0 0;
	font-size: 12px;
}
.screencast h2 {
	color: #007c92;
	font-size: 14px !important;
	padding: 17px 0 11px 0 !important;
}
.screencast p {
	font-size: 12px;
	color: #565a5c;
	padding: 0 0 16px 0;
}
.screencast img {
	float: left;
}
.screencast div {
	float: right;
	width: 570px;
}
.screen-button {
	height: 31px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-left", :format => "gif" %>') no-repeat left;
	padding: 0 0 0 23px;
	font-size: 11px;
	font-weight: bold;
	color: #007c92;
	letter-spacing: -1px;
	margin: 3px 12px 0 0;
	text-decoration: none;
}
.screen-button span {
	height: 24px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "plugin-download-right", :format => "gif" %>') no-repeat right;
	padding: 7px 23px 0 0;
	cursor: pointer;
}
.screen-button:hover {
	text-decoration: none !important;
}
.quick-time-link {
	height: 18px;
	background: #e6f2f4;
	float: left;
	font-size: 11px;
	color: #565a5c;
	padding: 2px 6px 0 6px;
	margin-top: 24px;
}
.quick-time-link a {
	color: #007c92;
	text-decoration: none;
}
.quick-time-link a:hover {
	text-decoration: underline;
}





#col-8 {
	width: 258px;
	float: left;
	padding: 28px 0 0 0;
}



/* Guide */


#guide{
   width:709px;
   position:relative;
}


#toc{
  width:180px;
  float:right;
  border-left:1px solid #eee;
  font-size:10px;
  color:#666;
  top:42px;
  position:absolute;
  left:720px;
}

#toc ul.sections a:link,
#toc ul.sections a:visited{
  color:#666;
}

#toc h3{
  margin:0 0 0 18px;
}


#toc ol li.chapter{
  margin-left:28px;
  margin-bottom:6px;
  font-weight:bold;
}

#toc ol ul{
  margin-bottom:0;
  font-weight:normal;
}



#guide-content {
  padding: 44px 27px 23px 59px;
  color: #333;
  font-size: 12px;
}

#guide h2 {
  font-size: 16px;
  padding:20px 0 5px 0;
}
#guide h3 {
  font-size: 16px;
  padding:20px 0 18px 0;
}
#guide h4 {
  font-size: 14px;
  padding:20px 0 14px 0;
}
#guide h5 {
  font-size: 13px;
  padding: 0 0 10px 0;
}

#guide p {
 font-size: 12px;
 line-height: 20px;
 padding: 0 0 22px 0;
}
#guide li {
  line-height: 20px;
}
#guide ul{
  margin: 0 0 30px 30px;
}

#guide a {
 color: #007c92;
 text-decoration: none;
}

#guide a:hover {
  text-decoration: underline;
}



#prologue{
  margin:0 0 20px 0;
  border:1px solid #00b0ca;
  padding:0 20px;
  -moz-border-radius: 5px;
  -khtml-border-radius: 5px;
  -webkit-border-radius: 5px;
  border-radius: 5px;
  background: #00b0ca;
  color: #fff;
}




/* Chapters */


.chapter-top {
	height: 6px;
	width: 258px;
	display: block;
	background: url('<%= url_for :action => "images", :id => "chapter-top", :format => "gif" %>') no-repeat;
	font-size: 0;
	line-height: 0;
}
.chapter-bottom {
	height: 6px;
	width: 258px;
	display: block;
	background: url('<%= url_for :action => "images", :id => "chapter-bottom", :format => "gif" %>') no-repeat;
	font-size: 0;
	line-height: 0;
}
.chapters {
	width: 233px;
	background: url('<%= url_for :action => "images", :id => "chapter-repeat", :format => "gif" %>') repeat-y;
	color: #565a5c;
	padding: 18px 0 100px 25px;
}
.chapters h3 {
	font-size: 18px;
	padding: 0 0 19px 0;
}
.chapters ol {
	font-size: 12px;
	color: #565a5c;
	line-height: 20px;
	list-style: none;
}
.chapters ol li {
	font-weight: bold;
	padding: 0 0 16px 0;
}
.chapters ol li a {
	color: #007c92;
	text-decoration: none;
}
.chapters ol li a:hover, .chapters ol li a.active {
	color: #565a5c;
}
.chapters ol li ol {
	padding: 0 0 0 17px;
}
.chapters ol li ol li {
	font-weight: normal;
	padding: 0;
}
.chapters ol li ol li a:hover, .chapters ol li ol li a.active {
	color: #ab5800;
}



.lang-control {
	padding: 0 0 20px 0;
}
.lang-active {
	color: #000 !important;
	cursor: default;
	text-decoration: none !important;
}
.lang-separator {
	color: #e4e4e4;
	padding: 0 3px;
}
.fix-me {
	height: 20px;
	display: inline-block;
	float: right;
	padding: 0 0 0 30px;
	background: url('<%= url_for :action => "images", :id => "fix-me-left", :format => "gif" %>') no-repeat left;
	font-weight: bold;
	color: #fff !important;
	text-decoration: none !important;
	margin: -4px 0 0 0;
}
.fix-me span {
	height: 19px;
	display: inline-block;
	padding: 1px 13px 0 0;
	background: url('<%= url_for :action => "images", :id => "fix-me-right", :format => "gif" %>') no-repeat right;
}



/* Code snippets */


table.snippet,
table.snippet th,
table.snippet td {
    margin:0 0 20px -36px;
    padding:0;
    border:hidden;
    background:none;
}

table.snippet code{
  font-size:12px;
  font-family:monaco,"Courier New",Courier,monospace;
  line-height:8px;
}

table.snippet td.snippet-cell{
    width:100%;
}

.code-snippet-title {
  height: 19px;
  font-weight: bold;
  font-size: 11px;
  color: #fff;
  font-family: "Courier New", Courier, monospace;
  letter-spacing: 1px;
  padding: 0 0 0 10px;
}


.code-snippet-title span {
  width: 80px;
  height:17px;
  text-align: center;
  background: #999;
  display: block;
  padding: 2px 0 0 0;
}

.code-snippet-title span.snippet-title-php{
  background:#009df6;
  width: 50px; 
}

.code-snippet-title span.snippet-title-shell {
  background: #333;
}

.code-snippet-title span.snippet-title-sql {
  background: #f8008b;
  width: 50px; 
}

.code-snippet-title span.snippet-title-js {
  background: #565a5c;
}

.code-snippet-title span.snippet-title-html {
  background: #009df6;
}

.code-snippet-title span.snippet-title-tpl {
  background: #eb7708;
  width: 50px; 
}

table.snippet .code-snippet-holder {
  margin-bottom:24px;
  font-family:monaco,"Courier New",Courier,monospace;
  font-size:12px;
  line-height:10px;
}

table.snippet .line-numbers {
  background: #eeeeee;
  color: #777;
  text-align: center;
  padding: 10px 0 20px 0;
  width: 30px;
}

table.snippet .snippet-cell{
  background: #fff;
  padding: 10px 0 0 12px;
  color: #333;
}

table.snippet td.snippet-separator{
  padding:0 2px;
}

table.snippet .code-snippet-php{
  background: #e6f7f9;
}
table.snippet .code-snippet-php-separator {
  background: #cceff4;
}

table.snippet .code-snippet-sql {
  background: #ffe8f5;
}
table.snippet .code-snippet-sql-separator {
  background: #f8bdde;
}

table.snippet .code-snippet-html {
  background: #e1eef6;
}
table.snippet .code-snippet-html-separator {
  background: #b0dcf6;
}
table.snippet .html-code-color-1 {
  color: #0600ff;
}
table.snippet .html-code-color-2 {
  color: #007c92;
}
table.snippet .code-snippet-tpl {
  background: #fbecdc;
}
table.snippet .code-snippet-tpl-separator {
  background: #ebcaab;
}
table.snippet .tpl-code-color-1 {
  color: #0600ff;
}
table.snippet .tpl-code-color-2 {
	color: #007c92;
}
table.snippet .code-snippet-shell {
  background: #565a5c;
  padding: 10px 0 0 7px;
  color: #fff;
}
table.snippet .code-snippet-shell-separator {
  background: #414446;
}
table.snippet .code-snippet-js {
  background: #eeeeee;
}
table.snippet .code-snippet-js-separator {
  background: #dadada;
}
table.snippet .js-code-color-1 {
	color: #0600ff;
}
table.snippet .js-code-color-2 {
	color: #007c92;
}
.copy-button {
	padding: 10px 0;
}
.copy-button a {
	height: 25px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-left", :format => "gif" %>') no-repeat;
	padding: 0 0 0 20px;
	font-family: "Lucida Grande", "Lucida Sans Unicode", Arial, Verdana, sans-serif;
	font-weight: bold;
	text-decoration: none !important;
	font-size: 11px;
}
.copy-button a span {
	height: 21px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-right", :format => "gif" %>') no-repeat right;
	padding: 4px 20px 0 0;
	cursor: pointer;
}
.copy-button-2 {
	padding: 10px 0;
}
.copy-button-2 a {
	height: 25px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-left-2", :format => "gif" %>') no-repeat;
	padding: 0 0 0 20px;
	font-family: "Lucida Grande", "Lucida Sans Unicode", Arial, Verdana, sans-serif;
	font-weight: bold;
	text-decoration: none !important;
	font-size: 11px;
}
.copy-button-2 a span {
	height: 21px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-right-2", :format => "gif" %>') no-repeat right;
	padding: 4px 20px 0 0;
	cursor: pointer;
}

/* Highlighted boxes: Warning, info, notes and tips boxes */

.highlighted-box {
  padding: 14px 27px !important;
  font-size: 12px;
  position: relative;
  margin: 0 0 25px 0;
}
.highlighted-box img {
  position: absolute;
  top: -13px;
  left: -9px;
}

.tip-box {
	background: #fbf4db;
}
.warning-box {
	background: #ffe9e9;
}
.note-box {
	background: #e6f7f9;
}
.info-box {
	background: #e1f1fa;
}


.tab-navigation {
	border-top: #dadada 1px solid;
	border-bottom: #dadada 1px solid;
	border-left: #dadada 1px solid;
	height: 27px;
	background: url('<%= url_for :action => "images", :id => "tab-navigation-bg", :format => "gif" %>') repeat-x;
	margin: 0 0 1px 0;
}
.tab-navigation ul {
	list-style: none;
	font-size: 12px;
}
.tab-navigation ul li {
	float: left;
	border-right: #dadada 1px solid;
	height: 22px;
	padding: 5px 8px 0 8px;
}
.tab-navigation ul li.last {
	padding: 5px 10px 0 10px;
}
.tab-content-box {
	height: 241px;
	border: #737373 1px solid;
	padding: 9px 48px 0 10px;
}
.tab-content-box p {
	padding: 0 0 10px 0 !important;
	line-height: normal !important;
}
.lang-tab {
	list-style: none;
	float: left;
}
.lang-tab li {
	float: left;
	width: 70px;
	padding: 0 1px 0 0;
}
.lang-tab li a {
	width: 70px;
	display: block;
	height: 25px;
	text-align: center;
	font-size: 12px;
	text-decoration: none !important;
	background: #eeeeee;
	padding: 5px 0 0 0;
}
.lang-tab li a:hover, .lang-tab li a.active {
	background: #565a5c;
	color: #fff !important;
}
.tab-content-links {
	height: 30px;
	line-height: 28px;
	padding: 0 0 0 12px;
	width: 350px;
	float: left;
}
.tab-link-left {
	float: left;
}
.tab-link-right {
	float: right;
}
.tab-content-button {
	padding: 30px 0 0 0;
	line-height: 23px;
}
.copy-button-3 {
	float: left;
	margin-right: 10px;
}
.copy-button-3 a {
	height: 25px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-left", :format => "gif" %>') no-repeat;
	padding: 0 0 0 20px;
	font-family: "Lucida Grande", "Lucida Sans Unicode", Arial, Verdana, sans-serif;
	font-weight: bold;
	text-decoration: none !important;
	font-size: 11px;
}
.copy-button-3 a span {
	height: 25px;
	display: inline-block;
	background: url('<%= url_for :action => "images", :id => "copy-button-right", :format => "gif" %>') no-repeat right;
	padding: 0px 20px 0 0;
	cursor: pointer;
}
#label-box {
	padding: 48px 0 0 0;
}
#label-box ul {
	list-style: none;
	font-size: 12px;
}
#label-box ul li.label-left {
	float: left;
	width: 101px;
	background: url('<%= url_for :action => "images", :id => "label-left", :format => "gif" %>') no-repeat;
	height: 30px;
	font-weight: bold;
	color: #fff;
	line-height: 27px;
	padding: 0 0 0 11px;
	margin-bottom: 1px;
}
#label-box ul li.label-right {
	float: left;
	width: 450px;
	background: url('<%= url_for :action => "images", :id => "label-right", :format => "gif" %>') no-repeat;
	height: 23px;
	color: #565a5c;
	padding: 7px 0 0 11px;
	margin-bottom: 1px;
}
#label-box ul li.label-right-2 {
	float: left;
	width: 316px;
	background: url('<%= url_for :action => "images", :id => "label-right-2", :format => "gif" %>') no-repeat;
	height: 68px;
	color: #565a5c;
	padding: 7px 134px 0 11px;
	margin-bottom: 1px;
	line-height: 14px;
}
.prvs-link {
	background: url('<%= url_for :action => "images", :id => "prvs-arrow", :format => "gif" %>') no-repeat left;
	display: inline-block;
	height: 23px;
	padding: 4px 0 0 24px;
	float: left;
}
.prvs-link span {
	color: #565a5c;
}
.next-link {
	background: url('<%= url_for :action => "images", :id => "next-arrow", :format => "gif" %>') no-repeat right;
	display: inline-block;
	height: 23px;
	padding: 4px 24px 0 0;
	float: right;
}
.next-link span {
	color: #565a5c;
}
/* Content CSS End */


/* Footer CSS Start */
#footer {
	font-size: 11px;
	color: #565a5c;
	padding: 35px 0 0 0;
	bottom: -140px;
	position: absolute;
	width: 100%;
}
#footer a {
	color: #007c92;
	text-decoration: none;
}
#footer a:hover {
	text-decoration: underline;
}
#footer p {
	padding: 25px 0 0 0;
	float: left;
}
.inner-footer {
	width: 924px;
	height: 114px;
	margin: 0 auto;
}
.footer-logo {
	float: right;
	padding: 32px 0 0 0;
}
.like-page {
	font-weight: bold;
	font-size: 11px;
	color: #565a5c;
}
.like-page span {
	display: block;
	float: left;
	line-height: 21px;
}
.like-page img {
	margin: 0 0 0 18px;
}
/* Footer CSS End */




/* Table Content CSS Start */
#table-content {
	background: #565a5c;
	width: 100%;
}
.inner-table-content {
	width: 924px;
	margin: 0 auto;
}
.inner-table-content ul {
	list-style:none;
	margin:0;
	padding:0;
}
.inner_col {
	width:200px;
	margin:0 20px 0 0;
	padding:10px 0 20px 10px;
	float:left;
	border-right:1px solid #3d3d3d;
}
.inner_col ul li {
	background:url('<%= url_for :action => "images", :id => "arrrow1", :format => "gif" %>') no-repeat 0px 10px;
	margin:0;
	padding:2px 0 0 15px;
}
.inner_col ul li a {
	text-decoration:none;
	color:#fff;
	margin:0;
	padding:0;
	font-size:12px;
}
.inner_col ul li a:hover {
	text-decoration:underline;
}
.inner_col h2 {
	font-size:14px;
	font-weight:bold;
	color: #fff;
	margin:0 0 0 2px;
	padding:20px 0 0px 0;
}
.border {
	border:0;
}
/* Table Content CSS End */



.radius_5 {
	-moz-border-radius: 5px;
	-khtml-border-radius: 5px;
	-webkit-border-radius: 5px;
	border-radius: 5px;
}

.radius_3 {
	-moz-border-radius: 3px;
	-khtml-border-radius: 3px;
	-webkit-border-radius: 3px;
	border-radius: 3px;
}

.remove_bottom_radius{
	-moz-border-radius-bottomleft: 0px;
	-khtml-border-bottom-left-radius: 0px;
	-webkit-border-bottom-left-radius: 0px;
	border-bottom-left-radius: 0px;
	-moz-border-radius-bottomright: 0px;
	-khtml-border-bottom-right-radius: 0px;
	-webkit-border-bottom-right-radius: 0px;
	border-bottom-right-radius: 0px;	
}

.remove_top_radius{
	-moz-border-radius-topleft: 0px;
	-khtml-border-top-left-radius: 0px;
	-webkit-border-top-left-radius: 0px;
	border-top-left-radius: 0px;
	-moz-border-radius-topright: 0px;
	-khtml-border-top-right-radius: 0px;
	-webkit-border-top-right-radius: 0px;
	border-top-right-radius: 0px;	
}


/* Terminal */

#terminal_canvas {
    color:#fff;
    background:#000;
    position:relative;
    min-height:400px;
    max-height:520px;
    font-family:courier;
}
.terminal_output{
    overflow:auto;
    padding:5px;
    height:90%;
    min-height:20px;
    max-height:490px;
}

.terminal_output .history{
  color:#ff0;
}
.terminal_output .history span{
  color:#666;
}

.prompt{
  color:#ff0;
  padding:5px;
  margin-top:-20px;
}
.prompt span{
  color:#666;
}
.prompt input{
    font-size:14px;
    font-weight:bold;
    background-color:#000;
    border:none;
    color:#ff0;
    width:800px;
}




.application_name{
  float:right;
  color:#666;
  margin:-75px 10px 0 0;
}

.wide-content{
  padding:0 0 15px 0;
  color:#222;
}
.wide-content p{
  padding:0 0 15px 0;
  font-size:13px;
}


.wide-content h1{
  font-size:22px;
  padding-bottom:10px;
}

.flash{
  border:1px solid #ccc;
  width:100%;
  background:#fff;
  font-size:10px;
}

.flash.warning{
  margin:0;
  padding:0;
  background:#ffdede;
  border:1px solid #ffdede;
  color:#c00;
}

.flash.notice{
  border:1px solid #fdf7d6;
  background:#fdf7d6;
  color:#222;
}
.flash{
  margin-bottom:2px;
}

.flash p span.icon{
  padding:0;
  margin:0 5px 0 0;
  display:block;
  float:left;
  overflow:hidden;
  width:20px;
  height:20px;
  text-indent:30px;
}

.flash p{
  padding:8px;
}

.flash.warning p span.icon{
   background:#ffdede  url('<%= url_for :action => "images", :id => "akelos_panel_sprite", :format => "png" %>') no-repeat -4px -49px;
}

.flash.notice p span.icon{
    background:#fdf7d6  url('<%= url_for :action => "images", :id => "akelos_panel_sprite", :format => "png" %>') no-repeat -4px -13px;
}



tt {
	font-family: monaco, "Bitstream Vera Sans Mono", "Courier New", courier, monospace;
}

div.code_container {
  padding:0 15px 20px 0;
}
.snippet-corner{
  float:right;
  margin:-15px -15px 0 0;
}

code {
    font-size:12px;
    border: none;
    display: block;
}


/* Main content for the Akelos panel */
div.main-content strong{
  font-weight:bold;
}

div.main-content tt{
  font-weight:bold;
  font-family: "Courier New", courier, monospace;
}

div.main-content h3{
  margin:25px 0 0 0;
}

div.main-content h2{
  margin:25px 0 0 0;
}

div.main-content .radius_5{
 padding:10px;
 background-color:#fff;
 border:1px solid #ccc;
}

div.main-content dl{
  font-size:12px;
  line-height:25px;
}

div.main-content dt{
  float:left;
  margin-right:10px;
}
div.main-content dd{
  color: #007c92;
}


/* Blue bullet points */


.important-item-list{
  padding-bottom:20px;
  color:#333;
}
.important-item-list h3 {
	font-size: 12px;
	padding: 0 0 8px 0;
}
.important-item-list ul {
	list-style: none;
	line-height: 20px;
	font-size: 14px;
}
.important-item-list ul li {
	background: url('<%= url_for :action => "images", :id => "category-bullet", :format => "gif" %>') no-repeat left 6px;
	line-height: 20px;
	padding: 0 0 0 16px;
}
.important-item-list ul li a {
	color: #007c92;
	text-decoration: none;
}
.important-item-list ul li a:hover {
	text-decoration: underline;
}


div.flash{
  margin-bottom:4px !important;
}


.tweets h3{
    font-size:12px;
    color:#666;
}

.tweets h3 span{
  width:25px;
  height:20px;
  display:block;
  float:left;
  background:transparent url('<%= url_for :action => "images", :id => "akelos_panel_sprite", :format => "png" %>') no-repeat -4px -152px;
}

.tweet {
	background: url('<%= url_for :action => "images", :id => "entry-bg", :format => "gif" %>') no-repeat bottom;
	padding: 20px;
	margin-bottom: 6px;
}
.tweet p {
	font-size: 12px;
	color: #565a5c;
}
.tweet a {
	color: #007c92;
	text-decoration: none;
}
.tweet a:hover {
	text-decoration: underline;
}
.tweet p strong {
	font-weight: strong;
	color: #007c92;
}



div.quickstart .code-snippet-title{
  margin-top:15px;
}


div.quickstart li{
  padding-bottom:10px;
}


ins,
tt{
 text-decoration:none;
 background:#e9e9e9;
 color:#000;
 font-size:13px;
 padding:2px;
 font-family: monospace;
}

.only-print {
  display: none !important;
}