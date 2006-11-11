<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// WARNING THIS CODE IS EXPERIMENTAL

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage Configuration
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
 * @todo Add
 *
 */

class AkPhpConfigFile
{
    function getAll()
    {
        if(!empty($_SESSION['__ak_config'])){
            return $_SESSION['__ak_config'];
        }
        include(AK_CONFIG_DIR.DS.'');
    }
}

?>