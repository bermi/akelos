<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActionController
 * @subpackage Paginator
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

require_once(AK_VENDOR_DIR.DS.'pear'.DS.'HTML'.DS.'Pager'.DS.'Pager.php');
require_once(AK_VENDOR_DIR.DS.'pear'.DS.'HTML'.DS.'Pager'.DS.'Common.php');

/**
 * AkPager and AkPaginator are a fork of Pear::Pager
 * coded by  Lorenzo Alberton <at quipo.it> and
 * Richard Heyes <mailto:richard at phpguru.org>,
 */


class AkPager extends Pager_Common
{
    // {{{ AkPager()

    /**
     * Constructor
     *
     * @param mixed $options    An associative array of option names
     *                          and their values
     * @access public
     */
    function init($options = array())
    {
        //set default AkPager options
        $this->_delta                 = 2;
        $this->_prevImg               = '&laquo;';
        $this->_nextImg               = '&raquo;';
        $this->_separator             = '|';
        $this->_spacesBeforeSeparator = 3;
        $this->_spacesAfterSeparator  = 3;
        $this->_curPageSpanPre        = '<b><u>';
        $this->_curPageSpanPost       = '</u></b>';

        //set custom options
        $err = $this->_setOptions($options);
        if ($err !== PAGER_OK) {
            return $this->raiseError($this->errorMessage($err), $err);
        }
        $this->_generatePageData();
        $this->_setFirstLastText();

        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            $this->links .= $this->_printFirstPage();
        }

        $this->links .= $this->_getBackLink();
        $this->links .= $this->_getPageLinks();
        $this->links .= $this->_getNextLink();

