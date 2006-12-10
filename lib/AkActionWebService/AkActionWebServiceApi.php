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
 * @subpackage AkActionWebservice
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
 * A web service API class specifies the methods that will be available for
 * invocation for an API. It also contains metadata such as the method type
 * signature hints.
 *
 * It is not intended to be instantiated.
 *
 * It is attached to web service implementation classes like
 * AkActionWebService and AkActionController derivatives by using
 * <tt>container::web_service_api</tt>, where <tt>container</tt> is an
 * AkActionController or an AkActionWebService.
 *
 * See AkActionWebService/AkDirectContainer.php class methods for an example
 * of use.
 */
class AkActionWebserviceApi extends AkObject 
{   
    /**
     * Whether to transform the public API method names into camel-cased names 
     */
    var $inflect_names = true;
    
    /**
     * Whether to localize the API documentation automatically.
     */
    var $localize_documentation = true;

    /**
    * If present, the name of a method to call when the remote caller
    * tried to call a nonexistent method. 
    */
    var $default_api_method;
    
    var $_api_methods = array();
    var $_api_public_method_names = array();
    
    /**
    * API methods have a +name+, which must be the PHP method name to use when
    * performing the invocation on the web service object.
    *
    * The signatures for the method input parameters and return value can
    * by specified in +options+.
    *
    * A signature is an array of one or more parameter specifiers. 
    * A parameter specifier can be one of the following:
    *
    * * A string representing one of the Action Web Service base types.
    *   See AkActionWebService/AkSignatureTypes.php for a canonical list of the base types.
    * * The Class object of the parameter type
    * * A single-element Array containing one of the two preceding items. This
    *   will cause Action Web Service to treat the parameter at that position
    *   as an array containing only values of the given type.
    * * An Array containing as key the name of the parameter, and as value
    *   one of the three preceding items
    * 
    * If no method input parameter or method return value signatures are given,
    * the method is assumed to take no parameters and/or return no values of
    * interest, and any values that are received by the server will be
    * discarded and ignored.
    *
    * Valid options:
    * <tt>expects</tt>             Signature for the method input parameters
    * <tt>returns</tt>             Signature for the method return value
    * <tt>expects_and_returns</tt> Signature for both input parameters and return value
    */
    function addApiMethod($name, $options = array())
    {
        $this->_validateOptions(array('expects', 'returns', 'expects_and_returns', 'documentation'), array_keys($options));
        if (!empty($options['expects_and_returns'])){
            $expects = $returns = $options['expects_and_returns'];
        }else{
            $expects = @$options['expects'];
            $returns = @$options['returns'];
        }

        $public_name = $this->getPublicApiMethodName($name);
        $method =& new AkActionWebServiceMethod($name, $public_name, $expects, $returns, @$options['documentation'], $this->localize_documentation);
        $this->_api_methods[$name] =& $method;
        $this->_api_public_method_names[$public_name] = $name;
    }

    /**
    * Whether the given method name is a service method on this API
    */
    function hasApiMethod($name)
    {
        return !empty($this->_api_methods[$name]);
    }

    /**
    * Whether the given public method name has a corresponding service method
    * on this API
    */
    function hasPublicApiMethod($public_name)
    {
        return !empty($this->_api_public_method_names[$public_name]);
    }

    /**
    * The corresponding public method name for the given service method name
    */
    function getPublicApiMethodName($name)
    {
        return $this->inflect_names ? AkInflector::camelize($name) : $name;
    }

    /**
    * The corresponding service method name for the given public method name
    */
    function getApiMethodName($public_name)
    {
        return $this->_api_public_method_names[$public_name];
    } 
    
    /**
    * An array containing all service methods on this API, and their
    * associated metadata.
    */
    function &getApiMethods()
    {
        return $this->_api_methods;
    }

    /**
    * The Method instance for the given public API method name, if any
    */
    function &getPublicApiMethodInstance($public_method_name)
    {
        return $this->getApiMethodInstance($this->getApiMethodName($public_method_name));
    }

    /**
    * The Method instance for the given API method name, if any
    */
    function &getApiMethodInstance($method_name)
    {
        return $this->_api_methods[$method_name];
    }

    /**
    * The Method instance for the default API method, if any
    */
    function &getDefaultApiMethodInstance()
    {
        if(empty($this->default_api_method)){
            return $GLOBALS['false'];
        }

        $name = $this->default_api_method;
        if(!empty($this->default_api_method_instance->name) && $this->default_api_method_instance->name == $name){
            return $this->default_api_method_instance;
        }

        $this->default_api_method_instance =& new AkActionWebServiceMethod($name, $this->getPublicApiMethodName($name), null, null, null);
        return $this->default_api_method_instance;
    }

    function _getApiPublicMethodNames()
    {
        return array_keys($this->_api_public_method_names);
    }

    function _validateOptions($valid_option_keys, $supplied_option_keys)
    {
        $unknown_option_keys = array_diff($supplied_option_keys, $valid_option_keys);
        if(!empty($unknown_option_keys)){
            trigger_error(Ak::t('Unknown options: %options', array('%options'=> var_export($unknown_option_keys,true))), E_USER_ERROR);
        }
    }
}


/**
* Represents an API method and its associated metadata, and provides functionality
* to assist in commonly performed API method tasks.
*/
class AkActionWebServiceMethod
{
    var $name;
    var $public_name;
    var $expects;
    var $returns;
    var $documentation;
    var $expects_documentation = array();
    var $returns_documentation = array();

    function AkActionWebServiceMethod($name, $public_name, $expects, $returns, $documentation, $localize_documentation = true)
    {
        $this->name = $name;
        $this->public_name = $public_name;
        $this->expects = $expects;
        $this->returns = $returns;
        $this->documentation = $documentation;
        $this->localize_documentation = $localize_documentation;
       
        $this->_extractDocumentationFromExpects();
        $this->_extractDocumentationFromReturns();
    }
    
    function _extractDocumentationFromExpects()
    {
        return $this->_extractDocumentationFromMethod('expects');
    }
    
    function _extractDocumentationFromReturns()
    {
        return $this->_extractDocumentationFromMethod('returns');
    }
    
    function _extractDocumentationFromMethod($expects_or_returns)
    {
        if(!in_array($expects_or_returns, array('expects', 'returns'))){
            trigger_error(Ak::t('Only expects and returns options are valid'), E_USER_ERROR);
            return false;
        }
        $parameters = array();
        $i = 0;
        foreach ((array)$this->{$expects_or_returns} as $parameter=>$documentation){
            
            if(is_numeric($parameter)){
                $parameters[] = $documentation;
            }else{
                $parameters[] = $parameter;
                $this->{$expects_or_returns.'_documentation'}[$i] = $documentation;
            }
            $i++;
        }
        $this->{$expects_or_returns} = $parameters;
    }
    
}

?>