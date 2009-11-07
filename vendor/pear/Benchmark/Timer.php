<?php
//
// +------------------------------------------------------------------------+
// | PEAR :: Benchmark                                                      |
// +------------------------------------------------------------------------+
// | Copyright (c) 2001-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>. |
// +------------------------------------------------------------------------+
// | This source file is subject to version 3.00 of the PHP License,        |
// | that is available at http://www.php.net/license/3_0.txt.               |
// | If you did not receive a copy of the PHP license and are unable to     |
// | obtain it through the world-wide-web, please send a note to            |
// | license@php.net so we can mail you a copy immediately.                 |
// +------------------------------------------------------------------------+
//
// $Id: Timer.php,v 1.13 2005/05/24 13:42:06 toggg Exp $
//

if(!function_exists('memory_get_usage')){
    function memory_get_usage()
    {
        if ( substr(PHP_OS,0,3) == 'WIN') {
            $tmp = explode(',"'.getmypid().'",',`TASKLIST /FO "CSV"`);
            $tmp = explode("\n",$tmp[1]);
            $tmp = explode('"',trim($tmp[0],'"KB '));
            return intval(str_replace(array('.',','),array(''),$tmp[count($tmp)-1]))*1024;
        }else{
            $pid = getmypid();
            exec("ps -o rss -p $pid", $output);
            return $output[1] *1024;
        }
        return false;
    }
}


if(!function_exists('number_to_human_size')){
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
}

require_once 'PEAR.php';

/**
 * Provides timing and profiling information.
 *
 * Example 1: Automatic profiling start, stop, and output.
 *
 * <code>
 * <?php
 * require_once 'Benchmark/Timer.php';
 *
 * $timer = new Benchmark_Timer(TRUE);
 * $timer->setMarker('Marker 1');
 * ?>
 * </code>
 *
 * Example 2: Manual profiling start, stop, and output.
 *
 * <code>
 * <?php
 * require_once 'Benchmark/Timer.php';
 *
 * $timer = new Benchmark_Timer();
 * $timer->start();
 * $timer->setMarker('Marker 1');
 * $timer->stop();
 *
 * $timer->display(); // to output html formated
 * // AND/OR :
 * $profiling = $timer->getProfiling(); // get the profiler info as an associative array
 * ?>
 * </code>
 *
 * @author    Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @author    Ludovico Magnocavallo <ludo@sumatrasolutions.com>
 * @copyright Copyright &copy; 2002-2005 Sebastian Bergmann <sb@sebastian-bergmann.de>
 * @license   http://www.php.net/license/3_0.txt The PHP License, Version 3.0
 * @category  Benchmarking
 * @package   Benchmark
 */
class Benchmark_Timer extends PEAR {
    /**
     * Contains the markers.
     *
     * @var    array
     * @access private
     */
    var $markers = array();

    /**
     * Auto-start and stop timer.
     *
     * @var    boolean
     * @access private
     */
    var $auto = FALSE;

    /**
     * Max marker name length for non-html output.
     *
     * @var    integer
     * @access private
     */
    var $maxStringLength = 0;

    /**
     * Constructor.
     *
     * @param  boolean $auto
     * @access public
     */
    function Benchmark_Timer($auto = FALSE) {
        $this->auto = $auto;

        if ($this->auto) {
            $this->start();
        }

        $this->PEAR();
    }

    /**
     * Destructor.
     *
     * @access private
     */
    function _Benchmark_Timer() {
        if ($this->auto) {
            $this->stop();
            $this->display();
        }
    }

    /**
     * Set "Start" marker.
     *
     * @see    setMarker(), stop()
     * @access public
     */
    function start() {
        $this->setMarker('Start');
    }

    /**
     * Set "Stop" marker.
     *
     * @see    setMarker(), start()
     * @access public
     */
    function stop() {
        $this->setMarker('Stop');
    }

    /**
     * Set marker.
     *
     * @param  string  $name Name of the marker to be set.
     * @see    start(), stop()
     * @access public
     */
    function setMarker($name) {
        $this->markers[$name] = $this->_getMicrotime();
        $this->_memory[$name] = memory_get_usage();
        $this->_files[$name] = count(get_included_files());
    }

