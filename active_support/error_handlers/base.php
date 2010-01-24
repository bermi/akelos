<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

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