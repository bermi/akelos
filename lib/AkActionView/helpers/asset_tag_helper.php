<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionView
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'tag_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'url_helper.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

if(!defined('JAVASCRIPT_DEFAULT_SOURCES')){
    define('JAVASCRIPT_DEFAULT_SOURCES','prototype,event_selectors,scriptaculous');
}

/**
 * Provides methods for linking a HTML page together with other assets, such as javascripts, stylesheets, and feeds.
 */ 
class AssetTagHelper extends AkActionViewHelper 
{
    
    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }

    /**
     * Returns a link tag that browsers and news readers can use to auto-detect a RSS or ATOM feed for this page. The +type+ can
     * either be <tt>'rss'</tt> (default) or <tt>'atom'</tt> and the +options+ follow the $controller->urlFor style of declaring a link target.
     *
     * Examples:
     *   $asset_tag_helper->auto_discovery_link_tag(); # =>
     *     <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/action" />
     *   $asset_tag_helper->auto_discovery_link_tag('atom'); # =>
     *     <link rel="alternate" type="application/atom+xml" title="ATOM" href="http://www.curenthost.com/controller/action" />
     *   $asset_tag_helper->auto_discovery_link_tag('rss', array('action' => 'feed')); # =>
     *     <link rel="alternate" type="application/rss+xml" title="RSS" href="http://www.curenthost.com/controller/feed" />
     *   $asset_tag_helper->auto_discovery_link_tag('rss', array('action'=>'feed'), array('title'=>'My RSS')); # =>
     *     <link rel="alternate" type="application/rss+xml" title="My RSS" href="http://www.curenthost.com/controller/feed" />
     */
    function auto_discovery_link_tag($type = 'rss', $url_options = array(), $tag_options = array())
    {
        return TagHelper::tag(
        'link',
        array(
        'rel' => empty($tag_options['rel']) ? 'alternate' : $tag_options['rel'],
        'type'  => empty($tag_options['type']) ? "application/$type+xml" : $tag_options['type'],
        'title' => empty($tag_options['title']) ? strtoupper($type) : $tag_options['title'],
        'href'  => is_array($url_options) ? $this->_controller->urlFor(array_merge($url_options,array('only_path'=>false))) : $url_options
        )
        );
    }

    /**
       * Returns path to a javascript asset. Example:
       *
       *   $asset_tag_helper->javascript_path('xmlhr'); # => /javascripts/xmlhr.js
       */
    function javascript_path($source)
    {
        return $this->_compute_public_path($source, 'javascripts', 'js');
    }


    /**
        * Returns a script include tag per source given as argument. Examples:
        *
        *   $asset_tag_helper->javascript_include_tag ("xmlhr"); # =>
        *     <script type="text/javascript" src="/javascripts/xmlhr.js"></script>
        *
        *   $asset_tag_helper->javascript_include_tag('common.javascript', '/elsewhere/cools'); # =>
        *     <script type="text/javascript" src="/javascripts/common.javascript"></script>
        *     <script type="text/javascript" src="/elsewhere/cools.js"></script>
        *
        *   $asset_tag_helper->javascript_include_tag('defaults'); # =>
        *     <script type="text/javascript" src="/javascripts/prototype.js"></script>
        *     <script type="text/javascript" src="/javascripts/effects.js"></script>
        *     ...
        *     <script type="text/javascript" src="/javascripts/application.js"></script> *see below
        *   
        * If there's an <tt>application.js</tt> file in your <tt>public/javascripts</tt> directory,
        * <tt>$asset_tag_helper->javascript_include_tag('defaults')</tt> will automatically include it. This file
        * facilitates the inclusion of small snippets of JavaScript code, along the lines of
        * <tt>controllers/application.php</tt> and <tt>helpers/application_helper.php</tt>.
        */
    function javascript_include_tag()
    {
        $sources = func_get_args();
        $num_args = func_num_args();
        $options = !empty($sources[$num_args-1]) && is_array($sources[$num_args-1]) ? array_pop($sources) : array();
        if(empty($sources) || $sources[0] == 'defaults'){
            $sources = $this->_get_javascript_included_defaults();
        }
        $javascript_include_tags = '';
        foreach ($sources as $source){
            $source = $this->javascript_path($source);
            $javascript_include_tags .= TagHelper::content_tag('script', '', array_merge($options,array('type'=>'text/javascript','src'=>$source)))."\n";
        }
        return $javascript_include_tags;
    }
    
    function _get_javascript_included_defaults()
    {
        static $defaults, $plugin_defaults = array();
        if(empty($defaults)){
            $defaults = array_unique(array_diff(array_filter(explode(',',JAVASCRIPT_DEFAULT_SOURCES.
            ','.(file_exists(AK_PUBLIC_DIR.DS.'javascript'.DS.'application.js') ? 'application' : '' )
            ),'trim'),array('')));
        }
        if(func_num_args()){
            $plugin_defaults = func_get_arg(0) === false ? array() : func_get_args();
        }
        return array_merge($defaults, $plugin_defaults);
    }

    /**
       * Register one or more additional JavaScript files to be included when
       *   
       *   javascript_include_tag :defaults
       *
       * is called. This method is intended to be called only from plugin initialization
       * to register extra .js files the plugin installed in <tt>public/javascripts</tt>.
       */
    function register_javascript_include_default($sources)
    {
        $this->_get_javascript_included_defaults($sources);
    }

    function reset_javascript_include_default()
    {
        $this->_get_javascript_included_defaults(false);
    }

    /**
        * Returns path to a stylesheet asset. Example:
        *
        *   $asset_tag_helper->stylesheet_path('style'); # => /stylesheets/style.css
        */
    function stylesheet_path($source)
    {
        return $this->_compute_public_path($source, 'stylesheets', 'css');
    }

    /**
     * Returns a css link tag per source given as argument. Examples:
     *
     *   $asset_tag_helper->stylesheet_link_tag('style'); # =>
     *     <link href="/stylesheets/style.css" media="screen" rel="Stylesheet" type="text/css" />
     *
     *   $asset_tag_helper->stylesheet_link_tag('style', array('media'=>'all')); # =>
     *     <link href="/stylesheets/style.css" media="all" rel="Stylesheet" type="text/css" />
     *
     *   $asset_tag_helper->stylesheet_link_tag('random.styles', '/css/stylish'); # =>
     *     <link href="/stylesheets/random.styles" media="screen" rel="Stylesheet" type="text/css" />
     *     <link href="/css/stylish.css" media="screen" rel="Stylesheet" type="text/css" />
     */
    function stylesheet_link_tag()
    {
        $sources = func_get_args();
        $num_args = func_num_args();
        
        $options = (!empty($sources[$num_args-1]) && is_array($sources[$num_args-1])) ? array_pop($sources) : array();

        $style_include_tags = '';
        foreach ($sources as $source){
            $source = $this->stylesheet_path($source);
            $style_include_tags .= TagHelper::tag('link', array_merge(array('rel'=>'Stylesheet','type'=>'text/css','media'=>'screen','href'=>$source),$options))."\n";
        }
        return $style_include_tags;
    }

    /**
       * Returns path to an image asset. Example:
       *
       * The +src+ can be supplied as a...
       * * full path, like "/my_images/image.gif"
       * * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
       * * file name without extension, like "logo", that gets expanded to "/images/logo.png"
       */
    function image_path($source)
    {
        return $this->_compute_public_path($source, 'images', 'png');
    }

    /**
        * Returns an image tag converting the +options+ into html options on the tag, but with these special cases:
        *
        * * <tt>alt</tt>  - If no alt text is given, the file name part of the +src+ is used (capitalized and without the extension)
        * * <tt>size</tt> - Supplied as "XxY", so "30x45" becomes width="30" and height="45"
        *
        * The +src+ can be supplied as a...
        * * full path, like "/my_images/image.gif"
        * * file name, like "rss.gif", that gets expanded to "/images/rss.gif"
        * * file name without extension, like "logo", that gets expanded to "/images/logo.png"
        */
    function image_tag($source, $options = array())
    {
        if(!empty($options['size'])){
            list($options['width'], $options['height']) = split('x|X| ',trim(str_replace(' ','',$options['size'])));
            unset($options['size']);
        }
        $options['src'] = $this->image_path($source);
        $options['alt'] = !empty($options['alt']) ? $options['alt'] : AkInflector::titleize(substr(basename($options['src']),0,strpos(basename($options['src']),'.')),'first');

        return TagHelper::tag('img', $options);
    }

    function _compute_public_path($source, $dir = '', $ext = '')
    {
        $source = $source[0] != '/' && !strstr($source,':') ? "/$dir/$source" : $source;
        $source = !strstr($source,'.') ? "$source.$ext" : $source;
        $source = !preg_match('/^[-a-z]+:\/\//',$source) ? AK_ASSET_URL_PREFIX.$source : $source;
        $source = strstr($source,':') ? $source : $this->_controller->asset_host.$source;
        $source = substr($source,0,2) == '//' ? substr($source,1) : $source;
        
        return $source;
    }
    
    function stylesheet_for_current_controller()
    {
        $stylesheet = AkInflector::underscore($this->_controller->getControllerName()).'.css';
        if(file_exists(AK_PUBLIC_DIR.DS.'stylesheets'.DS.$stylesheet)){
            return $this->stylesheet_link_tag($stylesheet);
        }
        return '';
    }
    
    function javascript_for_current_controller()
    {
        $js_file = AkInflector::underscore($this->_controller->getControllerName()).'.js';
        if(file_exists(AK_PUBLIC_DIR.DS.'javascripts'.DS.$js_file)){
            return $this->javascript_include_tag($js_file);
        }
        return '';
    }
}

?>
