<?php  echo '<?php'?>


class <?php  echo $service_class_name?> extends AkActionWebService
{
    var $web_service_api = '<?php  echo $api_class_name?>';
    
    <?php foreach ($api_methods as $method=>$params) {

        if(!empty($api_method_doc[$method])){
        ?>	
    /**<?php  echo $api_method_doc[$method]?>
    
    */
<?php
        }
    ?>
    function <?php  echo $method?>(<?php  echo $params?>)
    {
        
    }
    
<?php  
    }
    ?>
}

?>
