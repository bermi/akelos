<?php
require_once(AK_LIB_DIR.DS.'AkObject.php');

class AkType extends AkObject
{
    var $value;
    function __construct($value)
    {
        $this->value = $value;
        require_once('AkInflector.php');
    }
    function toString()
    {
        return $this->value.'';
    }
    function getValue()
    {
        return $this->value;
    }
    function inspect()
    {
        return var_export($this->value, true);
    }
    
    function blank()
    {
        return empty($this->value);
    }
}
require_once(AK_LIB_DIR.DS.'AkType'.DS.'AkNumber.php');
require_once(AK_LIB_DIR.DS.'AkType'.DS.'AkString.php');
require_once(AK_LIB_DIR.DS.'AkType'.DS.'AkTime.php');
require_once(AK_LIB_DIR.DS.'AkType'.DS.'AkArray.php');
/**require_once(AK_LIB_DIR.DS.'AkType'.DS.'AkDate.php');
*/

function &AkT($param,$command=null)
{
    $type = gettype($param);
    switch ($type) {
        case 'array':
            $obj = &new AkArray($param);
            break;
        case 'integer':
            $obj = &new AkNumber($param);
            break;
        case 'string':
        default:
            $obj = &new AkString($param);
            break;
    }
    if ($command!=null) {
        $items = preg_split('/\./',$command);
        $prepend = '';
        while($item = array_shift($items)) {
            $item = $prepend.$item;
            $args = array();
            preg_match('/([a-zA-Z_])+(\(.*?\)){0,1}/',$item,$matches);
            if (isset($matches[2])) {
                $item = str_replace($matches[2],'',$item);
                $args = preg_split('/\s*,\s*/',trim($matches[2],'()'));
            }
            if (method_exists($obj,$item)) {
                
                
                if (empty($args)) {
                    $obj = $obj->$item();
                } else {
                    $obj = call_user_func_array(array(&$obj,$item),$args);
                }
                $prepend = '';
            } else {
                $prepend = $item.$prepend;
            }
            
        }
        if (is_object($obj) && method_exists($obj,'getValue')) {
            $obj = $obj->getValue();
        }
    }
    return $obj;
}



?>
