<?php
class TestAuditor extends AkObserver
{
    public function update($state = ''){
        switch ($state){
            case "new person created" :
                echo $state;
                break;
            default:
                break;
        }
    }

    public function afterCreate(&$record){
        $record->audited = true;
    }
}
