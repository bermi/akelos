<?php

/**
                    Filters
====================================================================
*
* Filters enable controllers to run shared pre and post processing code for its actions. These filters can be used to do
* authentication, caching, or auditing before the intended action is performed. Or to do localization or output
* compression after the action has been performed.
*
* Filters have access to the request, response, and all the instance variables set by other filters in the chain
* or by the action (in the case of after filters). Additionally, it's possible for a pre-processing <tt>beforeFilter</tt>
* to halt the processing before the intended action is processed by returning false or performing a redirect or render.
* This is especially useful for filters like authentication where you're not interested in allowing the action to be
* performed if the proper credentials are not in order.
*
* == Filter inheritance
*
* Controller inheritance hierarchies share filters downwards, but subclasses can also add new filters without
* affecting the superclass. For example:
*
*   class BankController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->beforeFilter('_audit');
*       }
*
*       public function _audit(&$controller)
*       {
*           // record the action and parameters in an audit log
*       }
*   }
*
*   class VaultController extends BankController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->beforeFilter('_verifyCredentials');
*       }
*
*       public function _verifyCredentials(&$controller)
*       {
*           // make sure the user is allowed into the vault
*       }
*   }
*
* Now any actions performed on the BankController will have the audit method called before. On the VaultController,
* first the audit method is called, then the _verifyCredentials method. If the _audit method returns false, then
* _verifyCredentials and the intended action are never called.
*
* == Filter types
*
* A filter can take one of three forms: method reference, external class, or inline method. The first
* is the most common and works by referencing a method somewhere in the inheritance hierarchy of
* the controller by use of a method name. In the bank example above, both BankController and VaultController use this form.
*
* Using an external class makes for more easily reused generic filters, such as output compression. External filter classes
* are implemented by having a static +filter+ method on any class and then passing this class to the filter method. Example:
*
*   class OutputCompressionFilter
*   {
*       public function filter(&$controller)
*       {
*           $controller->response->body = compress($controller->response->body);
*       }
*   }
*
*   class NewspaperController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->afterFilter(new OutputCompressionFilter());
*       }
*   }
*
* The filter method is passed the controller instance and is hence granted access to all aspects of the controller and can
* manipulate them as it sees fit.
*
*
* == Filter chain ordering
*
* Using <tt>beforeFilter</tt> and <tt>afterFilter</tt> appends the specified filters to the existing chain. That's usually
* just fine, but some times you care more about the order in which the filters are executed. When that's the case, you
* can use <tt>prependBeforeFilter</tt> and <tt>prependAfterFilter</tt>. Filters added by these methods will be put at the
* beginning of their respective chain and executed before the rest. For example:
*
*   class ShoppingController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->beforeFilter('verifyOpenShop');
*       }
*   }
*
*
*   class CheckoutController extends AkActionController
*   {
*       public function __construct()
*       {
*           $this->prependBeforeFilter('ensureItemsInCart', 'ensureItemsInStock');
*       }
*   }
*
* The filter chain for the CheckoutController is now <tt>ensureItemsInCart, ensureItemsInStock,</tt>
* <tt>verifyOpenShop</tt>. So if either of the ensure filters return false, we'll never get around to see if the shop
* is open or not.
*
* You may pass multiple filter arguments of each type.
*
* == Around filters
*
* In addition to the individual before and after filters, it's also possible to specify that a single object should handle
* both the before and after call. That's especially useful when you need to keep state active between the before and after,
* such as the example of a benchmark filter below:
*
*   class WeblogController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->aroundFilter(new BenchmarkingFilter());
*       }
*
*       // Before this action is performed, BenchmarkingFilter->before($controller) is executed
*      public function index()
*      {
*      }
*       // After this action has been performed, BenchmarkingFilter->after($controller) is executed
*   }
*
*   class BenchmarkingFilter
*   {
*       public function before(&$controller)
*       {
*           start_timer();
*       }
*
*       public function after(&$controller)
*       {
*           stop_timer();
*           report_result();
*       }
*   }
*
* == Filter chain skipping
*
* Some times its convenient to specify a filter chain in a superclass that'll hold true for the majority of the
* subclasses, but not necessarily all of them. The subclasses that behave in exception can then specify which filters
* they would like to be relieved of. Examples
*
*   class ApplicationController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->beforeFilter('authenticate');
*       }
*   }
*
*   class WeblogController extends ApplicationController
*   {
*       // will run the authenticate filter
*   }
*
*   class SignupController extends AkActionController
*   {
*       public function __construct()
*       {
*           parent::init();
*           $this->skipBeforeFilter('authenticate');
*       }
*       // will not run the authenticate filter
*   }
*
* == Filter conditions
*
* Filters can be limited to run for only specific actions. This can be expressed either by listing the actions to
* exclude or the actions to include when executing the filter. Available conditions are +only+ or +except+, both
* of which accept an arbitrary number of method references. For example:
*
*   class Journal extends AkActionController
*   {
*       public function __construct()
*       {   // only require authentication if the current action is edit or delete
*           parent::init();
*           $this->beforeFilter(array('_authorize'=>array('only'=>array('edit','delete')));
*       }
*
*       public function _authorize(&$controller)
*       {
*         // redirect to login unless authenticated
*       }
*   }
*/
class AkControllerFilter
{
    protected
    $_includedActions = array(),
    $_beforeFilters = array(),
    $_afterFilters = array(),
    $_excludedActions = array();

