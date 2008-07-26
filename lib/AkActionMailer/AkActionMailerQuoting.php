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
 * @subpackage AkActionMailer
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2008, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


defined('AK_ACTION_MAILER_CHARS_NEEDING_QUOTING_REGEX') ? null :
define('AK_ACTION_MAILER_CHARS_NEEDING_QUOTING_REGEX', "/[\\000-\\011\\013\\014\\016-\\037\\177-\\377]/");
ak_define('ACTION_MAILER_EMULATE_IMAP_8_BIT', true);

class AkActionMailerQuoting
{

    /**
     * Convert the given text into quoted printable format, with an instruction
     * that the text be eventually interpreted in the given charset.
     */
    function quotedPrintable($text, $charset = AK_ACTION_MAILER_DEFAULT_CHARSET)
    {
        $text = str_replace(' ','_', preg_replace('/[^a-z ]/ie', 'AkActionMailerQuoting::quotedPrintableEncode("$0")', $text));
        return "=?$charset?Q?$text?=";
    }

    /**
     * Convert the given character to quoted printable format, taking into
     * account multi-byte characters
     */
    function quotedPrintableEncode($character, $emulate_imap_8bit = AK_ACTION_MAILER_EMULATE_IMAP_8_BIT)
    {
        $lines = preg_split("/(?:\r\n|\r|\n)/", $character);
        $search_pattern = $emulate_imap_8bit ? '/[^\x20\x21-\x3C\x3E-\x7E]/e' : '/[^\x09\x20\x21-\x3C\x3E-\x7E]/e';
        foreach ((array)$lines as $k=>$line){
            if (empty($line)){
                continue;
            }
            
            $line = preg_replace($search_pattern, 'sprintf( "=%02X", ord ( "$0" ) ) ;', $line );
            $length = strlen($line);
            
            $last_char = ord($line[$length-1]);
            if (!($emulate_imap_8bit && ($k==count($lines)-1)) && ($last_char==0x09) || ($last_char==0x20)) {
                $line[$length-1] = '=';
                $line .= ($last_char==0x09) ? '09' : '20';
            }
            if ($emulate_imap_8bit) {
                $line = str_replace(' =0D', '=20=0D', $line);
            }
            $lines[$k] = $line;
        }
        return implode(AK_ACTION_MAILER_EOL,$lines);
    }


    /**
    * Quote the given text if it contains any "illegal" characters
    */
    function quoteIfNecessary($text, $charset = AK_ACTION_MAILER_DEFAULT_CHARSET)
    {
        return preg_match(AK_ACTION_MAILER_CHARS_NEEDING_QUOTING_REGEX,$text) ? AkActionMailerQuoting::quotedPrintable($text,$charset) : $text;
    }

    /**
    * Quote any of the given strings if they contain any "illegal" characters
    */
    function quoteAnyIfNecessary($strings = array(), $charset = AK_ACTION_MAILER_DEFAULT_CHARSET)
    {
        foreach ($strings as $k=>$v){
            $strings[$k] = AkActionMailerQuoting::quoteIfNecessary($charset, $v);
        }
        return $strings;
    }

    /**
     *  Quote the given address if it needs to be. The address may be a
     * regular email address, or it can be a phrase followed by an address in
     * brackets. The phrase is the only part that will be quoted, and only if
     * it needs to be. This allows extended characters to be used in the
     * "to", "from", "cc", and "bcc" headers.
     */
    function quoteAddressIfNecessary($address, $charset = AK_ACTION_MAILER_DEFAULT_CHARSET)
    {
        if(is_array($address)){
            return join(", ".AK_ACTION_MAILER_EOL."     ",AkActionMailerQuoting::quoteAnyAddressIfNecessary($address, $charset));
        }elseif (preg_match('/^(\S.*)\s+(<?('.AK_ACTION_MAILER_EMAIL_REGULAR_EXPRESSION.')>?)$/i', $address, $match)){
            $address = $match[3];
            $phrase = AkActionMailerQuoting::quoteIfNecessary(preg_replace('/^[\'"](.*)[\'"]$/', '$1', $match[1]), $charset);
            return "$phrase <$address>";
        }else{
            return $address;
        }
    }

    /**
     *  Quote any of the given addresses, if they need to be.
     */
    function quoteAnyAddressIfNecessary($address = array(), $charset = AK_ACTION_MAILER_DEFAULT_CHARSET)
    {
        foreach ($address as $k=>$v){
            $address[$k] = AkActionMailerQuoting::quoteAddressIfNecessary($v,$charset);
        }
        return $address;
    }

    function chunkQuoted($quoted_string, $max_length = 74)
    {
        if(empty($max_length) || !is_string($quoted_string)){
            return $quoted_string;
        }

        $lines= preg_split("/(?:\r\n|\r|\n)/", $quoted_string);
        foreach ((array)$lines as $k=>$line){
            if (empty($line)){
                continue;
            }
            preg_match_all( '/.{1,'.($max_length - 2).'}([^=]{0,2})?/', $line, $match );
            $line = implode('='.AK_ACTION_MAILER_EOL.' ', $match[0] );

            $lines[$k] = $line;
        }
        return implode(AK_ACTION_MAILER_EOL,$lines);
    }
}

?>