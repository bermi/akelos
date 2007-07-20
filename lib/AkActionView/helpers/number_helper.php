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
* Provides methods for converting a number into a formatted string that currently represents
* one of the following forms: phone number, percentage, money, or precision level.
*/
class NumberHelper
{
    /**
      * Formats a +number+ into a US phone number string. The +options+ can be a array used to customize the 
      * format of the output.
      * The area code can be surrounded by parentheses by setting +area_code+ to true; default is false
      * The delimiter can be set using +delimiter+; default is "-"
      * Examples:
      *   $number_helper->number_to_phone(1235551234)   => 123-555-1234
      *   $number_helper->number_to_phone(1235551234, array('area_code' => true))   => (123) 555-1234
      *   $number_helper->number_to_phone(1235551234, array('delimiter' => " "))    => 123 555 1234
      *   $number_helper->number_to_phone(1235551234, array('area_code' => true, 'extension' => 555))  => (123) 555-1234 x 555
      */
    function number_to_phone($number, $options = array())
    {
        $default_options = array(
        'area_code'=>false,
        'delimiter' => '-',
        'extension'=> '',
        'extension_delimiter' =>  'x'
        );

        $options = array_merge($default_options, $options);

        $number = $options['area_code'] == true ? preg_replace(
        '/([0-9]{3})([0-9]{3})([0-9]{4})/',"(\\1) \\2{$options['delimiter']}\\3",$number) :
        preg_replace('/([0-9]{3})([0-9]{3})([0-9]{4})/',"\\1{$options['delimiter']}\\2{$options['delimiter']}\\3",$number);

        return empty($options['extension']) ? $number : "$number {$options['extension_delimiter']} {$options['extension']}";

    }

    /**
      * Formats a +number+ into a currency string. The +options+ array can be used to customize the format of the output.
      * The +number+ can contain a level of precision using the +precision+ key; default is 2
      * The currency type can be set using the +unit+ key; default is "$"
      * The unit separator can be set using the +separator+ key; default is "."
      * The delimiter can be set using the +delimiter+ key; default is ","
      * Examples:
      *    $number_helper->number_to_currency(1234567890.50)     => $1,234,567,890.50
      *    $number_helper->number_to_currency(1234567890.506)    => $1,234,567,890.51
      *    $number_helper->number_to_currency(1234567890.50, 
      * 	array('unit' => "&pound;", 'separator' => ",", 'delimiter' => "")) => &pound;1234567890,50
      *    $number_helper->number_to_currency(1234567890.50, 
      * 	array('unit' => " &euro;", 'separator' => ",", 'delimiter' => ".",
      * 			'unit_position' => 'right')) => 1.234.567.890,50 &euro;
      */

    function number_to_currency($number, $options = array())
    {
        $default_options = Ak::locale('currency');

        $options = array_merge($default_options, $options);

        return
        ($options['unit_position'] == 'left' ? $options['unit'] : '').
        number_format($number, $options['precision'],$options['separator'], $options['delimiter']).
        ($options['unit_position'] == 'right' ? $options['unit'] : '');

    }


    /**
      * Formats a +number+ as into a percentage string. The +options+ array can be used to customize the format of 
      * the output.
      * The +number+ can contain a level of precision using the +precision+ key; default is 2
      * The unit separator can be set using the +separator+ key; default is "."
      * Examples:
      *   $number_helper->number_to_percentage(100)    => 100.00%
      *   $number_helper->number_to_percentage(100, array('precision' => 0)) => 100%
      *   $number_helper->number_to_percentage(302.0576, array('precision' => 3))  => 302.058%
      */
    function number_to_percentage($number, $options = array())
    {

        $default_options = array(
        'precision'=>2,
        'separator' => '.',
        );

        $options = array_merge($default_options, $options);

        return number_format($number, $options['precision'],$options['separator'],'').'%';
    }

    /**
      * Formats a +number+ with a +delimiter+.
      * Example:
      *    $number_helper->number_with_delimiter(12345678) => 12,345,678
      */
    function number_with_delimiter($number, $delimiter=',')
    {
        return preg_replace('/(\d)(?=(\d\d\d)+(?!\d))/', "\\1{$delimiter}", $number);
    }

    /*
    * Returns a formatted-for-humans file size.
    *
    * Examples:
    *   $number_helper->human_size(123)          => 123 Bytes
    *   $number_helper->human_size(1234)         => 1.2 KB
    *   $number_helper->human_size(12345)        => 12.1 KB
    *   $number_helper->human_size(1234567)      => 1.2 MB
    *   $number_helper->human_size(1234567890)   => 1.1 GB
    */

    function number_to_human_size($size, $decimal = 1)
    {
        if(is_numeric($size )){
            $position = 0;
            $units = array( ' Bytes', ' KB', ' MB', ' GB', ' TB', ' PB', ' EB', ' ZB', ' YB');
            while( $size >= 1024 && ( $size / 1024 ) >= 1 ) {
                $size /= 1024;
                $position++;
            }
            return round( $size, $decimal ) . $units[$position];
        }else {
            return '0 Bytes';
        }
    }
    function human_size($size)
    {
        return NumberHelper::number_to_human_size($size);
    }

    function human_size_to_bytes($size)
    {
        $units = array('BYTE','KB','MB','GB','TB');
        $size = str_replace(array('BYTE','KILOBYTE','MEGABYTE','GIGABYTE','TERABYTE'), $units, rtrim(strtoupper($size),'S'));
        if(preg_match('/([0-9\.]+) ?('.join('|',$units).')/', $size, $match)){
            return intval(ceil((double)$match[1] * pow(1024,array_search($match[2], $units))));
        }
        return 0;
    }


    /**
      * Formats a +number+ with a level of +precision+.
      * Example:
      *    $number_helper->number_with_precision(111.2345) => 111.235
      */

    function number_with_precision($number, $precision=3)
    {
        /**
         * @todo fix number rounding. Precision on linux boxes rounds to the lower (Mac and Windows work as the example)
         */
        if(strstr($number,'.')){
            $decimal = round(substr($number,strpos($number,'.')+1),$precision+1);
            if(substr($decimal  , -1) == 5){
                $number = substr($number,0,strpos($number,'.')) .'.'. ($decimal+1);
            }
        }

        return round( $number, $precision );
    }


    /**
    * Add zeros to the begining of the string until it reaches desired length
    * Example:
    *   $number_helper->zeropad(123, 6) => 000123
    */
    function zeropad($number, $length)
    {
        return str_pad($number, $length*-1, '0');
    }

}

?>