    private
    $_FilteredObject;


    /**
    * The passed <tt>filters</tt> will be appended to the array of filters that's run _before_ actions
    * on this controller are performed.
    */
    public function appendBeforeFilter()
    {
        $filters = array_reverse(func_get_args());
        foreach (array_keys($filters) as $k){
            $conditions = $this->_extractConditions($filters[$k]);
            $this->_addActionConditions($filters[$k], $conditions);
            $this->_appendFilterToChain('before', $filters[$k]);
        }
    }

    /**
    * The passed <tt>filters</tt> will be prepended to the array of filters that's run _before_ actions
    * on this controller are performed.
    */
    public function prependBeforeFilter()
    {
        $filters = array_reverse(func_get_args());
        foreach (array_keys($filters) as $k){
            $conditions = $this->_extractConditions($filters[$k]);
            $this->_addActionConditions($filters[$k], $conditions);
            $this->_prependFilterToChain('before', $filters[$k]);
        }
    }

    /**
    * Short-hand for appendBeforeFilter since that's the most common of the two.
    */
    public function beforeFilter()
    {
        $filters = func_get_args();
        foreach (array_keys($filters) as $k){
            $this->appendBeforeFilter($filters[$k]);
        }
    }

    /**
    * The passed <tt>filters</tt> will be appended to the array of filters that's run _after_ actions
    * on this controller are performed.
    */
    public function appendAfterFilter()
    {
        $filters = array_reverse(func_get_args());
        foreach (array_keys($filters) as $k){
            $conditions = $this->_extractConditions($filters[$k]);
            $this->_addActionConditions($filters[$k], $conditions);
            $this->_appendFilterToChain('after', $filters[$k]);
        }

    }

    /**
    * The passed <tt>filters</tt> will be prepended to the array of filters that's run _after_ actions
    * on this controller are performed.
    */
    public function prependAfterFilter()
    {
        $filters = array_reverse(func_get_args());
        foreach (array_keys($filters) as $k){
            $conditions = $this->_extractConditions($filters[$k]);
            $this->_addActionConditions($filters[$k], $conditions);
            $this->_prependFilterToChain('after', $filters[$k]);
        }
    }

    /**
    * Short-hand for appendAfterFilter since that's the most common of the two.
    * */
    public function afterFilter()
    {
        $filters = func_get_args();
        foreach (array_keys($filters) as $k){
            $this->appendAfterFilter($filters[$k]);
        }
    }

