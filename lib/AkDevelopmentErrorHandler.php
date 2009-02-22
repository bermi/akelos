<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2009 Bermi Ferrer                                      |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @author Bermi Ferrer <bermi at bermilabs com>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 * @package ActiveSupport
 */

if(defined('AK_DEBUG') && AK_DEBUG){

    function ak_backtrace()
    {
        $bt = debug_backtrace();
        echo("\n\nBacktrace (most recent call last):\n\n\n");   
        for($i = 0; $i <= count($bt) - 1; $i++)
        {
            if($bt[$i]["function"]!='ak_backtrace' && $bt[$i]["function"]!='ak_development_error_handler'){
                if(!isset($bt[$i]["file"])){
                    echo("[PHP core called function]\n");
                }else{
                    echo("File: ".$bt[$i]["file"]."\n");
                    if(empty($file[$bt[$i]["file"]])){
                        $file[$bt[$i]["file"]] = explode("\n", file_get_contents($bt[$i]["file"]));
                    }
               }
               echo("    function called: ".$bt[$i]["function"])."\n";
                if(isset($bt[$i]["line"])){
                    echo("    line: ".$bt[$i]["line"]."\n");
                    echo "    code: ".(trim($file[$bt[$i]["file"]][$bt[$i]["line"]-1]));
                }
                echo("\n\n");
            }
        }
    }
    
    function ak_development_error_handler($error_number, $error_message, $file, $line) 
    {
        $error_number = $error_number & error_reporting();
        if($error_number == 0){
            return;
        }
        while (ob_get_level()) {
            ob_end_clean();
        }
        
        AK_WEB_REQUEST ? print('<pre>') : null;
        
        if(!defined('E_STRICT')) define('E_STRICT', 2048);
        if(!defined('E_RECOVERABLE_ERROR')) define('E_RECOVERABLE_ERROR', 4096);

        switch($error_number){
            case E_ERROR:               echo "Error";                  break;
            case E_WARNING:             echo "Warning";                break;
            case E_PARSE:               echo "Parse Error";            break;
            case E_NOTICE:              echo "Notice";                 break;
            case E_CORE_ERROR:          echo "Core Error";             break;
            case E_CORE_WARNING:        echo "Core Warning";           break;
            case E_COMPILE_ERROR:       echo "Compile Error";          break;
            case E_COMPILE_WARNING:     echo "Compile Warning";        break;
            case E_USER_ERROR:          echo "User Error";             break;
            case E_USER_WARNING:        echo "User Warning";           break;
            case E_USER_NOTICE:         echo "User Notice";            break;
            case E_STRICT:              echo "Strict Notice";          break;
            case E_RECOVERABLE_ERROR:   echo "Recoverable Error";      break;
            default:                    echo "Unknown error ($error_number)"; break;
        }
        echo ": $error_message in  $file on line $line\n";
        ak_backtrace();
        
        AK_WEB_REQUEST ? print('</pre>') : null;
    }

    set_error_handler('ak_development_error_handler');
}

?>