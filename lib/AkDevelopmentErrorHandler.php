<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @author Cezary Tomczak
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 * @package ActiveSupport
 * @subpackage Experimental
 */

/**
 * @todo This is a temporary Debug Library. File highlighting is really dangerous.
 * Functionality provided by this class is only enabled on "development" environments
 */


/**
* @version 2.0.1
* @license BSD
* @copyright (c) 2003,2004 Cezary Tomczak
* @link http://gosu.pl/software/mygosulib.html
*/

if(defined('AK_DEBUG') && AK_DEBUG){

    class AkDevelopmentErrorHandler {

        /**
        * Constructor
        * @access public
        */
        function AkDevelopmentErrorHandler()
        {
            if(AK_DEBUG == 0){
                return false;
            }
            if(isset($_GET['ak_debug_show_source'])){
                $file = @$_GET['ak_debug_file'];
                $line = @$_GET['ak_debug_line'];
                $prev = @$_GET['ak_debug_prev'] ? $_GET['ak_debug_prev'] : 10;
                $next = @$_GET['ak_debug_next'] ? $_GET['ak_debug_next'] : 10;
                $this->showSource($file, $line, $prev, $next);
                exit;
            }
            ini_set('docref_root', null);
            ini_set('docref_ext', null);
        }

        /**
    * @param int $errNo
    * @param string $errMsg
    * @param string $file
    * @param int $line
    * @return void
    * @access public
    */
        function raiseError($errNo, $errMsg, $file, $line) {

            if (! ($errNo & error_reporting())) {
                return;
            }
            if(AK_DEBUG == 0){
                return;
            }
            while (ob_get_level()) {
                ob_end_clean();
            }

            $errType = array (
            1    => "Php Error",
            2    => "Php Warning",
            4    => "Parsing Error",
            8    => "Php Notice",
            16   => "Core Error",
            32   => "Core Warning",
            64   => "Compile Error",
            128  => "Compile Warning",
            256  => "Php User Error",
            512  => "Php User Warning",
            1024 => "Php User Notice"
            );

            $info = array();

            if (($errNo & E_USER_ERROR) && !empty($errMsg) && is_array($arr = @unserialize($errMsg))) {
                foreach ($arr as $k => $v) {
                    $info[$k] = $v;
                }
            }

            $trace = array();

            if (function_exists('debug_backtrace')) {
                $trace = debug_backtrace();
                array_shift($trace);
            }


            $showSourceUri = @$_SERVER['PHP_SELF'].'?ak_debug_show_source=1';
            $showSourcePrev = 10;
            $showSourceNext = 10;
?>

<script type="text/javascript">
function showParam(i) {
    currentParam = i;
    document.getElementById('paramHide').style.display = ''
    document.getElementById('paramSpace').style.display = ''
    document.getElementById('param').style.display = ''
    document.getElementById('param').innerHTML = '<pre>' + document.getElementById('param' + i).innerHTML + '</pre>'
}
function hideParam() {
    currentParam = -1;
    document.getElementById('paramHide').style.display = 'none'
    document.getElementById('paramSpace').style.display = 'none'
    document.getElementById('param').style.display = 'none'
}
function showOrHideParam(i) {
    if (currentParam == i) {
        hideParam()
    } else {
        showParam(i)
    }
}
function showFile(id) {
    eval('display = document.getElementById("file' + id + '").style.display')
    eval('if (display == "none") { document.getElementById("file' + id + '").style.display = "" } else { document.getElementById("file' + id + '").style.display = "none" } ');
}
function showDetails(cnt) {
    for (i = 0; i < cnt; ++i) {
        eval('document.getElementById("file' + i + '").style.display = ""')
    }
}
function hideDetails(cnt) {
    for (i = 0; i < cnt; ++i) {
        eval('document.getElementById("file' + i + '").style.display = "none"')
    }
}
var currentParam = -1;
</script>

<pre>
<hr />

<b>Error type:</b> <?php echo $errType[$errNo]; ?>

<?php

function fontStart($color) {
    return '<font color="' . $color . '">';
}
function fontEnd() {
    return '</font>';
}

$c['default'] = '#000000';
$c['keyword'] = '#0000FF';
$c['number']  = '#FF0000';
$c['string']  = '#FF00FF';
$c['comment'] = '#999999';

if (count($info)) {
    foreach ($info as $k => $v) {
        echo '<b>';
        echo $k;
        echo ':</b> ';
        echo $v;
        echo "\r\n";
    }
} else {
    echo '<b>Message:</b> ';
    echo $errMsg;
    echo "\r\n";
}

echo "\r\n";

if (count($trace)) {

    echo '<span style="font-family: monospaced; font-size: 11px;">Trace: ' . count($trace) . "</span> ";
    echo '<span style="font-family: monospaced; font-size: 11px; cursor: pointer;" onclick="showDetails('.count($trace).')">[show details]</span> ';
    echo '<span style="font-family: monospaced; font-size: 11px; cursor: pointer;" onclick="hideDetails('.count($trace).')">[hide details]</span>';

    echo "\r\n";
    echo "\r\n";



    echo '<ul>';
    $currentParam = -1;

    foreach ($trace as $k => $v) {

        $currentParam++;

        echo '<li style="list-style-type: square;">';

        if (isset($v['class'])) {
            echo '<span onmouseover="this.style.color=\'#0000ff\'" onmouseout="this.style.color=\''.$c['keyword'].'\'" style="color: '.$c['keyword'].'; cursor: pointer;" onclick="showFile('.$k.')">';
            echo $v['class'];
            echo ".";
        } else {
            echo '<span onmouseover="this.style.color=\'#0000ff\'" onmouseout="this.style.color=\''.$c['keyword'].'\'" style="color: '.$c['keyword'].'; cursor: pointer;" onclick="showFile('.$k.')">';
        }

        echo $v['function'];
        echo '</span>';
        echo " (";

        $sep = '';
        $v['args'] = (array) @$v['args'];
        foreach ($v['args'] as $arg) {

            $currentParam++;

            echo $sep;
            $sep    = ', ';
            $color = '#404040';

            switch (true) {

                case is_bool($arg):
                $param  = 'TRUE';
                $string = $param;
                break;

                case is_int($arg):
                case is_float($arg):
                $param  = $arg;
                $string = $arg;
                $color = $c['number'];
                break;

                case is_null($arg):
                $param = 'NULL';
                $string = $param;
                break;

                case is_string($arg):
                $param = $arg;
                $string = 'string[' . strlen($arg) . ']';
                break;

                case is_array($arg):
                ob_start();
                print_r($arg);
                $param = ob_get_contents();
                ob_end_clean();
                $string = 'array[' . count($arg) . ']';
                break;

                case is_object($arg):
                ob_start();
                print_r($arg);
                $param = ob_get_contents();
                ob_end_clean();
                $string = 'object: ' . get_class($arg);
                break;

                case is_resource($arg):
                $param = 'resource: ' . get_resource_type($arg);
                $string = 'resource';
                break;

                default:
                $param = 'unknown';
                $string = $param;
                break;

            }

            echo '<span style="cursor: pointer; color: '.$color.';" onclick="showOrHideParam('.$currentParam.')" onmouseout="this.style.color=\''.$color.'\'" onmouseover="this.style.color=\'#dd0000\'">';
            echo $string;
            echo '</span>';
            echo '<span id="param'.$currentParam.'" style="display: none;">' . $param . '</span>';

        }

        echo ")";
        echo "\r\n";

        if (!isset($v['file'])) {
            $v['file'] = 'unknown';
        }
        if (!isset($v['line'])) {
            $v['line'] = 'unknown';
        }

        $v['line'] = @$v['line'];
        echo '<span id="file'.$k.'" style="display: none; color: gray;">';
        if ($v['file'] && $v['line']) {
            echo 'FILE: <a onmouseout="this.style.color=\'#007700\'" onmouseover="this.style.color=\'#FF6600\'" style="color: #007700; text-decoration: none;" target="_blank" href="'.$showSourceUri.'&ak_debug_file='.urlencode($v['file']).'&ak_debug_line='.$v['line'].'&ak_debug_prev='.$showSourcePrev.'&ak_debug_next='.$showSourceNext.'">'.basename($v['file']).'</a>';
        } else {
            echo 'FILE: ' . fontStart('#007700') . basename($v['file']) . fontEnd();
        }
        echo "\r\n";
        echo 'LINE: ' . fontStart('#007700') . $v['line'] . fontEnd() . "\r\n";
        echo 'DIR:  ' . fontStart('#007700') . dirname($v['file']) . fontEnd();
        echo '</span>';

        echo '</li>';
    }

    echo '</ul>';

} else {
    echo '<b>File:</b> ';
    echo basename($file);
    echo ' (' . $line . ') ';
    echo dirname($file);
}

?>

<?php echo '<span id="paramHide" style="display: none; font-family: monospaced; font-size: 11px; cursor: pointer;" onclick="hideParam()">[hide param]</span>';?>
<span id="paramSpace" style="display: none;">

</span><div id="param" perm="0" style="background-color: #FFFFE1; padding: 2px; display: none;"></div><hr />

Trick: click on a function's argument to see it fully
Trick: click on a function to see the file & line
Trick: click on the file name to see the source code

</pre>
<?php
exit;
        }

        function showSource($file, $line, $prev = 10, $next = 10)
        {
            if(AK_ENVIRONMENT != 'development'){
                echo Ak::t('Opsss! File highlighting is only available on development mode.');
                die();
            }

            // We only allow to show files inside the base path.
            $file = AK_BASE_DIR.DS.trim(str_replace(array(AK_BASE_DIR,';','..'), array('','',''), $file), DS.'.');

            if(strstr($file, AK_CONFIG_DIR)){
                echo Ak::t('Sorry but you can\'t view configuration files.');
                die();
            }

            if (!(file_exists($file) && is_file($file))) {
                echo Ak::t('%file_name is not available for showing its source code', array('%file_name'=> $file));
                die();
            }

            ini_set('highlight.default','#000000');
            ini_set('highlight.keyword','0000FF');
            ini_set('highlight.number','FF0000');
            ini_set('highlight.string','#FF00FF');
            ini_set('highlight.comment','#999999');


            //read code
            ob_start();
            highlight_file($file);
            $data = ob_get_contents();
            ob_end_clean();

            //seperate lines
            $data  = explode('<br />', $data);
            $count = count($data) - 1;

            //count which lines to display
            $start = $line - $prev;
            if ($start < 1) {
                $start = 1;
            }
            $end = $line + $next;
            if ($end > $count) {
                $end = $count + 1;
            }

            //displaying
            echo '<table cellspacing="0" cellpadding="0"><tr>';
            echo '<td style="vertical-align: top;"><code style="background-color: #FFFFCC; color: #666666;">';

            for ($x = $start; $x <= $end; $x++) {
                echo '<a name="'.$x.'"></a>';
                echo ($line == $x ? '<font style="background-color: red; color: white;">' : '');
                echo str_repeat('&nbsp;', (strlen($end) - strlen($x)) + 1);
                echo $x;
                echo '&nbsp;';
                echo ($line == $x ? '</font>' : '');
                echo '<br />';
            }
            echo '</code></td><td style="vertical-align: top;"><code>';
            while ($start <= $end) {
                echo '&nbsp;' . $data[$start - 1] . '<br />';
                ++$start;
            }
            echo '</code></td>';
            echo '</tr></table>';

            if ($prev != 10000 || $next != 10000) {
                echo '<br />';
                echo '<a style="font-family: tahoma; font-size: 12px;" href="'.@$_SERVER['PHP_SELF'].'?ak_debug_show_source=1&ak_debug_file='.urlencode($file).'&ak_debug_line='.$line.'&ak_debug_prev=10000&ak_debug_next=10000#'.($line - 15).'">View Full Source</a>';
            }

        }
    }

}

?>