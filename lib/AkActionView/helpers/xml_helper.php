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


/**
 * Cache Helpers lets you cache fragments of templates
*
* == Caching a block into a fragment
*
*   <b>Hello {name}</b>
*   <?php if (!$cache_helper->begin()) { ?>
*     All the topics in the system:
*     <?= $controller->renderPartial("topic", $Topic->findAll()); ?>
*   <?= $cache_helper->end();} ?>
*  
*
*
*   Normal view text
*/

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');
/**
 * <%= xml_instruct 'xml', :version => "1.0" %> 
<%= xml_rss_open :version => "2.0" %>
  <%= xml_channel_open %>
    <%= xml_title "Articles" %>
    <%= xml_description "Lots of articles" %>
    <%= xml_link formatted_articles_url(:rss) %>
    
    {loop articles}
      <%= xml_item_open %>
        <%= xml_title article.name %>
        <%= xml_description article.content %>
        <%= xml_pubDate article.created_at.to_s(:rfc822) %>
        <%= xml_link formatted_article_url(article, :rss) %>
        <%= xml_guid formatted_article_url(article, :rss) %>
      <%= xml_item_close %>
    {end}
  <%= xml_channel_close %>
<% xml_close %>
 * 
 *
 */
class XmlHelper extends AkObject 
{
    var $dynamic_helpers = array('xml_.*?');
    
    function rss2_builder($items = array(), $channel_options = array(), $item_options = array())
    {
        $return = array();
        
        $default_channel_options = array('title'=>'Please put your custom channel title in $channel_options[title]',
                                         'description'=>'Please put your custom channel description in $channel_options[description]',
                                         'link'=>'http://example.com/put/your/custom/link/here',
                                         'language'=>Ak::base_lang());
        $channel_parameters = array('available_options'=>array('title','description','link','language'));
        Ak::parseOptions($channel_options,$default_channel_options, $channel_parameters);
        
        $default_item_options = array('title'=>'name','description'=>'description','date'=>'created_at','author'=>false,'link'=>true,'guid'=>false);
        $item_parameters = array('available_options'=>array('title','description','link','date','author','guid'));
        Ak::parseOptions($item_options,$default_item_options, $item_parameters);
        
        $return[] = $this->xml_instruct('xml',array('version'=>'1.0'));
        $return[] = $this->xml_rss_open( array('version' => "2.0", 'xmlns' => array('dc' => "http://purl.org/dc/elements/1.1/", 'atom' => "http://www.w3.org/2005/Atom")));
        $return[] = $this->xml_channel_open();
        $return[] = $this->xml_title($channel_options['title']);
        $return[] = $this->xml_description($channel_options['description']);
        $return[] = $this->xml_link(htmlentities($channel_options['link']));
        $return[] = $this->xml_language($channel_options['language']);
        $items = Ak::toArray($items);
        
        foreach($items as $item) {
            $return[] = $this->xml_item_open();
            if(!empty($item_options['title'])) {
                $title = $this->_getValue($item, $item_options['title']);
                !empty($title)? $return[]=$this->xml_title($title):null;
            }
            if(!empty($item_options['link'])) {
                $item_link = $this->_getValue($item, $item_options['link']);
                !empty($item_link)? $return[]=$this->xml_link($item_link):null;
            }
            if(!empty($item_options['guid'])) {
                $guid = $this->_getValue($item, $item_options['guid']);
                
                !empty($guid)? $return[]=$this->xml_guid($guid,strstr($guid,'http://')?array():array('isPermaLink'=>'false')):(!empty($item_link)? $return[]=$this->xml_guid($item_link,strstr($item_link,'http://')?array():array('isPermaLink'=>'false')):null);
            }
            if(!empty($item_options['description'])) {
                $description = $this->_getValue($item, $item_options['description']);
                
                !empty($description)? $return[]=$this->xml_description('<![CDATA['.$description.']]>'):null;
            }
            if(!empty($item_options['date'])) {
                $created_at = $this->_getValue($item, $item_options['date']);
                !empty($created_at)? $return[]=$this->xml_dc__date($this->_generate_date($created_at)):null;
            }
            if(!empty($item_options['author'])) {
                $author = $this->_getValue($item, $item_options['author']);
                !empty($author)? $return[]=$this->xml_dc__author($author):null;
            }
            $return[] = $this->xml_item_close();
        }
        
        $return[] = $this->xml_channel_close();
        $return[] = $this->xml_rss_close();
        return implode("\n",$return);
    }
    
