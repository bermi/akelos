<?php
/**
 * PHP interface of Hyper Estraier
 *
 * A porting of estraierpure.rb which is a part of Hyper Estraier.
 *
 * Hyper Estraier is a full-text search system. You can search lots of
 * documents for some documents including specified words. If you run a web
 * site, it is useful as your own search engine for pages in your site.
 * Also, it is useful as search utilities of mail boxes and file servers.
 *
 * PHP version 4
 *
 * Copyright (C) 2005-2006 rsk
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions
 * are met:
 *
 * 1. Redistributions of source code must retain the above copyright
 *    notice, this list of conditions and the following disclaimer.
 * 2. Redistributions in binary form must reproduce the above copyright
 *    notice, this list of conditions and the following disclaimer in
 *    the documentation and/or other materials provided with the
 *    distribution.
 * 3. The names of the authors may not be used to endorse or promote
 *    products derived from this software without specific prior
 *    written permission.
 *
 * THIS SOFTWARE IS PROVIDED BY THE COPYRIGHT HOLDERS AND CONTRIBUTORS
 * "AS IS" AND ANY EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT
 * LIMITED TO, THE IMPLIED WARRANTIES OF MERCHANTABILITY AND FITNESS
 * FOR A PARTICULAR PURPOSE ARE DISCLAIMED. IN NO EVENT SHALL THE
 * COPYRIGHT OWNER OR CONTRIBUTORS BE LIABLE FOR ANY DIRECT, INDIRECT,
 * INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES (INCLUDING,
 * BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR SERVICES;
 * LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN
 * ANY WAY OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE
 * POSSIBILITY OF SUCH DAMAGE.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @copyright   2005-2006 rsk
 * @license     http://www.opensource.org/licenses/bsd-license.php BSD License
 * @link        http://hyperestraier.sourceforge.net/
 * @version     $Id:$
 * @filesource
 */

// {{{ load dependencies

require_once 'PEAR.php';
require_once 'PEAR/ErrorStack.php';
if (!defined('ESTRAIERPURE_USE_HTTP_STREAM') || ESTRAIERPURE_USE_HTTP_STREAM == 0) {
    require_once 'HTTP/Request.php';
}

// }}}
// {{{ constants

/**
 * The version number of EstraierPure.
 */
define('ESTRAIERPURE_VERSION', '0.2.2');

/**
 * Specifies debug mode.
 *
 * If set to `1', every methods check their argument datatype.
 */
if (!defined('ESTRAIERPURE_DEBUG')) {
    define('ESTRAIERPURE_DEBUG', 0);
}

/**
 * Specifies HTTP client type.
 *
 * If set to `1', EstraierPure_Utility::shuttle_url() uses stream functions.
 * By default, uses PEAR::HTTP_Request.
 */
if (!defined('ESTRAIERPURE_USE_HTTP_STREAM')) {
    define('ESTRAIERPURE_USE_HTTP_STREAM', 0);
}

// }}}
// {{{ class EstraierPure_Document

/**
 * Abstraction of document.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 */
class EstraierPure_Document
{
    // {{{ properties

    /**
     * The ID number
     *
     * @var int
     * @access  private
     */
    var $id;

    /**
     * Attributes
     *
     * @var array
     * @access  private
     */
    var $attrs;

    /**
     * Sentences of text
     *
     * @var array
     * @access  private
     */
    var $dtexts;

    /**
     * Hidden sentences of text
     *
     * @var array
     * @access  private
     */
    var $htexts;

    /**
     * Keywords
     *
     * @var array
     * @access  private
     */
    var $kwords;

    // }}}
    // {{{ constructor

