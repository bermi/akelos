<?php

class BelongInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('belongs', '
        id,
        many_id,
        name'
        );
    }

    public function down_1() {
        $this->dropTable('belongs');
    }
}

