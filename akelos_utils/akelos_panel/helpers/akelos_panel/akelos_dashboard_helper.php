<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkelosDashboardHelper extends AkBaseHelper {
    private $_capturing_snippet = false;

    function get_menu_option($description, $url, $options = array()){
        if(!empty($url['action']) && ($url['action'] == $this->C->params['action'] || $url['action'] == @$this->C->tab)){
            $options['class'] = 'active';
        }

        //<li><a href="#"><span>Contribute</span></a></li>
        return AkTagHelper::content_tag('li',
        $this->C->url_helper->link_to(
        AkTagHelper::content_tag('span',$description), $url, $options)
        );
    }

    public function flash_message($type, $message){
        return '<div class="flash radius_3 '.$type.'">'.
        '<p><span class="icon">'.$this->t(strtoupper($type)).': </span>'.
        str_replace("\n", "<br />", AkTextHelper::html_escape($message)).
        '</p></div>';
    }
    
    public function flash_error($message){
        return $this->flash_message('error', $message);
    }
    
    public function flash_notice($message){
        return $this->flash_message('notice', $message);
    }
    
    public function flash_warning($message){
        return $this->flash_message('warning', $message);
    }
    
    public function get_twitter_feeds($screen_name = 'akelos'){
        return json_decode(Ak::url_get_contents('http://twitter.com/statuses/user_timeline/'.$screen_name.'.json?count=4', array('cache'=>240)));
    }

    public function capture_snippet($snippet_type = true){
        $this->_capturing_snippet = $snippet_type;
        $this->_controller->ak_capture_helper->begin('snippet');
    }

    public function format_snippet($code = '', $snippet_type = ''){
        if($this->_capturing_snippet){
            $snippet_type = $this->_capturing_snippet;
            $code = $this->_controller->ak_capture_helper->end(false);
            $this->_capturing_snippet = false;
        }

        $code = trim($code);

        $lines = explode("\n", $code."\n");
        array_pop($lines);

        $snippet_type_set = !empty($snippet_type);
        $snippet_type = empty($snippet_type) ? 'php' : $snippet_type;
        $snippet_type_uc = strtoupper($snippet_type);
        $snippet_lines = '';

        $total = count($lines);
        $line_numbers = join("<br />", range(1, $total));
        $code = str_replace(array("\n"), array('<br />'), AkTextHelper::html_escape($code));

        $copy_button_version = in_array($snippet_type, array('shell')) ? '-2' : '';

        $snippet_corner_image = $this->_controller->ak_url_helper->url_for(array('action' => "images", 'controller' => 'virtual_assets', 'module' => 'akelos_panel', 'id' => "$snippet_type-box-corner", 'format' => "gif"));

        $snippet_type_description = (!$snippet_type_set ? '' : "<div class=\"code-snippet-title\"><span class=\"snippet-title-$snippet_type\">$snippet_type_uc</span></div>");

        $snippet_template = <<<SNIPPET
      $snippet_type_description
      <div class="code-snippet-holder">
        <table width="100%" border="0" cellspacing="0" cellpadding="0">
        <tr>
            <td align="left" valign="top"><pre class="line-numbers"><code>$line_numbers</code></pre></td>
            <td class="code-snippet-$snippet_type-separator">&nbsp;</td>
            <td align="left" valign="top" class="code-snippet-$snippet_type snippet-cell"><div class="code_container"><pre><code class="$snippet_type">$code</pre></code></div>
            <img src="$snippet_corner_image" width="30" height="35" alt="" class="snippet-corner" />
            </td>
          </tr>
        </table>
      </div>
SNIPPET;
        return $snippet_template;
    }
}

