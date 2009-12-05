<?php


class AkActiveRecordUtilities extends AkActiveRecordExtenssion
{


    /**
     * Reads Xml in the following format:
     *
     *
     * <?xml version="1.0" encoding="UTF-8"?>
     * <person>
     *    <id>1</id>
     *    <first-name>Hansi</first-name>
     *    <last-name>Müller</last-name>
     *    <email>hans@mueller.com</email>
     *    <created-at type="datetime">2008-01-01 13:01:23</created-at>
     * </person>
     *
     * and returns an ActiveRecord Object
     *
     * @param string $xml
     * @return AkActiveRecord
     */
    public function fromXml($xml)
    {
        $array = Ak::convert('xml','array', $xml);
        $array = $this->_fromXmlCleanup($array);
        return $this->_fromArray($array);
    }


    /**
     * Generate a json representation of the model record.
     *
     * parameters:
     *
     * @param array $options
     *
     *              option parameters:
     *             array(
     *              'collection' => array($Person1,$Person), // array of ActiveRecords
     *              'include' => array('association1','association2'), // include the associations when exporting
     *              'exclude' => array('id','name'), // exclude the attribtues
     *              'only' => array('email','last_name') // only export these attributes
     *              )
     * @return string in Json Format
     */
    public function toJson($options = array())
    {
        if (is_array($options) && isset($options[0]) && ($options[0] instanceof AkActiveRecord)) {
            $options = array('collection'=>$options);
        }
        if (isset($options['collection']) && is_array($options['collection']) && $options['collection'][0]->_modelName == $this->_ActiveRecord->_modelName) {
            $json = '';

            $collection = $options['collection'];
            unset($options['collection']);
            $jsonVals = array();
            foreach ($collection as $element) {
                $jsonVals[]= $element->toJson($options);
            }
            $json = '['.implode(',',$jsonVals).']';
            return $json;
        }
        /**
         * see if we need to include associations
         */
        $associatedIds = array();
        if (isset($options['include']) && !empty($options['include'])) {
            $options['include'] = is_array($options['include'])?$options['include']:preg_split('/,\s*/',$options['include']);
            foreach ($this->_ActiveRecord->_associations as $key => $obj) {
                if (in_array($key,$options['include'])) {
                    $associatedIds[$obj->getAssociationId() . '_id'] = array('name'=>$key,'type'=>$obj->getType());
                }
            }
        }
        if (isset($options['only'])) {
            $options['only'] = is_array($options['only'])?$options['only']:preg_split('/,\s*/',$options['only']);
        }
        if (isset($options['except'])) {
            $options['except'] = is_array($options['except'])?$options['except']:preg_split('/,\s*/',$options['except']);
        }
        foreach ($this->_ActiveRecord->_columns as $key => $def) {

            if (isset($options['except']) && in_array($key, $options['except'])) {
                continue;
            } else if (isset($options['only']) && !in_array($key, $options['only'])) {
                continue;
            } else {
                $val = $this->_ActiveRecord->$key;
                $type = $this->_ActiveRecord->getColumnType($key);
                if (($type == 'serial' || $type=='integer') && $val!==null) $val = intval($val);
                if ($type == 'float' && $val!==null) $val = floatval($val);
                if ($type == 'boolean') $val = $val?1:0;
                $data[$key] = $val;
            }
        }
        if (isset($options['include'])) {
            foreach($this->_ActiveRecord->_associationIds as $key=>$val) {
                if ((in_array($key,$options['include']) || in_array($val,$options['include']))) {
                    $this->_ActiveRecord->$key->load();
                    $associationElement = $key;
                    $associationElement = $this->_convertColumnToXmlElement($associationElement);
                    if (is_array($this->_ActiveRecord->$key)) {
                        $data[$associationElement] = array();
                        foreach ($this->_ActiveRecord->$key as $el) {
                            if ($el instanceof AkActiveRecord) {
                                $attributes = $el->getAttributes();
                                foreach($attributes as $ak=>$av) {
                                    $type = $el->getColumnType($ak);
                                    if (($type == 'serial' || $type=='integer') && $av!==null) $av = intval($av);
                                    if ($type == 'float' && $av!==null) $av = floatval($av);
                                    if ($type == 'boolean') $av = $av?1:0;
                                    $attributes[$ak]=$av;
                                }
                                $data[$associationElement][] = $attributes;
                            }
                        }
                    } else {
                        $el = $this->_ActiveRecord->$key->load();
                        if ($el instanceof AkActiveRecord) {
                            $attributes = $el->getAttributes();
                            foreach($attributes as $ak=>$av) {
                                $type = $el->getColumnType($ak);
                                if (($type == 'serial' || $type=='integer') && $av!==null) $av = intval($av);
                                if ($type == 'float' && $av!==null) $av = floatval($av);
                                if ($type == 'boolean') $av = $av?1:0;
                                $attributes[$ak]=$av;
                            }
                            $data[$associationElement] = $attributes;
                        }
                    }
                }
            }
        }
        return Ak::toJson($data);
    }



