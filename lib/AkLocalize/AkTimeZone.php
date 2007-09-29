<?php
/* vim: set expandtab tabstop=4 shiftwidth=4 softtabstop=4: */

// +----------------------------------------------------------------------+
// | Akelos Framework - http://www.akelos.org                             |
// +----------------------------------------------------------------------+
// | Copyright (c) 2002-2006, Akelos Media, S.L.  & Bermi Ferrer Martinez |
// | Released under the GNU Lesser General Public License, see LICENSE.txt|
// +----------------------------------------------------------------------+

/**
 * @package ActiveSupport
 * @subpackage I18n-L10n
 * @author Bermi Ferrer <bermi a.t akelos c.om>
 * @copyright Copyright (c) 2002-2006, Akelos Media, S.L. http://www.akelos.org
 * @license GNU Lesser General Public License <http://www.gnu.org/copyleft/lesser.html>
 */

/**
* A value object representing a time zone. A time zone is simply a named
* offset (in seconds) from GMT. Note that two time zone objects are only
* equivalent if they have both the same offset, and the same name.
* 
* A TimeZone instance may be used to convert a Time value to the corresponding
* time zone.
* 
* The class also includes a method named all(), which returns a list of all TimeZone objects.
*/
class AkTimeZone
{
    var $name;
    var $utc_offset;

    /**
    * Initiates a new AkTimeZone object with the given name and offset. The offset is
    * the number of seconds that this time zone is offset from UTC (GMT).
    */
    function init($name, $utc_offset)
    {
        $this->name = $name;
        $this->utc_offset = $utc_offset;
    }

    /**
    * Returns the offset of this time zone as a formatted string, of the
    * format "+HH:MM". If the offset is zero, this returns the empty
    * string. If $colon is false, a colon will not be inserted into the
    * result.
    */
    function getFormattedOffset($colon = true)
    {
        if($this->utc_offset == 0){
            return '';
        }
        $sign = ($this->utc_offset < 0 ? -1 : 1);
        $hours = abs($this->utc_offset) / 3600;
        $minutes = (abs($this->utc_offset) % 3600) / 60;
        return sprintf("%+03d%s%02d", $hours * $sign, $colon ? ":" : "", $minutes);
    }

    /**
    * Compute and return the current time, in the time zone represented by
    * AkTimeZone.
    */
    function now()
    {
        return $this->adjust(isset($this->_timestamp) ? $this->_timestamp : Ak::getTimestamp());
    }

    /**
    * Return the current date in this time zone.
    */
    function today()
    {
        return Ak::getDate($this->now(), Ak::locale('date_format'));
    }

    /**
    * Adjust the given time to the time zone represented by AkTimeZone.
    */
    function adjust($time)
    {
        return $time + $this->utc_offset;
    }

    /**
    * Reinterprets the given time value as a time in the current time
    * zone, and then adjusts it to return the corresponding time in the
    * local time zone.
    */
    function unadjust($time)
    {
        return $time - $this->utc_offset;
    }

    /**
    * Compare this time zone to the parameter. The two are compared first on
    * their offsets, and then by name.
    */
    function compare($zone)
    {
        $result = $this->utc_offset > $zone->utc_offset ? 1 : ($zone->utc_offset > $this->utc_offset ? -1 : 0);
        $result = $result == 0 ? strcoll($this->name, $zone->name) : $result;
        return $result == 0 ? 0 : ($result > 0 ? 1 : -1);
    }

    /**
    * Returns a textual representation of this time zone.
    */
    function toString()
    {
        return '(GMT'.$this->getFormattedOffset().") $this->name";
    }

    /**
    * Static method for creating new AkTimeZone object with the given name, offset and an options zones array.
    */
    function create($name, $timezone, $zones = null)
    {
        $Zone = new AkTimeZone();
        if(!empty($zones)){
            $Zone->zones = $zones;
        }
        $details = $Zone->locateTimezone($name);
        $details = empty($details) ? $Zone->locateTimezone($timezone) : $details;
        if(!empty($details)){
            $name = $details['name'];
            $timezone =  $details['offset'];
        }
        $Zone->init($name, $timezone);
        unset($Zone->zones);
        return $Zone;
    }

