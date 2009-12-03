<?php echo "<?php"; ?>


require_once(AK_LIB_DIR.DS.'AkActionMailer.php');

Ak::import('<?php echo $class_name; ?>');

class <?php echo $class_name; ?>TestCase extends AkUnitTest
{
    function setup()
    {
        $this-><?php echo $class_name; ?> =& new <?php echo $class_name; ?>();
        $this-><?php echo $class_name; ?>->delivery_method = 'test';
        $this->recipient = 'root@localhost';
    }
    
    <?php foreach($actions as $action){ ?>

    function test_<?php echo $action; ?>()
    {
        $this-><?php echo $class_name; ?>->create('<?php echo $action; ?>', $this->recipient);
    }
     
    <?php } ?>

}

?>