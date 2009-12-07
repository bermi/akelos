<?php

class BbInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('bbs', '
        id,
        aa_id,
        name,
        languages string(200),
        other string(200)'
        );
    }

    public function down_1() {
        $this->dropTable('bbs');
    }
}

