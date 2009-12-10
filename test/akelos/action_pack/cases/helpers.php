<?php

require_once(dirname(__FILE__).'/../config.php');

class HelperUnitTest extends ActionPackUnitTest
{
    public function __construct(){
        parent::__construct();
        Mock::generate('AkActiveRecord');
        Mock::generate('AkActionController');
        Mock::generate('AkRequest');
        $this->testing_url_path = AK_ASSET_URL_PREFIX;
    }

    static function getFixturesDir(){
        return AkConfig::getDir('fixtures').DS.'helpers';
    }
}

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

$_helper_files = glob(dirname(__FILE__).DS.'helpers'.DS.'*.php');
$_included_files = get_included_files();
if(count($_included_files) == count(array_diff($_included_files, $_helper_files))){
    foreach ($_helper_files as $file){
        include $file;
    }
}

unset($_helper_files);
unset($_included_files);
