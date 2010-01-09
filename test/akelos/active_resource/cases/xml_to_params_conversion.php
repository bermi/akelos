<?php

require_once(dirname(__FILE__).'/../config.php');

class XmlToParamsConversion_TestCase extends ActiveResourceUnitTest
{
    public function testXmlToArray() {
        $data = '<person><name>Steve</name><age>21</age></person>';
        $expected = array(
            'person'=>array('name'=>'Steve','age'=>'21')
        );
        $this->assertEqual($expected,$this->parseXml($data));
    }

    public function testXmlToArray2() {
        $data = '
        <people>
            <person><name>Steve</name><age>21</age></person>
            <person><name>Mart</name><age>21</age></person>
        </people>';
        
        $expected = array(
            'people'=>array(
                0=>array(
                    'name'=>'Steve',
                    'age'=>'21'),
                1=>array(
                    'name'=>'Mart',
                    'age'=>'21')
            )
        );
        $this->assertEqual($expected,$this->parseXml($data));
    }

    public function testXmlToArray3() {
        $data = '
        <people>
            <person>
                <name>Steve</name>
                <comments>
                    <comment>
                        <title>No1</title>
                    </comment>
                    <comment>
                        <title>No2</title>
                    </comment>
                </comments>
            </person>
            <person>
                <name>Mart</name>
                <comments>
                    <comment>
                        <title>No3</title>
                    </comment>
                    <comment>
                        <title>No4</title>
                    </comment>
                </comments>
            </person>
        </people>';
        
        $expected = array(
            'people'=>array(
                0=>array(
                    'name'=>'Steve',
                    'comments'=>array(
                        0=>array('title'=>'No1'),
                        1=>array('title'=>'No2'),
                    )
                ),
                1=>array(
                    'name'=>'Mart',
                    'comments'=>array(
                        0=>array('title'=>'No3'),
                        1=>array('title'=>'No4'),
                    )
                ),
            )
        );
        $as_array = $this->parseXml($data);
        $this->assertEqual($expected,$as_array);
        $this->assertEqual('No2',$as_array['people'][0]['comments'][1]['title']);
    }
    
    public function testXmlToArray4() {
        $data ='
        <person>
            <name>Steve</name>
            <details>
                <age>21</age>
            </details>
        </person>
        ';
        
        $expected = array(
            'person'=>array(
                'name'=>'Steve',
                'details'=>array(
                    'age'=>21))
        );
        $as_array = $this->parseXml($data);
        #var_dump($as_array);
        $this->assertEqual($expected,$as_array);
    }
    
    public function testXmlToArray5() {
        $data ='
        <person>
            <name>Steve</name>
            <photos>
                <photo>
                    <title>One</title>
                </photo>
                <photo>
                    <title>Two</title>
                </photo>
            </photos>
            <age>21</age>
        </person>
        ';
        
        $expected = array(
            'person'=>array(
                'name'=>'Steve',
                'photos'=>array(
                    0=>array('title'=>'One'),
                    1=>array('title'=>'Two')),
                'age'=>'21')
            );
        $as_array = $this->parseXml($data);
        $this->assertEqual($expected,$as_array);
        
    }
    
    public function testClassicConverterApi() {
        $xml_string = '<person><name>Steve</name></person>';
        $expected = array('person'=>array('name'=>'Steve'));
        
        $this->assertEqual($expected, Ak::convert('Xml','ParamsArray',$xml_string));
    }

    public function testConverterAcceptsXmlObjectAsInput() {
        $xml_string = '<person><name>Steve</name></person>';
        $xml = new SimpleXMLElement($xml_string);
        $expected = array('person'=>array('name'=>'Steve'));
        
        $this->assertEqual($expected, Ak::convert('Xml','ParamsArray',$xml));
    }
    
    public function parseXml($xml_string) {
        return Ak::convert('xml', 'params_array', $xml_string);
    }
}

ak_test_case('XmlToParamsConversion_TestCase');
