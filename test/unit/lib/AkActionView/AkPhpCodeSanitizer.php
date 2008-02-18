<?php

require_once(dirname(__FILE__).'/../../../fixtures/config/config.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkPhpCodeSanitizer.php');

class AkPhpCodeSanitizer_TestCase extends  AkUnitTest
{

    function test_should_avoid_private_variables()
    {
        $this->assertInvalidCode('<?php $_private; ?>');
        $this->assertInvalidCode('<?=$_private?>');
    }

    function test_should_avoid_private_array_keys()
    {
        $this->assertInvalidCode('<?php echo $var[\'_private\']; ?>');
        $this->assertInvalidCode('<?php $var["_private"]?>');
        $this->assertInvalidCode('<?php $var[public][_private]?>');
        $this->assertInvalidCode('<?php $var[{\'_private\'}]?>');
    }
    
    function test_should_avoid_private_object_attributes()
    {
        $this->assertInvalidCode('<?php echo $var->_private; ?>');
        $this->assertInvalidCode('<?php $var->_private?>');
        $this->assertInvalidCode('<?php $var->public->_private]?>');
        $this->assertInvalidCode('<?php $var->{\'_private\'}?>');
        $this->assertInvalidCode('<?php $var->$variable_attr?>');
    }
    
    
    /**/
    function assertValidCode($code)
    {
        $this->CodeSanitizer =& new AkPhpCodeSanitizer();
        $this->CodeSanitizer->setOptions(array('code'=>$code));
        $this->assertTrue($this->CodeSanitizer->isCodeSecure(), 'Secure code not accepted: '.$code);
    }
    
    function assertInvalidCode($code)
    {
        $this->CodeSanitizer =& new AkPhpCodeSanitizer();
        $this->CodeSanitizer->setOptions(array('code'=>$code));
        $this->assertFalse($this->CodeSanitizer->isCodeSecure(), 'Unsecure code not detected: '.$code);
        $this->assertErrorPattern('/You can\'t use/');
    }

}

?>
