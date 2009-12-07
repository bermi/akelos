<?php

class CcInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('ccs', '
        id,
        name'
        );
    }

    public function down_1() {
        $this->dropTable('ccs');
    }
}

