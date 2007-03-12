<?php

class AccountInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('accounts', '
        id,
        person_id,
        username,
        password,
        is_enabled,
        reset_key,
        created_at'
        );
    }

    function down_1()
    {
        $this->dropTable('accounts');
    }
}

?>