    /**
     * Returns the time elapsed betweens two markers.
     *
     * @param  string  $start        start marker, defaults to "Start"
     * @param  string  $end          end marker, defaults to "Stop"
     * @return double  $time_elapsed time elapsed between $start and $end
     * @access public
     */
    function timeElapsed($start = 'Start', $end = 'Stop') {
        if ($end == 'Stop' && !isset($this->markers['Stop'])) {
            $this->markers['Stop'] = $this->_getMicrotime();
            $this->_memory['Stop'] = memory_get_usage();
            $this->_files['Stop'] = count(get_included_files());
        }

        if (extension_loaded('bcmath')) {
            return bcsub($this->markers[$end], $this->markers[$start], 6);
        } else {
            return $this->markers[$end] - $this->markers[$start];
        }
    }

    /**
     * Returns profiling information.
     *
     * $profiling[x]['name']  = name of marker x
     * $profiling[x]['time']  = time index of marker x
     * $profiling[x]['diff']  = execution time from marker x-1 to this marker x
     * $profiling[x]['total'] = total execution time up to marker x
     *
     * @return array
     * @access public
     */
    function getProfiling() {
        $i = $total = 0;
        $result = array();
        $temp = reset($this->markers);
        $this->maxStringLength = 0;

        foreach ($this->markers as $marker => $time) {
            if (extension_loaded('bcmath')) {
                $diff  = bcsub($time, $temp, 6);
                $total = bcadd($total, $diff, 6);
            } else {
                $diff  = $time - $temp;
                $total = $total + $diff;
            }
            $initial_memory = !isset($initial_memory) ? $this->_memory[$marker] : $initial_memory+$tmp_memory;
            $files = !isset($files) ? $this->_files[$marker] : $files+$previous_files;

            $tmp_memory = @$this->_memory[$marker]-$initial_memory;

            $result[$i]['name']  = $marker;
            $result[$i]['time']  = $time;
            $result[$i]['diff']  = $diff;
            $result[$i]['total'] = $total;
            $result[$i]['memory'] = number_to_human_size($tmp_memory);
            $result[$i]['total_memory'] = number_to_human_size(@$this->_memory[$marker]);
            $result[$i]['files'] = @$this->_files[$marker] - $files;
            $result[$i]['total_files'] = @$this->_files[$marker];

            $previous_files = $result[$i]['files'];
            $previous_memory = $result[$i]['files'];

            $this->maxStringLength = (strlen($marker) > $this->maxStringLength ? strlen($marker) + 1 : $this->maxStringLength);

            $temp = $time;
            $i++;
        }

        $result[0]['diff'] = '-';
        $result[0]['total'] = '-';
        $this->maxStringLength = (strlen('total') > $this->maxStringLength ? strlen('total') : $this->maxStringLength);
        $this->maxStringLength += 2;

        return $result;
    }

