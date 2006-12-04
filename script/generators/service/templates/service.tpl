<?='<?php'?>


class <?=$service_class_name?> extends AkActionWebService
{
    var $web_service_api = '<?=$api_class_name?>';
    
    <?php foreach ($api_methods as $method=>$params) {

        if(!empty($api_method_doc[$method])){
        ?>	
    /**<?=$api_method_doc[$method]?>
    
    */
<?
        }
    ?>
    function <?=$method?>(<?=$params?>)
    {
        
    }
    
<?  
    }
    ?>
}

?>
