<?php

class AkMongoDbCache extends AkObject
{
    public $MongoDb;
    public  $_namespaces = array();
    protected $_servers = array();
    protected $_lifeTime = 0;

    public function init($options = array())
    {
        $default_options = array('servers' => array('localhost:27017'), 'lifeTime' => 0);
        $options = array_merge($default_options, $options);
        $this->_lifeTime = $options['lifeTime'];
        if (empty($options['servers'])) {
            trigger_error('Need to provide at least 1 server',E_USER_ERROR);
            return false;
        }
        $this->MongoDb = new MemCachedClient(is_array($options['servers'])?$options['servers']:array($options['servers']));
        $ping = $this->MongoDb->get('ping');
        if (!$ping) {
            if ($this->MongoDb->errno==ERR_NO_SOCKET) {
                trigger_error("Could not connect to MemCache daemon", E_USER_WARNING);
                return false;
            }
            $this->MongoDb->set('ping',1);
        }
        return true;
    }


    protected function _getNamespaceId($group)
    {
        $ident = $group;
        return $ident;
    }

    protected function _clearNamespace($group)
    {
        $group = 'group_'.md5($group);
        $ident = $this->_getNamespaceId($group);
        unset($this->_namespaces[$group]);
        return $this->MongoDb->incr($ident,1);
    }

    protected function _getNamespace($group)
    {
        $groupName = $group;
        $group = 'group_'.md5($groupName);
        if (!isset($this->_namespaces[$group])) {
            $ident = $this->_getNamespaceId($group);
            $namespaceVersion = $this->MongoDb->get($ident);
            if (!$namespaceVersion) {
                if ($this->MongoDb->errno==ERR_NO_SOCKET) {
                    trigger_error("Could not connect to MemCache daemon", E_USER_ERROR);
                }
                $namespaceVersion = 1;
                $this->MongoDb->set($ident,$namespaceVersion);

            }
            $this->_namespaces[$group] = $groupName.'_'.$namespaceVersion;
        }
        return $this->_namespaces[$group];
    }

    protected function _generateCacheKey($id,$group)
    {
        $namespace = $this->_getNamespace($group);
        $key = $namespace.'_'.$id;
        $key = 'key_'.md5($key);
        return $key;
    }

    public function get($id, $group = 'default')
    {
        $key = $this->_generateCacheKey($id, $group);
        $return = $this->MongoDb->get($key);

        if ($return === false) {
            return false;
        }

        @list($type,$data) = @preg_split('/@#!/',$return,2);
        if (isset($data)) {
            settype($data,$type);
        } else {
            if (is_string($return) && substr($return,0,15) == '@____join____@:') {
                @list($start,$parts) = @explode(':', $return, 2);
                $return = '';
                for($i=0;$i<(int)$parts;$i++) {
                    $return.=$this->MongoDb->get($key.'_'.$i);
                }
            }
            $data = &$return;
        }
        return $data;
    }

    public function save($data, $id = null, $group = null)
    {
        if (is_numeric($data) || is_bool($data)) {
            $type=gettype($data);
            $data = $type.'@#!'.$data;
        }

        $key = $this->_generateCacheKey($id, $group);
        $return = $this->MongoDb->set($key,$data, $this->_lifeTime);
        return $return !== false ? true:false;
    }

    public function remove($id, $group = 'default')
    {
        $key = $this->_generateCacheKey($id, $group);
        $return = $this->MongoDb->delete($key);
        return $return;
    }

    public function clean($group = false, $mode = 'ingroup')
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
    public function install(){}
    public function uninstall(){}
}