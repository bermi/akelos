<?php

class EeInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('ees', '
        id,
        name'
        );
    }

    public function down_1() {
        $this->dropTable('ees');
    }
}

