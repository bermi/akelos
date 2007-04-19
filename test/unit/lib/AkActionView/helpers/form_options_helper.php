<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'form_options_helper.php');


class FormOptionsHelperTests extends HelpersUnitTester
{
    function test_for_formOptionsHelper()
    {
        $FormOptionsHelper = & new FormOptionsHelper();

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->options_for_select(array('Admin','Moderator','Visitor','Demo'), 'Visitor'))),
        trim(str_replace("\n",'',
        '<option value="Admin">Admin</option>'.
        '<option value="Moderator">Moderator</option>'.
        '<option selected="selected" value="Visitor">Visitor</option>'.
        '<option value="Demo">Demo</option>')));

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->options_for_select(array('Admin'=>'1','Moderator'=>'2','Visitor'=>'3','Demo'=>'4'), '3'))),
        trim(str_replace("\n",'',
        '<option value="1">Admin</option>'.
        '<option value="2">Moderator</option>'.
        '<option selected="selected" value="3">Visitor</option>'.
        '<option value="4">Demo</option>')));


        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', 'Bermi', array('name'));
        $Person->setReturnValue('get', '3', array('role'));
        $Person->setReturnValue('get', '3', array('id'));
        $Controller =& new MockAkActionController($this);


        $AkFormHelperOptionsInstanceTag =& new AkFormHelperOptionsInstanceTag('person','role',&$FormOptionsHelper,null,&$Person);

        $this->assertEqual($AkFormHelperOptionsInstanceTag->getValue(), '3');

        $this->assertEqual(trim(str_replace("\n",'',
        $AkFormHelperOptionsInstanceTag->to_select_tag(array('a','b')))),
        '<select id="person_role" name="person[role]">'.
        '<option value="a">a</option>'.
        '<option value="b">b</option>'.
        '</select>');

        $Person1 =& new MockAkActiveRecord($this);
        $Person1->setReturnValue('get', 'Admin', array('role'));
        $Person1->setReturnValue('get', '1', array('id'));
        $Person2 =& new MockAkActiveRecord($this);
        $Person2->setReturnValue('get', 'Demo', array('role'));
        $Person2->setReturnValue('get', '2', array('id'));
        $Person3 =& new MockAkActiveRecord($this);
        $Person3->setReturnValue('get', 'Visitor', array('role'));
        $Person3->setReturnValue('get', '3', array('id'));

        $collection = array(&$Person1,&$Person2,&$Person3);
        $this->assertEqual(trim(str_replace("\n",'',
        $AkFormHelperOptionsInstanceTag->to_collection_select_tag($collection, 'id', 'role',array('prompt'=>true)))),
        '<select id="person_role" name="person[role]"><option value="1">Admin</option>'.
        '<option value="2">Demo</option>'.
        '<option selected="selected" value="3">Visitor</option>'.
        '</select>');

        ob_start();
        $this->assertErrorPattern('/private methods/',$AkFormHelperOptionsInstanceTag->to_collection_select_tag($collection, '_id', 'role'));
        ob_end_clean();


        $Person =& new MockAkActiveRecord($this);
        $AkFormHelperOptionsInstanceTag =& new AkFormHelperOptionsInstanceTag('person','role',&$FormOptionsHelper,null,&$Person);

        $this->assertEqual(
            $AkFormHelperOptionsInstanceTag->to_select_tag(array('a','b'),array(), array('prompt' => true)),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_options_helper_to_select_prompt.txt')
        );

        $this->assertEqual(
            $AkFormHelperOptionsInstanceTag->to_select_tag(array('a','b'), array(), array('include_blank' => true)),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_options_helper_to_select_include_blank.txt')
        );

        $this->assertEqual(
            $AkFormHelperOptionsInstanceTag->to_select_tag(array('a','b'), array(), array('include_blank' => true, 'prompt' => true)),
            file_get_contents(AK_TEST_HELPERS_DIR.DS.'form_options_helper_to_select_include_blank_prompt.txt')
        );

        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', 'USA', array('country'));
        $AkFormHelperOptionsInstanceTag =& new AkFormHelperOptionsInstanceTag('person','country',&$FormOptionsHelper,null,&$Person);

        $countrie_select = $AkFormHelperOptionsInstanceTag->to_country_select_tag(array('Spain'=>'ESP','United States'=>'USA'));

        $this->assertTrue(strstr($countrie_select,'<select id="person_country" name="person[country]">'));
        $this->assertTrue(strstr($countrie_select,'<option value="ESP">Spain</option>'));
        $this->assertTrue(strstr($countrie_select,'<option selected="selected" value="USA">United States</option>'));
        $this->assertTrue(strstr($countrie_select,'<option value="">-------------</option>'));
        unset($countrie_select);


        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', array('Madrid','London'), array('timezone'));
        $AkFormHelperOptionsInstanceTag =& new AkFormHelperOptionsInstanceTag('person','timezone',&$FormOptionsHelper,null,&$Person);

        $timezone_select_html = $AkFormHelperOptionsInstanceTag->to_time_zone_select_tag(array('(GMT +1:00) España'=>'Madrid'),array(),array('multiple'=>'multiple'));

        $this->assertTrue(strstr($timezone_select_html,'<select id="person_timezone" multiple="multiple" name="person[timezone][]">'));
        $this->assertTrue(strstr($timezone_select_html,'<option selected="selected" value="Madrid">(GMT +1:00) España</option>'));
        $this->assertTrue(strstr($timezone_select_html,'<option value="">-------------</option>'));
        $this->assertTrue(strstr($timezone_select_html,'<option selected="selected" value="London">(GMT) London</option>'));
        unset($timezone_select_html);


        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', 'Moderator', array('role'));
        $FormOptionsHelper = & new FormOptionsHelper();
        $FormOptionsHelper->addObject('person',$Person);

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->select('person',  'role', array('Admin','Moderator','Visitor'), array('onclick'=>'alert(\'Hola\')'), array('multiple'=>'multiple')))),
        '<select id="person_role" multiple="multiple" name="person[role][]"><option onclick="alert(\'Hola\')" value="Admin">Admin</option>'.
        '<option onclick="alert(\'Hola\')" selected="selected" value="Moderator">Moderator</option>'.
        '<option onclick="alert(\'Hola\')" value="Visitor">Visitor</option>'.
        '</select>');


        $Category_1 =& new MockAkActiveRecord($this);
        $Category_1->setReturnValue('get', 'Tech', array('description'));
        $Category_1->setReturnValue('get', '23', array('id'));
        $Category_2 =& new MockAkActiveRecord($this);
        $Category_2->setReturnValue('get', 'News', array('description'));
        $Category_2->setReturnValue('get', '3', array('id'));
        $Category_3 =& new MockAkActiveRecord($this);
        $Category_3->setReturnValue('get', 'Education', array('description'));
        $Category_3->setReturnValue('get', '6', array('id'));

        $Person->setReturnValue('get', '6', array('category'));

        $Person->categories = array(&$Category_1,&$Category_2,&$Category_3);

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->collection_select('person', 'category', $Person->categories, 'id', 'description'))),
        '<select id="person_category" name="person[category]"><option value="23">Tech</option>'.
        '<option value="3">News</option>'.
        '<option selected="selected" value="6">Education</option>'.
        '</select>');

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->collection_select('person', 'category', $Person->categories, 'id', 'description',array('onclick'=>'alert(\'Coleccion\')'), array('multiple'=>'multiple')))),
        '<select id="person_category" multiple="multiple" name="person[category][]"><option onclick="alert(\'Coleccion\')" value="23">Tech</option>'.
        '<option onclick="alert(\'Coleccion\')" value="3">News</option>'.
        '<option onclick="alert(\'Coleccion\')" selected="selected" value="6">Education</option>'.
        '</select>');

        $Person->setReturnValue('get', array('ESP','FRA'), array('country'));

        $this->assertTrue(strstr(trim(str_replace("\n",'',
        $FormOptionsHelper->country_select('person', 'country',
        array('Spain'=>'ESP','Ireland'=>'IRL'),
        array('onclick'=>'alert(this.value)'),
        array('onblur'=>'alert(this.value)','multiple'=>'multiple')))),
        '<select id="person_country" multiple="multiple" name="person[country][]" onblur="alert(this.value)">'.
        '<option onclick="alert(this.value)" selected="selected" value="ESP">Spain</option>'.
        '<option onclick="alert(this.value)" value="IRL">Ireland</option>'.
        '<option value="">-------------</option>'));

        $this->assertTrue(strstr(trim(str_replace("\n",'',
        $FormOptionsHelper->country_select('person', 'country',
        array('Spain'=>'ESP','Ireland'=>'IRL'),
        array('onclick'=>'alert(this.value)'),
        array('onblur'=>'alert(this.value)','multiple'=>'multiple')))),
        '<option onclick="alert(this.value)" selected="selected" value="FRA">France</option>'));


        $this->assertTrue(strstr(trim(str_replace("\n",'',
        $FormOptionsHelper->time_zone_select(
        'person',
        'timezone',
        array('(GMT +01:00) Madrid'=>'Madrid'),
        array('prompt'=>true),
        array('style'=>'border:5px solid red;')))),
        '<select id="person_timezone" name="person[timezone]" style="border:5px solid red;">'.
        '<option value="">Please select</option>'.
        '<option value="Madrid">(GMT +01:00) Madrid</option>'.
        '<option value="">-------------</option>'
        ));

        $Person->setReturnValue('get', 'Budapest', array('timezone'));
        $this->assertTrue(strstr(trim(str_replace("\n",'',
        $FormOptionsHelper->time_zone_select(
        'person',
        'timezone',
        array('(GMT +01:00) Madrid'=>'Madrid'),
        array('prompt'=>true),
        array('style'=>'border:5px solid red;')))),
        '<option selected="selected" value="Budapest">(GMT+01:00) Budapest</option>'
        ));




        $this->assertEqual(
        $FormOptionsHelper->options_for_select(array('VISA', 'MasterCard'), 'MasterCard'),
        '<option value="VISA">VISA</option>'."\n".
        '<option selected="selected" value="MasterCard">MasterCard</option>'."\n");

        $this->assertEqual(
        $FormOptionsHelper->options_for_select(array('Dollar'=>'$', 'Kroner'=>'DKK')),
        '<option value="$">Dollar</option>'."\n".
        '<option value="DKK">Kroner</option>'."\n");

        $this->assertEqual(
        $FormOptionsHelper->options_for_select(array('Basic'=>'$20','Plus'=>'$40'), '$40'),
        '<option value="$20">Basic</option>'."\n".
        '<option selected="selected" value="$40">Plus</option>'."\n");

        $this->assertEqual(
        $FormOptionsHelper->options_for_select(array('VISA','MasterCard','Discover'), array('VISA','Discover')),
        '<option selected="selected" value="VISA">VISA</option>'."\n".
        '<option value="MasterCard">MasterCard</option>'."\n".
        '<option selected="selected" value="Discover">Discover</option>'."\n");



        $Project =& new MockAkActiveRecord($this);

        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', '100', array('id'));
        $Person->setReturnValue('get', 'Bermi', array('name'));

        $Person2 =& new MockAkActiveRecord($this);
        $Person2->setReturnValue('get', '200', array('id'));
        $Person2->setReturnValue('get', 'Hilario', array('name'));

        $Person3 =& new MockAkActiveRecord($this);
        $Person3->setReturnValue('get', '250', array('id'));
        $Person3->setReturnValue('get', 'Salavert', array('name'));

        $Project->People = array(&$Person, &$Person2, &$Person3);

        $FormOptionsHelper = & new FormOptionsHelper();

        $this->assertEqual(
        $FormOptionsHelper->options_from_collection_for_select($Project->People,'id','name'),
        '<option value="100">Bermi</option>'."\n".
        '<option value="200">Hilario</option>'."\n".
        '<option value="250">Salavert</option>'."\n");

        $this->assertEqual(
        $FormOptionsHelper->options_from_collection_for_select($Project->People,'id','name',array(100,200)),
        '<option selected="selected" value="100">Bermi</option>'."\n".
        '<option selected="selected" value="200">Hilario</option>'."\n".
        '<option value="250">Salavert</option>'."\n");


        $AkTestContinentsGroup = array(
        new AkTestContinent(
        'Africa', array(new AkTestCountry('EGP','Egipt'), new AkTestCountry('RWD','Rwanda'))
        ),
        new AkTestContinent(
        'Asia', array(new AkTestCountry('ZHN','China'), new AkTestCountry('IND','India'), new AkTestCountry('JPN','Japan'))
        ),
        );

        $this->assertEqual(str_replace("\n",'',
        $FormOptionsHelper->option_groups_from_collection_for_select($AkTestContinentsGroup, 'getCountriesCollection', 'getContinentName', 'getCountryId', 'getCountryName', 'JPN')),
        '<optgroup label="Africa">'.
        '<option value="EGP">Egipt</option>'.
        '<option value="RWD">Rwanda</option>'.
        '</optgroup>'.

        '<optgroup label="Asia">'.
        '<option value="ZHN">China</option>'.
        '<option value="IND">India</option>'.
        '<option selected="selected" value="JPN">Japan</option>'.
        '</optgroup>');

        /**
         * @todo add tests for AkFormOptionsHelperBuilder
         */
    }

    function test_numerical_indexes_for_select()
    {
        $Person =& new MockAkActiveRecord($this);
        $Person->setReturnValue('get', '2', array('role'));
        $FormOptionsHelper = & new FormOptionsHelper();
        $FormOptionsHelper->addObject('person',$Person);

        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->select('person',  'role', array('Admin'=>1,'Moderator'=>2,'Visitor'=>3)))),
        '<select id="person_role" name="person[role]"><option value="1">Admin</option>'.
        '<option selected="selected" value="2">Moderator</option>'.
        '<option value="3">Visitor</option>'.
        '</select>');
        
        $this->assertEqual(trim(str_replace("\n",'',
        $FormOptionsHelper->select('person',  'role', array('Admin'=>0,'Moderator'=>1,'Visitor'=>2)))),
        '<select id="person_role" name="person[role]"><option value="0">Admin</option>'.
        '<option value="1">Moderator</option>'.
        '<option selected="selected" value="2">Visitor</option>'.
        '</select>');
    }

}

Ak::test('FormOptionsHelperTests', true);

?>