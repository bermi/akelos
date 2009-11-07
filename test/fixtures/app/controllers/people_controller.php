<?php

class PeopleController extends ApplicationController
{
    var $models = 'Person';
    
    function listing()
    {
        $this->people = $this->person->findAll();
        $this->respondToFormat();
        
    }
    
    function _handleListingAsXml()
    {
        $this->layout = false;
        $this->renderText($this->person->toXml($this->people));
    }
    
    function _handleListingAsCsv()
    {
        $this->layout = false;
        $rows = array();
        $columns = $this->person->getColumnNames();
        $rows[] = '"'.implode('","',array_values($columns)).'"';
        foreach ($this->people as $person) {
            $rows[] = '"'.implode('","',$person->getAttributes()).'"';
        }
        
        $this->renderText(implode("\n",$rows));
    }
    

}