<?php
    class TestAuditor extends AkObserver 
    { 
        function update($state)
        {
            switch ($state)
            {
                case "new person created" :
                echo $state;
                break;
                default:
                break;
            }
        }
        
        function afterCreate(&$record)
        {
            $record->audited = true;
        }

    }
?>