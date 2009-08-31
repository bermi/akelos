<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * Converts a PDF into text in order to index it for full text searching
 * 
 * @package ActiveSupport
 * @subpackage Converters
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */
class AkPdfToText
{

    public function extractTextFromPdf($postScriptData)
    {
        if (!is_string($postScriptData)) {
            return '';
        }
        $text = '';
        $postScriptData = str_replace('\)', '##ENDBRACKET##', $postScriptData);
        $postScriptData = str_replace('\]', '##ENDSBRACKET##', $postScriptData);
        preg_match_all(
        '/(T[wdcm*])[\s]*(\[([^\]]*)\]|\(([^\)]*)\))[\s]*Tj/si',
        $postScriptData,
        $matches
        );
        for ($i = 0; $i < sizeof($matches[0]); $i++) {
            if ($matches[3][$i] != '') {
                preg_match_all('/\(([^)]*)\)/si', $matches[3][$i], $subMatches);
                foreach ($subMatches[1] as $subMatch) {
                    $text .= $subMatch;
                }
            } else if ($matches[4][$i] != '') {
                $text .= ($matches[1][$i] == 'Tc' ? ' ' : '') . $matches[4][$i];
            }
        }
        $trans = array(
        '...'                => '&hellip;',
        '\205'                => '&hellip;',
        '\221'                => chr(145),
        '\222'                => chr(146),
        '\223'                => chr(147),
        '\224'                => chr(148),
        '\363'                => chr(243),
        '\226'                => '-',
        '\267'                => '&bull;',
        '\('                => '(',
        '\['                => '[',
        '##ENDBRACKET##'    => ')',
        '##ENDSBRACKET##'    => ']',
        chr(133)            => '-',
        chr(141)            => chr(147),
        chr(142)            => chr(148),
        chr(143)            => chr(145),
        chr(144)            => chr(146),
        '\032' => chr(136), '\036' => chr(176), '\037' => chr(152), '\041' => chr(33), '\042' => chr(34), '\043' => chr(35), '\044' => chr(36), '\045' => chr(37), '\046' => chr(38), '\047' => chr(39), '\050' => chr(40), '\051' => chr(41), '\052' => chr(42), '\053' => chr(43), '\054' => chr(44), '\055' => chr(45), '\056' => chr(46), '\057' => chr(47), '\061' => chr(49), '\062' => chr(50), '\063' => chr(51), '\064' => chr(52), '\065' => chr(53), '\066' => chr(54), '\067' => chr(55), '\070' => chr(56), '\071' => chr(57), '\072' => chr(58), '\073' => chr(59), '\074' => chr(60), '\075' => chr(61), '\076' => chr(62), '\100' => chr(64), '\101' => chr(65), '\102' => chr(66), '\103' => chr(67), '\104' => chr(68), '\105' => chr(69), '\106' => chr(70), '\107' => chr(71), '\110' => chr(72), '\111' => chr(73), '\112' => chr(74), '\113' => chr(75), '\114' => chr(76), '\115' => chr(77), '\116' => chr(78), '\117' => chr(79), '\120' => chr(80), '\121' => chr(81), '\122' => chr(82), '\123' => chr(83), '\124' => chr(84), '\125' => chr(85), '\126' => chr(86), '\127' => chr(87), '\130' => chr(88), '\131' => chr(89), '\132' => chr(90), '\133' => chr(91), '\134' => chr(92), '\135' => chr(93), '\136' => chr(94), '\137' => chr(95), '\140' => chr(96), '\141' => chr(97), '\142' => chr(98), '\143' => chr(99), '\144' => chr(100), '\145' => chr(101), '\146' => chr(102), '\147' => chr(103), '\150' => chr(104), '\151' => chr(105), '\152' => chr(106), '\153' => chr(107), '\154' => chr(108), '\155' => chr(109), '\156' => chr(110), '\157' => chr(111), '\160' => chr(112), '\161' => chr(113), '\162' => chr(114), '\163' => chr(115), '\164' => chr(116), '\165' => chr(117), '\166' => chr(118), '\167' => chr(119), '\170' => chr(120), '\171' => chr(121), '\173' => chr(123), '\174' => chr(124), '\175' => chr(125), '\176' => chr(126), '\200' => chr(149), '\201' => chr(134), '\202' => chr(135), '\203' => chr(133), '\204' => chr(151), '\205' => chr(150), '\206' => chr(131), '\207' => chr(47), '\210' => chr(139), '\211' => chr(155), '\212' => chr(45), '\213' => chr(137), '\214' => chr(132), '\215' => chr(147), '\216' => chr(148), '\217' => chr(145), '\220' => chr(146), '\221' => chr(130), '\222' => chr(153), '\223' => chr(102), '\224' => chr(102), '\225' => chr(76), '\226' => chr(79), '\227' => chr(138), '\230' => chr(159), '\231' => chr(142), '\232' => chr(105), '\233' => chr(108), '\234' => chr(111), '\235' => chr(154), '\240' => chr(128), '\241' => chr(161), '\242' => chr(162), '\243' => chr(163), '\244' => chr(164), '\246' => chr(166), '\247' => chr(167), '\250' => chr(168), '\251' => chr(169), '\252' => chr(170), '\253' => chr(171), '\254' => chr(172), '\256' => chr(174), '\257' => chr(175), '\260' => chr(176), '\261' => chr(177), '\262' => chr(178), '\263' => chr(179), '\264' => chr(180), '\265' => chr(181), '\266' => chr(182), '\267' => chr(183), '\270' => chr(184), '\271' => chr(185), '\272' => chr(186), '\273' => chr(187), '\274' => chr(188), '\275' => chr(189), '\276' => chr(190), '\277' => chr(191), '\300' => chr(192), '\301' => chr(193), '\302' => chr(194), '\303' => chr(195), '\304' => chr(196), '\305' => chr(197), '\306' => chr(198), '\307' => chr(199), '\310' => chr(200), '\311' => chr(201), '\312' => chr(202), '\313' => chr(203), '\314' => chr(204), '\315' => chr(205), '\316' => chr(206), '\317' => chr(207), '\320' => chr(208), '\321' => chr(209), '\322' => chr(210), '\323' => chr(211), '\324' => chr(212), '\325' => chr(213), '\326' => chr(214), '\327' => chr(215), '\330' => chr(216), '\331' => chr(217), '\332' => chr(218), '\333' => chr(219), '\334' => chr(220), '\335' => chr(221), '\336' => chr(222), '\337' => chr(223), '\340' => chr(224), '\341' => chr(225), '\342' => chr(226), '\343' => chr(227), '\344' => chr(228), '\345' => chr(229), '\346' => chr(230), '\347' => chr(231), '\350' => chr(232), '\351' => chr(233), '\352' => chr(234), '\353' => chr(235), '\354' => chr(236), '\355' => chr(237), '\356' => chr(238), '\357' => chr(239), '\360' => chr(240), '\361' => chr(241), '\362' => chr(242), '\363' => chr(243), '\364' => chr(244), '\365' => chr(245), '\366' => chr(246), '\367' => chr(247), '\370' => chr(248), '\371' => chr(249), '\372' => chr(250), '\373' => chr(251), '\374' => chr(252), '\375' => chr(253), '\376' => chr(254),
        );

        return strtr($text, $trans);

    }