    /**
    * The passed <tt>filters</tt> will have their +before+ method appended to the array of filters that's run both before actions
    * on this controller are performed and have their +after+ method prepended to the after actions. The filter objects must all
    * respond to both +before+ and +after+. So if you do appendAroundFilter(new A(), new B()), the callstack will look like:
    *
    *   B::before()
    *   A::before()
    *   A::after()
    *   B::after()
    */
    public function appendAroundFilter()
    {
        $filters = func_get_args();
        foreach (array_keys($filters) as $k){
            $this->_ensureRespondsToBeforeAndAfter($filters[$k]);
            $this->appendBeforeFilter(array($filters[$k],'before'));
        }
        $filters = array_reverse($filters);
        foreach (array_keys($filters) as $k){
            $this->prependAfterFilter(array($filters[$k],'after'));
        }
    }

    /**
    * The passed <tt>filters</tt> will have their +before+ method prepended to the array of filters that's run both before actions
    * on this controller are performed and have their +after+ method appended to the after actions. The filter objects must all
    * respond to both +before+ and +after+. So if you do appendAroundFilter(new A(), new B()), the callstack will look like:
    *
    *   A::before()
    *   B::before()
    *   B::after()
    *   A::after()
    */
    public function prependAroundFilter()
    {
        $filters = func_get_args();
        foreach (array_keys($filters) as $k){
            $this->_ensureRespondsToBeforeAndAfter($filters[$k]);
            $this->prependBeforeFilter(array($filters[$k],'before'));
        }
        $filters = array_reverse($filters);
        foreach (array_keys($filters) as $k){
            $this->appendAfterFilter(array($filters[$k],'after'));
        }
    }

    /**
    * Short-hand for appendAroundFilter since that's the most common of the two.
    */
    public function aroundFilter()
    {
        $filters = func_get_args();
        call_user_func_array(array($this,'appendAroundFilter'), $filters);
    }

    /**
    * Removes the specified filters from the +before+ filter chain.
    * This is especially useful for managing the chain in inheritance hierarchies where only one out
    * of many sub-controllers need a different hierarchy.
    */
    public function skipBeforeFilter($filters)
    {
        $filters = func_get_args();
        $this->_skipFilter($filters, 'before');
    }

    /**
    * Removes the specified filters from the +after+ filter chain. Note that this only works for skipping method-reference
    * filters, not instances. This is especially useful for managing the chain in inheritance hierarchies where only one out
    * of many sub-controllers need a different hierarchy.
    */
    public function skipAfterFilter($filters)
    {
        $filters = func_get_args();
        $this->_skipFilter($filters, 'after');
    }


    /**
    * Returns all the before filters for this class.
    */
    public function beforeFilters()
    {
        return $this->_beforeFilters;
    }

    /**
    * Returns all the after filters for this class and all its ancestors.
    */
    public function afterFilters()
    {
        return $this->_afterFilters;
    }


    public function performActionWithFilters($method = '')
    {
        if ($this->beforeAction($method) !== false && !empty($this->_FilteredObject) && method_exists($this->_FilteredObject, 'hasPerformed') && !$this->_FilteredObject->hasPerformed()){
            AK_ENABLE_PROFILER &&  Ak::profile("Called $method  before filters");
            $this->_FilteredObject->performActionWithoutFilters($method);
            AK_ENABLE_PROFILER &&  Ak::profile("Performed $method  action");
            $this->afterAction($method);
            AK_ENABLE_PROFILER &&  Ak::profile("Called $method  after filters");
            return true;
        }
        return false;
    }

    public function performAction($method = '')
    {
        $this->performActionWithFilters($method);
    }


    /**
    * Calls all the defined before-filter filters, which are added by using "beforeFilter($method)".
    * If any of the filters return false, no more filters will be executed and the action is aborted.
    */
    public function beforeAction($method = '')
    {
        return $this->_callFilters($this->_beforeFilters, $method);
    }

    /**
    * Calls all the defined after-filter filters, which are added by using "afterFilter($method)".
    * If any of the filters return false, no more filters will be executed.
    */
    public function afterAction($method = '')
    {
        return $this->_callFilters($this->_afterFilters, $method);
    }


    /**
    * Returns a mapping between filters and the actions that may run them.
    */
    public function getFilterIncludedActions()
    {
        return $this->_includedActions;
    }

    /**
    * Returns a mapping between filters and actions that may not run them.
    */
    public function getFilterExcludedActions()
    {
        return $this->_excludedActions;
    }