    /**
     * Return formatted profiling information.
     *
     * @param  boolean  $showTotal   Optionnaly includes total in output, default no
     * @return string
     * @see    getProfiling()
     * @access public
     */
    function getOutput($showTotal = FALSE)
    {
        if (function_exists('version_compare') &&
            version_compare(phpversion(), '4.1', 'ge'))
        {
            $http = isset($_SERVER['SERVER_PROTOCOL']);
        } else {
            global $HTTP_SERVER_VARS;
            $http = isset($HTTP_SERVER_VARS['SERVER_PROTOCOL']);
        }

        $total  = $this->TimeElapsed();
        $result = $this->getProfiling();
        $dashes = '';

        if ($http) {
            $out = '<table style="border:3px solid #ddd;margin:10px;background-color:#fff;color:#000;font-family:sans-serif;">'."\n";
            $out .= '<tr>
            <td>&nbsp;</td>
            <td align="center" style="border:1px solid #fff;padding:6px 10px;"><b>memory</b></td>
            <td align="center" style="border:1px solid #fff;padding:6px 10px;"><b>total memory</b></td>
            <td align="center" style="border:1px solid #fff;padding:6px 10px;"><b>included files</b></td>
            <td align="center" style="border:1px solid #fff;padding:6px 10px;"><b>total files</b></td>
            <td align="center" style="border:1px solid #fff;padding:6px 10px;"><b>execution time</b></td>
            <td align="center"><b>%</b></td>'.
            ($showTotal ?
              '<td align="center"><b>elapsed</b></td><td align="center"><b>%</b></td>'
               : '')."</tr>\n";
        } else {
            $dashes = $out = str_pad("\n",
                $this->maxStringLength + ($showTotal ? 70 : 45), '-', STR_PAD_LEFT);
            $out .= str_pad('marker', $this->maxStringLength) .
                    str_pad("time index", 22) .
                    str_pad("ex time", 16) .
                    str_pad("perct ", 8) .
                    ($showTotal ? ' '.str_pad("elapsed", 16)."perct" : '')."\n" .
                    $dashes;
        }

        foreach ($result as $k => $v) {
            $perc = (($v['diff'] * 100) / $total);
            $tperc = (($v['total'] * 100) / $total);

            if ($http) {

                $bg_color = $perc > 30 ? ($perc > 60 ? 'fcc' : 'ffc') : 'eee';
                $style = "border:1px solid #fff;padding:6px 10px;background-color:#$bg_color;";

                $out .= "<tr><td style='$style'><b>" . $v['name'] .
                       "</b></td><td style='$style'>" . $v['memory'] .
                       "</b></td><td style='$style'>" . $v['total_memory'] .
                       "</b></td><td style='$style'>" . $v['files'] .
                       "</b></td><td style='$style'>" . $v['total_files'] .
                       "</td><td style='$style'>" . $v['diff'] .
                       "</td><td align=\"right\" style='$style'>" . number_format($perc, 2, '.', '') .
                       "%</td>".
                       ($showTotal ?
                            "<td style='border:1px solid #fff;padding:6px 10px;'>" . $v['total'] .
                            "</td><td align=\"right\" style='border:1px solid #fff;padding:6px 10px;'>" .
                            number_format($tperc, 2, '.', '') .
                            "%</td>" : '').
                       "</tr>\n";
            } else {
                $out .= str_pad($v['name'], $this->maxStringLength, ' ') .
                        str_pad($v['time'], 22) .
                        str_pad($v['diff'], 14) .
                        str_pad(number_format($perc, 2, '.', '')."%",8, ' ', STR_PAD_LEFT) .
                        ($showTotal ? '   '.
                            str_pad($v['total'], 14) .
                            str_pad(number_format($tperc, 2, '.', '')."%",
                                             8, ' ', STR_PAD_LEFT) : '').
                        "\n";
            }

            $out .= $dashes;
        }

        if ($http) {
            $out .= "<tr style='border:1px solid #fff;background-color:#666;color:#fff'>
            <td style='padding:6px 10px;'><b>total</b></td>
            <td style='padding:6px 10px;'>-</td>
            <td style='padding:6px 10px;'>-</td>
            <td style='padding:6px 10px;'>-</td>
            <td style='padding:6px 10px;'>-</td>
            <td style='padding:6px 10px;'>${total}</td>
            <td style='padding:6px 10px;'>100.00%</td>
            ".($showTotal ? "<td>-</td><td>-</td>" : "")."</tr>\n";
            $out .= "</table>\n";
        } else {
            $out .= str_pad('total', $this->maxStringLength);
            $out .= str_pad('-', 22);
            $out .= str_pad($total, 15);
            $out .= "100.00%\n";
            $out .= $dashes;
        }

        return $out;
    }

    /**
     * Prints the information returned by getOutput().
     *
     * @param  boolean  $showTotal   Optionnaly includes total in output, default no
     * @see    getOutput()
     * @access public
     */
    function display($showTotal = FALSE) {
        print $this->getOutput($showTotal);
    }

    /**
     * Wrapper for microtime().
     *
     * @return float
     * @access private
     * @since  1.3.0
     */
    function _getMicrotime() {
        $microtime = explode(' ', microtime());
        return $microtime[1] . substr($microtime[0], 1);
    }
}
?>
