<?php

class ObservedPersonObserver extends AkObserver
{
    public $notified_observers = array();

    public function update($state = '') {
        switch ($state){
            case "new person created" :
                echo $state;
                break;
            default:
                break;
        }
    }

    public function afterCreate(&$record) {
        echo $record->get("first_name")." has been email with account details";
        $this->logNotified($record,__FUNCTION__);
    }

    public function afterSave(&$record){$this->logNotified($record,__FUNCTION__);}
    public function afterValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
    public function afterValidationOnUpdate(&$record){$this->logNotified($record,__FUNCTION__);}
    public function beforeSave(&$record){$this->logNotified($record,__FUNCTION__);
    if(!empty($record->city) && $record->city == "Carlet") {
        $record->set('state',"Valencia");
    }
    }
    public function beforeCreate(&$record){$this->logNotified($record,__FUNCTION__); }
    public function beforeValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
    public function beforeValidation(&$record){$this->logNotified($record,__FUNCTION__);}
    public function afterValidation(&$record) {$this->logNotified($record,__FUNCTION__);}

    public function logNotified(&$record, $function) {
        if(!isset($record->notified_observers[$function])){
            $record->notified_observers[$function] = 0;
        }
        $record->notified_observers[$function]++;
    }
}

