<?php

class TagInstaller extends AkInstaller
{
    public function up_1() {
        $this->createTable('tags', '
        id integer max=10 auto increment primary,
        score int default 100,
        name string 50'
        );
    }

    public function down_1() {
        $this->dropTable('tags', array('sequence'=>true));
    }
}

