<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

if(!@include('.'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php')){
    define('AK_ENVIRONMENT', 'setup');
	require('app'.DIRECTORY_SEPARATOR.'controllers'.DIRECTORY_SEPARATOR.'framework_setup_controller.php');
	exit;
}

include(AK_PUBLIC_DIR.DS.'index.php');

?>
