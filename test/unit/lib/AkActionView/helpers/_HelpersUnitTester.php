<?php

require_once(dirname(__FILE__).'/../../../../fixtures/config/config.php');

require_once(AK_LIB_DIR.DS.'AkActiveRecord.php');
require_once(AK_LIB_DIR.DS.'AkActionController.php');

require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'AkActionViewHelper.php');

class AkTestContinent{
    public function AkTestContinent($p_name, $p_countries){ $this->continent_name = $p_name; $this->countries = $p_countries;}
    public function getContinentName(){ return $this->continent_name; }
    public function getCountriesCollection(){ return $this->countries; }
}
class AkTestCountry {
    public function AkTestCountry($id, $name){ $this->id = $id; $this->name = $name; }
    public function getCountryId(){ return $this->id; }
    public function getCountryName(){ return $this->name;}
}


Stub::generate('AkActiveRecord');
Stub::generate('AkActionController');

class HelpersUnitTester extends AkUnitTest 
{
    
    public function HelpersUnitTester()
    {
        $this->testing_url_path = AK_ASSET_URL_PREFIX;
    }
}


?>