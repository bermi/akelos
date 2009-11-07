<?php

class TestOtherConnectionInstaller extends AkInstaller
{
    function __construct()
    {
        $adapter =& AkDbAdapter::getInstance('sqlite_databases');
        parent::AkInstaller($adapter);
    }
    
    function up_1()
    {
        $this->createTable('test_other_connections', 'id,name');
    }
    
    function down_1()
    {
        $this->dropTable('test_other_connections');
    }
}

?>
