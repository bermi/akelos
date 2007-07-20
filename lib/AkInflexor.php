<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage Inflector
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_LIB_DIR.DS.'AkInflector.php');

/**
* Spanish Inflector
*/
class AkInflexor extends AkInflector 
{

    function pluralize($word)
    {
        $plural = array(
        '/([aeiou])x$/i'=> '\\1x', // This could fail if the word is oxytone.
        '/([áéíóú])([ns])$/i'=> '|1\\2es',
        '/(^[bcdfghjklmnñpqrstvwxyz]*)an$/i'=>'\\1anes', //clan->clanes
        '/([áéíóú])s$/i'=> '|1ses',
        '/(^[bcdfghjklmnñpqrstvwxyz]*)([aeiou])([ns])$/i'=>'\\1\\2\\3es', //tren->trenes
        '/([aeiouáéó])$/i'=> '\\1s', // casa->casas, padre->padres, papá->papás
        '/([aeiou])s$/i'=> '\\1s', // atlas->atlas, virus->virus, etc.
        '/([éí])(s)$/i'=> '|1\\2es', // inglés->ingleses
        '/z$/i'=> 'ces',  // luz->luces
        '/([íú])$/i' => '\\1es', // ceutí->ceutíes, tabú->tabúes
        '/(ng|[wckgtp])$/'=>'\\1s', // Anglicismos como puenting, frac, crack, show (En que casos podría fallar esto?)
        '/$/i'=> 'es',	// ELSE +es (v.g. árbol->árboles)
        );	// We should manage _orden_ -> _órdenes_, _joven_->_jóvenes_ and so.

        $uncountable = array('tijeras','gafas', 'vacaciones','víveres','déficit');
        /* In fact these words have no singular form: you cannot say neither
        "una gafa" nor "un vívere". So we should change the variable name to
        $onlyplural or something alike.*/
        $irregular = array(
        'país'=>'países',
        'champú'=>'champús',
        'jersey'=>'jerséis',
        'carácter'=>'caracteres',
        'espécimen'=>'especímenes',
        'menú'=>'menús',
        'régimen'=>'regímenes',
        'curriculum' => 'currículos',
        'ultimátum' => 'ultimatos',
        'memorándum' => 'memorandos',
        'referéndum' => 'referendos'
        );
        $lowercased_word = strtolower($word);


        foreach ($uncountable as $_uncountable){
            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_plural.')$/i', $word, $arr)) {
                return preg_replace('/('.$_plural.')$/i', substr($arr[0],0,1).substr($_singular,1), $word);
            }
        }

        foreach ($plural as $rule => $replacement) {
            if (preg_match($rule, $word, $match)) {
                if(strstr($replacement,'|')){
                    foreach ($match as $k=>$v){
                        $replacement = str_replace("|$k",strtr($v,'ÁÉÍÓÚáéíóú','AEIOUaeiou'), $replacement);
                    }
                }
                $result = preg_replace($rule, $replacement, $word);
                // Esto acentua los sustantivos que al pluralizarse se convierten en esdrújulos como esmóquines, jóvenes...
                if(preg_match('/([aeiou]).{1,3}[aeiou]nes$/i',$result,$match) && !preg_match('/[áéíóú]/i',$word)){
                    $result = str_replace($match[0], strtr($match[1],'AEIOUaeiou','ÁÉÍÓÚáéíóú').substr($match[0],1), $result);
                }
                return $result;
            }
        }
        return false;

    }

    function singularize($word)
    {
        $singular = array (
        '/^([bcdfghjklmnñpqrstvwxyz]*)([aeiou])([ns])es$/i'=> '\\1\\2\\3',
        '/([aeiou])([ns])es$/i'=> '~1\\2',
        '/oides$/i'=> 'oide', //androides->androide
        '/(ces)$/i' => 'z',
        '/(sis|tis|xis)+$/i'=> '\\1', //crisis, apendicitis, praxis
        '/(é)s$/i'=> '\\1', // bebés->bebé
        '/([^e])s$/i'=> '\\1', // casas->casa
        '/([bcdfghjklmnñprstvwxyz]{2,}e)s$/i'=>'\\1', // cofres->cofre
        '/([ghñpv]e)s$/i'=>'\\1', // 24-01 llaves->llave
        '/es$/i'=>'', // ELSE remove _es_  monitores->monitor
        );


        $uncountable = array('paraguas','tijeras','gafas', 'vacaciones','víveres','lunes','martes','miércoles','jueves','viernes','cumpleaños','virus','atlas','sms');
        $irregular = array(
        'jersey'=>'jerséis',
        'espécimen'=>'especímenes',
        'carácter'=>'caracteres',
        'régimen'=>'regímenes',
        'menú'=>'menús',
        'régimen'=>'regímenes',
        'curriculum' => 'currículos',
        'ultimátum' => 'ultimatos',
        'memorándum' => 'memorandos',
        'referéndum' => 'referendos',
        'sándwich' => 'sándwiches'
        );

        $lowercased_word = strtolower($word);
        foreach ($uncountable as $_uncountable){
            if(substr($lowercased_word,(-1*strlen($_uncountable))) == $_uncountable){
                return $word;
            }
        }

        foreach ($irregular as $_plural=> $_singular){
            if (preg_match('/('.$_singular.')$/i', $word, $arr)) {
                return preg_replace('/('.$_singular.')$/i', substr($arr[0],0,1).substr($_plural,1), $word);
            }
        }

        foreach ($singular as $rule => $replacement) {
            if (preg_match($rule, $word, $match)) {
                if(strstr($replacement,'~')){
                    foreach ($match as $k=>$v){
                        $replacement = str_replace("~$k",strtr($v,'AEIOUaeiou','ÁÉÍÓÚáéíóú'), $replacement);
                    }
                }

                $result = preg_replace($rule, $replacement, $word);
                // Esta es una posible solución para el problema de dobles acentos. Un poco sucio pero funciona
                $result = preg_match('/([áéíóú]).*([áéíóú])/',$result) ? strtr($result,'ÁÉÍÓÚáéíóú','AEIOUaeiou') : $result;

                return $result;
            }
        }

        return $word;
    }
}

?>
