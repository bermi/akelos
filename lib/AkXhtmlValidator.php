<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */
// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Development
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkXhtmlValidator
{
    var $_attributes = array(
    'core' => array(
    'except' => array(
    'base',
    'head',
    'html',
    'meta',
    'param',
    'script',
    'style',
    'title'
    ) ,
    'attributes' => array(
    'class',
    'id',
    'style',
    'title',
    'accesskey',
    'tabindex'
    ) ,
    ) ,
    'language' => array(
    'except' => array(
    'base',
    'br',
    'hr',
    'iframe',
    'param',
    'script'
    ) ,
    'attributes' => array(
    'dir' => array(
    'ltr',
    'rtl'
    ) ,
    'lang',
    'xml:lang'
    ) ,
    ) ,
    'keyboard' => array(
    'attributes' => array(
    'accesskey' => '/^(\w){1}$/',
    'tabindex' => '/^(\d)+$/'
    ) ,
    ) ,
    );
    var $_events = array(
    'window' => array(
    'only' => array(
    'body'
    ) ,
    'attributes' => array(
    'onload',
    'onunload'
    ) ,
    ) ,
    'form' => array(
    'only' => array(
    'form',
    'input',
    'textarea',
    'select',
    'a',
    'label',
    'button'
    ) ,
    'attributes' => array(
    'onchange',
    'onsubmit',
    'onreset',
    'onselect',
    'onblur',
    'onfocus'
    ) ,
    ) ,
    'keyboard' => array(
    'except' => array(
    'base',
    'bdo',
    'br',
    'frame',
    'frameset',
    'head',
    'html',
    'iframe',
    'meta',
    'param',
    'script',
    'style',
    'title'
    ) ,
    'attributes' => array(
    'onkeydown',
    'onkeypress',
    'onkeyup'
    ) ,
    ) ,
    'mouse' => array(
    'except' => array(
    'base',
    'bdo',
    'br',
    'head',
    'html',
    'meta',
    'param',
    'script',
    'style',
    'title'
    ) ,
    'attributes' => array(
    'onclick',
    'ondblclick',
    'onmousedown',
    'onmousemove',
    'onmouseover',
    'onmouseout',
    'onmouseup'
    ) ,
    ) ,
    );
    var $_tags = array(
    'a' => array(
    'attributes' => array(
    'charset',
    'coords',
    'href',
    'hreflang',
    'name',
    'rel' => '/^(alternate|designates|stylesheet|start|next|prev|contents|index|glossary|copyright|chapter|section|subsection|appendix|help|bookmark| |shortcut|icon)+$/',
    'rev' => '/^(alternate|designates|stylesheet|start|next|prev|contents|index|glossary|copyright|chapter|section|subsection|appendix|help|bookmark| |shortcut|icon)+$/',
    'shape' => '/^(rect|rectangle|circ|circle|poly|polygon)$/',
    'type',
    ) ,
    ) ,
    'abbr',
    'acronym',
    'address',
    'area' => array(
    'attributes' => array(
    'alt',
    'coords',
    'href',
    'nohref' => '/^(true|false)$/',
    'shape' => '/^(rect|rectangle|circ|circle|poly|polygon)$/'
    ) ,
    'required' => array(
    'alt'
    ) ,
    ) ,
    'b',
    'base' => array(
    'attributes' => array(
    'href'
    ) ,
    'required' => array(
    'href'
    )
    ) ,
    'bdo' => array(
    'attributes' => array(
    'dir' => '/^(ltr|rtl)$/'
    ) ,
    'required' => array(
    'dir'
    )
    ) ,
    'big',
    'blockquote' => array(
    'attributes' => array(
    'cite'
    )
    ) ,
    'body',
    'br',
    'button' => array(
    'attributes' => array(
    'disabled' => '/^(disabled)$/',
    'type' => '/^(button|reset|submit)$/',
    'value'
    ) ,
    'inside' => 'form'
    ) ,
    'caption',
    'cite',
    'code',
    'col' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify)$/',
    'char',
    'charoff',
    'span' => '/^(\d)+$/',
    'valign' => '/^(top|middle|bottom|baseline)$/',
    'width',
    ) ,
    'inside' => 'colgroup'
    ) ,
    'colgroup' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify)$/',
    'char',
    'charoff',
    'span' => '/^(\d)+$/',
    'valign' => '/^(top|middle|bottom|baseline)$/',
    'width',
    )
    ) ,
    'dd',
    'del' => array(
    'attributes' => array(
    'cite',
    'datetime' => '/^([0-9]){8}/'
    )
    ) ,
    'div',
    'dfn',
    'dl',
    'dt',
    'em',
    'fieldset' => array(
    'inside' => 'form'
    ) ,
    'form' => array(
    'attributes' => array(
    'action',
    'accept',
    'accept-charset',
    'enctype',
    'method' => '/^(get|post)$/'
    ) ,
    'required' => array(
    'action'
    )
    ) ,
    'head' => array(
    'attributes' => array(
    'profile'
    )
    ) ,
    'h1',
    'h2',
    'h3',
    'h4',
    'h5',
    'h6',
    'hr',
    'html' => array(
    'attributes' => array(
    'xmlns'
    )
    ) ,
    'i',
    'img' => array(
    'attributes' => array(
    'alt',
    'src',
    'height',
    'ismap',
    'longdesc',
    'usemap',
    'width'
    ) ,
    'required' => array(
    'alt',
    'src'
    ) ,
    ) ,
    'input' => array(
    'attributes' => array(
    'accept',
    'alt',
    'checked' => '/^(checked)$/',
    'disabled' => '/^(disabled)$/',
    'maxlength' => '/^(\d)+$/',
    'name',
    'readonly' => '/^(readonly)$/',
    'size' => '/^(\d)+$/',
    'src',
    'type' => '/^(button|checkbox|file|hidden|image|password|radio|reset|submit|text)$/',
    'value'
    ) ,
    'inside' => 'form'
    ) ,
    'ins' => array(
    'attributes' => array(
    'cite',
    'datetime' => '/^([0-9]){8}/'
    )
    ) ,
    'kbd',
    'label' => array(
    'attributes' => array(
    'for'
    ) ,
    'inside' => 'form'
    ) ,
    'legend',
    'li',
    'link' => array(
    'attributes' => array(
    'charset',
    'href',
    'hreflang',
    'media' => '/^(all|braille|print|projection|screen|speech|,|;| )+$/i',
    'rel' => '/^(alternate|appendix|bookmark|chapter|contents|copyright|glossary|help|home|index|next|prev|section|start|stylesheet|subsection| |shortcut|icon)+$/i',
    'rev' => '/^(alternate|appendix|bookmark|chapter|contents|copyright|glossary|help|home|index|next|prev|section|start|stylesheet|subsection| |shortcut|icon)+$/i',
    'type'
    ) ,
    'inside' => 'head'
    ) ,
    'map' => array(
    'attributes' => array(
    'id',
    'name'
    ) ,
    'required' => array(
    'id'
    )
    ) ,
    'meta' => array(
    'attributes' => array(
    'content',
    'http-equiv' => '/^(content\-type|expires|refresh|set\-cookie)$/i',
    'name',
    'scheme'
    ) ,
    'required' => array(
    'content'
    )
    ) ,
    'noscript',
    'object' => array(
    'attributes' => array(
    'archive',
    'classid',
    'codebase',
    'codetype',
    'data',
    'declare',
    'height',
    'name',
    'standby',
    'type',
    'usemap',
    'width'
    )
    ) ,
    'ol',
    'optgroup' => array(
    'attributes' => array(
    'label',
    'disabled' => '/^(disabled)$/'
    ) ,
    'required' => array(
    'label'
    )
    ) ,
    'option' => array(
    'attributes' => array(
    'label',
    'disabled' => '/^(disabled)$/',
    'selected' => '/^(selected)$/',
    'value'
    ) ,
    'inside' => 'select',
    ) ,
    'p',
    'param' => array(
    'attributes' => array(
    'type',
    'valuetype' => '/^(data|ref|object)$/',
    'valuetype',
    'value'
    ) ,
    'required' => array(
    'name'
    ) ,
    ) ,
    'pre',
    'q' => array(
    'attributes' => array(
    'cite'
    )
    ) ,
    'samp',
    'script' => array(
    'attributes' => array(
    'type' => '/^(text\/ecmascript|text\/javascript|text\/jscript|text\/vbscript|text\/vbs|text\/xml)$/',
    'charset',
    'defer' => '/^(defer)$/',
    'src'
    ) ,
    'required' => array(
    'type'
    )
    ) ,
    'select' => array(
    'attributes' => array(
    'disabled' => '/^(disabled)$/',
    'multiple' => '/^(multiple)$/',
    'name',
    'size'
    ) ,
    'inside' => 'form'
    ) ,
    'small',
    'span',
    'strong',
    'style' => array(
    'attributes' => array(
    'type',
    'media' => '/^(screen|tty|tv|projection|handheld|print|braille|aural|all)$/'
    ) ,
    'required' => array(
    'type'
    )
    ) ,
    'sub',
    'sup',
    'table' => array(
    'attributes' => array(
    'border',
    'cellpadding',
    'cellspacing',
    'frame' => '/^(void|above|below|hsides|lhs|rhs|vsides|box|border)$/',
    'rules' => '/^(none|groups|rows|cols|all)$/',
    'summary',
    'width'
    )
    ) ,
    'tbody' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify)$/',
    'char',
    'charoff',
    'valign' => '/^(top|middle|bottom|baseline)$/'
    )
    ) ,
    'td' => array(
    'attributes' => array(
    'abbr',
    'align' => '/^(left|right|center|justify|char)$/',
    'axis',
    'char',
    'charoff',
    'colspan' => '/^(\d)+$/',
    'headers',
    'rowspan' => '/^(\d)+$/',
    'scope' => '/^(col|colgroup|row|rowgroup)$/',
    'valign' => '/^(top|middle|bottom|baseline)$/'
    )
    ) ,
    'textarea' => array(
    'attributes' => array(
    'cols',
    'rows',
    'disabled',
    'name',
    'readonly'
    ) ,
    'required' => array(
    'cols',
    'rows'
    ) ,
    'inside' => 'form'
    ) ,
    'tfoot' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify)$/',
    'char',
    'charoff',
    'valign' => '/^(top|middle|bottom)$/',
    'baseline'
    )
    ) ,
    'th' => array(
    'attributes' => array(
    'abbr',
    'align' => '/^(left|right|center|justify|char)$/',
    'axis',
    'char',
    'charoff',
    'colspan' => '/^(\d)+$/',
    'headers',
    'rowspan' => '/^(\d)+$/',
    'scope' => '/^(col|colgroup|row|rowgroup)$/',
    'valign' => '/^(top|middle|bottom|baseline)$/'
    )
    ) ,
    'thead' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify)$/',
    'char',
    'charoff',
    'valign' => '/^(top|middle|bottom|baseline)$/'
    )
    ) ,
    'title',
    'tr' => array(
    'attributes' => array(
    'align' => '/^(right|left|center|justify|char)$/',
    'char',
    'charoff',
    'valign' => '/^(top|middle|bottom|baseline)$/'
    )
    ) ,
    'tt',
    'ul',
    'var',
    );

    var $_entities = array(
    '&nbsp;' => '&#160;',
    '&iexcl;' => '&#161;',
    '&cent;' => '&#162;',
    '&pound;' => '&#163;',
    '&curren;' => '&#164;',
    '&yen;' => '&#165;',
    '&brvbar;' => '&#166;',
    '&sect;' => '&#167;',
    '&uml;' => '&#168;',
    '&copy;' => '&#169;',
    '&ordf;' => '&#170;',
    '&laquo;' => '&#171;',
    '&not;' => '&#172;',
    '&shy;' => '&#173;',
    '&reg;' => '&#174;',
    '&macr;' => '&#175;',
    '&deg;' => '&#176;',
    '&plusmn;' => '&#177;',
    '&sup2;' => '&#178;',
    '&sup3;' => '&#179;',
    '&acute;' => '&#180;',
    '&micro;' => '&#181;',
    '&para;' => '&#182;',
    '&middot;' => '&#183;',
    '&cedil;' => '&#184;',
    '&sup1;' => '&#185;',
    '&ordm;' => '&#186;',
    '&raquo;' => '&#187;',
    '&frac14;' => '&#188;',
    '&frac12;' => '&#189;',
    '&frac34;' => '&#190;',
    '&iquest;' => '&#191;',
    '&Agrave;' => '&#192;',
    '&Aacute;' => '&#193;',
    '&Acirc;' => '&#194;',
    '&Atilde;' => '&#195;',
    '&Auml;' => '&#196;',
    '&Aring;' => '&#197;',
    '&AElig;' => '&#198;',
    '&Ccedil;' => '&#199;',
    '&Egrave;' => '&#200;',
    '&Eacute;' => '&#201;',
    '&Ecirc;' => '&#202;',
    '&Euml;' => '&#203;',
    '&Igrave;' => '&#204;',
    '&Iacute;' => '&#205;',
    '&Icirc;' => '&#206;',
    '&Iuml;' => '&#207;',
    '&ETH;' => '&#208;',
    '&Ntilde;' => '&#209;',
    '&Ograve;' => '&#210;',
    '&Oacute;' => '&#211;',
    '&Ocirc;' => '&#212;',
    '&Otilde;' => '&#213;',
    '&Ouml;' => '&#214;',
    '&times;' => '&#215;',
    '&Oslash;' => '&#216;',
    '&Ugrave;' => '&#217;',
    '&Uacute;' => '&#218;',
    '&Ucirc;' => '&#219;',
    '&Uuml;' => '&#220;',
    '&Yacute;' => '&#221;',
    '&THORN;' => '&#222;',
    '&szlig;' => '&#223;',
    '&agrave;' => '&#224;',
    '&aacute;' => '&#225;',
    '&acirc;' => '&#226;',
    '&atilde;' => '&#227;',
    '&auml;' => '&#228;',
    '&aring;' => '&#229;',
    '&aelig;' => '&#230;',
    '&ccedil;' => '&#231;',
    '&egrave;' => '&#232;',
    '&eacute;' => '&#233;',
    '&ecirc;' => '&#234;',
    '&euml;' => '&#235;',
    '&igrave;' => '&#236;',
    '&iacute;' => '&#237;',
    '&icirc;' => '&#238;',
    '&iuml;' => '&#239;',
    '&eth;' => '&#240;',
    '&ntilde;' => '&#241;',
    '&ograve;' => '&#242;',
    '&oacute;' => '&#243;',
    '&ocirc;' => '&#244;',
    '&otilde;' => '&#245;',
    '&ouml;' => '&#246;',
    '&divide;' => '&#247;',
    '&oslash;' => '&#248;',
    '&ugrave;' => '&#249;',
    '&uacute;' => '&#250;',
    '&ucirc;' => '&#251;',
    '&uuml;' => '&#252;',
    '&yacute;' => '&#253;',
    '&thorn;' => '&#254;',
    '&yuml;' => '&#255;',
    '&fnof;' => '&#402;',
    '&Alpha;' => '&#913;',
    '&Beta;' => '&#914;',
    '&Gamma;' => '&#915;',
    '&Delta;' => '&#916;',
    '&Epsilon;' => '&#917;',
    '&Zeta;' => '&#918;',
    '&Eta;' => '&#919;',
    '&Theta;' => '&#920;',
    '&Iota;' => '&#921;',
    '&Kappa;' => '&#922;',
    '&Lambda;' => '&#923;',
    '&Mu;' => '&#924;',
    '&Nu;' => '&#925;',
    '&Xi;' => '&#926;',
    '&Omicron;' => '&#927;',
    '&Pi;' => '&#928;',
    '&Rho;' => '&#929;',
    '&Sigma;' => '&#931;',
    '&Tau;' => '&#932;',
    '&Upsilon;' => '&#933;',
    '&Phi;' => '&#934;',
    '&Chi;' => '&#935;',
    '&Psi;' => '&#936;',
    '&Omega;' => '&#937;',
    '&alpha;' => '&#945;',
    '&beta;' => '&#946;',
    '&gamma;' => '&#947;',
    '&delta;' => '&#948;',
    '&epsilon;' => '&#949;',
    '&zeta;' => '&#950;',
    '&eta;' => '&#951;',
    '&theta;' => '&#952;',
    '&iota;' => '&#953;',
    '&kappa;' => '&#954;',
    '&lambda;' => '&#955;',
    '&mu;' => '&#956;',
    '&nu;' => '&#957;',
    '&xi;' => '&#958;',
    '&omicron;' => '&#959;',
    '&pi;' => '&#960;',
    '&rho;' => '&#961;',
    '&sigmaf;' => '&#962;',
    '&sigma;' => '&#963;',
    '&tau;' => '&#964;',
    '&upsilon;' => '&#965;',
    '&phi;' => '&#966;',
    '&chi;' => '&#967;',
    '&psi;' => '&#968;',
    '&omega;' => '&#969;',
    '&thetasym;' => '&#977;',
    '&upsih;' => '&#978;',
    '&piv;' => '&#982;',
    '&bull;' => '&#8226;',
    '&hellip;' => '&#8230;',
    '&prime;' => '&#8242;',
    '&Prime;' => '&#8243;',
    '&oline;' => '&#8254;',
    '&frasl;' => '&#8260;',
    '&weierp;' => '&#8472;',
    '&image;' => '&#8465;',
    '&real;' => '&#8476;',
    '&trade;' => '&#8482;',
    '&alefsym;' => '&#8501;',
    '&larr;' => '&#8592;',
    '&uarr;' => '&#8593;',
    '&rarr;' => '&#8594;',
    '&darr;' => '&#8595;',
    '&harr;' => '&#8596;',
    '&crarr;' => '&#8629;',
    '&lArr;' => '&#8656;',
    '&uArr;' => '&#8657;',
    '&rArr;' => '&#8658;',
    '&dArr;' => '&#8659;',
    '&hArr;' => '&#8660;',
    '&forall;' => '&#8704;',
    '&part;' => '&#8706;',
    '&exist;' => '&#8707;',
    '&empty;' => '&#8709;',
    '&nabla;' => '&#8711;',
    '&isin;' => '&#8712;',
    '&notin;' => '&#8713;',
    '&ni;' => '&#8715;',
    '&prod;' => '&#8719;',
    '&sum;' => '&#8721;',
    '&minus;' => '&#8722;',
    '&lowast;' => '&#8727;',
    '&radic;' => '&#8730;',
    '&prop;' => '&#8733;',
    '&infin;' => '&#8734;',
    '&ang;' => '&#8736;',
    '&and;' => '&#8743;',
    '&or;' => '&#8744;',
    '&cap;' => '&#8745;',
    '&cup;' => '&#8746;',
    '&int;' => '&#8747;',
    '&there4;' => '&#8756;',
    '&sim;' => '&#8764;',
    '&cong;' => '&#8773;',
    '&asymp;' => '&#8776;',
    '&ne;' => '&#8800;',
    '&equiv;' => '&#8801;',
    '&le;' => '&#8804;',
    '&ge;' => '&#8805;',
    '&sub;' => '&#8834;',
    '&sup;' => '&#8835;',
    '&nsub;' => '&#8836;',
    '&sube;' => '&#8838;',
    '&supe;' => '&#8839;',
    '&oplus;' => '&#8853;',
    '&otimes;' => '&#8855;',
    '&perp;' => '&#8869;',
    '&sdot;' => '&#8901;',
    '&lceil;' => '&#8968;',
    '&rceil;' => '&#8969;',
    '&lfloor;' => '&#8970;',
    '&rfloor;' => '&#8971;',
    '&lang;' => '&#9001;',
    '&rang;' => '&#9002;',
    '&loz;' => '&#9674;',
    '&spades;' => '&#9824;',
    '&clubs;' => '&#9827;',
    '&hearts;' => '&#9829;',
    '&diams;' => '&#9830;',
    '&quot;' => '&#34;',
    '&amp;' => '&#38;',
    '&lt;' => '&#60;',
    '&gt;' => '&#62;',
    '&OElig;' => '&#338;',
    '&oelig;' => '&#339;',
    '&Scaron;' => '&#352;',
    '&scaron;' => '&#353;',
    '&Yuml;' => '&#376;',
    '&circ;' => '&#710;',
    '&tilde;' => '&#732;',
    '&ensp;' => '&#8194;',
    '&emsp;' => '&#8195;',
    '&thinsp;' => '&#8201;',
    '&zwnj;' => '&#8204;',
    '&zwj;' => '&#8205;',
    '&lrm;' => '&#8206;',
    '&rlm;' => '&#8207;',
    '&ndash;' => '&#8211;',
    '&mdash;' => '&#8212;',
    '&lsquo;' => '&#8216;',
    '&rsquo;' => '&#8217;',
    '&sbquo;' => '&#8218;',
    '&ldquo;' => '&#8220;',
    '&rdquo;' => '&#8221;',
    '&bdquo;' => '&#8222;',
    '&dagger;' => '&#8224;',
    '&Dagger;' => '&#8225;',
    '&permil;' => '&#8240;',
    '&lsaquo;' => '&#8249;',
    '&rsaquo;' => '&#8250;',
    '&euro;' => '&#8364;'
    );

    var $_parser;
    var $_stack = array();
    var $_errors = array();

    function AkXhtmlValidator()
    {
        $this->_parser = xml_parser_create('');
        xml_set_object($this->_parser, &$this);
        xml_set_element_handler($this->_parser, 'tagOpen', 'tagClose');
        xml_set_character_data_handler($this->_parser, 'cdata');
        xml_parser_set_option($this->_parser, XML_OPTION_CASE_FOLDING, false);
        xml_parser_set_option($this->_parser, XML_OPTION_TARGET_ENCODING, 'UTF-8');
    }

    function validateTagAttributes($tag, $attributes = array())
    {
        $possible_attributes = $this->getPossibleTagAttributes($tag);
        foreach($attributes as $attribute => $value) {
            if (!in_array($attribute, $possible_attributes)) {
                $this->addError(Ak::t("Attribute %attribute can't be used inside &lt;%tag> tags", array(
                '%attribute' => $attribute,
                '%tag' => $tag
                )) , array(
                array(
                $attribute,
                $tag
                )
                ));
            } elseif ($this->doesAttributeNeedsValidation($tag, $attribute)) {
                $this->validateAttribute($tag, $attribute, $value);
            }
        }
    }

    function doesAttributeNeedsValidation($tag, $attribute)
    {
        return isset($this->_tags[$tag]['attributes'][$attribute]) || isset($this->_tags[$tag]['required']) && in_array($attribute, $this->_tags[$tag]['required']);
    }

    function validateAttribute($tag, $attribute, $value = null)
    {
        if (isset($this->_tags[$tag]['attributes'][$attribute]) && (strlen($value) > 0)) {
            if (!preg_match($this->_tags[$tag]['attributes'][$attribute], $value)) {
                $this->addError(Ak::t("Invalid value on &lt;%tag %attribute=\"%value\"... Valid values must match the pattern \"%pattern\"", array(
                '%tag' => $tag,
                '%attribute' => $attribute,
                '%value' => $value,
                '%pattern' => htmlentities($this->_tags[$tag]['attributes'][$attribute])
                )) , array(
                array(
                $attribute,
                $value
                )
                ));
            }
        }
        if (isset($this->_tags[$tag]['required']) && in_array($attribute, $this->_tags[$tag]['required']) && (strlen($value) == 0)) {
            $this->addError(Ak::t("Missing required attribute %attribute on &lt;%tag&gt;", array(
            '%tag' => $tag,
            '%attribute' => $attribute
            )) , array(
            array(
            $tag,
            $attribute
            )
            ));
        }
    }

    function addError($error, $highlight_text = array())
    {
        $this->_errors[] = $this->highlightError($error, $highlight_text) .' on line '.$this->getCurrentLine();
    }

    function highlightError($error, $highlight_text = array())
    {
        if (empty($highlight_text)) {
            return $error;
        }
        require_once (AK_LIB_DIR.DS.'AkColor.php');
        require_once (AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'text_helper.php');
        $line = $this->getCurrentLine();
        $highlighted_error = '';
        foreach($highlight_text as $phrases) {
            $color = AkColor::getRandomHex();
            if (is_array($phrases)) {
                $highlighted_error_line = $error;
                foreach($phrases as $phrase) {
                    $this->_linesToHighlight[$line][$error] = array(
                    'color' => $color,
                    'phrase' => htmlentities($phrase)
                    );
                    $highlighted_error_line = TextHelper::highlight($highlighted_error_line, $phrase.' ', ' <strong style="border:2px solid #'.$color.'; background: #ffc;">\1</strong> ');
                }
                $highlighted_error.= $highlighted_error_line;
            } else {
                $highlighted_error = TextHelper::highlight($error, $phrases.' ', ' <strong style="border:2px solid #'.$color.'; background: #ffc">\1</strong> ');
                $this->_linesToHighlight[$line][$error] = array(
                'color' => $color,
                'phrase' => htmlentities($phrases)
                );
            }
        }
        return $highlighted_error;
    }

    function highlightErrors($xhtml)
    {
        $highlighted_xhtml = array();
        if (!empty($this->_linesToHighlight)) {
            $xhtml_arr = preg_split('/\n|\r/', $xhtml);
            foreach($xhtml_arr as $k => $xhtml_line) {
                $pos = $k+$this->_startLine;
                $highlighted_xhtml[$k] = $pos."&nbsp;&nbsp;&nbsp;&nbsp;";
                $xhtml_line = htmlentities($xhtml_line);
                if (isset($this->_linesToHighlight[$pos])) {
                    foreach($this->_linesToHighlight[$pos] as $highlight_details) {
                        $highlighted_xhtml[$k].= TextHelper::highlight($xhtml_line, $highlight_details['phrase'], '<strong style="border:2px solid #'.$highlight_details['color'].';padding:1px; margin:1px; background: #ffc;">\1</strong>');
                    }
                } else {
                    $highlighted_xhtml[$k].= $xhtml_line;
                }
                $highlighted_xhtml[$k].= "<br />\n";
            }
        }
        return empty($highlighted_xhtml) ? $xhtml : join($highlighted_xhtml);
    }

    function getCurrentLine()
    {
        return xml_get_current_line_number($this->_parser) +$this->_startLine;
    }

    function hasErrors(&$xhtml)
    {
        $this->validateUniquenessOfIds();
        if (count($this->getErrors()) > 0) {
            $xhtml = $this->highlightErrors($xhtml);
            return true;
        } else {
            return false;
        }
    }

    function getErrors()
    {
        return array_unique($this->_errors);
    }

    function showErrors()
    {
        echo '<ul><li>'.join("</li>\n<li>", $this->getErrors()) .'</li></ul>';
    }

    function getPossibleTagAttributes($tag)
    {
        static $cache;
        if (!isset($cache[$tag])) {
            $cache[$tag] = array_unique(array_merge($this->getUniqueAttributesAndEventsForTag($tag) , $this->getDefaultAttributesAndEventsForTag($tag)));
            sort($cache[$tag]);
        }
        return $cache[$tag];
    }

    function validateRequiredAttributes($tag, $attributes)
    {
        $compulsory = $this->getCompulsoryTagAttributes($tag);
        $errors = array_diff($compulsory, array_keys($attributes));
        if (!empty($errors)) {
            $this->addError(Ak::t('Tag %tag requires %attributes to be defined', array(
            '%tag' => $tag,
            '%attributes' => (count($errors) == 1 ? 'attribute "' : 'attributes "') .join('", "', $errors) .'"'
            )) , array(
            $tag
            ));
        }
    }

    function protectFromDuplicatedIds($tag, $attributes)
    {
        if (isset($attributes['id'])) {
            if (isset($this->_idTagXref[$attributes['id']])) {
                $this->addError(Ak::t('Repeating id %id', array(
                '%id' => $attributes['id']
                )) , array(
                $attributes['id']
                ));
            }
            $this->_tagIdCounter[$attributes['id']] = isset($this->_tagIdCounter[$attributes['id']]) ? $this->_tagIdCounter[$attributes['id']]+1 : 1;
            $this->_idTagXref[$attributes['id']][] = $tag;
        }
    }

    function validateUniquenessOfIds()
    {
        if (isset($this->_tagIdCounter) && max(array_values($this->_tagIdCounter)) > 1) {
            foreach($this->_tagIdCounter as $id => $count) {
                if ($count > 1) {
                    $this->addError(Ak::t('You have repeated the id %id %count times on your xhtml code. Duplicated Ids found on %tags', array(
                    '%id' => "\"$id\"",
                    '%count' => $count,
                    '%tags' => (count($this->_idTagXref[$id]) == 1 ? 'tag "' : 'tag "') .join('", "', $this->_idTagXref[$id]) .'"'
                    )));
                }
            }
        }
    }

    function getCompulsoryTagAttributes($tag)
    {
        return !empty($this->_tags[$tag]['required']) ? (array)$this->_tags[$tag]['required'] : array();
    }

    function getUniqueAttributesAndEventsForTag($tag)
    {
        $result = array();
        if (isset($this->_tags[$tag]['attributes']) && is_array($this->_tags[$tag]['attributes'])) {
            foreach($this->_tags[$tag]['attributes'] as $k => $candidate) {
                $result[] = is_numeric($k) ? $candidate : $k;
            }
        }
        return $result;
    }

    function getDefaultAttributesAndEventsForTag($tag)
    {
        $default = array();
        if (isset($this->_tags[$tag]) || in_array($tag, $this->_tags)) {
            foreach($this->getDefaultAttributesAndEventsForTags() as $defaults) {
                if ((isset($defaults['except']) && in_array($tag, $defaults['except'])) || (isset($defaults['only']) && !in_array($tag, $defaults['only']))) {
                    continue;
                }
                foreach(isset($defaults['attributes']) ? $defaults['attributes'] : $defaults['events'] as $k => $candidate) {
                    $default[] = is_array($candidate) ? $k : $candidate;;
                }
            }
        }
        return $default;
    }

    function getDefaultAttributesAndEventsForTags()
    {
        if (!isset($this->default_values_for_tags)) {
            $this->default_values_for_tags = array_merge($this->_attributes, $this->_events);
        }
        return $this->default_values_for_tags;
    }

    function getAvailableTags()
    {
        $tags = array();
        foreach(array_keys($this->_tags) as $k) {
            $tags[] = is_numeric($k) ? $this->_tags[$k] : $k;
        }
        sort($tags);
        return $tags;
    }

    function validate(&$xhtml)
    {
        $this->_startLine = 1;
        $xhtml_copy = $this->removeDoctypeHeader($xhtml);
        $xhtml_copy = $this->removeCdata($xhtml_copy);
        $xhtml_copy = $this->convertLiteralEntitiesToNumericalEntities($xhtml_copy);
        $xhtml_copy = '<all>'.$xhtml_copy.'</all>';
        if (!xml_parse($this->_parser, $xhtml_copy)) {
            $this->addError(Ak::t('XHTML is not well-formed.') .' '.xml_error_string(xml_get_error_code($this->_parser)));
        }
        return !$this->hasErrors($xhtml);
    }

    function removeDoctypeHeader($xhtml)
    {
        if (substr($xhtml, 0, 9) == '<!DOCTYPE') {
            $replacement = substr($xhtml, 0, strpos($xhtml, '>'));
            $this->_startLine = count(substr_count($replacement, "\n"));
        }
        return (isset($replacement)) ? substr($xhtml, strlen($replacement)) : $xhtml;
    }

    function removeCdata($xhtml)
    {
        $xhtml = preg_replace('(<\!\[CDATA\[(.|\n)*\]\]>)', '', $xhtml);
        return str_replace(array('<![CDATA[',']]>') , '', $xhtml);
    }
    

    function convertLiteralEntitiesToNumericalEntities($xhtml)
    {
        return str_replace(array_keys($this->_entities), array_values($this->_entities), $xhtml);
    }

    function tagOpen($parser, $tag, $attributes)
    {
        $this->_start_byte = xml_get_current_byte_index($parser);
        if ($tag == 'all') {
            $this->_stack[] = 'all';
            return;
        }
        $previous = $this->_stack[count($this->_stack) -1];
        $this->validateRequiredAttributes($tag, $attributes);
        $this->protectFromDuplicatedIds($tag, $attributes);
        if (!in_array($previous, $this->getAvailableTags())) {
            $this->validateTagAttributes($tag, $attributes);
            $this->_stack[] = $tag;
            return;
        }
        if (!in_array($tag, $this->getAvailableTags())) {
            $this->addError(Ak::t("Illegal tag: <code>%tag</code>", array(
            '%tag' => $tag
            )) , array(
            $tag
            ));
            $this->_stack[] = $tag;
            return;
        }
        // Is tag allowed in the current context?
        if (!$this->isTagAlowedOnCurrentContext($tag, $previous)) {
            if ($previous != 'all') {
                //$this->addError(Ak::t("Tag <code>%tag</code> must occur inside another tag",array('%tag'=>$tag)));
                //} else {
                $this->addError(Ak::t("Tag %tag is not allowed within tag %previous", array(
                '%tag' => $tag,
                '%previous' => $previous
                )) , array(
                $tag
                ));
            }
        }
        $this->validateTagAttributes($tag, $attributes);
        $this->_stack[] = $tag;
    }

    function isTagAlowedOnCurrentContext($tag, $previous)
    {
        $rules = $this->getRules();
        $result = isset($rules[$previous]) ? in_array($tag, $rules[$previous]) : true;
        $inverse_rules = $this->getInverseRulesForTag($tag);
        $result = isset($inverse_rules[$tag]) ? in_array($previous, $inverse_rules[$tag]) : $result;
        return $result;
    }

    function getRules()
    {
        static $rules;
        if (!isset($rules)) {
            //$inline = array ('abbr','cite','code','dfn','em','kbd','object','quote','q','samp','span','strong','var','a','sup','sub','acronym','img','#PCDATA');
            $inline = array(
            '#pcdata',
            'a',
            'abbr',
            'acronym',
            'applet',
            'b',
            'basefont',
            'bdo',
            'big',
            'br',
            'button',
            'cite',
            'code',
            'dfn',
            'em',
            'font',
            'i',
            'img',
            'input',
            'kbd',
            'label',
            'map',
            'object',
            'q',
            's',
            'samp',
            'select',
            'small',
            'span',
            'strike',
            'strong',
            'sub',
            'sup',
            'textarea',
            'tt',
            'u',
            'var'
            );
            //$block = array('dl','nl','ol','ul','address','blockcode','blockquote','div','p','pre','handler','section','separator','table');
            $block = array(
            'address',
            'blockcode',
            'blockquote',
            'center',
            'dir',
            'div',
            'dl',
            'fieldset',
            'form',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'handler',
            'hr',
            'iframe',
            'isindex',
            'menu',
            'nl',
            'noframes',
            'script',
            'noscript',
            'ol',
            'p',
            'pre',
            'section',
            'separator',
            'table',
            'ul'
            );
            $flow = array_merge($block, $inline);
            $rules = array(
            'html' => array(
            'head',
            'body'
            ) ,
            'head' => array(
            'script',
            'style',
            'meta',
            'base',
            'link',
            'title'
            ) ,
            'body' => array_merge(array(
            'ins',
            'del'
            ) , $flow) ,
            'ul' => array(
            'li'
            ) ,
            'ol' => array(
            'li'
            ) ,
            //'p' => array_merge($inline, array('blockcode', 'blockquote', 'pre', 'table', 'dl', 'nl', 'ol', 'ul')),
            'blockquote' => $block,
            'dl' => array(
            'dt',
            'dd'
            ) ,
            'pre' => array_diff($inline, array(
            'img',
            'object',
            'big',
            'small',
            'sub',
            'sup'
            )) ,
            'form' => array_diff($flow, array(
            'form'
            )) ,
            // Tables
            'table' => array(
            'caption',
            'colgroup',
            'col',
            'thead',
            'tbody',
            'tr'
            ) ,
            'colgroup' => array(
            'col'
            ) ,
            'thead' => array(
            'tr'
            ) ,
            'tbody' => array(
            'tr'
            ) ,
            'tr' => array(
            'th',
            'td'
            ) ,
            'address' => array_merge($inline, array(
            'p'
            )) ,
            'fieldset' => array_merge($flow, array(
            'legend'
            )) ,
            'a' => array_diff($inline, array(
            'a'
            )) ,
            'object' => array_merge($flow, array(
            'param'
            )) ,
            'map' => array_merge($block, array(
            'area'
            )) ,
            'select' => array(
            'optgroup',
            'option'
            ) ,
            'optgroup' => array(
            'option'
            ) ,
            'label' => array_diff($inline, array(
            'label'
            )) ,
            'button' => array_diff($flow, array(
            'a',
            'input',
            'select',
            'textarea',
            'label',
            'button',
            'form',
            'fieldset',
            'iframe'
            )) ,
            );
            $flow_tags = array(
            'div',
            'center',
            'blockquote',
            'script',
            'noscript',
            'dd',
            'li',
            'th',
            'td'
            );
            foreach($flow_tags as $flow_tag) {
                $rules[$flow_tag] = $flow;
            }
            $inline_tags = array(
            'p',
            'h1',
            'h2',
            'h3',
            'h4',
            'h5',
            'h6',
            'dt',
            'caption',
            'legend',
            'tt',
            'abbr',
            'acronym',
            'b',
            'bdo',
            'big',
            'cite',
            'code',
            'dfn',
            'em',
            'font',
            'i',
            'kbd',
            'q',
            's',
            'samp',
            'small',
            'span',
            'strike',
            'strong',
            'sub',
            'sup',
            'u',
            'var'
            );
            foreach($inline_tags as $inline_tag) {
                $rules[$inline_tag] = $inline;
            }
        }
        return $rules;
    }

    function getInverseRulesForTag($tag)
    {
        static $inverse_rules;
        if (!isset($inverse_rules[$tag])) {
            $inverse_rules[$tag] = array();
            $rules = $this->getRules();
            foreach($rules as $container_tag => $rule) {
                if (in_array($rule, $rule)) {
                    $inverse_rules[$tag][] = $container_tag;
                }
            }
        }
        return $inverse_rules[$tag];
    }

    function cdata($parser, $cdata)
    {
        // Simply check that the 'previous' tag allows CDATA
        $previous = $this->_stack[count($this->_stack) -1];
        if ($cdata != '' && in_array($previous, array(
        'base',
        'area',
        'basefont',
        'br',
        'col',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param'
        ))) {
            $this->addError(Ak::t("%previous tag is not a content tag. close it like this '&lt;%previous /&gt;'", array(
            '%previous' => $previous
            )) , array(
            $previous
            ));
        }
        // If previous tag is illegal, no point in running test
        if (!in_array($previous, $this->getAvailableTags())) {
            return;
        }
        if (trim($cdata) != '') {
            if (!$this->isTagAlowedOnCurrentContext('#pcdata', $previous)) {
                $this->addError(Ak::t("Tag <code>%previous</code> may not contain raw character data", array(
                '%previous' => $previous
                )) , array(
                $previous
                ));
            }
        }
    }

    function tagClose($parser, $tag)
    {
        if (in_array($tag, array(
        'base',
        'area',
        'basefont',
        'br',
        'col',
        'hr',
        'img',
        'input',
        'link',
        'meta',
        'param'
        ))) {
            $this->_end_byte = xml_get_current_byte_index($parser);
            if ($this->_end_byte-$this->_start_byte == 4) {
                $this->addError(Ak::t("%tag tag is not a content tag. close it like this '&lt;%tag /&gt;'", array(
                '%tag' => $tag
                )) , array(
                $tag
                ));
            }
        }
        array_pop($this->_stack);
    }

}

?>
