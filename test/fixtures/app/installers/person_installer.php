<?php

class PersonInstaller extends AkInstaller
{
    function up_1()
    {
        $this->createTable('people', '
        id,
        first_name,
        last_name,
        email,
        created_at'
        );
    }

    function down_1()
    {
        $this->dropTable('people');
    }
}

?>