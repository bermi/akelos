<?php
/**
 * Converts a Xml-representation of an Active Record to an array that can be used as params-array.
 * Used internally to convert POST and PUT message bodys.
 * 
 *      <person>
 *          <name>Steve</name>
 *          <details>
 *              <age>21</age>
 *          </details>
 *      </person>
 * 
 *   => array('person'=>array('name'=>'Steve','details'=>array('age'=>'21')))
 *   => $params['person']['name']  //= Steve
 * 
 * 
 *      <person>
 *          <name>Steve</name>
 *          <photos>
 *              <photo>
 *                  <title>One</title>
 *              </photo>
 *              <photo>
 *                  <title>Two</title>
 *              </photo>
 *          </photos>
 *          <age>21</age>
 *      </person>
 *
 *   => array('person'=>array('name'=>'Steve','photos'=>array(0=>array('title'=>'One'),1=>array('title'=>'Two'))))
 *   => $params['person']['photos'][1]['title'] //= Two 
 *
 */
class AkXmlToParamsArray
{
    public function convert() {
        return self::convertToArray($this->source);    
    }
    
    static public function convertToArray($xml_or_string) {
        $xml = is_string($xml_or_string) ? new SimpleXMLElement($xml_or_string) : $xml_or_string;
        return self::parseXml($xml);
    }
    
    static private function parseXml(SimpleXMLElement $xml) {
        $properties = array();
        $properties[$xml->getName()] = self::addChildren($xml);
        return $properties;
    }
    
    static private function addChildren(SimpleXMLElement $xml) {
        $properties = array();
    
        foreach ($xml as $child){
            if (count($child->children())>0){
                $children = self::addChildren($child);
                if (AkInflector::isCollectionOf($child->getName(),$xml->getName())){
                    $properties[]= $children;
                }else{
                    $properties[$child->getName()] = $children;
                }
            }else{
                $properties[$child->getName()] = (string)$child;
            }
        }
        return $properties;
    }    
}
