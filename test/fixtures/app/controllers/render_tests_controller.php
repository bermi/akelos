<?php

class RenderTestsController extends AkActionController 
{
    function index()
    {
        $this->renderText('RenderTestsController is available on tests');
    }
    
    function hello_partial()
    {
        $this->renderText($this->renderPartial('hello_world'));
    }
    
    function hello_partial_with_options()
    {
        $this->renderText($this->render(array('partial'=>'hello_world','locals'=>array('cruel'=>'Cruel'))));
    }
    
    function shared_partial()
    {
        $advertisement = new stdClass();
        $advertisement->name = 'first_ad';
        $this->renderText($this->render(array('partial'=>'advertiser/ad', 'locals' => array('ad' => $advertisement ))));
    }
    function ad()
    {
        $this->advertisement = new stdClass();
        $this->advertisement->name = 'first_ad';
    }
}


?>