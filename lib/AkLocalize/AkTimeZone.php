<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package AkelosFramework
 * @subpackage I18n-L10n
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

class AkTimeZone
{
    function getTimeZoneDescriptions()
    {
        return explode("\n", Ak::t("GMT|Casablanca
GMT|Dublin
GMT|Edinburgh
GMT|Lisbon
GMT|London
GMT|Monrovia
GMT+01:00|Amsterdam
GMT+01:00|Belgrade
GMT+01:00|Berlin
GMT+01:00|Bern
GMT+01:00|Bratislava
GMT+01:00|Brussels
GMT+01:00|Budapest
GMT+01:00|Copenhagen
GMT+01:00|Ljubljana
GMT+01:00|Madrid
GMT+01:00|Paris
GMT+01:00|Prague
GMT+01:00|Rome
GMT+01:00|Sarajevo
GMT+01:00|Skopje
GMT+01:00|Stockholm
GMT+01:00|Vienna
GMT+01:00|Warsaw
GMT+01:00|West Central Africa
GMT+01:00|Zagreb
GMT+02:00|Athens
GMT+02:00|Bucharest
GMT+02:00|Cairo
GMT+02:00|Harare
GMT+02:00|Helsinki
GMT+02:00|Istanbul
GMT+02:00|Jerusalem
GMT+02:00|Kyev
GMT+02:00|Minsk
GMT+02:00|Pretoria
GMT+02:00|Riga
GMT+02:00|Sofia
GMT+02:00|Tallinn
GMT+02:00|Vilnius
GMT+03:00|Baghdad
GMT+03:00|Kuwait
GMT+03:00|Moscow
GMT+03:00|Nairobi
GMT+03:00|Riyadh
GMT+03:00|St. Petersburg
GMT+03:00|Volgograd
GMT+03:30|Tehran
GMT+04:00|Abu Dhabi
GMT+04:00|Baku
GMT+04:00|Muscat
GMT+04:00|Tbilisi
GMT+04:00|Yerevan
GMT+04:30|Kabul
GMT+05:00|Ekaterinburg
GMT+05:00|Islamabad
GMT+05:00|Karachi
GMT+05:00|Tashkent
GMT+05:30|Chennai
GMT+05:30|Kolkata
GMT+05:30|Mumbai
GMT+05:30|New Delhi
GMT+05:45|Kathmandu
GMT+06:00|Almaty
GMT+06:00|Astana
GMT+06:00|Dhaka
GMT+06:00|Novosibirsk
GMT+06:00|Sri Jayawardenepura
GMT+06:30|Rangoon
GMT+07:00|Bangkok
GMT+07:00|Hanoi
GMT+07:00|Jakarta
GMT+07:00|Krasnoyarsk
GMT+08:00|Beijing
GMT+08:00|Chongqing
GMT+08:00|Hong Kong
GMT+08:00|Irkutsk
GMT+08:00|Kuala Lumpur
GMT+08:00|Perth
GMT+08:00|Singapore
GMT+08:00|Taipei
GMT+08:00|Ulaan Bataar
GMT+08:00|Urumqi
GMT+09:00|Osaka
GMT+09:00|Sapporo
GMT+09:00|Seoul
GMT+09:00|Tokyo
GMT+09:00|Yakutsk
GMT+09:30|Adelaide
GMT+09:30|Darwin
GMT+10:00|Brisbane
GMT+10:00|Canberra
GMT+10:00|Guam
GMT+10:00|Hobart
GMT+10:00|Melbourne
GMT+10:00|Port Moresby
GMT+10:00|Sydney
GMT+10:00|Vladivostok
GMT+11:00|Magadan
GMT+11:00|New Caledonia
GMT+11:00|Solomon Is.
GMT+12:00|Auckland
GMT+12:00|Fiji
GMT+12:00|Kamchatka
GMT+12:00|Marshall Is.
GMT+12:00|Wellington
GMT+13:00|Nuku'alofa
GMT-01:00|Azores
GMT-01:00|Cape Verde Is.
GMT-02:00|Mid-Atlantic
GMT-03:00|Brasilia
GMT-03:00|Buenos Aires
GMT-03:00|Georgetown
GMT-03:00|Greenland
GMT-03:30|Newfoundland
GMT-04:00|Atlantic Time (Canada)
GMT-04:00|Caracas
GMT-04:00|La Paz
GMT-04:00|Santiago
GMT-05:00|Bogota
GMT-05:00|Eastern Time (US & Canada)
GMT-05:00|Indiana (East)
GMT-05:00|Lima
GMT-05:00|Quito
GMT-06:00|Central America
GMT-06:00|Central Time (US & Canada)
GMT-06:00|Guadalajara
GMT-06:00|Mexico City
GMT-06:00|Monterrey
GMT-06:00|Saskatchewan
GMT-07:00|Arizona
GMT-07:00|Chihuahua
GMT-07:00|La Paz
GMT-07:00|Mazatlan
GMT-07:00|Mountain Time (US & Canada)
GMT-08:00|Pacific Time (US & Canada)
GMT-08:00|Tijuana
GMT-09:00|Alaska
GMT-10:00|Hawaii
GMT-11:00|Midway Island
GMT-11:00|Samoa
GMT-12:00|International Date Line West",array(),'localize/timezones'));
    }


    function all()
    {
        $timezones = AkTimeZone::getTimeZoneDescriptions();
        $timezone_array = array();
        $i = 0;
        foreach ($timezones as $timezone){
            $timezone_pieces = explode('|',$timezone);
            $timezone_array[$timezone_pieces[1]] = $timezone_pieces[0];
        }
        return $timezone_array;
    }
}



?>