    private function _skipFilter(&$filters, $type)
    {
        $_filters =& $this->{'_'.$type.'Filters'};
        // array_diff doesn't play nice with some PHP5 releases when it comes to
        // Objects as it only diff equal references, not object types
        foreach (array_keys($filters) as $k){
            if(AK_PHP5){
                if(is_object($filters[$k])){
                    foreach (array_keys($_filters) as $k2){
                        if(is_object($_filters[$k2]) && get_class($_filters[$k2]) == get_class($filters[$k])){
                            $pos = $k2;
                            break;
                        }
                    }
                }else{
                    $pos = array_search($filters[$k], $_filters);
                }

                array_splice($_filters, $pos, 1, null);
                return ;
            }
            $_filters = array_diff($_filters,array($filters[$k]));
        }
    }


    private function _appendFilterToChain($condition, $filters)
    {
        $this->{"_{$condition}Filters"}[] = $filters;
    }

    private function _prependFilterToChain($condition, $filters)
    {
        array_unshift($this->{"_{$condition}Filters"}, $filters);
    }

    private function _ensureRespondsToBeforeAndAfter(&$filter_object)
    {
        if(!method_exists($filter_object, 'before') && !method_exists($filter_object, 'after')){
            trigger_error(Ak::t('Filter object must respond to both before and after'), E_USER_ERROR);
        }
    }

    private function _extractConditions(&$filters)
    {
        if(is_array($filters) && !isset($filters[0])){
            $keys = array_keys($filters);
            $conditions = $filters[$keys[0]];
            $filters = $keys[0];
            return $conditions;
        }
    }

    private function _addActionConditions($filters, $conditions)
    {
        if(!empty($conditions['only'])){
            $this->_includedActions[$this->_filterId($filters)] =  $this->_conditionArray($this->_includedActions, $conditions['only']);
        }
        if(!empty($conditions['except'])){
            $this->_excludedActions[$this->_filterId($filters)] =  $this->_conditionArray($this->_excludedActions, $conditions['except']);
        }
    }

    public function _conditionArray($actions, $filter_actions)
    {
        $filter_actions = is_array($filter_actions) ? $filter_actions : array($filter_actions);
        $filter_actions = array_map(array($this,'_filterId'),$filter_actions);
        return array_unique(array_merge($actions, $filter_actions));
    }


    static function _filterId($filters)
    {
        return is_string($filters) ? $filters : md5(serialize($filters));
    }


    public function _callFilters(&$filters, $method = '')
    {
        $filter_result = null;
        foreach (array_keys($filters) as $k){
            $filter =& $filters[$k];
            if(!$this->_actionIsExempted($filter, $method)){
                if(is_array($filter) && is_object($filter[0]) && method_exists($filter[0], $filter[1])){
                    $filter_result = $filter[0]->$filter[1]($this->_FilteredObject);
                }elseif(!is_object($filter) && $this->_FilteredObject && method_exists($this->_FilteredObject, $filter)){
                    $filter_result = $this->_FilteredObject->$filter($this->_FilteredObject);
                }elseif(is_object($filter) && method_exists($filter, 'filter')){
                    $filter_result = $filter->filter($this->_FilteredObject);
                }else{
                    trigger_error(Ak::t('Invalid filter %filter. Filters need to be a method name or a class implementing a static filter method', array('%filter'=>$filter)), E_USER_WARNING);
                }

            }
            if($filter_result === false){
                !empty($this->_Logger) ? $this->_Logger->info(Ak::t('Filter chain halted as '.$filter.' returned false')) : null;
                return false;
            }
        }
        return $filter_result;
    }


    public function setObjectBeenFiltered(&$FilteredObject)
    {
        $this->_FilteredObject = $FilteredObject;
    }


    public function _actionIsExempted($filter, $method = '')
    {
        $method_id = is_string($method) ? $method : $this->_filterId($method);
        $filter_id = $this->_filterId($filter);

        if((!empty($this->_includedActions[$filter_id]) && !in_array($method_id, $this->_includedActions[$filter_id])) ||
        (!empty($this->_excludedActions[$filter_id]) && in_array($method_id, $this->_excludedActions[$filter_id]))){
            return true;
        }

        return false;
    }

}

