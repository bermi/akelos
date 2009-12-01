<?php

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+


/**
 * @package ActionPack
 * @subpackage CacheSweeping
 * @author Arno Schneider
 */

/**
 * Cache Sweepers need to be stored under:
 * 
 * AK_BASE_DIR/app/sweepers
 * 
 * Sweepers are the terminators of the caching world and responsible for expiring caches when model objects change.
 * They do this by being half-observers, half-filters and implementing callbacks for both roles. A Sweeper example:
 *
 *   class ListSweeper extends AkCacheSweeper
 *   {
 *     public $observe = array("List", "Item");
 *
 *     public function afterSave(&$record) {
 *         $list = ($record instanceof List) ? $record : $record->list;
 *         $this->expirePage(array("controller" => "lists", "action" => "public", "id" => $list->id));
 *         $this->expireAction(array("controller" => "lists", "action" => "all"));
 *         foreach($list->shares as $share) {
 *             $this->expirePage(array("controller" => "lists", "action" => "show", "id" => $share->id));
 *         }
 *     }
 *   }
 *
 * The sweeper is assigned in the controllers that wish to have its job performed using the <tt>$cache_sweeper</tt> class attribute:
 *
 *   class ListsController extends ApplicationController {
 *     public $caches_action = array("index", "show", "public", "feed");
 *     public $cache_sweeper = array("list_sweeper" => array("only" => array("edit", "destroy", "share")));
 *     ....
 *   }
 *
 * In the example above, four actions are cached and three actions are responsible for expiring those caches.
 */
class AkCacheSweeper extends AkObserver
{
    public $_cache_handler;

    public function __construct(&$cache_handler)
    {
        $this->_cache_handler = $cache_handler;
        parent::__construct();
    }
    public function expirePage($path = null, $language=null)
    {
        return $this->_cache_handler->expirePage($path,$language);
    }
    public function expireAction($options, $params = array())
    {
        return $this->_cache_handler->expireAction($options, $params);
    }
    public function expireFragment($key, $options = array())
    {
        return $this->_cache_handler->expireFragment($key, $options);
    }
}