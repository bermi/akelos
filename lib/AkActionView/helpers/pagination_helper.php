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
 * @subpackage Helpers
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */


require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

class PaginationHelper extends AkActionViewHelper
{
    function sortable_link($column, $url_options = array(), $link_options = array())
    {
        $default_url_options = array(
        'sort'=>$column,
        'direction' => empty($this->_controller->params['sort']) ? 'desc' : ($this->_controller->params['sort'] == $column ? (empty($this->_controller->params['direction']) ? 'desc' : ($this->_controller->params['direction'] == 'desc' ? 'asc' : 'desc')) : 'desc'),);

        $page_var_on_url = empty($url_options['page_var_on_url']) ? 'page' : $url_options['page_var_on_url'];
        unset($url_options['page_var_on_url']);

        $url_options = array_merge($default_url_options, $url_options);

        $link_options['href'] = html_entity_decode($this->_controller->url_helper->modify_current_url($url_options,array($page_var_on_url)));

        if(empty($link_options['title'])){
            $link_options['title'] = $this->_controller->t("Sort by $column ({$url_options['direction']})");
        }
        if(!empty($link_options['link_text'])){
            $link_text = $link_options['link_text'];
            unset($link_options['link_text']);
        }else{
            $link_text =  $this->_controller->t(AkInflector::humanize($column));
        }
        return TagHelper::content_tag('a',$link_text,$link_options);
    }



    function getFindOptions(&$object)
    {
        $paginator_name = AkInflector::underscore($object->getModelName()).'_pages';

        $limit_and_offset = isset($this->$paginator_name) ? array('limit' =>  $this->$paginator_name->getItemsPerPage(),
        'offset' =>  $this->$paginator_name->getOffset()) : array();

        $find_options = array_merge($limit_and_offset,(!empty($this->_controller->params['sort']) &&
        $object->hasColumn($this->_controller->params['sort']) ? array('order'=>$this->_getOrderColumnFromCurrentModel($object, $paginator_name).$this->getSortDirection()) : array()));

        empty($find_options['sort']) ? ($this->_getOrderFromCustomDictionary($paginator_name, $find_options) ? null : $this->_getOrderFromAssociations($object, $paginator_name, $find_options)) : null;

        if(!empty($this->$paginator_name->_ak_options['include'])){
            $find_options['include'] = Ak::toArray($this->$paginator_name->_ak_options['include']);
        }

        return $find_options;
    }

    function _getOrderColumnFromCurrentModel(&$object, $paginator_name)
    {
        return empty($this->$paginator_name->_ak_options['include']) ? $this->_controller->params['sort'] :  'parent_'.$object->getModelName().'.'.$this->_controller->params['sort'];
    }

    function _getOrderFromCustomDictionary($paginator_name, &$find_options)
    {
        if(!empty($this->_controller->params['sort']) && !empty($this->$paginator_name->_ak_options['column_dictionary'][$this->_controller->params['sort']])){
            $find_options['order'] = $this->$paginator_name->_ak_options['column_dictionary'][$this->_controller->params['sort']].$this->getSortDirection();
            return true;
        }
        return false;
    }

    function _getOrderFromAssociations(&$object, $paginator_name, &$find_options)
    {
        if(!empty($this->_controller->params['sort']) && !empty($this->$paginator_name->_ak_options['include']) && $object->hasAssociations()){
            $included_models = Ak::toArray($this->$paginator_name->_ak_options['include']);
            foreach ($object->getAvailableAssociates() as $association_type=>$associated_models){
                foreach ($associated_models as $associated_model){
                    if(in_array($this->_controller->params['sort'], array_keys($object->$associated_model->getAvailableAttributes()))){
                        $find_options['order'] = $association_type.'_'.$associated_model.'.'.$this->_controller->params['sort'].$this->getSortDirection();
                        return true;
                    }
                }
            }
        }
        return false;
    }

    function getSortDirection()
    {
        return (empty($this->_controller->params['direction']) || $this->_controller->params['direction'] == 'asc' ? ' ASC' : ' DESC');
    }


    /**
     * Returns a paginator object
     *
     * Options:
     * 'items_per_page' => 15, // Number of items per page
     * 'page_var_on_url' => 'page', // This var will be passed thru the url for pointing to current page
     * 'count_method' => 'count' // This method will be called on selected model to get the total number of items.
     * 'count_conditions' => null // A string that will be passed as the first parameter (conditions) to the count method
     * 'count_joins' => null // A string that will be passed as the seccond parameter (join options) to the count method.
     * 'column_dictionary' => array() // In case you need to map the sort key from the url to the database column you must define an array pair
     * 'include' => array() // In case current sort column is not found on current model or in the column_dictionary this helper will look for the first associated model witrh a column named like given sort parameter.
     */
    function getPaginator(&$object, $options = array())
    {
        $default_options = array(
        'items_per_page' => 15,
        'page_var_on_url' => 'page',
        'count_method' => 'count',
        'count_conditions' => null,
        'count_joins' => null
        );
        $options = array_merge($default_options, $options);
        $paginator_name = AkInflector::underscore($object->getModelName()).'_pages';
        $this->$paginator_name = new AkPaginator($this->_controller,
        $object->{$options['count_method']}($options['count_conditions'], $options['count_joins']),
        $options['items_per_page'], @$this->_controller->params[$options['page_var_on_url']]);
        $this->$paginator_name->_ak_options =& $options;
        return $this->$paginator_name;
    }
}


?>