    public function convert()
    {
        $searchstart = 'stream';
        $searchend = 'endstream';
        $pdfText = '';
        $pos = 0;
        $pos2 = 0;
        $startpos = 0;
        while ($pos !== false && $pos2 !== false) {
            $pos = strpos($this->source, $searchstart, $startpos);
            $pos2 = strpos($this->source, $searchend, $startpos + 1);
            if ($pos !== false && $pos2 !== false){
                if ($this->source[$pos] == 0x0d && $this->source[$pos + 1] == 0x0a) {
                    $pos += 2;
                } else if ($this->source[$pos] == 0x0a) {
                    $pos++;
                }
                if ($this->source[$pos2 - 2] == 0x0d && $this->source[$pos2 - 1] == 0x0a) {
                    $pos2 -= 2;
                } else if ($this->source[$pos2 - 1] == 0x0a) {
                    $pos2--;
                }
                $textsection = substr(
                $this->source,
                $pos + strlen($searchstart) + 2,
                $pos2 - $pos - strlen($searchstart) - 1
                );
                $data = @gzuncompress($textsection);
                $pdfText .= $this->extractTextFromPdf($data);
                $startpos = $pos2 + strlen($searchend) - 1;

            }
        }

        return preg_replace('/(\s)+/', ' ', $pdfText);


    }

}

?>
