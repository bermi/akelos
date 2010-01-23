<?php

class AkError{
    static function handle(Exception $e){
        if(AK_WEB_REQUEST){
            echo "<pre>";
        }
        throw $e;
        if(AK_WEB_REQUEST){
            echo "</pre>";
        }
    }
}