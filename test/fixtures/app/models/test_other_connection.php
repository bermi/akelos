<?php

class TestOtherConnection extends ActiveRecord
{
    public function &establishConnection()
    {
        return parent::establishConnection('sqlite_databases');
    }
}

?>