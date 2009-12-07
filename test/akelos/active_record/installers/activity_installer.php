<?php

class ActivityInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('activities', '
        id,
        kid_id,
        name,
        created_at'
        );
    }

    public function down_1() {
        $this->dropTable('activities');
    }
}

