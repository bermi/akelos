<?php

class AaInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('aas', '
        id,
        name'
        );
    }

    public function down_1() {
        $this->dropTable('aas');
    }
}

