<?php

class WebPage extends AkActiveDocument
{
    public $callbacks = array();
    public $halt_on_callback = '';

    public function beforeCreate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeValidation(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeValidationOnCreate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeValidationOnUpdate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeSave(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeUpdate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterUpdate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterValidation(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterValidationOnCreate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterValidationOnUpdate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterInstantiate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterCreate(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterDestroy(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function beforeDestroy(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
    public function afterSave(){$this->callbacks[] = __FUNCTION__; return $this->halt_on_callback != __FUNCTION__;}
}