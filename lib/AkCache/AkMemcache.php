<?php
require_once(AK_VENDOR_DIR.DS.'phpmemcached'.DS.'class_MemCachedClient.php');
require_once(AK_LIB_DIR.DS.'AkObject.php');

class AkMemcache extends AkObject
{
    
    
    /**
     * @var MemCachedClient
     */
    var $_memcache;
    /**
     * caching the integer namespace values
     *
     * @var array
     */
    var $_namespaces = array();
    
    /**
     * max storable size for 1 key,
     * above this size, the class will autosplit
     * the data into chunks
     *
     * @var int
     */
    var $_max_size = 1000000;
    
    var $_servers = array();
    var $_lifeTime = 0;
    
    function init($options = array())
    {
        $default_options = array('servers'=>array('localhost:11211'),'lifeTime'=>0);
        $options = array_merge($default_options, $options);
        $this->_lifeTime = $options['lifeTime'];
        if (empty($options['servers'])) {
            trigger_error('Need to provide at least 1 server',E_USER_ERROR);
            return false;
        }
        $this->_memcache = new MemCachedClient(is_array($options['servers'])?$options['servers']:array($options['servers']));
        $ping = $this->_memcache->get('ping');
        if (!$ping) {
            if ($this->_memcache->errno==ERR_NO_SOCKET) {
                trigger_error("Could not connect to MemCache daemon", E_USER_WARNING);
                return false;
            }
            $this->_memcache->set('ping',1);
        }
        return true;
    }
    

    function _getNamespaceId($group)
    {
        $ident = $group;
        return $ident;
    }
    
    function _clearNamespace($group)
    {
        $group = 'group_'.md5($group);
        $ident = $this->_getNamespaceId($group);
        unset($this->_namespaces[$group]);
        return $this->_memcache->incr($ident,1);
    }
    
    function _getNamespace($group)
    {
        $groupName = $group;
        $group = 'group_'.md5($groupName);
        if (!isset($this->_namespaces[$group])) {
            $ident = $this->_getNamespaceId($group);
            $namespaceVersion = $this->_memcache->get($ident);
            if (!$namespaceVersion) {
                if ($this->_memcache->errno==ERR_NO_SOCKET) {
                    trigger_error("Could not connect to MemCache daemon", E_USER_ERROR);
                }
                $namespaceVersion = 1;
                $this->_memcache->set($ident,$namespaceVersion);
                
            }
            $this->_namespaces[$group] = $groupName.'_'.$namespaceVersion;
        }
        return $this->_namespaces[$group];
    }
    
    function _generateCacheKey($id,$group)
    {
        $namespace = $this->_getNamespace($group);
        $key = $namespace.'_'.$id;
        $key = 'key_'.md5($key);
        return $key;
    }
    
    function get($id, $group = 'default')
    {
        $key = $this->_generateCacheKey($id, $group);
        $return = $this->_memcache->get($key);
        
        if ($return === false) {
            return false;
        }
        
        @list($type,$data) = @split('@#!',$return,2);
        if (isset($data)) {
            settype($data,$type);
        } else {
            if (is_string($return) && substr($return,0,15) == '@____join____@:') {
                @list($start,$parts) = @split(':',$return,2);
                $return = '';
                for($i=0;$i<(int)$parts;$i++) {
                    $return.=$this->_memcache->get($key.'_'.$i);
                }
            }
            $data = &$return;
        }
        return $data;
    }
    
    function save($data, $id = null, $group = null)
    {   
        if (is_numeric($data) || is_bool($data)) {
            $type=gettype($data);
            $data = $type.'@#!'.$data;
        } else if (is_string($data) && ($strlen=strlen($data))> $this->_max_size) {
            $parts = round($strlen / $this->_max_size);
            $key = $this->_generateCacheKey($id, $group);
            $keys = array();
            for ($i=0;$i<$parts;$i++) {
                $nkey = $key.'_'.$i;
                $this->_memcache->set($nkey,substr($data,$i*$this->_max_size,$this->_max_size),$this->_lifeTime);
            }
            
            $return = $this->_memcache->set($key,'@____join____@:'. $parts);
            return $return !== false ? true:false;
        }
        $key = $this->_generateCacheKey($id, $group);
        $return = $this->_memcache->set($key,$data, $this->_lifeTime);
        return $return !== false ? true:false;
    }
    
    function remove($id, $group = 'default')
    {
        $key = $this->_generateCacheKey($id, $group);
        $return = $this->_memcache->delete($key);
        return $return;
    }
    
    function clean($group = false, $mode = 'ingroup')
    {
        switch ($mode) {
            case 'ingroup':
                return $this->_clearNamespace($group);
            case 'notingroup':
                return false;
            case 'old':
                return true;
            default:
                return true;
        }
        
    }
}