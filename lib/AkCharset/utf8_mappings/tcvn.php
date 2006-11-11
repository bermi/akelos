<?php

/**
*@file tcvn.php
* TCVN Mapping and Charset implementation.
*
*/

//
// +----------------------------------------------------------------------+
// | Akelos PHP Application Framework                                     |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2005, Akelos Media, S.L.  http://www.akelos.org/  |
// | Released under the GNU Lesser General Public License                 |
// +----------------------------------------------------------------------+
// | You should have received the following files along with this library |
// | - COPYRIGHT (Additional copyright notice)                            |
// | - DISCLAIMER (Disclaimer of warranty)                                |
// | - README (Important information regarding this library)              |
// +----------------------------------------------------------------------+
//





/**
* TCVN  driver for Charset Class
*
* Charset::tcvn provides functionality to convert
* TCVN strings, to UTF-8 multibyte format and vice versa.
*
* @package AKELOS
* @subpackage Localize
* @author Bermi Ferrer Martinez <bermi@akelos.org>
* @copyright Copyright (c) 2002-2005, Akelos Media, S.L. http://www.akelos.org
* @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
* @link http://www.unicode.org/Public/MAPPINGS/ Original Mapping taken from Unicode.org
* @since 0.1
* @version $Revision 0.1 $
*/
class tcvn extends AkCharset
{


	// ------ CLASS ATTRIBUTES ------ //



	// ---- Private attributes ---- //


	/**
	* TCVN to UTF-8 mapping array.
	*
	* @access private
	* @var    array    $_toUtfMap
	*/
	var $_toUtfMap = array(0=>0,1=>218,2=>7908,3=>3,4=>7914,5=>7916,6=>7918,7=>7,8=>8,9=>9,10=>10,11=>11,12=>12,13=>13,14=>14,15=>15,16=>16,17=>7912,18=>7920,19=>7922,20=>7926,21=>7928,22=>221,23=>7924,24=>24,25=>25,26=>26,27=>27,28=>28,29=>29,30=>30,31=>31,32=>32,33=>33,34=>34,35=>35,36=>36,37=>37,38=>38,39=>39,40=>40,41=>41,42=>42,43=>43,44=>44,45=>45,46=>46,47=>47,48=>48,49=>49,50=>50,51=>51,52=>52,53=>53,54=>54,55=>55,56=>56,57=>57,58=>58,59=>59,60=>60,61=>61,62=>62,63=>63,64=>64,65=>65,66=>66,67=>67,68=>68,69=>69,70=>70,71=>71,72=>72,73=>73,74=>74,75=>75,76=>76,77=>77,78=>78,79=>79,80=>80,81=>81,82=>82,83=>83,84=>84,85=>85,86=>86,87=>87,88=>88,89=>89,90=>90,91=>91,92=>92,93=>93,94=>94,95=>95,96=>96,97=>97,98=>98,99=>99,100=>100,101=>101,102=>102,103=>103,104=>104,105=>105,106=>106,107=>107,108=>108,109=>109,110=>110,111=>111,112=>112,113=>113,114=>114,115=>115,116=>116,117=>117,118=>118,119=>119,120=>120,121=>121,122=>122,123=>123,124=>124,125=>125,126=>126,127=>127,128=>192,129=>7842,130=>195,131=>193,132=>7840,133=>7862,134=>7852,135=>200,136=>7866,137=>7868,138=>201,139=>7864,140=>7878,141=>204,142=>7880,143=>296,144=>205,145=>7882,146=>210,147=>7886,148=>213,149=>211,150=>7884,151=>7896,152=>7900,153=>7902,154=>7904,155=>7898,156=>7906,157=>217,158=>7910,159=>360,160=>160,161=>258,162=>194,163=>202,164=>212,165=>416,166=>431,167=>272,168=>259,169=>226,170=>234,171=>244,172=>417,173=>432,174=>273,175=>7856,176=>768,177=>777,178=>771,179=>769,180=>803,181=>224,182=>7843,183=>227,184=>225,185=>7841,186=>7858,187=>7857,188=>7859,189=>7861,190=>7855,191=>7860,192=>7854,193=>7846,194=>7848,195=>7850,196=>7844,197=>7872,198=>7863,199=>7847,200=>7849,201=>7851,202=>7845,203=>7853,204=>232,205=>7874,206=>7867,207=>7869,208=>233,209=>7865,210=>7873,211=>7875,212=>7877,213=>7871,214=>7879,215=>236,216=>7881,217=>7876,218=>7870,219=>7890,220=>297,221=>237,222=>7883,223=>242,224=>7892,225=>7887,226=>245,227=>243,228=>7885,229=>7891,230=>7893,231=>7895,232=>7889,233=>7897,234=>7901,235=>7903,236=>7905,237=>7899,238=>7907,239=>249,240=>7894,241=>7911,242=>361,243=>250,244=>7909,245=>7915,246=>7917,247=>7919,248=>7913,249=>7921,250=>7923,251=>7927,252=>7929,253=>253,254=>7925,255=>7888);
		

	/**
	*  UTF-8 to TCVN mapping array.
	*
	* @access private
	* @var    array    $_fromUtfMap
	*/
	var $_fromUtfMap = null;


	// ------------------------------



	// ------ CLASS METHODS ------ //


	// ---- Public methods ---- //


	/**
	* Encodes given TCVN string into UFT-8
	*
	* @access public
	* @see UtfDecode
	* @param    string    $string TCVN string
	* @return    string    UTF-8 string data
	*/
	function _Utf8StringEncode($string)
	{
		return parent::_Utf8StringEncode($string, $this->_toUtfMap);
	
	}// -- end of &Utf8StringEncode -- //

	/**
	* Decodes given UFT-8 string into TCVN
	*
	* @access public
	* @see UtfEncode
	* @param    string    $string UTF-8 string
	* @return    string    TCVN string data
	*/
	function _Utf8StringDecode($string)
	{
		$this->_LoadInverseMap();
		return parent::_Utf8StringDecode($string, $this->_fromUtfMap);
	}// -- end of &Utf8StringDecode -- //
		
		
	// ---- Private methods ---- //
		
	/**
	* Flips $this->_toUtfMap to $this->_fromUtfMap
	*
	* @access private
	* @return	null
	*/
	function _LoadInverseMap()
	{
		static $loaded;
		if(!isset($loaded)){
			$loaded = true;
			$this->_fromUtfMap = array_flip($this->_toUtfMap);
		}
	}// -- end of _LoadInverseMap -- //
	
}

?>