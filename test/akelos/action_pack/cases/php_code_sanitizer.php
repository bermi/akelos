<?php

require_once(dirname(__FILE__).'/../config.php');

class PhpCodeSanitizer_TestCase extends ActionPackUnitTest
{
    public function test_should_avoid_private_variables() {
        $this->assertInvalidCode('<?php $_private; ? >');
        $this->assertInvalidCode('<?=$_private?>');
    }

    public function test_should_avoid_private_array_keys() {
        $this->assertInvalidCode('<?php echo $var[\'_private\']; ?>');
        $this->assertInvalidCode('<?php $var["_private"]?>');
        $this->assertInvalidCode('<?php $var[public][_private]?>');
        $this->assertInvalidCode('<?php $var[{\'_private\'}]?>');
    }

    public function test_should_avoid_private_object_attributes() {
        $this->assertInvalidCode('<?php echo $var->_private; ?>');
        $this->assertInvalidCode('<?php $var->_private?>');
        $this->assertInvalidCode('<?php $var->public->_private]?>');
        $this->assertInvalidCode('<?php $var->{\'_private\'}?>');
        $this->assertInvalidCode('<?php $var->$variable_attr?>');
    }

    public function test_should_allow_ternary_operators() {
        $this->assertValidCode('<?php empty($Post->comments) ? null : $comment_loop_counter = 0; ?>');
    }

    public function test_should_allow_conditional_assingments() {
        $this->assertValidCode('<?php if (isset($Preference->value)){ $value = $Preference->value; } ?>');
    }

    /**/
    public function assertValidCode($code) {
        $this->CodeSanitizer = new AkPhpCodeSanitizer();
        $this->CodeSanitizer->setOptions(array('code'=>$code));
        $this->assertTrue($this->CodeSanitizer->isCodeSecure(), 'Secure code not accepted: '.$code);
    }

    public function assertInvalidCode($code) {
        $this->CodeSanitizer = new AkPhpCodeSanitizer();
        $this->CodeSanitizer->setOptions(array('code'=>$code));

        $this->expectError(new PatternExpectation('/You can\'t use/'));
        $this->assertFalse($this->CodeSanitizer->isCodeSecure(), 'Unsecure code not detected: '.$code);
    }
}

ak_test_case('PhpCodeSanitizer_TestCase');