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

class WebPageAuditor extends AkObserver
{
    public function beforeCreate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeValidation(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeValidationOnCreate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeValidationOnUpdate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeSave(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeUpdate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterUpdate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterValidation(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterValidationOnCreate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterValidationOnUpdate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterInstantiate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterCreate(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterDestroy(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function beforeDestroy(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
    public function afterSave(&$Record){$Record->callbacks[] = __METHOD__; return $Record->halt_on_callback != __METHOD__;}
}
