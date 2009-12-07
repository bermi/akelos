<?php

class AkModelObserver extends AkModelExtenssion
{
    /**
    * $state store the state of this observable object
    */
    private $_observable_state;

    /**
    * Calls the $method using the reference to each
    * registered observer.
    * @return true (this is used internally for triggering observers on default callbacks)
    */
    public function notifyObservers ($method = null)
    {
        $observers = $this->getObservers();
        $observer_count = count($observers);

        if(!empty($method)){
            $this->setObservableState($method);
        }

        $model_name = $this->_Model->getModelName();
        for ($i=0; $i<$observer_count; $i++) {
            if(in_array($model_name, $observers[$i]->_observing)){
                if(method_exists($observers[$i], $method)){
                    if($observers[$i]->$method($this->_Model) === false){
                        $this->setObservableState('');
                        return false;
                    }
                }else{
                    $observers[$i]->update($this->getObservableState(), $this);
                }
            }else{
                $observers[$i]->update($this->getObservableState(), $this);
            }
        }
        $this->setObservableState('');

        return true;
    }


    public function setObservableState($state_message)
    {
        $this->_observable_state = $state_message;
    }

    public function getObservableState()
    {
        return $this->_observable_state;
    }

    /**
    * Register the reference to an object object
    *
    *
    * @param $observer AkObserver
    * @param $options array of options for the observer
    * @return void
    */
    public function addObserver($observer)
    {
        $staticVarNs='AkActiveModel::observers::' . $this->_Model->getModelName();
        $observer_class_name = get_class($observer);
        /**
         * get the statically stored observers for the namespace
         */
        $observers = Ak::getStaticVar($staticVarNs);
        if (!is_array($observers)) {
            $observers = array('classes'=>array(),'objects'=>array());
        }
        /**
         * if not already registered, the observerclass will
         * be registered now
         */
        if (!in_array($observer_class_name,$observers['classes'])) {
            $observers['classes'][] = $observer_class_name;
            $observers['objects'][] = $observer;
            Ak::setStaticVar($staticVarNs, $observers);
        }
    }
    /**
    * Register the reference to an object object
    * @return void
    */
    public function &getObservers()
    {
        $staticVarNs='AkActiveModel::observers::' . $this->_Model->getModelName();
        $key = 'objects';

        $array = array();
        $observers_arr = Ak::getStaticVar($staticVarNs);
        if (isset($observers_arr[$key])) {
            $observers = $observers_arr[$key];
        } else {
            $observers = $array;
        }
        return $observers;
    }

}

