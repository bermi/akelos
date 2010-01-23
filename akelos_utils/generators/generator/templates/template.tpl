You can use PHP in your templates.

PHP $variables are made available to the view from the generator by calling:

    $this->assignVarToTemplate('variables', 'Got value');