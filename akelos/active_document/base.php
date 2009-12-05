<?php

class AkActiveDocument extends AkLazyObject
{

}

class AkActiveDocumentExtenssion
{
    protected $_ActiveDocument;
    public function setExtendedBy(&$ActiveDocument)
    {
        $this->_ActiveDocument = $ActiveDocument;
    }
}

