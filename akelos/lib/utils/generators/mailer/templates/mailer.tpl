<?php echo "<?php"; ?>


class <?php echo $class_name; ?> extends AkActionMailer
{
    <?php foreach($actions as $action){ ?>

    function <?php echo $action; ?>($recipient)
    {
        $this->recipients    =  $recipient;
        $this->subject       =  "[<?php echo $class_name.'] '.AkInflector::humanize($action); ?>";
        $this->from          =  '';
        $this->body          =  array();
        $this->headers       =  array();
     }
     
    <?php } ?>

}

?>