<?php
    class ObservedPersonObserver extends AkObserver 
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
            echo $record->get("first_name")." has been email with account details";
            $this->logNotified($record,__FUNCTION__);
        }
        
        function afterSave(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidationOnUpdate(&$record){$this->logNotified($record,__FUNCTION__);}
        function beforeSave(&$record){$this->logNotified($record,__FUNCTION__);
            if(!empty($record->city) && $record->city == "Carlet")
            {
                $record->set('state',"Valencia");
            }
        }
        function beforeCreate(&$record){$this->logNotified($record,__FUNCTION__); }
        function beforeValidationOnCreate(&$record){$this->logNotified($record,__FUNCTION__);}
        function beforeValidation(&$record){$this->logNotified($record,__FUNCTION__);}
        function afterValidation(&$record) {$this->logNotified($record,__FUNCTION__);}

        function logNotified(&$record, $function)
        {
            if(!isset($record->notified_observers[$function])){
                $record->notified_observers[$function] = 0;
            }
            $record->notified_observers[$function]++;
        }

    }
?>