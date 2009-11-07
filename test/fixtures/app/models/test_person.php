<?php

class TestPerson extends ActiveRecord 
{ 
    function validate()
    {
        $this->validatesPresenceOf("first_name");            
    }
    
    function validateOnCreate()
    {
        $this->validatesAcceptanceOf("tos");
    }
    
    function validateOnUpdate()
    {
        $this->validatesPresenceOf("email");
    }

} 

?>