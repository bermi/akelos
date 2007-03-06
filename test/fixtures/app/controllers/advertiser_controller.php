<?php

class AdvertiserController extends AkActionController 
{
    function partial_in_template()
    {
        $this->account =& new stdClass();
        $this->account->name = 'Big Corp';
    }
    
    function buy()
    {
        $this->buyer =& new stdClass();
        $this->buyer->name = 'Akelos Media';
        
        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
                $ad2->name = 'seccond_ad';
        $this->advertisements = array($ad1,$ad2);
        
    }
    
    function all()
    {
        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
        $ad2->name = 'seccond_ad';
        $this->advertisements = array($ad1,$ad2);
        
    }
    
    function show_all()
    {
        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
        $ad2->name = 'seccond_ad';
        $advertisements = array($ad1,$ad2);
        
        $this->renderText($this->render(array('partial'=>'ad','collection'=>$advertisements)));
        
    }
}

?>