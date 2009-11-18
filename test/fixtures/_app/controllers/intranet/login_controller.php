<?php

class Intranet_LoginController extends IntranetController 
{

    function custom_layout()
    {
        $this->render(array('layout'=>'custom_layout'));
    }
    
    function with_custom_template_and_layout()
    {
        $this->render(array('template'=>'custom_template','layout'=>'custom_layout'));
    }
}

?>