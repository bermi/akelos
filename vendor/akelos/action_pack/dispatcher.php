<?php

/**
 * This class provides an interface for dispatching a request
 * to the appropriate controller and action.
 */
class AkDispatcher
{
    public $Request;
    public $Response;
    public $Controller;

    public function dispatch() {
        if(!$this->dispatchCached()){
            $time_start = microtime(true);
            AK_ENABLE_PROFILER &&  Ak::profile(__CLASS__.'::'.__FUNCTION__.'() call');
            $this->Request = new AkRequest();
            $this->Response = new AkResponse();
            $this->Controller = $this->Request->recognize();
            $this->Controller->ak_time_start = $time_start;
            AK_ENABLE_PROFILER && Ak::profile('Request::recognize() completed');
            $this->Controller->process($this->Request, $this->Response);
        }
    }

    public function dispatchCached() {
        $cache_settings = Ak::getSettings('caching', false);
        if ($cache_settings['enabled']) {
            $null = null;
            $pageCache = new AkCacheHandler();;
            $pageCache->init($null, $cache_settings);
            if (isset($_GET['allow_get'])) {
                $options['include_get_parameters'] = explode(',',$_GET['allow_get']);
            }
            if (isset($_GET['use_if_modified_since'])) {
                $options['use_if_modified_since'] = true;
            }
            if (($cachedPage = $pageCache->getCachedPage())!==false) {
                return $cachedPage->render();
            }
        }
        return false;
    }
    /**
     * @todo Implement a mechanism for enabling multiple requests on the same dispatcher
     * this will allow using Akelos as an Application Server using the
     * approach described at http://blog.milkfarmsoft.com/?p=51
     *
     */
    public function restoreRequest() {
    }
}

