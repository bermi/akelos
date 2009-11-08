<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @author Bermi Ferrer <bermi a.t bermilabs c.om>
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

// Use the command line installer to only make your public the only public accesible point.
define('AK_INSECURE_APP_DIRECTORY_LAYOUT', true);
include('public'.DIRECTORY_SEPARATOR.'index.php');

?>
