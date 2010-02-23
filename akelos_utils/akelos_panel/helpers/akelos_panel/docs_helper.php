<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class DocsHelper extends AkBaseHelper
{
    public $docs_path;
    public $insert_toc = false;
    public $prologue = '';

    public function get_doc_contents($doc_name){
        $doc_file = $this->_getdocPath($doc_name, Ak::lang());
        return file_exists($doc_file) ? file_get_contents($doc_file) : @file_get_contents($this->_getdocPath($doc_name, 'en'));
    }

    public function render_prologue($doc_contents){
        $doc_contents = explode('endprologue.', $doc_contents.'endprologue.');
        $this->insert_toc = false;
        return $this->_afterRender(AkTextHelper::textilize($this->_beforeRender($doc_contents[0])));
    }
    public function render_doc($doc_contents){
        $doc_contents = explode('endprologue.', $doc_contents.'endprologue.');
        $this->insert_toc = true;
        return $this->_afterRender(AkTextHelper::textilize($this->_beforeRender($doc_contents[1])));
    }

    public function link_to_guide($guide_name, $slug = '', $html_options = array()){
        return $this->_controller->ak_url_helper->link_to($this->t($guide_name), array('action'=>'guide', 'id' => $this->t($slug)), $html_options);
    }



    public function processIndex($string, $current_level= 3, $counters = array(1)){
        if(!is_array($string)){
            $s = explode("\n", $string);
        }else{
            $s = $string;
        }

        $level_array = array();

        while (!empty($s)) {
            $line = array_shift($s);
            if(preg_match('/^h([0-9])\.(.+)$/', $line, $matches)){
                $title = $matches[2];
                $level = intval($matches[1]);

                if($level < $current_level){
                    return $level_array;
                }elseif ($level == $current_level){
                    $index = join('.', $counters);
                    $bookmark = preg_replace('/^a-z0-9-/', '', str_replace('_', '-', AkInflector::urlize($title)));
                    $this->_result = str_replace($matches[0], "h$level(#$bookmark). {$index}{$title}", $this->_result);
                    if(count($counters) <= 2){
                        $this->toc[$counters[0]][] = array('title' => $title, 'id' => $bookmark, 'index' => $index);
                    }
                    $counters[] = 1;
                    $level_array[] = $this->processIndex($s, $current_level + 1, $counters);
                    array_pop($counters);
                    // Increment the current level
                    $last = array_pop($counters);
                    $counters[] = $last + 1;
                }
            }
        }
        return $level_array;
    }


    public function _insertTocAndPrologue($textile){

        $this->_result = $textile;
        $this->toc = array();
        $this->processIndex($textile);
        return $this->_renderToc().$this->_result;
    }

    public function _renderToc(){
        $toc = '<div id="toc">
        <h3>'.$this->t('Table of Contents').'</h3>
        <ol class="index toc">';

        foreach ($this->toc as $chapter => $details){
            $chapter_details = array_shift($details);
            $toc .= '<li class="chapter"><a href="#'.$chapter_details['id'].'">'.$chapter_details['title'].'</a>';
            if(!empty($details)){
                $toc .= '<ul class="sections">';
                foreach ($details as $detail){
                    $toc .= '<li class="section"><a href="#'.$detail['id'].'"><span class="only-print">'.$detail['index'].' </span>'.$detail['title'].'</a></li>';
                }
                $toc .= '</ul>';
            }
            $toc .= '</li>';
        }
        $toc .= '</ol></div>';
        return '<notextile>'.$toc.'</notextile>';
    }


    private function _getdocPath($doc_name, $language){
        $doc_name = AkInflector::underscore($language != 'en' ? Ak::untranslate($doc_name, $language) : $doc_name);
        return AkConfig::getDir('docs').DS.(empty($this->docs_path) ? '' : trim($this->docs_path, DS).DS).$language.DS.$doc_name.'.textile';
    }

    private function _afterRender($html){
        // hack for preventing code snippets from being enclosed by <p> by the Textile parser
        $replacements = array(
        '<p>      <div class="code-snippet-title">' => '<div class="code-snippet-title">',
        '</pre></code></div>' => '</pre></code>'
        );

        $html = str_replace(array_keys($replacements), array_values($replacements), $html);
        return $html;
    }

    private function _beforeRender($textile){
        $textile = $this->_replacePlusPlus($textile);
        $textile = $this->_rebaseImagePaths($textile);
        $textile = $this->_setCodeBlocks($textile);
        if($this->insert_toc){
            $textile = $this->_insertTocAndPrologue($textile);
        }

        $textile = $this->_highlightNotes($textile);
        return $textile;
    }

    private function _highlightNotes($textile){
        if(preg_match_all('/(IMPORTANT|CAUTION|WARNING|NOTE|INFO|TIP)(?:\.|\:)(.*)/', $textile, $matches)){
            foreach ($matches[1] as $k => $class){
                $css_class = strtolower($class);
                $css_class = in_array($css_class, array('caution', 'important')) ? 'warning' : $css_class;
                $css_class = in_array($css_class, array('tip')) ? 'info' : $css_class;
                $note_caption = $this->t(ucfirst($css_class));

                $pin_note = "<notextile><p class='$css_class-box highlighted-box'> <img height='52' width='27' class='no-print' alt='' src='".$this->_controller->url_helper->url_for(array('action' => 'images', 'id' => "$css_class-pin", 'format' => 'gif', 'controller' => 'virtual_assets' )).
                "'><strong class='only-print'>$note_caption: </strong>".
                AkTextHelper::textilize_without_paragraph($this->_replacePlusPlus($matches[2][$k]))."</p></notextile>";

                // $simple_note = "<notextile><div class='$css_class'><p>".strip_tags($matches[2][$k]).'</p></div></notextile>';
                $textile = str_replace($matches[0][$k], $pin_note, $textile);
            }
        }
        return $textile;
    }

    private function _replacePlusPlus($textile){
        if(preg_match_all('/\+([^\+\/\. -_]+)\+/i', $textile, $matches)){
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
                // $textile = str_replace($matches[0][$k], $this->_tabText("<notextile><div class='code_container'><code class='$css_class'>$escaped</code></div></notextile>"), $textile);
                $textile = str_replace($matches[0][$k], '<notextile>'.$this->_controller->akelos_dashboard_helper->format_snippet($matches[2][$k], $css_class).'</notextile>', $textile);
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