    /**
     * Create a document object.
     *
     * @param   string  $draft  A string of draft data.
     * @access  public
     */
    function EstraierPure_Document($draft = '')
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($draft, 'string')
        );
        $this->id = -1;
        $this->attrs = array();
        $this->dtexts = array();
        $this->htexts = array();
        $this->kwords = null;
        if (strlen($draft)) {
            $lines = explode("\n", $draft);
            $num = 0;
            $len = count($lines);
            $line = ' ';
            while ($num< $len) {
                $num++;
                if (strlen($line) == 0) {
                    break;
                }
                if ($line{0} == '%') {
                    if (preg_match('/^%VECTOR\\t/', $line)) {
                        $fields = explode("\t", $line);
                        $i = 1;
                        $flen = count($fields) - 1;
                        while ($i < $flen) {
                            $this->kwords[$fields[$i]] = $fields[$i+1];
                            $i += 2;
                        }
                    }
                    continue;
                }
                $line = EstraierPure_Utility::sanitize($lines[$num]);
                if (strpos($line, '=')) {
                    @list($key, $value) = explode(' ', $line);
                    $this->attrs[$key] = $value;
                }
            }
            while ($num < $len) {
                $line = $lines[$num];
                $num++;
                if (strlen($line) == 0) {
                    continue;
                }
                if ($line{0} == "\t") {
                    if (strlen($line) > 1) {
                        $this->_htexts[] = substr($line, 1);
                    }
                } else {
                    $this->dtexts[] = $line;
                }
            }
        }
    }

    // }}}
    // {{{ setter methods

    /**
     * Add an attribute.
     *
     * @param   string  $name   The name of an attribute.
     * @param   string  $value  The value of the attribute.
     *                          If it is `null', the attribute is removed.
     * @return  void
     * @access  public
     */
    function add_attr($name, $value = null)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string'), array($value, 'string', 'NULL')
        );
        $name = EstraierPure_Utility::sanitize($name);
        $value = EstraierPure_Utility::sanitize($value);
        if (!is_null($value)) {
            $this->attrs[$name] = $value;
        } else {
            unset($this->attrs[$name]);
        }
    }

    /**
     * Add a sentence of text.
     *
     * @param   string  $text   A sentence of text.
     * @return  void
     * @access  public
     */
    function add_text($text)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($text, 'string')
        );
        $text = EstraierPure_Utility::sanitize($text);
        if (strlen($text)) {
            $this->dtexts[] = $text;
        }
    }

    /**
     * Add a hidden sentence.
     *
     * @param   string  $text   A hidden sentence.
     * @return  void
     * @access  public
     */
    function add_hidden_text($text)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($text, 'string')
        );
        $text = EstraierPure_Utility::sanitize($text);
        if (strlen($text)) {
            $this->htexts[] = $text;
        }
    }

    /**
     * Attach keywords.
     *
     * @param   array   $kwords A list of keywords.
     *                          Keys of the map should be keywords of the document
     *                          and values should be their scores in decimal string.
     * @return  void
     * @access  public
     */
    function set_keywords($kwords)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($kwords, 'array')
        );
        $this->kwords = $kwords;
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the ID number.
     *
     * @return  int     The ID number of the document object.
     *                  If the object has never beenregistered, returns -1.
     * @access  public
     */
    function id()
    {
        return $this->id;
    }

    /**
     * Get a list of attribute names of a document object.
     *
     * @return  array   A list of attribute names/
     * @access  public
     */
    function attr_names()
    {
        $names = array_keys($this->attrs);
        sort($names);
        return $names;
    }

    /**
     * Get the value of an attribute.
     *
     * @param   string  $name   The name of an attribute.
     * @return  string  The value of the attribute. If it does not exist, returns `null'.
     * @access  public
     */
    function attr($name)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string')
        );
        return (isset($this->attrs[$name])) ? $this->attrs[$name] : null;
    }

    /**
     * Get a list of sentences of the text.
     *
     * @return  array   A list of sentences of the text.
     * @access  public
     */
    function texts()
    {
        return $this->dtexts;
    }

    /**
     * Concatenate sentences of the text of a document object.
     *
     * @return  string  Concatenated sentences.
     * @access  public
     */
    function cat_texts()
    {
        return implode(' ', $this->dtexts);
    }

    /**
     * Dump draft data of a document object.
     *
     * @return  string  The draft data.
     * @access  public
     */
    function dump_draft()
    {
        $buf = '';
        foreach ($this->attr_names() as $name) {
            $buf .= sprintf("%s=%s\n", $name, $this->attrs[$name]);
        }
        if ($this->kwords) {
            $buf .= '%VECTOR';
            foreach ($this->kwords as $key => $value) {
                $buf .= sprintf("\t%s\t%s", $key, $value);
            }
            $buf .= "\n";
        }
        $buf .= "\n";
        if ($this->dtexts) {
            $buf .= implode("\n", $this->dtexts) . "\n";
        }
        if ($this->htexts) {
            $buf .= "\t" . implode("\n\t", $this->htexts) . "\n";
        }
        return $buf;
    }

    /**
     * Get attached keywords.
     *
     * @return  array   A list of keywords and their scores in decimal string.
     *                  If no keyword is attached, `null' is returned.
     * @access  public
     */
    function keywords()
    {
        return $this->kwords;
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_Condition

// {{{ constants

/**
 * option: check N-gram keys skipping by three
 */
define('ESTRAIERPURE_CONDITION_SURE', 1 << 0);

/**
 * option: check N-gram keys skipping by two
 */
define('ESTRAIERPURE_CONDITION_USUAL', 1 << 1);

/**
 * option: without TF-IDF tuning
 */
define('ESTRAIERPURE_CONDITION_FAST', 1 << 2);

/**
 * option: with the simplified phrase
 */
define('ESTRAIERPURE_CONDITION_AGITO', 1 << 3);

/**
 * option: check every N-gram key
 */
define('ESTRAIERPURE_CONDITION_NOIDF', 1 << 4);

/**
 * option: check N-gram keys skipping by one
 */
define('ESTRAIERPURE_CONDITION_SIMPLE', 1 << 10);

// }}}

/**
 * Abstraction of search condition.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 */
class EstraierPure_Condition
{
    // {{{ properties

    /**
     * The search phrase
     *
     * @var string
     * @access  private
     */
    var $phrase;

    /**
     * The order of a condition object
     *
     * @var string
     * @access  private
     */
    var $order;

    
    var $skip;
    
    /**
     * The maximum number of retrieval
     *
     * @var int
     * @access  private
     */
    var $max;

    /**
     * Options of retrieval
     *
     * @var int
     * @access  private
     */
    var $options;

    // }}}
    // {{{ constructor

    /**
     * Create a search condition object.
     *
     * @access  public
     */
    function EstraierPure_Condition()
    {
        $this->phrase = null;
        $this->attrs = array();
        $this->order = null;
        $this->max = -1;
        $this->options = 0;
    }

    // }}}
    // {{{ setter methods

    /**
     * Set the search phrase.
     *
     * @param   string  $phrase     A search phrase.
     * @return  void
     * @access  public
     */
    function set_phrase($phrase)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($phrase, 'string')
        );
        $this->phrase = EstraierPure_Utility::sanitize($phrase);
        $phrase = $this->phrase;
        $parts = split(' ',$phrase);
        foreach($parts as $idx=>$part) {
            $part = preg_replace('/^(.*?)\*$/','[BW] \\1',$part);
            $part = preg_replace('/^\*(.*?)$/','[EW] \\1',$part);
            $parts[$idx]=$part;
        }
        $this->phrase=implode(' ',$parts);
    }

    /**
     * Add an expression for an attribute.
     *
     * @param   string  $expr   A search expression.
     * @return  void
     * @access  public
     */
    function add_attr($expr)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($expr, 'string')
        );
        $this->attrs[] = EstraierPure_Utility::sanitize($expr);
    }

    /**
     * Set the order of a condition object.
     *
     * @param   string  $order  An expression for the order.
     *                          By default, the order is by score descending.
     * @return  void
     * @access  public
     */
    function set_order($order)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($order, 'string')
        );
        $this->order = EstraierPure_Utility::sanitize($order);
    }
    function set_skip($skip)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($skip, 'integer')
        );
        $this->skip = EstraierPure_Utility::sanitize($skip);
    }
    /**
     * Set the maximum number of retrieval.
     *
     * @param   int     $max    The maximum number of retrieval.
     *                          By default, the number of retrieval is not limited.
     * @return  void
     * @access  public
     */
    function set_max($max)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($max, 'integer')
        );
        $this->max = $max;
    }

    /**
     * Set options of retrieval.
     *
     * @param   int     $options    Options:
     * - `ESTRAIERPURE_CONDITION_SURE' specifies that it checks every N-gram key.
     * - `ESTRAIERPURE_CONDITION_USUAL', which is the default,
     *      specifies that it checks N-gram keys with skipping one key.
     * - `ESTRAIERPURE_CONDITION_FAST' skips two keys.
     * - `ESTRAIERPURE_CONDITION_AGITO' skips three keys.
     * - `ESTRAIERPURE_CONDITION_NOIDF' specifies not to perform TF-IDF tuning.
     * - `ESTRAIERPURE_CONDITION_SIMPLE' specifies to use simplified phrase.
     *  Each option can be specified at the same time by bitwise or.
     *  If keys are skipped, though search speed is improved, the relevance ratio grows less.
     * @return  void
     * @access  public
     */
    function set_options($options)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($options, 'integer')
        );
        $this->options |= $options;
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the search phrase.
     *
     * @return  string  The search phrase.
     * @access  public
     */
    function phrase()
    {
        return $this->phrase;
    }

    /**
     * Get expressions for attributes.
     *
     * @return  array   Expressions for attributes.
     * @access  public
     */
    function attrs()
    {
        return $this->attrs;
    }

    /**
     * Get the order expression.
     *
     * @return  string  The order expression.
     * @access  public
     */
    function order()
    {
        return $this->order;
    }

    /**
     * Get the maximum number of retrieval.
     *
     * @return  string  The maximum number of retrieval.
     * @access  public
     */
    function max()
    {
        return $this->max;
    }
    function skip()
    {
        return $this->skip;
    }
    /**
     * Get options of retrieval.
     *
     * @return  string  Options by bitwise or.
     * @access  public
     */
    function options()
    {
        return $this->options;
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_ResultDocument

/**
 * Abstraction document in result set.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 */
class EstraierPure_ResultDocument
{
    // {{{ properties

    /**
     * The URI of the result document object
     *
     * @var string
     * @access  private
     */
    var $uri;

    /**
     * A list of attribute names
     *
     * @var array
     * @access  private
     */
    var $attrs;

    /**
     * Snippet of a result document object
     *
     * @var string
     * @access  private
     */
    var $snippet;

    /**
     * The keyword vector
     *
     * @var string
     * @access  private
     */
    var $keywords;

    // }}}
    // {{{ constructor

    /**
     * Create a result document object.
     *
     * @param   string  $uri        The URI of the result document object.
     * @param   array   $attrs      A list of attribute names.
     * @param   string  $snippet    The snippet of a result document object
     * @param   string  $keywords   Keywords of the result document object.
     * @access  public
     */
    function EstraierPure_ResultDocument($uri, $attrs, $snippet, $keywords)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string'), array($attrs, 'array'),
            array($snippet, 'string'), array($keywords, 'string')
        );
        $this->uri = $uri;
        $this->attrs = $attrs;
        $this->snippet = $snippet;
        $this->keywords = $keywords;
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the URI.
     *
     * @return  string  The URI of the result document object.
     * @access  public
     */
    function uri()
    {
        return $this->uri;
    }

    /**
     * Get a list of attribute names.
     *
     * @return  array   A list of attribute names.
     * @access  public
     */
    function attr_names()
    {
        $names = array_keys($this->attrs);
        sort($names);
        return $names;
    }

    /**
     * Get the value of an attribute.
     *
     * @param   string  $name   The name of an attribute.
     * @return  string  The value of the attribute. If it does not exist, returns `null'.
     * @access  public
     */
    function attr($name)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string')
        );
        return (isset($this->attrs[$name])) ? $this->attrs[$name] : null;
    }

    /**
     * Get the snippet of a result document object.
     *
     * @return  string  The snippet of the result document object.
     *                  There are tab separated values.
     *                  Each line is a string to be shown.
     *                  Though most lines have only one field,
     *                  some lines have two fields.
     *                  If the second field exists, the first field isto be shown with
     *                  highlighted, and the second field means its normalized form.
     * @access  public
     */
    function snippet()
    {
        return $this->snippet;
    }

    /**
     * Get keywords of a result document object.
     *
     * @return  string  Serialized keywords of the result document object.
     *                  There are tab separated values.
     *                  Keywords and their scores come alternately.
     * @access  public
     */
    function keywords()
    {
        return $this->keywords;
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_NodeResult

/**
 * Abstraction of result set from node.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 */
class EstraierPure_NodeResult
{
    // {{{ properties

    /**
     * Documents
     *
     * @var array
     * @access  private
     */
    var $docs;

    /**
     * Hint informations
     *
     * @var array
     * @access  private
     */
    var $hints;

    // }}}
    // {{{ constructor

    /**
     * Create a node result object.
     *
     * @param   array   $docs   Documents.
     * @param   array   $hints  Hint informations.
     * @access  public
     */
    function EstraierPure_NodeResult($docs, $hints)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($docs, 'array'), array($hints, 'array')
        );
        $this->docs = $docs;
        $this->hints = $hints;
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the number of documents.
     *
     * @return  int     The number of documents.
     * @access  public
     */
    function doc_num()
    {
        return count($this->docs);
    }

    /**
     * Get a document object.
     *
     * @param   int     $index  The index of a document.
     * @return  object  EstraierPure_ResultDocument
     *                  A result document object.
     *                  If the index is out of bounds, returns `null'.
     * @access  public
     */
    function &get_doc($index)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($index, 'integer')
        );
        if (isset($this->docs[$index])) {
            return $this->docs[$index];
        } else {
            $null = null;
            return $null;
        }
    }

    /**
     * Get the value of hint information.
     *
     * @param   string  $key    The key of a hint.
     *                          "VERSION", "NODE", "HIT", "HINT#n", "DOCNUM",
     *                          "WORDNUM", "TIME", "LINK#n", and "VIEW"
     *                          are provided for keys.
     * @return  string  The hint. If the key does not exist, returns `null'.
     * @access  public
     */
    function hint($key)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($key, 'string')
        );
        return (isset($this->hints[$key])) ? $this->hints[$key] : null;
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_Node

