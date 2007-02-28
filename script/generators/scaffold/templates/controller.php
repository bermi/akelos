<?='<?php'?>


class <?=$controller_class_name?> extends ApplicationController
{
<?php 
    if($model_name != $controller_name){ // if equal will be handled by the Akelos directly
        echo "    var \$models = '$singular_name';";
    }
?>
    function index()
    {
        $this->renderAction('listing');
    }

<? foreach((array)@$actions as $action) :?>
    function <?=$action?>()
    {
    }

<? endforeach; ?>
    function listing()
    {
        $this-><?=$singular_name?>_pages = $this->pagination_helper->getPaginator($this-><?=$model_name?>, array('items_per_page' => 10));        
        $this-><?=$plural_name?> = $this-><?=$model_name?>->find('all', $this->pagination_helper->getFindOptions($this-><?=$model_name?>));
    }

    function show()
    {
        $this-><?=$singular_name?> = $this-><?=$model_name?>->find(@$this->params['id']);
    }

    function add()
    {
        if(!empty($this->params['<?=$singular_name?>'])){
            $this-><?=$model_name?>->setAttributes($this->params['<?=$singular_name?>']);
            if ($this->Request->isPost() && $this-><?=$model_name?>->save()){
                $this->flash['notice'] = $this->t('<?=$model_name?> was successfully created.');
                $this->redirectTo(array('action' => 'show', 'id' => $this-><?=$model_name?>->getId()));
            }
        }
    }

    function edit()
    {
        if (empty($this->params['id'])){
         $this->redirectToAction('listing');
        }
        if(!empty($this->params['<?=$singular_name?>']) && !empty($this->params['id'])){
            $this-><?=$singular_name?> = $this-><?=$model_name?>->find($this->params['id']);
            $this-><?=$singular_name?>->setAttributes($this->params['<?=$singular_name?>']);
            if($this->Request->isPost() && $this-><?=$singular_name?>->save()){
                $this->flash['notice'] = $this->t('<?=$model_name?> was successfully updated.');
                $this->redirectTo(array('action' => 'show', 'id' => $this-><?=$singular_name?>->getId()));
            }
        }
    }

    function destroy()
    {
        if(!empty($this->params['id'])){
            $this-><?=$singular_name?> = $this-><?=$model_name?>->find($this->params['id']);
            if($this->Request->isPost()){
                $this-><?=$singular_name?>->destroy();
                $this->redirectTo(array('action' => 'listing'));
            }
        }
    }  
}

?>