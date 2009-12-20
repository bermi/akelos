<?php

class AdvertiserController extends AkActionController
{
    function partial_in_template() {
        $this->account = new stdClass();
        $this->account->name = 'Big Corp';
    }

    function buy() {
        $this->buyer = new stdClass();
        $this->buyer->name = 'Bermi Labs';

        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
        $ad2->name = 'seccond_ad';
        $this->advertisements = array($ad1,$ad2);

    }

    function all() {
        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
        $ad2->name = 'seccond_ad';
        $this->advertisements = array($ad1, $ad2);
    }

    function show_all() {
        $ad1 = new stdClass();
        $ad1->name = 'first_ad';
        $ad2 = new stdClass();
        $ad2->name = 'seccond_ad';
        $advertisements = array($ad1,$ad2);
        $this->renderText($this->render(array('partial'=>'ad','collection'=>$advertisements)));

    }
    
    function empty_collection() {
        $advertisements = array();
        $this->renderText($this->render(array('partial'=>'ad','collection'=>$advertisements)));

    }
    function use_object_and_not_controllers_item() {
        $this->ad = new stdClass();
        $this->ad->name = 'controller';
        $render= new stdClass();
        $render->name = 'render';
        $this->renderText($this->render(array('partial'=>'ad','object'=>$render)));
    }
}

