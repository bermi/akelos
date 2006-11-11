<?php

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

class AkTestContinent{
    function AkTestContinent($p_name, $p_countries){ $this->continent_name = $p_name; $this->countries = $p_countries;}
    function getContinentName(){ return $this->continent_name; }
    function getCountriesCollection(){ return $this->countries; }
}
class AkTestCountry {
    function AkTestCountry($id, $name){ $this->id = $id; $this->name = $name; }
    function getCountryId(){ return $this->id; }
    function getCountryName(){ return $this->name;}
}


Stub::generate('AkActiveRecord');
Stub::generate('AkActionController');

class HelpersUnitTester extends AkUnitTest 
{
    
    function HelpersUnitTester()
    {
        $base_url = parse_url(dirname(@$_SERVER['SCRIPT_NAME']));
        $this->testing_url_path = '/'.ltrim($base_url['path'], '/');
    }
}


?>