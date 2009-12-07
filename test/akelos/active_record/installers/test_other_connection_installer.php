<?php

class TestOtherConnectionInstaller extends AkInstaller
{
    public function __construct() {
        $adapter = AkDbAdapter::getInstance('sqlite_databases', true, 'database');
        parent::__construct($adapter);
    }

    public function up_1() {
        $this->createTable('test_other_connections', 'id,name');
    }

    public function down_1() {
        $this->dropTable('test_other_connections');
    }
}

