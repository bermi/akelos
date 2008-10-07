<?php
//define('AK_HOST','localhost');
include_once(dirname(__FILE__).DIRECTORY_SEPARATOR.'..'.DIRECTORY_SEPARATOR.'config'.DIRECTORY_SEPARATOR.'config.php');
$host = AK_HOST;
require_once(AK_LIB_DIR.DS.'Ak.php');
$cache_settings = Ak::getSettings('caching', false);
if ($cache_settings['enabled']) {
    
    require_once(AK_LIB_DIR . DS . 'AkActionController'.DS.'AkCacheHandler.php');
    $null = null;
    $pageCache = &Ak::singleton('AkCacheHandler',$null);
    
    $pageCache->init($null, $cache_settings);
    if (isset($_GET['allow_get'])) {
        $options['include_get_parameters'] = split(',',$_GET['allow_get']);
    }
    
    if (isset($_GET['use_if_modified_since'])) {
        $options['use_if_modified_since'] = true;
    }
    if (($cachedPage = $pageCache->getCachedPage())!==false) {
        $cachedPage->render();
    }
}
require_once(AK_LIB_DIR . DS . 'AkDispatcher.php');
$Dispatcher =& new AkDispatcher();
$Dispatcher->dispatch();

?>