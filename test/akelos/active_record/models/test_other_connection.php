<?php

class TestOtherConnection extends ActiveRecord
{
    public function &establishConnection($specification_or_profile = AK_DEFAULT_DATABASE_PROFILE, $force = false) {
        return parent::establishConnection('sqlite_databases', $force);
    }
}

