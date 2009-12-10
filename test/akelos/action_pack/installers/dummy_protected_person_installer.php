<?php

class DummyProtectedPersonInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('dummy_protected_people','
          id,
          name string(32) not null,
          birthday datetime,
          is_active boolean not null default 1,
          credit_points int default 1000,
          created_by int default null,
          created_at,
          updated_at
        ');
    }

    function down_1()
    {
        $this->dropTable('dummy_protected_people', array('sequence'=>true));
    }
}
