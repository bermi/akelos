<?php
/**
 * This is a port by Milian Wolff of Aaron Swartz' html2text python script.
 *
 * Quote:
 * > html2text is a Python script that converts a page of HTML into clean, easy-
 * > to-read plain ASCII text. Better yet, that ASCII also happens to be valid
 * > Markdown (a text-to-HTML format).
 *
 * Support for PHP Markdown Extra [1] by Michel Fortin [2] was also added
 *
 * [1]: http://www.michelf.com/projects/php-markdown/extra/
 * [2]: http://www.michelf.com/
 *
 * @version 1.4
 * @license http://www.gnu.org/licenses/gpl.txt GNU GPL 2
 *
 * # port:
 * @author Milian Wolff (milianwolff@gmx.net)
 * @copyright (c) 2006 Milian Wolff
 * # original:
 * @author Aaron Swartz (me@aaronsw.com)
 * @copyright (c) 2004 Aaron Swartz
 */
class html2text {
	# input html
	var $html = '';
	# output markdown
	var $outtext = '';
	# some control structures
	var $p_p = 0;
	var $start = 1;
	var $space = 0;
	var $force_html = false;
	var $force_html_start = array('tag'=>'','parents'=>0);
	# links
	var $a = array();
	var $astack = array();
	var $acount = 0;
	# lists
	var $list = array ();
	var $list_depth = 0;
	var $lastWasNL = false;
	# indenting and appending
	var $append = '';
	var $indent = '';
	# these elements will be dropped with all subelements
	var $drop = array(
		'script',
		'head',
		'style',
		'form',
	);
	# these elements will be quietly ignored, their children will be parsed
	var $ignore = array(
		'wrapper', # important!
		'html', # closing html tag
		'body', # closing body tag
		'thead',
		'tbody',
		'tfoot',
	);
	# these elements can have some attributes
	var $has_attrs = array(
		# tag => list of allowed attrs
		'h1' => array('id'),
		'h2' => array('id'),
		'h3' => array('id'),
		'h4' => array('id'),
		'h5' => array('id'),
		'h6' => array('id'),
		'a' => array('href','title'),
		'img' => array('src','alt','title'),
		# tables
		'th' => array('align'),
		'td' => array('align'),
		# footnotes
		'sup' => array('id'),
		'footnote' => array('nr'),
		# abbrevations
		'abbr' => array('title'),
		'acronym' => array('title'),
	);
	# table
	var $max_len = array();
	var $align = array();
	var $cols = array();
	var $rows = array();
	var $col = 0;
	var $row = 0;
	var $header = array();
	# parents
	var $parents = array();
	# abbrevations
	var $abbrs = array();
	# buffer
	var $buffer = array();
	var $buffer_lvl = 0;
	# options
	var $LINKS_EACH_PARAGRAPH;
	var $BODY_WIDTH;
	var $KEEP_HTML;
	# global xml parser
	var $xml_parser;
	/**
	 * setup the xml_parser
	 * $links_each_paragraph: if set to true, the list of links will be
	 * displayed after each paragraph, else it will be displayed on the end of
	 * the file
	 * $body_width: if set to a integer greater 0 the output text will be
	 * wrapped to that width (in characters)
	 * $keep_html: if set to true, all unrecognized html tags will be kept, else
	 * they'll be removed
	 *
	 * @param bool $links_each_paragraph default true
	 * @param integer $body_width default 0
	 * @param bool $keep_html default true
	 * @return void
	 */
	function html2text($links_each_paragraph = true,$body_width = 0,$keep_html = true) {
		$this->LINKS_EACH_PARAGRAPH = $links_each_paragraph;
		$this->BODY_WIDTH = $body_width;
		$this->KEEP_HTML = $keep_html;
		$this->xml_parser = xml_parser_create();
		xml_set_object($this->xml_parser, $this);
		xml_parser_set_option($this->xml_parser, XML_OPTION_CASE_FOLDING, 0);
		xml_set_element_handler($this->xml_parser, 'starttag', 'endtag');
		xml_set_character_data_handler($this->xml_parser, 'handle_data');
		xml_set_default_handler($this->xml_parser,'handle_default');
	}
	/**
	 * parse a html string to text
	 *
	 * @param string $html
	 * @return string
	 */
	function load_string($html) {
		$html = trim($html);
		if(empty($html)){
			return '';
		}
		# use unix style newlines
		$html = str_replace("\r","\n",str_replace("\r\n","\n",$html));
		# remove doctype and xml tags
		$html = preg_replace('#^.*<body[^>]*>#Us','<html><body>',$html);
		/*
		 * cope  with bad html
		 */
		$html = preg_replace('#&(?!amp;)#','&amp;',$html);
		$html = str_replace('<','&lt;',$html);
		$html = preg_replace('#&lt;([a-z]+[^>]*) ?/>#Us','<$1 />',$html);
		# unmatched tags (poor performance)
		preg_match_all('#&lt;(([a-z]|h[1-6])+)(?= |>)#',$html,$matches);
		foreach($matches[1] as $tag){
			$html = preg_replace('#&lt;'.$tag.'( |>)(.*)&lt;/'.$tag.'>#Us','<'.$tag.'$1$2</'.$tag.'>',$html,1);
		}
		# encode < to &lt; and & to &amp; inside <pre>|<code>
		$html = preg_replace_callback('#(<pre[^>]*>\s*<code[^>]*>|<code[^>]*>|<pre[^>]*>)(.*)(</pre>\s*</code>|</code>|</pre>)#Us',
				create_function(
					 '$matches',
					 'return $matches[1].str_replace(\'<\',\'&lt;\',$matches[2]).$matches[3];'
				 ),$html);
		# handle empty attributes (e.g. <input checked>)
		$html = preg_replace_callback('#<([a-z]+)(?>[^>]* [^=]+(?> [^>]*)?) ?/?>#s',array(&$this,'parse_empty_attribs'),$html);
		# fake wrapper
		$html = '<wrapper>'.$html.'</wrapper>';
		# footnotes
		$html = preg_replace_callback('#<div class="footnotes">\s*<hr />\s*<ol>\s*(<li id="fn:\d+">.+</li>)\s*</ol>\s*</div>#Us',array(&$this,'footnotes'),$html);
		# last newline inside <pre> should not be parsed
		$html = preg_replace('#\n</code></pre>#s','</code></pre>',$html);
		# some html elements should not be parsed if their children wont be parsed:
		if($this->KEEP_HTML){
			# <ul|ol><li class="asdf">, complex because we need to handle nested lists
			if(preg_match('#<li [^>]+>#',$html)){
				preg_match_all('#(?:<li [^>]+>|</?(?:ul|ol)[^>]*>)#',$html,$matches,PREG_OFFSET_CAPTURE);
				$lists = array();
				$offset = 0;
				$ins = ' forcehtml="1"';
				$add = strlen($ins);
				foreach($matches[0] as $k => $a){
					if(substr($a[0],0,3) == '<li'){
						$list = &$lists[count($lists)-1];
						if(!$list['forced']){
							$list['forced'] = true;
							$html = substr_replace($html,$ins,$list['offset']+$offset,0);
							$offset += $add;
						}
					} else {
						if(substr($a[0],0,2) == '</'){ # close tag
							array_pop($lists);
						} else { # open tag
							array_push($lists,array(
								'offset' => $a[1]+3,
								'forced' => strstr($a[0],'forcehtml='),
							));
						}
					}
				}
			}
			# <pre><code class="asdf">
			$html = preg_replace('#(?><pre>)\s*(<code .+>)#Us','<pre forcehtml="1">$1',$html);
		}
		$this->html = $html;
		# ok, now lets start parsing!
		#echo dump($html);
		$this->parse();
		return $this->close();
	}
	/**
	 * clean up footnotes
	 *
	 * @param array $matches
	 * @return string
	 */
	function footnotes($matches){
		# remove footnote link
		$matches = preg_replace('@<a href="#fnref:\d+" rev="footnote"[^>]*>&amp;#8617;</a>@U','',$matches[1]);
		# remove empty paragraph
		$matches = str_replace('<p></p>','',$matches);
		# wrap in footnotes tag
		$matches = '<footnotes>'.$matches.'</footnotes>';
		# <li id="fn:1">...</li> -> <footnote nr="1">...</footnote>
		$matches = str_replace('<li id="fn:','<footnote nr="',$matches);
		return preg_replace('#</li>\s*(<footnote|</footnotes)#s','</footnote>$1',$matches);
	}
	/**
	 * @param array $matches
	 * @return string
	 */
	function parse_empty_attribs($matches){
		if(preg_match('#^<[a-z]+(?: [a-z]+=(?:"[^"]*"|\'[^\']*\'))+ ?/?>$#s',$matches[0])){
			# mismatch, this tag is correct
			return $matches[0];
		}
		echo dump($matches[0]);
		die();
		$rep = $this->KEEP_HTML ? '$1="$1"' : '';
		return '<'.$matches[1].preg_replace('#(?<= )([^ =>]{2,})(?= |$)#Us',$rep,$matches[2]).'>';
	}
	/**
	 * parse a html file to text
	 *
	 * @param string $file
	 * @return string
	 */
	function load_file($file) {
		$contents = file_get_contents($file);
		if(!$contents){
			 trigger_error('could not open XML input',E_USER_WARNING);
			 return false;
		}
		return $this->load_string($contents);
	}
	/**
	 * start parsing html to text
	 *
	 * @param void
	 * @return void
	 */
	function parse() {
		$html = explode("\n", $this->html);
		foreach ($html as $line) {
			if (!xml_parse($this->xml_parser, $line . "\n")) {
				$errcode = xml_get_error_code($this->xml_parser);
				trigger_error(sprintf("XML error #%d: %s at line %d:<br /><pre><code>%s</code></pre>", $errcode,xml_error_string($errcode), xml_get_current_line_number($this->xml_parser),htmlspecialchars($line)),E_USER_WARNING);
				#return;
			}
		}
	}
	/**
	 * close parser and return text
	 *
	 * @param void
	 * @return string
	 */
	function close() {
		xml_parser_free($this->xml_parser);
		$this->pbr();
		$this->o('', false, 'end');
		$this->out("\n");
		$this->links();
		# blockquotes
		$this->outtext = preg_replace_callback('#^(\s*)((> )+)#m',array(&$this,'cleanup_bq'),$this->outtext);
		# cleanup
		$this->outtext = str_replace('&amp;','&',str_replace('&lt;','<',str_replace('&gt;','>',$this->outtext)));
		# empty lines (not preformatted)
		$this->outtext = preg_replace('#^\s{1,4}$#m','',$this->outtext);
		# empty quoted lines
		$this->outtext = preg_replace('#^(>+)\s{1,5}$#m','$1',$this->outtext);
		return rtrim($this->optwrap($this->outtext));
	}
	/**
	 * replace "> > > " with ">>> "
	 *
	 * @param array $m matches
	 * @return string
	 */
	function cleanup_bq($m){
		return $m[1].str_repeat('>',strlen($m[2])/2).' ';
	}
	/**
	 * handles html comments
	 *
	 * @param resource $parser
	 * @param string $data
	 * @return void
	 */
	function handle_default($parser,$data){
		if(substr($data,0,4) == '<!--' && substr($data,-3) == '-->'){
			$this->outtext .= "\n\n".$data."\n";
		}
	}
	/**
	 * adds pure data to the output (e.g. <p>DATA</p>)
	 *
	 * @param resource $parser
	 * @param string $data
	 * @return void
	 */
	function handle_data($parser, $data) {
		$this->o($data, true);
	}
	/**
	 * start tags (e.g. <p>)
	 *
	 * @param resource $parser
	 * @param string $tag
	 * @param array $attrs
	 * @return void
	 */
	function starttag($parser, $tag, $attrs) {
		$this->handle_tag($tag, $attrs, true);
	}
	/**
	 * end tags (e.g. </p>)
	 *
	 * @param resource $parser
	 * @param string $tag
	 * @return void
	 */
	function endtag($parser, $tag) {
		$this->handle_tag($tag, null, false);
	}
	/**
	 * force html output of all children
	 *
	 * @param $tag
	 * @return void
	 */
	function force_html($tag){
		$this->force_html = true;
		$this->force_html_start = array(
			'tag' => $tag,
			'parents' => isset($this->parents[$tag]) ? strlen($this->parents[$tag]) : 0
		);
	}
	/**
	 * parsing logic based on tag name
	 *
	 * @param string $tag
	 * @param array $attrs
	 * @param bool $start
	 * @return void
	 */
	function handle_tag($tag, $attrs, $start) {
		if(in_array($tag,$this->drop)){ # drop tags with content
			if($start){
				$this->buffer();
			} else {
				$this->unbuffer();
			}
			return;
		}
		if(in_array($tag,$this->ignore)){ # drop tags but keep content
			return;
		}
		# keeping the original html
		if($this->KEEP_HTML){
			if($start){
				# is the force html attr set?
				if(!$this->force_html && isset($attrs['forcehtml'])){
					$this->force_html($tag);
				}
				# we'll have to keep this tag
				if($this->force_html) {
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				} else {
					# tag has attrs which can't be converted
					if(!empty($attrs) && $this->keep_tag($tag,$attrs,$start)){
						return;
					}
				}
			} else {
				if($this->force_html){
					$this->keep_tag($tag,$attrs,$start,true);
					if($tag == $this->force_html_start['tag'] && strlen($this->parents[$tag]) == $this->force_html_start['parents']){
						$this->force_html = false;
					}
					return;
				} elseif($this->parent($tag,'kept') && $this->keep_tag($tag,$attrs,$start)) {
					return;
				}
			}
		}
		switch ($tag) {
			case 'h1' :
			case 'h2' :
			case 'h3' :
			case 'h4' :
			case 'h5' :
			case 'h6' :
				$this->p();
				if ($start) {
					$this->o(str_repeat('#', intval($tag[1])) . ' ');
					if(!empty($attrs['id'])){
						$this->append = ' {#'.$attrs['id'].'}';
					}
				} else {
					$this->out($this->append);
					$this->append = '';
				}
				break;
			case 'div' :
				$this->p();
				break;
			case 'p' :
				$this->p();
				break;
			case 'br' :
				if ($start) {
					$this->o("  \n");
				}
				break;
			case 'hr' :
				if ($start) {
					$this->p();
					$this->o('* * *');
					$this->p();
				}
				break;
			case 'blockquote' :
				$this->indent('> ',$start);
				if ($start) {
					$this->start = true;
					$this->out("\n\n".$this->indent);
				}
				break;
			case 'em' :
			case 'i' :
			case 'u' :
				$this->o('_');
				break;
			case 'strong' :
			case 'b' :
				$this->o('**');
				break;
			# footnotes
			case 'sup':
				if($start){
					if(count($attrs) != 1 || !isset($attrs['id']) || !preg_match('#^fnref:(\d+)$#',$attrs['id'],$matches)){
						# keep tag
						$this->keep_tag($tag,$attrs,$start,true);
						return;
					}
					# parse footnote
					$this->out('[^'.$matches[1].']');
					# omit output of link (<a href="#fn:1" rel="footnote">1</a>)
					$this->buffer();
				} else {
					# last sup was not parsed -> keep tag
					if(!$this->parent('sup')){
						$this->keep_tag($tag,$attrs,$start);
						return;
					}
					# sup was parsed -> reset buffer
					$this->unbuffer();
				}
				break;
			case 'footnotes':
				$this->p();
				break;
			case 'footnote':
				if($start){
					$this->o('[^'.$attrs['nr']."]:\n".$this->indent.'    ');
					$this->start = true;
				}
				$this->indent('    ',$start);
				break;
			case 'a':
				if($start) {
					# buffer to check for inline links like <foo@bar.com> and the like
					if (isset ($attrs['href'])) {
						$this->buffer();
						array_push($this->astack, $attrs);
					} else {
						array_push($this->astack, null);
					}
				} else {
					if($this->astack) {
						$a = array_pop($this->astack);
						if ($a) {
							# for emails
							$a['href'] = $this->decode($a['href']);
							$buffer = $this->unbuffer();
							$buffer_check = $this->decode(trim($buffer));
							if((substr($a['href'],0,7) == 'mailto:' && 'mailto:'.$buffer_check == $a['href']) || $a['href'] == $buffer_check){
							# inline link
								$this->out('<'.$buffer_check.'>',true);
							} else {
							# block link
								$this->previousIndex($a);
								$this->out('['.$buffer.']['.$a['count'].']',true);
							}
						}
					}
				}
				break;
			# abbrevations
			case 'abbr':
			case 'acronym':
				if($start){
					$this->buffer();
					array_push($this->abbrs,isset($attrs['title'])?$attrs['title']:'');
				} else {
					$abbr = $this->unbuffer();
					$def = array_pop($this->abbrs);
					# only add abbr if its not already defined
					if(!isset($this->abbrs[$abbr])){
						$this->abbrs[$abbr] = $def;
					}
					$this->o($abbr);
				}
				break;
			case 'img' :
				if ($start) {
					if (isset ($attrs['src'])) {
						$attrs['href'] = $attrs['src'];
						$alt = '';
						if (isset ($attrs['alt'])) {
							$alt = $attrs['alt'];
						} elseif(isset($attrs['title'])){
							$alt = $attrs['title'];
						}
						$this->previousIndex($attrs);
						$this->o('!['.$alt.'][' . $attrs['count'] . ']');
					}
				}
				break;
			case 'code':
				# do we have to keep this tag?
				# or is a parent <pre> element existing?
				if($this->keep_tag($tag,$attrs,$start) || $this->parent('pre')){
					return;
				}
				# convert to `code` and handle backticks inside code block
				# <code>foo`bar</code> has to get ``foo`bar`` and so forth
				if($start){
					$this->buffer();
				} else {
					$str = $this->unbuffer();
					preg_match_all('#`+#',$str,$matches);
					if(!empty($matches[0])){
						rsort($matches[0]);
						$len = strlen($matches[0][0])+1;
					} else {
						$len = 1;
					}
					$ticks = str_repeat('`',$len);
					$this->out($ticks.$str.$ticks);
				}
				break;
			case 'dl' :
				# note: if <dl> gets parsed, its direct children (<dd> and <dt>) will be parsed as well
				if ($start) {
					$this->p();
				}
				break;
			case 'dd' :
				# is the parent dl parsed?
				if(!$this->parent('dl')){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if ($start) {
					$this->o(':   ');
					$this->start = true;
				} else {
					$this->outtext .= "\n";
					$this->pbr();
				}
				$this->indent('    ',$start);
				break;
			case 'dt' :
				# is the parent dl parsed?
				if(!$this->parent('dl')){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if (!$start) {
					$this->pbr();
				}
				break;
			case 'ol' :
			case 'ul' :
				# note: if this element gets parsed, its direct children <li>s will be parsed as well
				if ($start) {
					array_push($this->list, array (
						'name' => $tag,
						'num' => 0
					));
				} else {
					array_pop($this->list);
					$this->pbr();
				}
				break;
			case 'li' :
				if ($this->list) {
					$li = &$this->list[count($this->list) - 1];
				}
				# not inside a list or the list tag was not parsed
				if(!isset($li) || !$this->parent($li['name'])){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if ($start) {
					$this->pbr();
					if($li['name'] == 'ul'){
						$this->o('*  ');
					} else {
						$li['num']++;
						/**
						* @todo line up <ol><li>s > 9 correctly.
						*/
						$this->o($li['num'].'. ');
					}
					$this->start = true;
					$this->indent('   ',$start);
				} else {
					$this->indent('   ',$start);
				}
				break;
			case 'table':
				# NOTE: if the <table> tag gets parsed, all its children will be as well!

				# finally: parse the whole table
				if(!$start){
					$this->outtext .= "\n\n";
					$separator = array();
					# seperator with correct align identifikators
					foreach($this->cols as $col => $arr){
						$this->max_len[$col] = max($arr);
						$left = $right = '';
						switch($this->align[$col]){
							case 'center':
								$right = ':';
							case 'left':
								$left = ':';
								break;
							case 'right':
								$right = ':';
								break;
						}
						array_push($separator,$left.str_repeat('-',$this->max_len[$col]).$right);
					}
					$separator = '| '.implode(' | ',$separator).' |';
					# set equal width
					array_walk($this->rows,array(&$this,'fill_td'));
					$rows = $this->rows;
					foreach($rows as $row => $cols){
						$this->pbr();
						$this->o('| '.implode(' | ',$cols).' |');
						if(in_array($row,$this->header)){
							$this->pbr();
							$this->o($separator);
						}
					}
					$this->cols = array();
					$this->rows = array();
					$this->align = array();
					$this->pbr();
				}
				break;
			case 'tr':
				# not inside a table or the parent table was not parsed
				if(!$this->parent('table')){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if($start){
					$this->row++;
				} else {
					$this->col = 0;
				}
				break;
			case 'th':
				# not inside a table or the parent table was not parsed
				if(!$this->parent('table')){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if($start){
					if(!in_array($this->row,$this->header)){
						array_push($this->header,$this->row);
					}
					$this->col++;
					$this->align[$this->col] = !empty($attrs['align']) ? $attrs['align'] : null;
				}
				break;
			case 'td':
				# not inside a table or the parent table was not parsed
				if(!$this->parent('table')){
					$this->keep_tag($tag,$attrs,$start,true);
					return;
				}
				if($start){
					$this->col++;
					if(!empty($attrs['align']) && is_null($this->align[$this->col])){
						$this->align[$this->col] = $attrs['align'];
						if($attrs['align'] == 'center'){
							$this->max_len[$this->col] +=2;
						}
					}
				}
				break;
			case 'pre':
				$this->indent('    ',$start,true);
				if ($start) {
					$this->pbr();
				}
				break;
			default:
				$this->keep_tag($tag,$attrs,$start,true);
				return;
		}
		# if we want to keep all non convertible html this function has to know if some parent elemts
		# were parsed or not (also some elements need to know if)
		if($start){
			if(!isset($this->parents[$tag])){
				$this->parents[$tag] = '1';
			} else {
				$this->parents[$tag] .= '1';
			}
		} else {
			if($this->LINKS_EACH_PARAGRAPH && in_array($tag,array('p','ul','blockquote','ol','dl','table','h1','h2','h3','h4','h5','h6'))){
				$this->links();
			}
			$this->parents[$tag] = substr($this->parents[$tag],0,-1);
		}
		return;
	}
	/**
	 * adds a string to the output ($this->outtext)
	 * also copes with tables
	 *
	 * @param string $str
	 * @return void
	 */
	function out($str) {
		# buffering
		if($this->buffer_lvl){
			$this->buffer[$this->buffer_lvl] .= $str;
			return;
		}
		# this is for tables (see php markdown extra by michel fortin)
		if(($this->parent('th') || $this->parent('td'))){
			$str = trim($str);
			if(!isset($this->rows[$this->row][$this->col])){
				$this->rows[$this->row][$this->col] = $str;
			} else {
				$this->rows[$this->row][$this->col] .= $str;
			}
			if(!isset($this->cols[$this->col][$this->row])){
				$this->cols[$this->col][$this->row] = strlen($str);
			} else {
				$this->cols[$this->col][$this->row] += strlen($str);
			}
			return;
		}
		$this->outtext .= $str;
	}
	/**
	 * further parse the output and add newlines, remove whitespaces and such
	 *
	 * @param string $data
	 * @param bool $puredata
	 * @param string $force
	 * @return void
	 */
	function o($data, $puredata = false, $force = false) {
		if($this->parent('table') && trim($data) == ''){ # drop whitespaces inside tables
			return;
		} elseif ($puredata && !$this->parent('code','both') && !$this->parent('pre','both')) { # keep whitespace for code
			$data = preg_replace('#\s+#', ' ', $data);
		}
		if (!$data && !$force) {
			return;
		}
		if (!empty($this->indent)) {
			$data = str_replace("\n", "\n".$this->indent, $data);
		}
		if ($this->start) {
			if($data == ' '){
				return;
			}
			$this->p_p = 0;
			$this->start = 0;
		}
		if ($force == 'end') {
			# It's the end.
			$this->p_p = 0;
			$this->out("\n");
		}
		if ($this->p_p) {
			if($data == ' '){
				return;
			}
			$data = ltrim($data);
			$this->out(str_repeat("\n".$this->indent, $this->p_p));
		}
		$this->p_p = 0;
		$this->out($data);
		if($data){
			$this->lastWasNL = substr($data, -1) == "\n";
		}
	}
	/**
	 * display block links after paragraph etc.
	 * also handle abbrs
	 *
	 * @param void
	 * @return void
	 */
	function links(){
		$this->abbrs();
		if(empty($this->a)){
			return; # no links stored
		}
		$pre = '';
		$this->out("\n\n");
		foreach($this->a as $links){
				/**
				 * @todo  base href
				 */
				foreach($links as $link){
					$a = $pre.' [' . $link['count'] . ']: ' . $link['href'];
					if (isset ($link['title'])) {
						$a .= ' (' . $link['title'] . ')';
					}
					$this->out($a."\n");
				}
		}
		$this->a = array();
		$this->out("\n");
		$this->lastWasNL = true;
	}
	/**
	 * display abbr list
	 *
	 * @param void
	 * @return void
	 */
	function abbrs(){
		if(empty($this->abbrs)){
			return; # no abbrs stored
		}
		$this->out("\n\n");
		foreach($this->abbrs as $abbr => $def){
			$this->out('*['.$abbr.']: '.$def."\n");
		}
		$this->abbrs = array();
		$this->out("\n");
		$this->lastWasNL = true;
	}
	/**
	 * if the link is already set use its count, else increase acount
	 *
	 * @param array &$attrs link attributes
	 * @return void
	 */
	function previousIndex(&$attrs) {
		# check for existing link
		if(isset($this->a[$attrs['href']])){
			foreach($this->a[$attrs['href']] as $a){
				if (!empty($attrs['title']) || !empty($a['title'])){
					if($a['title'] == $attrs['title']) {
						$attrs = $a;
						return;
					}
				} else {
					$attrs = $a;
					return;
				}
			}
		}
		# if we come here, no matching link was found
		$this->acount++;
		$attrs['count'] = $this->acount;
		if(isset($this->a[$attrs['href']])){
			array_push($this->a[$attrs['href']],$attrs);
		} else {
			$this->a[$attrs['href']] = array($attrs);
		}
	}
	/**
	 * handles bad html to avoid xml parse errors
	 *
	 * @param string $html
	 * @return string
	 */
	function handle_bad_html($html){
		return preg_replace_callback('#&lt;([a-z1-6]+)( [^>]*)?>(.*(?R).*)&lt;/\\1>#Us',array(&$this,'replace_bad_html'),$html);
	}
	/**
	 * callback function which is used in handle_bad_html()
	 *
	 * @param array $matches
	 * @return string
	 */
	function replace_bad_html($matches){
		# recursion
		$matches[3] = $this->handle_bad_html($matches[3]);
		return '<'.$matches[1].$matches[2].'>'.$matches[3].'</'.$matches[1].'>';
	}
	/**
	 * if the option BODY_WIDTH is set, this option will wrap text to the
	 * provided width
	 *
	 * @param string $text
	 * @return string
	 *
	 * @todo wrapping of code (also kept code blocks)
	 */
	function optwrap($text) {
		if ($this->BODY_WIDTH < 30) {
			return $text;
		}
		$result = '';
		$split = explode("\n", $text);
		foreach ($split as $para) {
			if (strlen($para) > 0) {
				if (preg_match('#^(\s*):   #',$para,$indent)) { # definition lists
					$indent = isset($indent[1]) ? $indent[1] : '';
					$result .= wordwrap($para, $this->BODY_WIDTH - strlen($indent) - 4, "\n".$indent.'    ')."\n";
				} elseif(preg_match('#^(\s*>+)#',$para,$indent)){ # blockquote
					$result .= wordwrap($para,$this->BODY_WIDTH - (strlen($indent[0])+1),"\n".$indent[0].' ')."\n";
				} elseif(preg_match('#^\s*\|#',$para)){ # table
					$result .= $para."\n"; # dont wrap
				} elseif(preg_match('#^(\s*)\*#',$para,$indent)) { # list item @todo: ol
					$indent = isset($indent[1]) ? $indent[1] : '';
					$indent.= '   ';
					$result .= wordwrap($para,$this->BODY_WIDTH - strlen($indent),"\n".$indent). "\n";
				} elseif(preg_match('#^ \[[^\]]+\]:#',$para)){ # block links
					# don't wrap at the moment
					$result .= $para."\n";
					continue;
				} else { # something else
					preg_match('#^\s+#',$para,$indent);
					$indent = isset($indent[0]) ? $indent[0] : '';
					$result .= wordwrap($para,$this->BODY_WIDTH - strlen($indent),"\n".$indent). "\n";
				}
			} else {
				$result .= "\n";
			}
		}
		return $result;
	}
	/**
	 * handles html tags which are not represented by the parser logic
	 * if $this->KEEP_HTML is set to true, the tag will be appended to the
	 * output and `markdown="1"` added to its attributes
	 *
	 * @param string $tag
	 * @param array $attrs
	 * @param bool $start
	 * @param array $known_attrs these attrs can be handled by markdown
	 * @return bool
	 */
	function keep_tag($tag,$attrs,$start,$force = false){
		if(!$force && !$this->KEEP_HTML){
			return false;
		}

		# start tag
		if($start){
			# if there is a attr which cannot be handled by markdown
			# this tag will be kept.
			if(isset($this->has_attrs[$tag])){
				$known_attrs = $this->has_attrs[$tag];
			} else {
				$known_attrs = array();
			}
			if(!$force && count($known_attrs) >= count($attrs)){
				if(empty($attrs) || count(array_diff(array_keys($attrs),$known_attrs)) == 0){
					# tag can be handled by markdown!
					return false;
				}
			}
			$attr = '';
			if(!empty($attrs)){
				foreach($attrs as $key => $value){
					if($key == 'forcehtml'){
						continue;
					}
					$attr.=' '.$key.'="'.$value.'"';
				}
			}
			if(!$force && in_array($tag,array('div','center','li','dt','dd'))){
				$attr.= ' markdown="1"';
			} elseif(!$this->force_html) {
				$this->force_html($tag);
			}
			$this->o('<'.$tag.$attr.'>',true);
			# add to list of parents:
			if(isset($this->parents[$tag])){
				$this->parents[$tag] .= '2';
			} else {
				$this->parents[$tag] = '2';
			}
		# close tag
		} else {
			if(!$force && !$this->parent($tag,'kept')){
				# the start tag of this element was not parsed
				return false;
			}
			$this->o('</'.$tag.'>');
			$this->parents[$tag] = substr($this->parents[$tag],0,-1);
			# newlines after </tag>
			if(in_array($tag,array('th','td','dt','dd','li','p'))){
				$this->o("\n");
			}
		}
		# newlines after <tag> and </tag>
		if(in_array($tag,array('div','center','table','tr','ul','ol','dl','pre'))){
			$this->o("\n");
		}
		return true;
	}
	/**
	 * outputs a cell widened to the proper width
	 *
	 * @param array &$row
	 * @return void
	 */
	function fill_td(&$row){
		$len = 0;
		foreach($row as $col => $cont){
			$width = $this->max_len[$col];
			switch($this->align[$col]){
				case 'center':
					$width += 2;
					$row[$col] = str_pad($row[$col],$width,' ',STR_PAD_BOTH);
					break;
				case 'left':
					$width++;
				default:
					$row[$col] = str_pad($row[$col],$width,' ');
					break;
				case 'right':
					$width++;
					$row[$col] = str_pad($row[$col],$width,' ',STR_PAD_LEFT);
					break;
			}
		}
	}
	/**
	 * some sort of <br />
	 *
	 * @param void
	 * @return void
	 */
	function pbr() {
		if ($this->p_p == 0) {
			$this->p_p = 1;
		}
	}
	/**
	 * text <p> (e.g. newlines after output)
	 *
	 * @param void
	 * @return void
	 */
	function p() {
		if($this->parent('table')){
			return;
		}
		$this->p_p = 2;
	}
	/**
	 * add $indent before each line
	 *
	 * @param string $indent
	 * @param bool $start wether it's an opening tag or a closing one
	 * @param bool $output shall $indent be outputted? (only if $start is true)
	 * @return void
	 */
	function indent($indent,$start,$output=false){
		if($start){
			if($output){
				$this->o($indent);
			}
			$this->indent .= $indent;
		} else {
			$len = strlen($indent);
			if($len >= strlen($this->indent)){
				$this->indent = '';
			} else {
				$this->indent = substr($this->indent,0,-$len);
			}
		}
	}
	/**
	 * checks if a parent element exists
	 * use $type to check for a parsed parent element or a kept element
	 * @param string $parent name of the parent tag
	 * @param string $type either 'parsed' or 'kept' or 'both'
	 * @return bool
	 */
	function parent($parent,$type = 'parsed'){
		if(!isset($this->parents[$parent])){
			return false;
		}
		if($type != 'both'){
			$type = $type == 'parsed' ? '1' : '2';
			return substr($this->parents[$parent],-1) === $type;
		} else {
			return !empty($this->parents[$parent]);
		}
	}
	/**
	 * start buffer
	 *
	 * @param void
	 * @return void
	 */
	function buffer(){
		if($this->p_p){
			$this->out(str_repeat("\n".$this->indent, $this->p_p));
			$this->p_p = 0;
		}
		$this->buffer_lvl++;
		$this->buffer[$this->buffer_lvl] = '';
	}
	/**
	 * end buffer and return buffered output
	 *
	 * @param void
	 * @return string
	 */
	function unbuffer(){
		$out = $this->buffer[$this->buffer_lvl];
		unset($this->buffer[$this->buffer_lvl]);
		$this->buffer_lvl--;
		return $out;
	}
	/**
	 * decode email
	 *
	 * @author derernst@gmx.ch <http://www.php.net/manual/en/function.html-entity-decode.php#68536>
	 */
	function decode($text,$quote_style = ENT_NOQUOTES){
		if (function_exists('html_entity_decode')) {
			$text = html_entity_decode($text, $quote_style, 'ISO-8859-1'); // NOTE: UTF-8 does not work!
		}
		else {
			$trans_tbl = get_html_translation_table(HTML_ENTITIES, $quote_style);
			$trans_tbl = array_flip($trans_tbl);
			$text = strtr($text, $trans_tbl);
		}
		$text = preg_replace('~&#x([0-9a-f]+);~ei', 'chr(hexdec("\\1"))', $text);
		$text = preg_replace('~&#([0-9]+);~e', 'chr("\\1")', $text);
		return $text;
	}
}
?>