// {{{ constants

/**
 * mode: delete the account
 */
define('ESTRAIERPURE_NODE_USER_DELETE', 0);

/**
 * mode: set the account as an administrator
 */
define('ESTRAIERPURE_NODE_ USER_ADMIN', 1);

/**
 * mode: set the account as a guest
 */
define('ESTRAIERPURE_NODE_USER_GUEST', 2);

// }}}

/**
 * Abstraction of connection to P2P node.
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 * @uses        PEAR
 */
class EstraierPure_Node
{
    // {{{ properties

    /**
     * The URL of a node server
     *
     * @var string
     * @access  private
     */
    var $url;

    /**
     * The host name of a proxy server
     *
     * @var string
     * @access  private
     */
    var $pxhost;

    /**
     * The port number of the proxy server
     *
     * @var int
     * @access  private
     */
    var $pxport;

    /**
     * Timeout of the connection in seconds
     *
     * @var int
     * @access  private
     */
    var $timeout;

    /**
     * The authentication information
     *
     * @var string
     * @access  private
     */
    var $auth;

    /**
     * The name of the node
     *
     * @var string
     * @access  private
     */
    var $name;

    /**
     * The label of the node
     *
     * @var string
     * @access  private
     */
    var $label;

    /**
     * The number of documents
     *
     * @var int
     * @access  private
     */
    var $dnum;

