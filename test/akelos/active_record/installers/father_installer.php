<?php

class FatherInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('fathers', '
        id,
        name,
        created_at'
        );
    }

    public function down_1() {
        $this->dropTable('fathers');
    }
}

