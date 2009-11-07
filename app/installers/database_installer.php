<?php

class DatabaseInstaller extends AkInstaller
{
    function up_1()
    {
        $this->db->debug = true;
        if($this->_loadDbDesignerDbSchema()){
            foreach ($this->db_designer_schema as $table=>$columns){
                $this->createTable($table, $columns, array('timestamp'=>false));
            }
        }
    }

    function down_1()
    {
        if($this->_loadDbDesignerDbSchema()){
            foreach ($this->db_designer_schema as $table=>$columns){
                $this->dropTable($table);
            }
        }
    }
    
    
    function reset()
    {
        if($this->_loadDbDesignerDbSchema()){
            foreach ($this->db_designer_schema as $table=>$columns){
                $this->dropTable($table);
                $this->createTable($table, $columns, array('timestamp'=>false));
            }
        }
    }
}

?>