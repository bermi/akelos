<?php

class KidInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('kids', '
        id,
        father_id,
        name,
        created_at'
        );
    }

    public function down_1() {
        $this->dropTable('kids');
    }
}

