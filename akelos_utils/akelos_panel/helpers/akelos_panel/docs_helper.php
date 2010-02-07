<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class DocsHelper extends AkBaseHelper
{
    public $docs_path;
    
    public function get_doc_contents($doc_name){
        $doc_file = $this->_getdocPath($doc_name, Ak::lang());
        return file_exists($doc_file) ? file_get_contents($doc_file) : @file_get_contents($this->_getdocPath($doc_name, 'en'));
    }

    public function render_doc($doc_contents){
        return $this->_afterRender(AkTextHelper::textilize($this->_beforeRender($doc_contents)));
    }
    
    public function link_to_guide($guide_name, $slug = '', $html_options = array()){
        return $this->_controller->ak_url_helper->link_to($this->t($guide_name), array('action'=>'guide', 'id' => $this->t($slug)), $html_options);
    }

    private function _getdocPath($doc_name, $language){
        $doc_name = AkInflector::underscore($language != 'en' ? Ak::untranslate($doc_name, $language) : $doc_name);
        return AkConfig::getDir('docs').DS.(empty($this->docs_path) ? '' : trim($this->docs_path, DS).DS).$language.DS.$doc_name.'.textile';
    }

    private function _afterRender($html){
        $html = $this->_highlightNotes($html);
        return $html;
    }

    private function _beforeRender($textile){
        $textile = $this->_replacePlusPlus($textile);
        $textile = $this->_rebaseImagePaths($textile);
        $textile = $this->_setCodeBlocks($textile);
        return $textile;
    }

    private function _highlightNotes($html){
        if(preg_match_all('/(IMPORTANT|CAUTION|WARNING|NOTE|INFO|TIP)(?:\.|\:)(.*)/', $html, $matches)){
            foreach ($matches[1] as $k => $class){
                $css_class = strtolower($class);
                $css_class = in_array($css_class, array('caution', 'important')) ? 'warning' : $css_class;
                $css_class = in_array($css_class, array('tip')) ? 'info' : $css_class;
                $html = str_replace($matches[0][$k], "<div class='$css_class'><p>".strip_tags($matches[2][$k]).'</p></div>', $html);
            }
        }
        return $html;
    }

    private function _replacePlusPlus($textile){
        if(preg_match_all('/\+(.+)\+/', $textile, $matches)){
            foreach ($matches[1] as $k => $tt){
                $textile = str_replace($matches[0][$k], "<notextile><tt>$tt</tt></notextile>", $textile);
            }
        }
        $textile = str_replace('<plus>', '+', $textile);
        return $textile;
    }

    private function _rebaseImagePaths($textile){
        //
        if(preg_match_all('/\!\/images\/([^\.]+)\.([^\(]+)\(/', $textile, $matches)){
            foreach ($matches[1] as $k => $name){
                $new_url = $this->C->url_helper->url_for(array(
                    'controller'=> 'virtual_assets', 
                    'action'    => 'guide_images', 
                    'id'        => $name, 
                    'format'    => $matches[2][$k],
                    ));
                $textile = str_replace($matches[0][$k], '!'.$new_url.'(', $textile);
            }
        }
        return $textile;
    }

    private function _setCodeBlocks($textile){
        if(preg_match_all('/<(yaml|shell|php|tpl|html|sql|plain)>(.*?)<\/\\1>/ms', $textile, $matches)){
            foreach ($matches[1] as $k => $class){
                $css_class = strtolower($class);
                $css_class = in_array($css_class, array('shell')) ? 'html' : $css_class;
                
                $escaped = AkTextHelper::html_escape($matches[2][$k]);
                $textile = str_replace($matches[0][$k], $this->_tabText("<notextile><div class='code_container'><code class='$css_class'>$escaped</code></div></notextile>"), $textile);
            }
        }
        return $textile;
    }
    
    private function _tabText($text){
        $lines = explode("\n", $text."\n");
        $result = array();
        foreach ($lines as $line){
            $result[] = str_repeat(' ', 4).$line;
        }
        return trim(join("\n", $result));
    }
}
