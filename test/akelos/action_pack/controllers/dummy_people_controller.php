<?php

class DummyPeopleController extends ApplicationController
{
    var $models = 'DummyPerson';

    function listing() {
        $this->DummyPeople = $this->DummyPerson->findAll();
        $this->respondToFormat();

    }

    function _handleListingAsXml() {
        $this->layout = false;
        $this->renderText($this->DummyPerson->toXml($this->people));
    }

    function _handleListingAsCsv() {
        $this->layout = false;
        $rows = array();
        $columns = $this->DummyPerson->getColumnNames();
        $rows[] = '"'.implode('","',array_values($columns)).'"';
        foreach ($this->people as $person) {
            $rows[] = '"'.implode('","',$person->getAttributes()).'"';
        }

        $this->renderText(implode("\n",$rows));
    }
}