<?php

class ProtectedPerson extends ActiveRecord
{
    public $_accessibleAttributes = array('name','birthday');
}

