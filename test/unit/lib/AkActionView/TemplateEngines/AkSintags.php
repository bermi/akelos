<?php

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'TemplateEngines'.DS.'AkSintags.php');

define('AK_SINTAGS_AVALABLE_HELPERS', 'a:8:{s:7:"url_for";s:10:"url_helper";s:7:"link_to";s:10:"url_helper";s:7:"mail_to";s:10:"url_helper";s:10:"email_link";s:10:"url_helper";s:9:"translate";s:11:"text_helper";s:20:"number_to_human_size";s:13:"number_helper";s:6:"render";s:10:"controller";s:25:"distance_of_time_in_words";s:11:"date_helper";}');

class Test_of_AkSintags extends  UnitTestCase
{

    function test_sintags()
    {
        $this->_run_helpers_from_file('sintags_test_data.txt');
    }
    function test_sintags_helpers()
    {
        $this->_run_helpers_from_file('sintags_helpers_data.txt');
    }

    function _run_helpers_from_file($file_name, $all_in_one_test = true)
    {
        $multiple_expected_php = $multiple_sintags = '';
        $tests = explode('===================================',file_get_contents(AK_TEST_DIR.DS.'fixtures'.DS.'data'.DS.$file_name));
        foreach ($tests as $test) {
            list($sintags, $php) = explode('-----------------------------------',$test);
            $sintags = trim($sintags);
            $expected_php = trim($php);
            if(empty($sintags)){
                break;
            }else{
                $multiple_sintags .= $sintags;
                $multiple_expected_php .= $expected_php;
            }
            $AkSintags =& new AkSintagsParser();
            $php = $AkSintags->parse($sintags);
            if($php != $expected_php){
                Ak::trace('GENERATED: '.$php);
                Ak::trace('EXPECTED: '.$expected_php);
                Ak::trace('SINTAGS: '.$sintags);
            }

            $this->assertEqual($php, $expected_php);
        }
        if($all_in_one_test){
            $AkSintags =& new AkSintagsParser();
            $php = $AkSintags->parse($multiple_sintags);
            if($php != $multiple_expected_php){
                Ak::trace('GENERATED: '.$php);
                Ak::trace('EXPECTED: '.$expected_php);
                Ak::trace('SINTAGS: '.$sintags);
            }
            $this->assertEqual($php, $multiple_expected_php);
        }
    }
}

Ak::test('Test_of_AkSintags');

?>
