<?php

class AkTestUser extends AkActiveRecord
{
    //var $expiresOnDataType = "date";
    public function callBackFunctionCompose() {
        $args = func_get_arg(0);
        return "<a href='mailto:{$args['email']}'>{$args['name']}</a>";
    }
    public function callBackFunctionDecompose($email_link) {
        $results = sscanf($email_link, "<a href='mailto:%[^']'>%[^<]</a>");
        return array('email'=>$results[0],'name'=>$results[1]);
    }
    public function getPassword() {
        parent::get("password", false);
        return "*********";
    }
    public function setPassword($password) {
        parent::set("password", md5($password), false);
    }
}
