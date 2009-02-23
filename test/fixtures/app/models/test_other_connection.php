<?php

class TestOtherConnection extends ActiveRecord
{
    function &establishConnection()
    {
        return parent::establishConnection('sqlite_databases');
    }
}

?>