    /**
     * Reads Json string in the following format:
     *
     * {"id":1,"first_name":"Hansi","last_name":"M\u00fcller",
     *  "email":"hans@mueller.com","created_at":"2008-01-01 13:01:23"}
     *
     * and returns an ActiveRecord Object
     *
     * @param string $json
     * @return AkActiveRecord
     */
    public function fromJson($json)
    {
        $json = Ak::fromJson($json);
        $array = Ak::convert('Object','Array',$json);
        return $this->_fromArray($array);
    }

    /**
     * Generate a xml representation of the model record.
     *
     * Example result:
     *
     * <?xml version="1.0" encoding="UTF-8"?>
     * <person>
     *    <id>1</id>
     *    <first-name>Hansi</first-name>
     *    <last-name>Müller</last-name>
     *    <email>hans@mueller.com</email>
     *    <created-at type="datetime">2008-01-01 13:01:23</created-at>
     * </person>
     *
     * parameters:
     *
     * @param array $options
     *
     *              option parameters:
     *             array(
     *              'collection' => array($Person1,$Person), // array of ActiveRecords
     *              'include' => array('association1','association2'), // include the associations when exporting
     *              'exclude' => array('id','name'), // exclude the attribtues
     *              'only' => array('email','last_name') // only export these attributes
     *              )
     * @return string in Xml Format
     */
    public function toXml($options = array())
    {
        if (is_array($options) && isset($options[0]) && ($options[0] instanceof AkActiveRecord)) {
            $options = array('collection'=>$options);
        }
        if (isset($options['collection']) && is_array($options['collection']) && $options['collection'][0]->_modelName == $this->_ActiveRecord->_modelName) {
            $root = strtolower(AkInflector::pluralize($this->_ActiveRecord->_modelName));
            $root = $this->_convertColumnToXmlElement($root);
            $xml = '';
            if (!(isset($options['skip_instruct']) && $options['skip_instruct'] == true)) {
                $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
            }
            $xml .= '<' . $root . '>';
            $collection = $options['collection'];
            unset($options['collection']);
            $options['skip_instruct'] = true;
            foreach ($collection as $element) {
                $xml .= $element->toXml($options);
            }
            $xml .= '</' . $root .'>';
            return $xml;
        }
        /**
         * see if we need to include associations
         */
        $associatedIds = array();
        if (isset($options['include']) && !empty($options['include'])) {
            $options['include'] = is_array($options['include'])?$options['include']:preg_split('/,\s*/',$options['include']);
            foreach ($this->_ActiveRecord->_associations as $key => $obj) {
                if (in_array($key,$options['include'])) {
                    if ($obj->getType()!='hasAndBelongsToMany') {
                        $associatedIds[$obj->getAssociationId() . '_id'] = array('name'=>$key,'type'=>$obj->getType());
                    } else {
                        $associatedIds[$key] = array('name'=>$key,'type'=>$obj->getType());
                    }
                }
            }
        }
        if (isset($options['only'])) {
            $options['only'] = is_array($options['only'])?$options['only']:preg_split('/,\s*/',$options['only']);
        }
        if (isset($options['except'])) {
            $options['except'] = is_array($options['except'])?$options['except']:preg_split('/,\s*/',$options['except']);
        }
        $xml = '';
        if (!(isset($options['skip_instruct']) && $options['skip_instruct'] == true)) {
            $xml .= '<?xml version="1.0" encoding="UTF-8"?>';
        }
        $root = $this->_convertColumnToXmlElement(strtolower($this->_ActiveRecord->_modelName));

        $xml .= '<' . $root . '>';
        $xml .= "\n";
        foreach ($this->_ActiveRecord->_columns as $key => $def) {

            if (isset($options['except']) && in_array($key, $options['except'])) {
                continue;
            } else if (isset($options['only']) && !in_array($key, $options['only'])) {
                continue;
            } else {
                $columnType = $def['type'];
                $elementName = $this->_convertColumnToXmlElement($key);
                $xml .= '<' . $elementName;
                $val = $this->_ActiveRecord->$key;
                if (!in_array($columnType,array('string','text','serial'))) {
                    $xml .= ' type="' . $columnType . '"';
                    if ($columnType=='boolean') $val = $val?1:0;
                }
                $xml .= '>' . Ak::utf8($val) . '</' . $elementName . '>';
                $xml .= "\n";
            }
        }
        if (isset($options['include'])) {
            foreach($this->_ActiveRecord->_associationIds as $key=>$val) {
                if ((in_array($key,$options['include']) || in_array($val,$options['include']))) {
                    if (is_array($this->_ActiveRecord->$key)) {

                        $associationElement = $key;
                        $associationElement = AkInflector::pluralize($associationElement);
                        $associationElement = $this->_convertColumnToXmlElement($associationElement);
                        $xml .= '<'.$associationElement.'>';
                        foreach ($this->_ActiveRecord->$key as $el) {
                            if ($el instanceof AkActiveRecord) {
                                $xml .= $el->toXml(array('skip_instruct'=>true));
                            }
                        }
                        $xml .= '</' . $associationElement .'>';
                    } else {
                        $el = $this->_ActiveRecord->$key->load();
                        if ($el instanceof AkActiveRecord) {
                            $xml.=$el->toXml(array('skip_instruct'=>true));
                        }
                    }
                }
            }
        }
        $xml .= '</' . $root . '>';
        return $xml;
    }
    /**
     * converts to yaml-strings
     *
     * examples:
     * User::toYaml($users->find('all'));
     * $Bermi->toYaml();
     *
     * @param array of ActiveRecords[optional] $data
     */
    public function toYaml($data = null)
    {
        return Ak::convert('active_record', 'yaml', empty($data) ? $this->_ActiveRecord : $data);
    }