        $this->linkTags .= $this->_getFirstLinkTag();
        $this->linkTags .= $this->_getPrevLinkTag();
        $this->linkTags .= $this->_getNextLinkTag();
        $this->linkTags .= $this->_getLastLinkTag();

        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            $this->links .= $this->_printLastPage();
        }
    }

    // }}}
    // {{{ getPageIdByOffset()

    /**
     * "Overload" PEAR::Pager method. VOID. Not needed here...
     * @param integer $index Offset to get pageID for
     * @deprecated
     * @access public
     */
    function getPageIdByOffset($index=null) { }

    // }}}
    // {{{ getPageRangeByPageId()

    /**
     * Given a PageId, it returns the limits of the range of pages displayed.
     * While getOffsetByPageId() returns the offset of the data within the
     * current page, this method returns the offsets of the page numbers interval.
     * E.g., if you have pageId=5 and delta=2, it will return (3, 7).
     * PageID of 9 would give you (4, 8).
     * If the method is called without parameter, pageID is set to currentPage#.
     *
     * @param integer PageID to get offsets for
     * @return array  First and last offsets
     * @access public
     */
    function getPageRangeByPageId($pageid = null)
    {
        $pageid = isset($pageid) ? (int)$pageid : $this->_currentPage;
        if (!isset($this->_pageData)) {
            $this->_generatePageData();
        }
        if (isset($this->_pageData[$pageid]) || is_null($this->_itemData)) {
            if ($this->_expanded) {
                $min_surplus = ($pageid <= $this->_delta) ? ($this->_delta - $pageid + 1) : 0;
                $max_surplus = ($pageid >= ($this->_totalPages - $this->_delta)) ?
                                ($pageid - ($this->_totalPages - $this->_delta)) : 0;
            } else {
                $min_surplus = $max_surplus = 0;
            }
            return array(
                max($pageid - $this->_delta - $max_surplus, 1),
                min($pageid + $this->_delta + $min_surplus, $this->_totalPages)
            );
        }
        return array(0, 0);
    }

    // }}}
    // {{{ getLinks()

    /**
     * Returns back/next/first/last and page links,
     * both as ordered and associative array.
     *
     * @param integer $pageID Optional pageID. If specified, links
     *                for that page are provided instead of current one.
     * @return array back/pages/next/first/last/all links
     * @access public
     */
    function getLinks($pageID = null)
    {
        if ($pageID != null) {
            $_sav = $this->_currentPage;
            $this->_currentPage = $pageID;

            $this->links = '';
            if ($this->_totalPages > (2 * $this->_delta + 1)) {
                $this->links .= $this->_printFirstPage();
            }
            $this->links .= $this->_getBackLink();
            $this->links .= $this->_getPageLinks();
            $this->links .= $this->_getNextLink();
            if ($this->_totalPages > (2 * $this->_delta + 1)) {
                $this->links .= $this->_printLastPage();
            }
        }

        $back  = str_replace('&nbsp;', '', $this->_getBackLink());
        $next  = str_replace('&nbsp;', '', $this->_getNextLink());
        $pages = $this->_getPageLinks();
        $first = $this->_printFirstPage();
        $last  = $this->_printLastPage();
        $all   = $this->links;
        $linkTags = $this->linkTags;

        if ($pageID != null) {
            $this->_currentPage = $_sav;
        }

        return array(
            $back,
            $pages,
            trim($next),
            $first,
            $last,
            $all,
            $linkTags,
            'back'  => $back,
            'pages' => $pages,
            'next'  => $next,
            'first' => $first,
            'last'  => $last,
            'all'   => $all,
            'linktags' => $linkTags
        );
    }

    // }}}
    // {{{ _getPageLinks()

    /**
     * Returns pages link
     *
     * @return string Links
     * @access private
     */
    function _getPageLinks($url = '')
    {
        //legacy setting... the preferred way to set an option now
        //is adding it to the constuctor
        if (!empty($url)) {
            $this->_path = $url;
        }
        
        //If there's only one page, don't display links
        if ($this->_clearIfVoid && ($this->_totalPages < 2)) {
            return '';
        }

        $links = '';
        if ($this->_totalPages > (2 * $this->_delta + 1)) {
            if ($this->_expanded) {
                if (($this->_totalPages - $this->_delta) <= $this->_currentPage) {
                    $expansion_before = $this->_currentPage - ($this->_totalPages - $this->_delta);
                } else {
                    $expansion_before = 0;
                }
                for ($i = $this->_currentPage - $this->_delta - $expansion_before; $expansion_before; $expansion_before--, $i++) {
                    $print_separator_flag = ($i != $this->_currentPage + $this->_delta); // && ($i != $this->_totalPages - 1)
                    
                    $this->range[$i] = false;
                    $this->_linkData[$this->_urlVar] = $i;
                    $links .= $this->_renderLink($this->_altPage.' '.$i, $i)
                           . $this->_spacesBefore
                           . ($print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                }
            }

            $expansion_after = 0;
            for ($i = $this->_currentPage - $this->_delta; ($i <= $this->_currentPage + $this->_delta) && ($i <= $this->_totalPages); $i++) {
                if ($i < 1) {
                    ++$expansion_after;
                    continue;
                }

                // check when to print separator
                $print_separator_flag = (($i != $this->_currentPage + $this->_delta) && ($i != $this->_totalPages));

                if ($i == $this->_currentPage) {
                    $this->range[$i] = true;
                    $links .= $this->_curPageSpanPre . $i . $this->_curPageSpanPost;
                } else {
                    $this->range[$i] = false;
                    $this->_linkData[$this->_urlVar] = $i;
                    $links .= $this->_renderLink($this->_altPage.' '.$i, $i);
                }
                $links .= $this->_spacesBefore
                        . ($print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
            }

            if ($this->_expanded && $expansion_after) {
                $links .= $this->_separator . $this->_spacesAfter;
                for ($i = $this->_currentPage + $this->_delta +1; $expansion_after; $expansion_after--, $i++) {
                    $print_separator_flag = ($expansion_after != 1);
                    $this->range[$i] = false;
                    $this->_linkData[$this->_urlVar] = $i;
                    $links .= $this->_renderLink($this->_altPage.' '.$i, $i)
                      . $this->_spacesBefore
                      . ($print_separator_flag ? $this->_separator.$this->_spacesAfter : '');
                }
            }

        } else {
            //if $this->_totalPages <= (2*Delta+1) show them all
            for ($i=1; $i<=$this->_totalPages; $i++) {
                if ($i != $this->_currentPage) {
                    $this->range[$i] = false;
                    $this->_linkData[$this->_urlVar] = $i;
                    $links .= $this->_renderLink($this->_altPage.' '.$i, $i);
                } else {
                    $this->range[$i] = true;
                    $links .= $this->_curPageSpanPre . $i . $this->_curPageSpanPost;
                }
                $links .= $this->_spacesBefore
                       . (($i != $this->_totalPages) ? $this->_separator.$this->_spacesAfter : '');
            }
        }
        return $links;
    }

    // }}}
    
    /**
     * Renders a link using the appropriate method
     *
     * @param altText Alternative text for this link (title property)
     * @param linkText Text contained by this link
     * @return string The link in string form
     * @access private
     */
    function _renderLink($altText, $linkText)
    {        
        $href = $this->controller->url_helper->modify_current_url($this->_linkData);
        
        if ($this->_httpMethod == 'GET') {

            return sprintf('<a href="%s"%s title="%s">%s</a>',
                           $href,
                           empty($this->_classString) ? '' : ' '.$this->_classString,
                           $altText,
                           $linkText
            );
        }
        if ($this->_httpMethod == 'POST') {
            return sprintf("<a href='javascript:void(0)' onClick='%s'%s title='%s'>%s</a>",
                           $this->_generateFormOnClick($this->_url, $this->_linkData),
                           empty($this->_classString) ? '' : ' '.$this->_classString,
                           $altText,
                           $linkText
            );
        }
        return '';
    }


}


/**
* A class representing a paginator for an Active Record collection.
*/
class AkPaginator
{
    /**
    * Creates a new AkPaginator on the given +controller+ for a set of items
    * of size +item_count+ and having +items_per_page+ items per page.
    * Raises an error if items_per_page is out of bounds (i.e., less
    * than or equal to zero). The page GET parameter for links defaults to
    * "page" and can be overridden with +page_parameter+.
    */
    function &AkPaginator(&$controller, $item_count, $items_per_page, $current_page=1)
    {
        static $paginator;
        if($items_per_page <= 0){
            trigger_error(Ak::t('must have at least one item per page'),E_USER_WARNING);
        }

        if(empty($current_page)){
            $current_page = 1;
            $controller->params[$controller->_pagination_options['parameter']] = 1;
        }
        $this->controller =& $controller;
        $controller_name = $controller->Request->getController();
        $this->item_count = !empty($item_count) ? $item_count : 0;
        $this->items_per_page = $items_per_page;
        $this->pages = ceil($item_count/$items_per_page);

        if(!isset($paginator[$controller_name.'_paginator'])){
            
            $pager_options = array(
            'totalItems'=>$item_count,// Number of items to page (used only if itemData is not provided).
            'perPage'=>$items_per_page,//Number of items to display on each page.
            'currentPage'=>$current_page,//Initial page number (if you want to show page #2 by default, set currentPage to 2)
            'delta'=>4,// Number of page numbers to display before and after the current one.
            'mode'=>'Sliding',// "Jumping" or "Sliding" -window - It determines pager behaviour.
            'httpMethod'=>'GET',// Specifies the HTTP method to use. Valid values are 'GET' or 'POST'.
            //'formID'=>'',//Specifies which HTML form to use in POST mode.
            'importQuery'=>true,//if true (default behaviour), variables and values are imported from
            //'extraVars'=>'',//additional URL vars to be added to the querystring.
            //'excludeVars'=>'',//URL vars to be excluded from the querystring.
            // the submitted data (query string) and used in the generated links, otherwise they're ignored completely
            'expanded'=>true,// if TRUE, window size is always 2*delta+1
            'linkClass'=> 'paginationLink',//Name of CSS class used for link styling.
            'curPageLinkClassName'=>'paginationCurrent',//Name of CSS class used for current page link.
            'urlVar'=>$controller->_pagination_options['parameter'],//Name of URL var used to indicate the page number. Default value is "pageID".            
            'path'=> '',//Complete path to the page (without the page name).
            'fileName'=>'?'.$controller->_pagination_options['parameter'].'=%d',//name of the page, with a "%d" if append == TRUE.
            'append'=>false,//If TRUE pageID is appended as GET value to the URL. If FALSE it is embedded in the URL according to fileName specs.
            'altFirst'=>Ak::t('first page'),//Alt text to display on the link of the first page. Default value is "first page";
            //if you want a string with the page number, use "%d" as a placeholder (for instance "page %d")
            'altPrev'=>Ak::t('previous page'),// Alt text to display on the link of the previous page. Default value is "previous page";
            'altNext'=>Ak::t('next page'),//Alt text to display on the link of the next page. Default value is "next page";
            'altLast'=>Ak::t('last page'),//Alt text to display on the link of the last page. Default value is "last page";
            //if you want a string with the page number, use "%d" as a placeholder (for instance "page %d")
            'altPage'=>Ak::t('page').' ',//Alt text to display before the page number. Default value is "page ".
            'prevImg'=>'<span class="paginationPrevious">'.Ak::t('previous')."</span>",//Something to display instead of "<<". It can be text such as "<< PREV" or an <img/> as well.
            'nextImg'=>'<span class="paginationNext">'.Ak::t('next').'</span>',//Something to display instead of ">>". It can be text such as "NEXT >>" or an <img/> as well.
            'separator'=>' ',// What to use to separate numbers. It can be an <img/>, a comma, an hyphen, or whatever.
            'spacesBeforeSeparator'=>0,//Number of spaces before the separator.
            'spacesAfterSeparator'=>0,//Number of spaces after the separator.
            'firstPagePre'=>' ',//String used before first page number. It can be an <img/>, a "{", an empty string, or whatever.
            'firstPageText'=>'<span class="paginationFirst">'.Ak::t('first').'</span>',//String used in place of first page number.
            'firstPagePost'=>' ',//String used after first page number. It can be an <img/>, a "}", an empty string, or whatever.
            'lastPagePre'=>' ',//Similar to firstPagePre, but used for last page number.
            'lastPageText'=>'<span class="paginationLast">'.Ak::t('last').'</span>',//Similar to firstPageText, but used for last page number.
            'lastPagePost'=>' ',//Similar to firstPagePost, but used for last page number.
            'clearIfVoid'=>true,//if there's only one page, don't display pager links (returns an empty string).
            'useSessions'=>true,//if TRUE, number of items to display per page is stored in the $_SESSION[$_sessionVar] var.
            'closeSession'=>false,//if TRUE, the session is closed just after R/W.
            'sessionVar'=>$controller_name.'_paginator',//Name of the session var for perPage value. A value different from default can be useful when using more than one Pager istance in the page.
            'showAllText'=>Ak::t('show all')// Text to be used for the 'show all' option in the select box generated by getPerPageSelectBox()
            );
            
            $paginator[$controller_name.'_paginator'] =& new AkPager();
            $paginator[$controller_name.'_paginator']->controller =& $controller;
            $paginator[$controller_name.'_paginator']->init($pager_options);
        }

        $this->paginator =& $paginator[$controller_name.'_paginator'];
        $this->controller->paginator =& $this->paginator;
        
        $this->links = $this->links();
        
        //$paginator[$controller_name.'_paginator']->links = 'Hola';
        return $paginator[$controller_name.'_paginator'];

    }

    function &getController()
    {
        return $this->controller;
    }

    function getItemCount()
    {
        return $this->item_count;
    }

    function getItemsPerPage()
    {
        return $this->items_per_page;
    }
    
    function getOffset()
    {
        return array_shift($this->paginator->getOffsetByPageId($this->getCurrent()))-1;
    }

    function getCurrent()
    {
        return $this->paginator->getCurrentPageID();
    }
    
    function getCurrentPage()
    {
        return $this->paginator->getCurrentPageID();
    }

    function getFirstPage()
    {
        return 1;
    }
    function getFirst()
    {
        return 1;
    }

    function getLast()
    {
        return $this->paginator->getLastPage();
    }

    function pageCount()
    {
        return $this->paginator->numPages();
    }

    function lenght()
    {
        return $this->pageCount();
    }

    /**
      * Returns true if this paginator contains the page of index +number+.
      */
    function hasPageNumber($number)
    {
        return $number >= 1 && $number <= $this->pageCount();
    }
    
    function links()
    {
        return $this->paginator->links;
    }

}

?>
