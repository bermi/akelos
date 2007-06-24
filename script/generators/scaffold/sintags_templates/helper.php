<?php  echo '<?php'?>


class <?php  echo $controller_name?>Helper extends AkActionViewHelper
{ 
    function cancel($url = array('action' => 'listing'))
    {
        return '<input type="button" value="'.$this->_controller->t('Cancel').'" style="width: auto;" onclick="window.location.href = \''.$this->_controller->urlFor($url).'\';" />';
    }

    function save()
    {
        return '<input type="submit" value="'.$this->_controller->t('OK').'" class="primary" />';
    }

    function confirm_delete()
    {
        return '<input type="submit" value="'.$this->_controller->t('Delete').'" />';
    }

    function link_to_show(&$record)
    {
        return $this->_controller->url_helper->link_to($this->_controller->t('Show'), array('action' => 'show', 'id' => $record->getId()));
    }  

    function link_to_edit(&$record)
    {
        return $this->_controller->url_helper->link_to($this->_controller->t('Edit'), array('action' => 'edit', 'id' => $record->getId()));
    }  

    function link_to_destroy(&$record)
    {
        return $this->_controller->url_helper->link_to($this->_controller->t('Delete'), array('action' => 'destroy', 'id' => $record->getId()));
    }
}

?>