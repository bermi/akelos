<?php

class AccountInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('accounts', '
        id,
        person_id,
        username,
        password,
        is_enabled,
        credit_limit int,
        firm_id,
        reset_key,
        created_at'
        );
    }

    public function down_1() {
        $this->dropTable('accounts');
    }
}

