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
 * @subpackage AkImage
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

 defined('AK_IMAGE_DRIVER') ? null : define('AK_IMAGE_DRIVER', 'GD');

 require_once(AK_VENDOR_DIR.DS.'pear'.DS.'Image'.DS.'Transform.php');
 require_once(AK_LIB_DIR.DS.'AkImage'.DS.'AkImageFilter.php');

 class AkImage extends Image_Transform
 {
     var $image_path;
     var $Transform;
     var $filters = array();

     function AkImage($image_path = null, $tranform_using = AK_IMAGE_DRIVER)
     {         
         $this->Transform =& Image_Transform::factory($tranform_using);
         
         if(PEAR::isError($this->Transform)){
             trigger_error($this->Transform->getMessage(), E_USER_ERROR);
         }
         if(!empty($image_path)){
             $this->load($image_path);
         }
     }

     function load($image_path)
     {
         $this->image_path = $image_path;
         $this->Transform->load($image_path);
     }

     function save($path = null, $quality = 90, $options = array())
     {
         if(!$tmp_image_name = tempnam(AK_CACHE_DIR,'ak_image_')){
             trigger_error(Ak::t('Could not create the temporary file %tmp_image_name for apliying changes and saving', array('%tmp_image_name'=>$tmp_image_name)), E_USER_ERROR);
         }
         
         $path = empty($path) ? $this->image_path : $path;
         $this->Transform->save($tmp_image_name, $this->getExtension($path), $quality);         
         Ak::file_put_contents($path, file_get_contents($tmp_image_name), $options);
         @unlink($tmp_image_name);
     }

     function transform($transformation, $options = array())
     {
         $this->filters = array();
         $this->addFilter($transformation, $options);
         $this->applyFilters();
     }

     function getWidth()
     {
         return $this->Transform->getImageWidth();
     }

     function getHeight()
     {
         return $this->Transform->getImageHeight();
     }

     function getExtension($path = null)
     {
         return substr(strrchr(empty($path) ? $this->image_path : $path, '.'), 1);
     }

     function addFilter($filter_name, $options = array())
     {
         if($this->_filterExists($filter_name)){
             $class_name = $this->_getFilterClassName($filter_name);
             $filter =& new $class_name();
             if(method_exists($filter,'init')){
                 $filter->init();
             }
             $filter->setImage($this);
             $filter->setOptions($options);
             $this->filters[] =& $filter;
             return true;
         }
         return false;
     }

     function _filterExists($filter_name)
     {
         if(class_exists($filter_name)){
             return true;
         }

         $class_name = $this->_getFilterClassName($filter_name);
         $file_name = AK_LIB_DIR.DS.'AkImage'.DS.'AkImageFilter'.DS.$class_name.'.php';
         $success = true;
         if(!file_exists($file_name)){
             $success = false;
         }else{
             require_once($file_name);
         }
         $success = class_exists($class_name) ? $success : false;

         if(!$success){
             trigger_error(Ak::t('Could not find image filter %class_name at %file_name', array('%class_name'=>$class_name, '%file_name'=>$file_name)), E_USER_ERROR);
         }
         return $success;
     }

     function _getFilterClassName($filter_name)
     {
         // We might allow other classes to be created as filters in order to create image filter plugins from outside the framework
         if(!class_exists($filter_name)){
             return 'AkImage'.AkInflector::classify($filter_name).'Filter';
         }else{
             return $filter_name;
         }
     }

     function _getFilterChainPath($name, $path)
     {
         return (empty($path) ? AK_APP_DIR.DS.'image_filters' : rtrim($path,DS.'/')).DS.$name.'_filter.php';
     }

     function applyFilters()
     {
         foreach (array_keys($this->filters) as $k){
             $this->filters[$k]->apply();
         }
     }

     function saveFilterChain($name, $filter_chain = null, $filters_directory = null, $options = array())
     {
         $path = $this->_getFilterChainPath($name, $filters_directory);
         $filter_chain = empty($filter_chain) ? $this->getFilterChain() : $filter_chain;
         return Ak::file_put_contents($path, '<?php $filter_chain = '.var_export($filter_chain, true).'; ?>', $options);
     }

     function getFilterChain()
     {
         $filter_chain = array();
         foreach (array_keys($this->filters) as $k){
             $filter_chain[] = array('name'=>$this->filters[$k]->getName(),'options'=>$this->filters[$k]->getOptions());
         }
         return $filter_chain;
     }

     function setFilterChain($filter_chain)
     {
         $this->filters = array();
         $this->appendFilterChain($filter_chain);
     }

     function appendFilterChain($filter_chain)
     {
         foreach ($filter_chain as $filter){
             $this->addFilter($filter['name'],$filter['options']);
         }
     }

     function loadFilterChain($name, $filters_directory = null)
     {
         $path = $this->_getFilterChainPath($name, $filters_directory);
         $success = file_exists($path);
         @include($path);
         if(!$success || empty($filter_chain)){
             trigger_error(Ak::t('Could not find a valid %name filer chain at %path.', array('%name'=>$name,'%path'=>$path)), E_USER_ERROR);
         }
         $this->setFilterChain($filter_chain);
     }

     function applyFilterChain($name, $filters_directory = null)
     {
         $this->loadFilterChain($name, $filters_directory);
         $this->applyFilters();
     }

 }

?>
