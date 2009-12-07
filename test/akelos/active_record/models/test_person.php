<?php

class TestPerson extends ActiveRecord
{
    public function validate() {
        $this->validatesPresenceOf("first_name");
    }

    public function validateOnCreate() {
        $this->validatesAcceptanceOf("tos");
    }

    public function validateOnUpdate() {
        $this->validatesPresenceOf("email");
    }

}

