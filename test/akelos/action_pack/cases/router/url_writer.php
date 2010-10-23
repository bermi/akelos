<?php

require_once(dirname(__FILE__).'/../router.php');
require_once(dirname(__FILE__).'/../../lib/url_writer.php');

 class UrlWriter_TestCase extends UrlWriterUnitTest
{
    public function testUseLastRequestToFillController() {
        $this->withRequestTo(array('controller'=>'author'));
        $this->urlFor(array())->isRewrittenTo(array('controller'=>'author'));
    }

    public function testAGivenParameterOverridesTheOldOne() {
        $this->withRequestTo(array('controller'=>'author','action'=>'show'));
        $this->urlFor(array('action'=>'list'))->isRewrittenTo(array('controller'=>'author','action'=>'list'));
        $this->urlFor(array('controller'=>'author'))->isRewrittenTo(array('controller'=>'author'));
    }

    public function testDontFillBeyondAGivenParameter() {
        $this->withRequestTo(array('controller'=>'author','action'=>'show','name'=>'martin'));
        $this->urlFor(array('controller'=>'author','action'=>'list'))->isRewrittenTo(array('controller'=>'author','action'=>'list'));
    }

    public function testAParameterSetToNullWillBeUnset() {
        $this->withRequestTo(array('controller'=>'author','action'=>'show','name'=>'martin'));
        $this->urlFor(array('controller'=>'author', 'action'=>null))->isRewrittenTo(array('controller'=>'author'));
    }

    public function testOverwriteParametersOptionShouldNotStopTheFilling() {
        $this->withRequestTo(array('controller'=>'author','action'=>'show','name'=>'martin'));
        $this->urlFor(array('overwrite_params'=>array('action'=>'edit')))
        ->isRewrittenTo(array('controller'=>'author','name'=>'martin', 'action'=>'edit'));
    }

    public function testSplitGivenControllerIntoModuleAndControllerPart() {
        $this->withRequestTo(array('controller'=>'author','action'=>'show'));
        $this->urlFor(array('controller'=>'admin/user'))
        ->isRewrittenTo(array('controller'=>'user', 'module'=>'admin'));
    }

    public function testFiltersSetOptions() {
        $keywords = array('anchor', 'only_path', 'host', 'protocol', 'trailing_slash', 'skip_relative_url_root');

        $this->withRequestTo(array('controller'=>'author'));
        $this->urlFor(array_flip($keywords))
        ->isRewrittenTo(array('controller'=>'author'));
    }

    public function testPassThroughLangSettingByDefault() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $this->urlFor(array('action'=>'list'))
        ->isRewrittenTo(array('lang'=>'en','controller'=>'author','action'=>'list'));
    }

    public function testPassThroughLangSettingIfOptionIsFalse() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $this->urlFor(array('action'=>'list','skip_url_locale'=>false))
        ->isRewrittenTo(array('lang'=>'en','controller'=>'author','action'=>'list'));
    }

    public function testFilterLangSettingIfOptionIsTrue() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $this->urlFor(array('action'=>'list','skip_url_locale'=>true))
        ->isRewrittenTo(array('controller'=>'author','action'=>'list'));
    }

    public function testOnlyUseSpecifiedParametersFromOldRequest() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $this->urlFor(array('skip_old_parameters_except'=>array('controller')))
        ->isRewrittenTo(array('controller'=>'author'));
    }

    public function testFilterAllOldParameters() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $this->urlFor(array('skip_old_parameters_except'=>array()))
        ->isRewrittenTo(array());
    }

    public function testShouldAllowImplicitBooleanParameters() {
        $this->withRequestTo(array('controller'=>'users','action'=>'destroy', 'module'=>'admin'));
        $this->urlFor(array('controller'=>'account','action'=>'sign_in', 'module'=>false))
        ->isRewrittenTo(array('controller'=>'account','action'=>'sign_in', 'module'=>false));
    }

    public function testUseNamedRouteIfSpecified() {
        $this->withRequestTo(array('lang'=>'en','controller'=>'author','action'=>'show'));
        $asked_url_for_parameters = array('lang'=>'es','use_named_route'=>'default');
        $rewritten_parameters     = array('lang'=>'es');

        $Router = $this->partialMock('AkRouter',array('urlize'),array('urlize' => new AkUrl('')));
        $Router->expectOnce('urlize', array($rewritten_parameters,'default'));
        $UrlWriter = new AkUrlWriter($this->Request,$Router);
               
        $UrlWriter->urlFor($asked_url_for_parameters);
    }   
}

ak_test_case('UrlWriter_TestCase');