    private function _convertColumnToXmlElement($col)
    {
        return str_replace('_','-',$col);
    }

    private function _convertColumnFromXmlElement($col)
    {
        return str_replace('-','_',$col);
    }

    private function _parseXmlAttributes($attributes)
    {
        $new = array();
        foreach($attributes as $key=>$value)
        {
            $new[$this->_convertColumnFromXmlElement($key)] = $value;
        }
        return $new;
    }

    private function &_generateModelFromArray($modelName,$attributes)
    {
        if (isset($attributes[0]) && is_array($attributes[0])) {
            $attributes = $attributes[0];
        }
        $record = new $modelName('attributes', $this->_parseXmlAttributes($attributes));
        $record->_newRecord = !empty($attributes['id']);

        $associatedIds = array();
        foreach ($record->getAssociatedIds() as $key) {
            if (isset($attributes[$key]) && is_array($attributes[$key])) {
                $class = $record->$key->_AssociationHandler->getOption($key,'class_name');
                $related = $this->_generateModelFromArray($class,$attributes[$key]);
                $record->$key->build($related->getAttributes(),false);
                $related = $record->$key->load();
                $record->$key = $related;
            }
        }
        return $record;
    }

    private function _fromArray($array)
    {
        $data  = $array;
        $modelName = $this->_ActiveRecord->getModelName();
        $values = array();
        if (!isset($data[0])) {
            $data = array($data);
        }
        foreach ($data as $key => $value) {
            if (is_array($value)){
                $values[] = $this->_generateModelFromArray($modelName, $value);
            }
        }
        return count($values)==1?$values[0]:$values;
    }

    private function _fromXmlCleanup($array)
    {
        $result = array();
        $key = key($array);
        while(is_string($key) && is_array($array[$key]) && count($array[$key])==1) {
            $array = $array[$key][0];
            $key = key($array);
        }
        if (is_string($key) && is_array($array[$key])) {
            $array = $array[$key];
        }
        return $array;
    }

}