    /**
    * Locate a specific time zone. If the argument is a string, it
    * is interpreted to mean the name of the timezone to locate. If it is a
    * numeric value it is either the hour offset, or the second offset, of the
    * timezone to find. (The first one with that offset will be returned.)
    * Returns false if no such time zone is known to the system.
    */
    function locateTimezone($timezone_name_or_offset)
    {
        if(is_string($timezone_name_or_offset)){
            $timezones = $this->getTimezones();
            if(isset($timezones[$timezone_name_or_offset])){
                return array('offset'=>$timezones[$timezone_name_or_offset], 'name' => $timezone_name_or_offset);
            }
        }elseif (is_numeric($timezone_name_or_offset)){
            $timezones = $this->getTimezones();
            foreach ($timezones as $zone => $offset){
                if($timezone_name_or_offset == $offset){
                    return array('offset'=>$offset, 'name' => $zone);
                }
            }
        }else{
            trigger_error(Ak::t('Invalid argument at AkTimeZone::locateTimezone(), you must supply a Time zone name or offset'), E_USER_NOTICE);
        }
        return false;
    }

    /**
    * Return an array of all time zones, as Place => Offset. There are multiple
    * places per time zone, in many cases, to make it easier for users to
    * find their own time zone.
    */
    function getTimezones()
    {
        if(empty($this->zones)){
            $zones =  Ak::t(
            '-43200 | International Date Line West
-39600 | Midway Island, Samoa
-36000 | Hawaii
-32400 | Alaska
-28800 | Pacific Time (US & Canada), Tijuana
-25200 | Mountain Time (US & Canada), Chihuahua, Mazatlan, Arizona
-21600 | Central Time (US & Canada), Saskatchewan, Guadalajara, Mexico City, Monterrey, Central America
-18000 | Eastern Time (US & Canada), Indiana (East), Bogota, Lima, Quito
-14400 | Atlantic Time (Canada), Caracas, La Paz, Santiago
-12600 | Newfoundland
-10800 | Brasilia, Buenos Aires, Georgetown, Greenland
 -7200 | Mid-Atlantic
 -3600 | Azores, Cape Verde Is.
     0 | Dublin, Edinburgh, Lisbon, London, Casablanca, Monrovia
  3600 | Belgrade, Bratislava, Budapest, Ljubljana, Prague, Sarajevo, Skopje, Warsaw, Zagreb, Brussels, Copenhagen, Madrid, Paris, Amsterdam, Berlin, Bern, Rome, Stockholm, Vienna, West Central Africa
  7200 | Bucharest, Cairo, Helsinki, Kyev, Riga, Sofia, Tallinn, Vilnius, Athens, Istanbul, Minsk, Jerusalem, Harare, Pretoria
 10800 | Moscow, St. Petersburg, Volgograd, Kuwait, Riyadh, Nairobi, Baghdad
 12600 | Tehran
 14400 | Abu Dhabi, Muscat, Baku, Tbilisi, Yerevan
 16200 | Kabul
 18000 | Ekaterinburg, Islamabad, Karachi, Tashkent
 19800 | Chennai, Kolkata, Mumbai, New Delhi
 20700 | Kathmandu
 21600 | Astana, Dhaka, Sri Jayawardenepura, Almaty, Novosibirsk
 23400 | Rangoon
 25200 | Bangkok, Hanoi, Jakarta, Krasnoyarsk
 28800 | Beijing, Chongqing, Hong Kong, Urumqi, Kuala Lumpur, Singapore, Taipei, Perth, Irkutsk, Ulaan Bataar
 32400 | Seoul, Osaka, Sapporo, Tokyo, Yakutsk
 34200 | Darwin, Adelaide
 36000 | Canberra, Melbourne, Sydney, Brisbane, Hobart, Vladivostok, Guam, Port Moresby
 39600 | Magadan, Solomon Is., New Caledonia
 43200 | Fiji, Kamchatka, Marshall Is., Auckland, Wellington
 46800 | Nuku\'alofa', null, 'localize/timezone');

            $this->zones = array();
            foreach (explode("\n", $zones) as $zone){
                list($offset, $places) = explode('|', $zone);
                $offset = intval($offset);
                $places = array_map('trim', array_diff(explode(',', $places), array('')));
                sort($places);
                foreach ($places as $place){
                    $this->zones[$place] = $offset;
                }
            }

        }
        return $this->zones;
    }

    /**
    * Return an array of all AkTimeZone objects. There are multiple AkTimeZone objects
    * per time zone, in many cases, to make it easier for users to find their own time zone.
    */
    function &all()
    {
        $TimeZone = new AkTimeZone();
        $time_zones = $TimeZone->getTimezones();
        $Zones = array();
        foreach ($time_zones as $name => $offset){
            $Zones[] = AkTimeZone::create($name, $offset, $time_zones);
        }
        return $Zones;
    }
}


?>