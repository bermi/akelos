<?php

class UserInstaller extends AkInstaller
{
    function up_2()
    {
        $this->addColumn('users', 'preferences text');
    }
    
    function down_2()
    {
        $this->removeColumn('users', 'preferences');
    }
    
    function up_1()
    {
        $this->createTable('users', 'id,name,email,login,password,is_admin,is_enabled,created_at,last_login_at');
    }
    
    function down_1()
    {
        $this->dropTable('users');
    }
}

?>
