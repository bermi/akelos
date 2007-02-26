<?php

class ProtectedPersonInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('protected_people','
          id int auto increment not null primary,
          name string(32) not null,
          birthday datetime,
          is_active boolean not null default 1,
          created_by int default null,
          created_at,
          updated_at
        ');
    }

    function down_1()
    {
        $this->dropTable('protected_people');
    }
}

?>