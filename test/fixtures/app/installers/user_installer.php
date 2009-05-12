<?php

class UserInstaller extends AkInstaller
{
    function xup_2()
    {
        $this->addColumn('users', 'preferences text');
    }
    
    function xdown_2()
    {
        $this->removeColumn('users', 'preferences');
    }
    
    function up_1()
    {
        $this->log('up 1 on user');
        $this->createTable('users', 'id,name,email,login,password,is_admin,is_enabled,created_at,last_login_at,preferences text');
    }
    
    function down_1()
    {
        $this->log('down 1 on user');
        $this->dropTable('users');
    }
}

?>