    function _getValue($obj, $identifier)
    {
        $value = false;
        if(is_array($identifier)) {
            $value = '';
            foreach($identifier as $id) {
                $value.=$this->_getValue($obj, $id);
            }
            return $value;
        }
        if (strstr($identifier,'->')) {
            while(strstr($identifier,'->')) {
                list($getter,$identifier) = split('->',$identifier,2);
                $args = array();
                if (preg_match('/^([\w_]+)\((.*?)\)$/',$getter,$matches)) {
                    $args = $matches[2];
                    $getter = $matches[1];
                    $arg_eval = '$args = array('.$args.');';
                    @eval($arg_eval);
                }
                if ((is_object($obj) && method_exists($obj,$getter)) || (method_exists($obj,'isCallable') && $obj->isCallable($getter))) {
                    $obj = call_user_func_array(array($obj,$getter),$args);
                } else if (!empty($obj->$getter)) {
                    $obj = $obj->$getter;
                }
            }
        }
        $args = array();
        if (preg_match('/^([\w_]+)\((.*?)\)$/',$identifier,$matches)) {
            $args = $matches[2];
            $identifier = $matches[1];
            //$this->log('Identified method:'.$identifier.' with args:'.var_export($args,true));
            $arg_eval = '$args = array('.$args.');';
            @eval($arg_eval);
            //$this->log('Identified method:'.$identifier.' with args:'.var_export($args,true));
        }
        if ((is_object($obj) && method_exists($obj,$identifier)) || (method_exists($obj,'isCallable') && $obj->isCallable($identifier))) {
            $value = call_user_func_array(array($obj,$identifier),$args);
             //$this->log('Calling method:'.$identifier.' with args:'.var_export($args,true).' got:'.var_export($value,true));
        } else if(method_exists($obj,'get') && is_string($identifier)){
            $value = $obj->get($identifier);
        } else if(is_object($obj)){
            $value = isset($obj->$identifier)?$obj->$identifier:false;
        } else if (is_array($obj)) {
            $value = isset($obj[$identifier])?$obj[$identifier]:false;
        } else {
            $value = $identifier;
        }
        
        if (empty($value) && is_string($identifier)) {
            $value = $identifier;
        }
        return $value;
    }
    
    function _generate_date($db_date)
    {
        //1998-05-12T14:15:00
        if (!is_int($db_date)) {
            $db_date=strtotime($db_date);
        }
        return Ak::getDate($db_date,'Y-m-d\Th:i:sP');

    }
    function setController(&$controller)
    {
        $this->_controller =& $controller;
    }
    
    function xml_instruct($type, $options = array()) 
    {
        $default_options = array('version'=>'1.0');
        $config = array('available_options'=>array('version'));
        Ak::parseOptions($options,$default_options,$config);
        return $this->_renderTag(false,$type,'',$options,'<?','?>','?>');
    }
    function __call($name,$args)
    {
        if(preg_match('/^xml_([\w_]+?)_(open|close)$/',$name,$matches)) {
            $tagName = str_replace('-',':',$matches[1]);
            $name_space = false;
            $tagNames = split('__',$tagName);
            if (count($tagNames)>1) {
                $name_space = array_shift($tagNames);
                $tagName = join('__',$tagNames);
            }
            $open = $matches[2]=='open';
            $close = !$open;
            $content=array_shift($args);
            $options = array();
            if (!empty($args)) {
                $options = array_shift($args);
            }
            if(is_array($content)) {
                $options = $content;
                $content = null;
            }
            if($open) {
                return $this->_renderTag($name_space,$tagName,$content,$options,'<','>','>');
            } else {
                return $this->_renderTag($name_space,$tagName,null,array(),'</','>','>');
            }
        } else if (preg_match('/^xml_([\w]+)$/',$name,$matches)) {
            $tagName =$matches[1];
            $name_space = false;
            $tagNames = split('__',$tagName);
            if (count($tagNames)>1) {
                $name_space = array_shift($tagNames);
                $tagName = join('__',$tagNames);
            }
            $content=array_shift($args);
            $options = array();
            if (!empty($args)) {
                $options = array_shift($args);
            }
            if(is_array($content)) {
                $options = $content;
                $content = null;
            }
            return $this->_renderTag($name_space,$tagName,$content,$options,'<','>','/>');
        }
    }
    function _renderTag($name_space, $tagName, $content = null, $attributes = array(), $open = '<', $close = '>', $closeTag='/>')
    {
        $attribute_array = array();
        if (is_array($attributes) && count($attributes)>0) {
            
            foreach($attributes as $name => $value) {
                if(is_string($value)) {
                    
                    $attribute_array[]=$name.'="'.htmlentities($value).'"';
                } else if (is_array($value)) {
                    $attr_name_space = $name;
                    foreach($value as $name => $v) {
                        $attribute_array[]=$attr_name_space.':'.$name.'="'.htmlentities($v).'"';
                    }
                }
            }
            $attribute_string =' '.implode(' ',$attribute_array);
        }
        return $open.($name_space!=false?$name_space.':':'').$tagName.(count($attribute_array)>0?$attribute_string:'').($content!=null?$close.$content.$open.'/'.($name_space!=false?$name_space.':':'').$tagName.$close:$closeTag)."\n";
    }
}

?>
