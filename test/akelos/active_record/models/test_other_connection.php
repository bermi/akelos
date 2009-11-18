<?php

class TestOtherConnection extends ActiveRecord
{
    public function &establishConnection($specification_or_profile = AK_DEFAULT_DATABASE_PROFILE)
    {
        return parent::establishConnection('sqlite_databases');
    }
}