    /**
     * The number of unique words
     *
     * @var int
     * @access  private
     */
    var $wnum;

    /**
     * The size of the datbase
     *
     * @var float
     * @access  private
     */
    var $size;

    /**
     * Whole width of a snippet
     *
     * @var int
     * @access  private
     */
    var $wwidth;

    /**
     * Width of strings picked up from the beginning of the text
     *
     * @var int
     * @access  private
     */
    var $hwidth;

    /**
     * Width of strings picked up around each highlighted word
     *
     * @var int
     * @access  private
     */
    var $awidth;

    /**
     * The status code of the response
     *
     * @var int
     * @access  private
     */
    var $status;

    // }}}
    // {{{ constructor

    /**
     * Create a node connection object.
     *
     * @access  public
     */
    function EstraierPure_Node()
    {
        $this->url = null;
        $this->pxhost = null;
        $this->pxport = -1;
        $this->timeout = -1;
        $this->auth = null;
        $this->name = null;
        $this->label = null;
        $this->dnum = -1;
        $this->wnum = -1;
        $this->size = -1.0;
        $this->wwdith = 480;
        $this->hwidth = 96;
        $this->awidth = 96;
        $this->status = -1;
    }

    // }}}
    // {{{ setter methods
        
    /**
     * Set the URL of a node server.
     *
     * @param   string  $url    The URL of a node.
     * @return  void
     * @access  public
     */
    function set_url($url)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($url, 'string')
        );
        $this->url = $url;
    }

    /**
     * Set the URL of a node server.
     *
     * @param   string  $host   The host name of a proxy server.
     * @param   int     $port   The port number of the proxy server.
     * @return  void
     * @access  public
     */
    function set_proxy($host, $port)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($host, 'string'), array($port, 'integer')
        );
        $this->pxhost = $host;
        $this->pxport = $port;
    }

    /**
     * Set timeout of a connection.
     *
     * @param   int     $sec    Timeout of the connection in seconds.
     * @return  void
     * @access  public
     */
    function set_timeout($sec)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($sec, 'integer')
        );
        $this->timeout = $sec;
    }

    /**
     * Set the authentication information.
     *
     * @param   string  $name       The name of authentication.
     * @param   string  $password   The password of the authentication.
     * @return  void
     * @access  public
     */
    function set_auth($name, $password)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string'), array($password, 'string')
        );
        $this->auth = $name . ':' . $password;
    }

    /**
     * Set width of snippet in the result.
     *
     * @param   int     $wwidth  Whole width of a snippet.
     *                           By default, it is 480.
     *                           If it is 0, no snippet is sent.
     *                           If it is negative, whole body text is sent
     *                           instead of snippet.
     * @param   int     $hwidth  Width of strings picked up from the beginning of the text.
     *                           By default, it is 96.
     *                           If it is negative 0, the current setting is not changed.
     * @param   int     $awidth  Width of strings picked up around each highlighted word.
     *                           By default, it is 96.
     *                           If it is negative, the current setting is not changed.
     * @return  void
     * @access  public
     */
    function set_snippet_width($wwidth, $hwidth, $awidth)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($wwidth, 'integer'), array($hwidth, 'integer'),
            array($awidth, 'integer')
        );
        $this->wwidth = $wwidth;
        if ($hwidth >= 0) {
            $this->hwidth = $hwidth;
        }
        if ($awidth >= 0) {
            $this->awidth = $awidth;
        }
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the name.
     *
     * @return  string  The name.
     *                  On error, returns `null'.
     * @access  public
     */
    function name()
    {
        if (is_null($this->name)) {
            $this->set_info();
        }
        return $this->name;
    }

    /**
     * Get the label.
     *
     * @return  string  The label.
     *                  On error, returns `null'.
     * @access  public
     */
    function label()
    {
        if (is_null($this->label)) {
            $this->set_info();
        }
        return $this->label;
    }

    /**
     * Get the number of documents.
     *
     * @return  int     The number of documents.
     *                  On error, returns -1.
     * @access  public
     */
    function doc_num()
    {
        if ($this->dnum < 0) {
            $this->set_info();
        }
        return $this->dnum;
    }

    /**
     * Get the number of unique words.
     *
     * @return  int     The number of unique words.
     *                  On error, returns -1.
     * @access  public
     */
    function word_num()
    {
        if ($this->wnum < 0) {
            $this->set_info();
        }
        return $this->wnum;
    }

    /**
     * Get the size of the datbase.
     *
     * @return  float   The size of the datbase.
     *                  On error, returns -1.0.
     * @access  public
     */
    function size()
    {
        if ($this->size < 0.0) {
            $this->set_info();
        }
        return $this->size;
    }

    /**
     * Get the status code of the last request.
     *
     * @return  int     The status code of the last request.
     *                  -1 means failure of connection.
     * @access  public
     */
    function status()
    {
        return $this->status;
    }

    // }}}
    // {{{ document manipulation methods

    /**
     * Add a document.
     *
     * @param   object  $doc    EstraierPure_Document
     *                          which is a document object.
     *                          The document object should have the URI attribute.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function put_doc(&$doc)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($doc, 'object', 'EstraierPure_Document')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/put_doc';
        $reqheads = array('Content-Type' => 'text/x-estraier-draft');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = $doc->dump_draft();
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    /**
     * Remove a document.
     *
     * @param   int     $id     The ID number of a registered document.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function out_doc($id)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($id, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/out_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'id=' . $id;
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    /**
     * Remove a document specified by URI.
     *
     * @param   string  $uri    The URI of a registered document.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function out_doc_by_uri($uri)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/out_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'uri=' . urlencode($uri);
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    /**
     * Edit attributes of a document.
     *
     * @param   object  $doc    EstraierPure_Document
     *                          which is a document object.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function edit_doc(&$doc)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($doc, 'object', 'EstraierPure_Document')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/edit_doc';
        $reqheads = array('Content-Type' => 'text/x-estraier-draft');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = $doc->dump_draft();
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    /**
     * Retrieve a document.
     *
     * @param   int     $id     The ID number of a registered document.
     * @return  object  EstraierPure_Document
     *                  A document object.
     *                  On error, returns `null'.
     * @access  public
     */
    function &get_doc($id)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($id, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            $null = null;
            return $null;
        }
        $turl = $this->url . '/get_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'id=' . $id;
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            $null = null;
            return $null;
        }
        $doc = &new EstraierPure_Document($res->body());
        return $doc;
    }

    /**
     * Remove a document specified by URI.
     *
     * @param   string  $uri    The URI of a registered document.
     * @return  object  EstraierPure_Document
     *                  A document object.
     *                  On error, returns `null'.
     * @access  public
     */
    function &get_doc_by_uri($uri)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            $null = null;
            return $null;
        }
        $turl = $this->url . '/get_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'uri=' . urlencode($uri);
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            $null = null;
            return $null;
        }
        $doc = &new EstraierPure_Document($res->body());
        return $doc;
    }

    /**
     * Retrieve the value of an attribute of a document.
     *
     * @param   int     $id     The ID number of a registered document.
     * @param   string  $name   The name of an attribute.
     * @return  string  The value of the attribute. If it does not exist, returns `null'.
     * @access  public
     */
    function get_doc_attr($id, $name)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($id, 'integer'), array($name, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            return null;
        }
        $turl = $this->url . '/get_doc_attr';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'id=' . $id . '&name=' . urlencode($name);
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return null;
        }
        return rtrim($res->body(), "\n");
    }

    /**
     * Retrieve the value of an attribute of a document specified by URI.
     *
     * @param   string  $uri    The URI of a registered document.
     * @param   string  $name   The name of an attribute.
     * @return  string  The value of the attribute. If it does not exist, returns `null'.
     * @access  public
     */
    function get_doc_attr_by_uri($uri, $name)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string'), array($name, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            return null;
        }
        $turl = $this->url . '/get_doc_attr';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'uri=' . urlencode($uri) . '&name=' . urlencode($name);
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return null;
        }
        return rtrim($res->body(), "\n");
    }

    /**
     * Extract keywords of a document.
     *
     * @param   int     $id     The ID number of a registered document.
     * @return  array   Pairs of keywords and their scores in decimal string.
     *                  On error, returns `null'.
     * @access  public
     */
    function etch_doc($id)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($id, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            return null;
        }
        $turl = $this->url . '/etch_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'id=' . $id;
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return null;
        }
        $kwords = array();
        $lines = explode("\n", $res->body());
        foreach ($lines as $line) {
            if (strpos($line, "\t")) {
                $pair = explode("\t", $line);
                $kwords[$pair[0]] = $pair[1];
            }
        }
        return $kwords;
    }

    /**
     * Extract keywords of a document specified by URI.
     *
     * @param   string  $uri    The URI of a registered document.
     * @return  array   Pairs of keywords and their scores in decimal string.
     *                  On error, returns `null'.
     * @access  public
     */
    function etch_doc_by_uri($uri)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            return null;
        }
        $turl = $this->url . '/etch_doc';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'uri=' . urlencode($uri);
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return null;
        }
        $kwords = array();
        $lines = explode("\n", $res->body());
        foreach ($lines as $line) {
            if (strpos($line, "\t")) {
                $pair = explode("\t", $line);
                $kwords[$pair[0]] = $pair[1];
            }
        }
        return $kwords;
    }

    // }}}
    // {{{ node management methods

    /**
     * Manage a user account of a node.
     *
     * @param   string  $name   The name of a user.
     * @param   int     $mode   The operation mode.
     * - `ESTRAIERPURE_NODE_USER_DELETE' means to delete the account.
     * - `ESTRAIERPURE_NODE_USER_ADMIN' means to set the account as an administrator.
     * - `ESTRAIERPURE_NODE_USER_GUEST' means to set the account as a guest.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function set_user($name, $mode)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string'), array($mode, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/_set_user';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'name=' . urlencode($name) . '&mode=' . $mode;
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    /**
     * Manage a link of a node.
     *
     * @param   string  $url    The URL of the target node of a link.
     * @param   string  $label  The label of the link.
     * @param   int     $credit  The credit of the link.
     *                           If it is negative, the link is removed.
     * @return  bool    True if success, else false.
     * @access  public
     */
    function set_link($url, $label, $credit)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($url, 'string'), array($label, 'string'),
            array($credit, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            return false;
        }
        $turl = $this->url . '/_set_link';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'url=' . urlencode($url) . '&label=' . $label;
        if ($credit >= 0) {
            $reqbody .= '&credit=' . $credit;
        }
        $res = null;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return false;
        }
        $this->status = $rv;
        return ($rv == 200);
    }

    // }}}
    // {{{ other public methods

    /**
     * Get the ID of a document specified by URI.
     *
     * @param   string  $uri    The URI of a registered document.
     * @return  int     The ID of the document.
     *                  On error, returns -1.
     * @access  public
     */
    function uri_to_id($uri)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($uri, 'string')
        );
        $this->status = -1;
        if (!$this->url) {
            return -1;
        }
        $turl = $this->url . '/uri_to_id';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = 'url=' . urlencode($url) . '&label=' . $label;
        if ($credit >= 0) {
            $reqbody .= '&credit=' . $credit;
        }
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return -1;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return -1;
        }
        return intval(rtrim($res->body(), "\n"));
    }

    /**
     * Search documents corresponding a condition.
     *
     * @param   object  $cond   EstraierPure_Condition
     *                          which is a condition object.
     * @param   int     $depth  The depth of meta search.
     * @return  object  EstraierPure_NodeResult
     *                  A node result object.
     *                  On error, returns `null'.
     * @access  public
     */
    function &search(&$cond, $depth)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($cond, 'object', 'EstraierPure_Condition'), array($depth, 'integer')
        );
        $this->status = -1;
        if (!$this->url) {
            $null = null;
            return $null;
        }
        $turl = $this->url . '/search';
        $reqheads = array('Content-Type' => 'application/x-www-form-urlencoded');
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $reqbody = EstraierPure_Utility::cond_to_query(
            $cond, $depth, $this->wwdith, $this->hwidth, $this->awidth
        );
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, $reqbody, $res);
        if (PEAR::isError($rv)) {
            return null;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return null;
        }
        $lines = explode("\n", $res->body());
        if (count($lines) == 0) {
            return null;
        }
        $docs = array();
        $hints = array();
        $border = $lines[0];
        $isend = false;
        $lnum = 1;
        $llen = count($lines);
        $blen = strlen($border);
        while ($lnum < $llen) {
            $line = $lines[$lnum];
            $lnum++;
            if (strlen($line) >= $blen && strpos($line, $border) === 0) {
                if (substr($line, $blen) == ':END') {
                    $isend = true;
                }
                break;
            }
            if (strpos($line, "\t")) {
                list($key, $value) = explode("\t", $line, 2);
                $hints[$key] = $value;
            }
        }
        $snum = $lnum;
        while (!$isend && $lnum < $llen) {
            $line = $lines[$lnum];
            $lnum++;
            if (strlen($line) >= $blen && strpos($line, $border) === 0) {
                if ($lnum > $snum) {
                    $rdattrs = array();
                    $sb = '';
                    $rdvector = '';
                    $rlnum = $snum;
                    while ($rlnum < $lnum - 1) {
                        $rdline = trim($lines[$rlnum]);
                        $rlnum++;
                        if (strlen($rdline) == 0) {
                            break;
                        }
                        if ($rdline{0} == '%') {
                            $lidx = strpos($rdline, "\t");
                            if (strpos($rdline, '%VECTOR') === 0 && $lidx) {
                                $rdvector = substr($rdline, $lidx + 1);
                            }
                        } else {
                            if (strpos($rdline, '=')) {
                                list($key, $value) = explode('=', $rdline, 2);
                                $rdattrs[$key] = $value;
                            }
                        }
                    }
                    while ($rlnum < $lnum - 1) {
                        $rdline = $lines[$rlnum];
                        $rlnum++;
                        $sb .= $rdline . "\n";
                    }
                    $rduri = $rdattrs['@uri'];
                    $rdsnippet = $sb;
                    if ($rduri) {
                        $docs[] = &new EstraierPure_ResultDocument(
                            $rduri, $rdattrs, $rdsnippet, $rdvector
                        );
                    }
                }
                $snum = $lnum;
                if (substr($line, $blen) == ':END') {
                    $isend = true;
                }
            }
        }
        if (!$isend) {
            $null = null;
            return $null;
        }
        $result = &new EstraierPure_NodeResult($docs, $hints);
        return $result;
    }

    // }}}
    // {{{ other private methods

    /**
     * Set information of the node.
     *
     * @return  void
     * @access  private
     */
    function set_info()
    {
        $this->status = -1;
        if (!$this->url) {
            return;
        }
        $turl = $this->url . '/inform';
        $reqheads = array();
        if ($this->auth) {
            $reqheads['Authorization'] = 'Basic ' . base64_encode($this->auth);
        }
        $res = &new EstraierPure_Response;
        $rv = EstraierPure_Utility::shuttle_url(
            $turl, $this->pxhost, $this->pxport, $this->timeout,
            $reqheads, null, $res);
        if (PEAR::isError($rv)) {
            return;
        }
        $this->status = $rv;
        if ($rv != 200) {
            return;
        }
        $lines = explode("\n", rtrim($res->body(), "\n"));
        if (count($lines) == 0) {
            return;
        }
        $elems = explode("\t", $lines[0]);
        if (count($elems) != 5) {
            return;
        }
        $this->name = $elems[0];
        $this->label = $elems[1];
        $this->dnum = intval($elems[2]);
        $this->wnum = intval($elems[3]);
        $this->size = floatval($elems[4]);
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_Utility

/**
 * Class for utility
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 * @static
 * @ignore
 */
class EstraierPure_Utility
{
    // {{{ public methods

    /**
     * Check types of arguments.
     *
     * @param   array   $types  Pairs of the argument and the expected type.
     * @return  void
     * @access  public
     * @static
     */
    function check_types()
    {
        $stack = &EstraierPure_Utility::errorstack();
        $code = 4; // same as PEAR::Log PEAR_LOG_WARNING
        $bt = debug_backtrace();
        $i = 0;
        foreach (func_get_args() as $types) {
            $i++;
            $var = array_shift($types);
            $type = gettype($var);
            $is_error = false;
            if ($types[0] == 'object' && isset($types[1]) && class_exists($types[1])) {
                if (!is_object($var) || !is_a($var, $types[1])) {
                    $is_error = true;
                    $params = array('argnum' => $i,
                        'given' => (is_object($var)) ? get_class($var) : $type,
                        'expected' => $types[1]);
                    $message = 'Argument#%argnum% should be an instance of %expected%,'
                             . ' %given% given.';
                }
            } elseif (!in_array($type, $types)) {
                $is_error = true;
                $params = array('argnum' => $i, 'given' => $type,
                    'expected' => implode(' or ', $types));
                $message = 'Argument#%argnum% should be a kind of %expected%,'
                         . ' %given% given.';
            }
            if ($is_error) {
                $stack->push($code, 'warning', $params, $message, false, $bt);
                $errmsg = $message;
                foreach ($params as $pname => $pvalue) {
                    $errmsg = str_replace('%' . $pname . '%', strval($pvalue), $errmsg);
                }
                trigger_error($errmsg, E_USER_WARNING);
            }
        }
    }

    /**
     * Perform an interaction of a URL.
     *
     * @param   string  $url        A URL.
     * @param   string  $pxhost     The host name of a proxy.
     *                              If it is `null', it is not used.
     * @param   int     $pxport     The port number of the proxy.
     * @param   int     $outsec     Timeout in seconds.
     *                              If it is negative, it is not used.
     * @param   array   $reqheads   An array of extension headers.
     *                              If it is `null', it is not used.
     * @param   string  $reqbody    The pointer of the entitiy body of request.
     *                              If it is `null', "GET" method is used.
     * @param   object  $res    EstraierPure_Response
     *                          an object into which headers and
     *                          the entity body of response are stored.
     *                          If it is `null', it is not used.
     * @return  int     The status code of the response.
     *                  On error, returns PEAR_Error.
     * @access  public
     * @static
     * @uses    PEAR
     * @uses    HTTP_Request
     */
    function shuttle_url($url, $pxhost, $pxport, $outsec, $reqheads, $reqbody, &$res)
    {
        // HTTPS checking disabled.
        /*$https = preg_match('!^https://!i', $url);
        if ($https && !extension_loaded('openssl')) {
            $err = PEAR::raiseError('HTTPS is not supported.');
            EstraierPure_Utility::push_error($err);
            return $err;
        }*/
        if (is_null($reqheads)) {
            $reqheads = array();
        }
        $reqheads['User-Agent'] = sprintf('EstraierPure/%s (for PHP 4.3)',
            ESTRAIERPURE_VERSION);

        if (ESTRAIERPURE_USE_HTTP_STREAM) {
            // {{{ using stream functions

            // set request parameters
            $params = array('http'=>array());
            if (is_null($reqbody)) {
                $params['http']['method'] = 'GET';
            } else {
                $params['http']['method'] = 'POST';
                $params['http']['content'] = $reqbody;
                $reqheads['Content-Length'] = strlen($reqbody);
            }
            if (!is_null($pxhost)) {
                /*if ($https && version_compare(phpversion(), '5.1.0', 'lt')) {
                    $err = PEAR::raiseError('HTTPS proxies are not supported.');
                    EstraierPure_Utility::push_error($err);
                    return $err;
                }*/
                $params['http']['proxy'] = sprintf('tcp://%s:%d', $pxhost, $pxport);
            }
            $params['http']['header'] = '';
            foreach ($reqheads as $key => $value) {
                $params['http']['header'] .= sprintf("%s: %s\r\n", $key, $value);
            }
            $context = stream_context_create($params);

            // open a stream and send the request
            $fp = fopen($url, 'r', false, $context);
            if (!$fp) {
                $err = PEAR::raiseError(sprintf('Cannot connect to %s.', $url));
                EstraierPure_Utility::push_error($err);
                return $err;
            }
            if ($outsec >= 0) {
                stream_set_timeout($fp, $outsec);
            }

            // process the response
            $first_header = array_shift($http_response_header);
            if (!preg_match('!^HTTP/(.+?) (\\d+) ?(.*)!', $first_header, $matches)) {
                $err = PEAR::raiseError('Malformed response.');
                EstraierPure_Utility::push_error($err);
                return $err;
            }
            $code = intval($matches[2]);
            if (is_object($res) && is_a($res, 'EstraierPure_Response')) {
                if ($res->save_heads) {
                    foreach ($http_response_header as $header) {
                        list($name, $value) = explode(':', $header, 2);
                        $res->add_head(strtolower($name), ltrim($value));
                    }
                }
                if ($res->save_body) {
                    $body = '';
                    while (!feof($fp)) {
                        $body .= fread($fp, 65536);
                    }
                    $res->set_body($body);
                }
            }

            // close the stream
            fclose($fp);

            // }}}
        } else {
            // {{{{ using PEAR::HTTP_Request

            // set request parameters
            $params = array();
            $params['requestHeaders'] = $reqheads;
            if (isset($params['requestHeaders']['Content-Type'])) {
                unset($params['requestHeaders']['Content-Type']);
                $params['requestHeaders']['content-type'] = $reqheads['Content-Type'];
            }
            if (!is_null($pxhost)) {
                $params['proxy_host'] = $pxhost;
                $params['proxy_port'] = $pxport;
            }
            if ($outsec >= 0) {
                $params['timeout'] = floatval($outsec);
                $params['readTimeout'] = array($outsec, 0);
            }

            // create an instance of HTTP_Request
            $req = &new HTTP_Request($url, $params);
            if (is_null($reqbody)) {
                $req->setMethod('GET');
            } else {
                $req->setMethod('POST');
                $req->setBody($reqbody);
            }

            // send the request
            $err = $req->sendRequest(is_object($res) && !empty($res->save_body));
            if (PEAR::isError($err)) {
                EstraierPure_Utility::push_error($err);
                return $err;
            }
            $code = $req->getResponseCode();

            // process the response
            if (is_object($res) && is_a($res, 'EstraierPure_Response')) {
                if ($res->save_heads) {
                    $res->set_heads($req->getResponseHeader());
                }
                if ($res->save_body) {
                    $res->set_body($req->getResponseBody());
                }
            }

            // }}}
        }

        return $code;
    }

    /**
     * Serialize a condition object into a query string.
     *
     * @param   object  $cond   EstraierPure_Condition
     *                          which is a condition object.
     * @param   int     $depth  Depth of meta search.
     * @param   int     $wwidth  Whole width of a snippet.
     * @param   int     $hwidth  Width of strings picked up from the beginning of the text.
     * @param   int     $awidth  Width of strings picked up around each highlighted word.
     * @return  string  The serialized string.
     * @access  public
     * @static
     */
    function cond_to_query(&$cond, $depth, $wwidth, $hwidth, $awidth)
    {
        $params = array();
        if ($phrase = $cond->phrase()) {
            $params['phrase'] = $phrase;
        }
        if ($attrs = $cond->attrs()) {
            foreach ($attrs as $i => $attr) {
                $params['attr' . ($i + 1)] = $attr;
            }
        }
        if ($order = $cond->order()) {
            $params['order'] = $order;
        }
        if ($skip = $cond->skip()) {
            $params['skip'] = $skip;
        }
        $params['max'] = (($max = $cond->max()) > 0) ? $max : 1 << 30;
        if (($options = $cond->options()) > 0) {
            $params['options'] = $cond->options;
        }
        if ($depth > 0) {
            $params['depth'] = $depth;
        }
        $params['wwidth'] = $wwidth;
        $params['hwidth'] = $hwidth;
        $params['awidth'] = $awidth;
        $params['nomask0']='on';
        $params['allmask']='on';
        $query = '';
        foreach ($params as $key => $value) {
            $trimmedvalue = trim($value);
            if (preg_match('/^(.*?)\*$/',$trimmedvalue, $matches)) {
                $value = '[BW] '.$matches[1];
            } else if (preg_match('/^\*(.*?)$/',$trimmedvalue, $matches)) {
                $value = '[EW] '.$matches[1];
            }
            
            $query .= sprintf('&%s=%s', urlencode($key), urlencode($value));
        }
        $query = substr($query, 1);
        return $query;
    }

    /**
     * Sanitize an attribute name, an attribute value or a hidden sentence.
     *
     * @param   string  $str  A non-sanitized string.
     * @return  string  The sanitized string.
     * @access  public
     * @static
     */
    function sanitize($str)
    {
        return trim(preg_replace('/[ \\t\\r\\n\\x0B\\f]+/', ' ', $str), ' ');
    }

    /**
     * Get an instance of PEAR_ErrorStack.
     *
     * @return  object  PEAR_ErrorStack
     * @access  public
     * @static
     */
    function &errorstack() 
    {
        $stack = &PEAR_ErrorStack::singleton('EstraierPure');
        return $stack;
    }

    /**
     * Push the error to the error stack.
     *
     * @param   object  &$error PEAR_Error  An error object
     * @return  void
     * @access  public
     * @static
     */
    function push_error(&$error) 
    {
        $stack = &EstraierPure_Utility::errorstack();
        $stack->push($error->getCode(), 'error',
            array('object' => $error), $error->getMessage(),
            false, $error->getBacktrace());
    }

    // }}}
}

// }}}
// {{{ class EstraierPure_Response

/**
 * Container for HTTP response headers and the entity body
 *
 * @category    Tools and Utilities
 * @package     EstraierPure_PHP4
 * @author      rsk <rsky0711@gmail.com>
 * @version     Release: 0.2.2
 * @ignore
 */
class EstraierPure_Response
{
    // {{{ properties

    /**
     * Whether save headers or not
     *
     * @var bool
     * @access  public
     */
    var $save_heads;

    /**
     * Whether save the entity body or not
     *
     * @var bool
     * @access  public
     */
    var $save_body;

    /**
     * Headers of response
     *
     * @var array
     * @access  private
     */
    var $heads;

    /**
     * The entity body of response
     *
     * @var string
     * @access  private
     */
    var $body;

    // }}}
    // {{{ constructor

    /**
     * Create a response storage object.
     *
     * @param   bool    $save_heads     Whether to store response headers.
     * @param   bool    $save_body      Whether to store response body.
     * @see     HTTP_Request::sendRequest()
     * @access  public
     */
    function EstraierPure_Response($save_heads = false, $save_body = true)
    {
        $this->save_heads = $save_heads;
        $this->save_body = $save_body;
        $this->heads = array();
        $this->body = null;
    }

    // }}}
    // {{{ setter methods

    /**
     * Add a header.
     *
     * @param   string  $name   The name of a header.
     * @param   string  $value  The value of the header.
     * @return  void
     * @access  public
     */
    function add_head($name, $value)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string'), array($value, 'string')
        );
        $this->heads[$name] = $value;
    }

    /**
     * Set headers of response.
     *
     * @param   string  $heads  Headers of response.
     * @return  void
     * @access  public
     */
    function set_heads($heads)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($heads, 'array')
        );
        $this->heads = array_merge($this->heads, $heads);
    }

    /**
     * Set the entity body of response
     *
     * @param   string  $body   The entity body of response.
     * @return  void
     * @access  public
     */
    function set_body($body)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($body, 'string')
        );
        $this->body = $body;
    }

    // }}}
    // {{{ getter methods

    /**
     * Get the value of a header.
     *
     * @param   string  $name  The name of a header
     * @return  string  The value of the header. If it does not exist, returns `null'.
     * @access  public
     */
    function head($name)
    {
        ESTRAIERPURE_DEBUG && EstraierPure_Utility::check_types(
            array($name, 'string')
        );
        return (isset($this->heads[$name])) ? $this->heads[$name] : null;
    }

    /**
     * Get a hash of headers.
     *
     * @return  array   All response headers.
     * @access  public
     */
    function heads()
    {
        return $this->heads;
    }

    /**
     * Get the entity body of response
     *
     * @return  string  The entity body of response. If it has not set, returns `null'.
     * @access  public
     */
    function body()
    {
        return $this->body;
    }

    // }}}
}

// }}}

/*
 * Local variables:
 * tab-width: 4
 * c-basic-offset: 4
 * indent-tabs-mode: nil
 * mode: php
 * End:
 */
// vim600:syn=php ai et ts=4 sw=4 sts=4 fdm=marker
?>
