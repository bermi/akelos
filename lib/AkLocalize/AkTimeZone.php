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
    var $dst;
    var $zones = array();
    var $dst_zones = array();

    /**
    * Initiates a new AkTimeZone object with the given name and offset. The offset is
    * the number of seconds that this time zone is offset from UTC (GMT).
    */
    function init($name, $utc_offset, $dst = false)
    {
        $this->name = $name;
        $this->utc_offset = $utc_offset;
        $this->dst = $dst;
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
    * Return the current time in this time zone.
    */
    function time()
    {
        return Ak::getDate($this->now(), Ak::locale('time_format'));
    }

    /**
    * Return the current time in this time zone.
    */
    function dateTime()
    {
        return Ak::getDate($this->now(), Ak::locale('date_time_format'));
    }

    /**
    * Adjust the given time to the time zone represented by AkTimeZone.
    */
    function adjust($time)
    {
        return $time + $this->utc_offset + ($this->dst && $this->inDst($time, $this->name) ? 3600 : 0) - AK_UTC_OFFSET;
    }

    /**
    * Reinterprets the given time value as a time in the current time
    * zone, and then adjusts it to return the corresponding time in the
    * local time zone.
    */
    function unadjust($time)
    {
        return $time - $this->utc_offset - ($this->dst && $this->inDst($time, $this->name) ? 3600 : 0) + AK_UTC_OFFSET;
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


    function inDst($timestamp, $zone_name)
    {
        $env_tz = function_exists('date_default_timezone_get') ?  @date_default_timezone_get() : @getenv('TZ');
        function_exists('date_default_timezone_set') ?  date_default_timezone_set($zone_name) : @putenv('TZ='.$zone_name);
        $localtime = localtime($timestamp, true);
        if(!empty($env_tz)){
            function_exists('date_default_timezone_set') ?  date_default_timezone_set($env_tz) : @putenv('TZ='.$env_tz);
        }
        return !empty($localtime['tm_isdst']);
    }


    /**
    * Static method for creating new AkTimeZone object with the given name, offset and an options zones array.
    */
    function create($name, $timezone = null, $zones = null, $dst_zones = null)
    {
        $Zone = new AkTimeZone();
        if(!empty($zones)){
            $Zone->zones = $zones;
        }
        if(!empty($dst_zones)){
            $Zone->dst_zones = $dst_zones;
        }
        $details = $Zone->locateTimezone($name);
        $details = empty($details) ? $Zone->locateTimezone($timezone) : $details;
        if(!empty($details)){
            $name = $details['name'];
            $timezone =  $details['offset'];
        }
        $Zone->init($name, $timezone, !empty($Zone->dst_zones) && in_array($name, $Zone->dst_zones));
        unset($Zone->zones, $Zone->dst_zones);
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
    * 
    * Places prefixed with "-" are those who have DST in the South Hemisphere
    */
    function getTimezones()
    {
        if(empty($this->zones)){
            $zones =  Ak::t(
            '-43200 | Etc/GMT+12
-39600 | Etc/GMT+11, MIT, Pacific/Apia, Pacific/Midway, Pacific/Niue, Pacific/Pago Pago, Pacific/Samoa, US/Samoa
-36000 | -America/Adak, -America/Atka, Etc/GMT+10, HST, Pacific/Fakaofo, Pacific/Honolulu, Pacific/Johnston, Pacific/Rarotonga, Pacific/Tahiti, SystemV/HST10, -US/Aleutian, US/Hawaii
-34200 | Pacific/Marquesas
-32400 | -AST, -America/Anchorage, -America/Juneau, -America/Nome, -America/Yakutat, Etc/GMT+9, Pacific/Gambier, SystemV/YST9, -SystemV/YST9YDT, -US/Alaska
-28800 | -America/Dawson, -America/Ensenada, -America/Los Angeles, -America/Tijuana, -America/Vancouver, -America/Whitehorse, -Canada/Pacific, -Canada/Yukon, Etc/GMT+8, -Mexico/BajaNorte, -PST, -PST8PDT, Pacific/Pitcairn, SystemV/PST8, -SystemV/PST8PDT, -US/Pacific, -US/Pacific-New
-25200 | -America/Boise, -America/Cambridge Bay, -America/Chihuahua, America/Dawson Creek, -America/Denver, -America/Edmonton, America/Hermosillo, -America/Inuvik, -America/Mazatlan, America/Phoenix, -America/Shiprock, -America/Yellowknife, -Canada/Mountain, Etc/GMT+7, -MST, -MST7MDT, -Mexico/BajaSur, -Navajo, PNT, SystemV/MST7, -SystemV/MST7MDT, US/Arizona, -US/Mountain
-21600 | America/Belize, -America/Cancun, -America/Chicago, America/Costa Rica, America/El Salvador, America/Guatemala, America/Managua, -America/Menominee, -America/Merida, America/Mexico City, -America/Monterrey, -America/North Dakota/Center, -America/Rainy River, -America/Rankin Inlet, America/Regina, America/Swift Current, America/Tegucigalpa, -America/Winnipeg, -CST, -CST6CDT, -Canada/Central, Canada/East-Saskatchewan, Canada/Saskatchewan, -Chile/EasterIsland, Etc/GMT+6, Mexico/General, -Pacific/Easter, Pacific/Galapagos, SystemV/CST6, -SystemV/CST6CDT, -US/Central
-18000 | America/Bogota, America/Cayman, -America/Detroit, America/Eirunepe, America/Fort Wayne, -America/Grand Turk, America/Guayaquil, -America/Havana, America/Indiana/Indianapolis, America/Indiana/Knox, America/Indiana/Marengo, America/Indiana/Vevay, America/Indianapolis, -America/Iqaluit, America/Jamaica, -America/Kentucky/Louisville, -America/Kentucky/Monticello, America/Knox IN, America/Lima, -America/Louisville, -America/Montreal, -America/Nassau, -America/New York, -America/Nipigon, America/Panama, -America/Pangnirtung, America/Port-au-Prince, America/Porto Acre, America/Rio Branco, -America/Thunder Bay, Brazil/Acre, -Canada/Eastern, -Cuba, -EST, -EST5EDT, Etc/GMT+5, IET, Jamaica, SystemV/EST5, -SystemV/EST5EDT, US/East-Indiana, -US/Eastern, US/Indiana-Starke, -US/Michigan
-14400 | America/Anguilla, America/Antigua, America/Aruba, -America/Asuncion, America/Barbados, America/Boa Vista, America/Caracas, -America/Cuiaba, America/Curacao, America/Dominica, -America/Glace Bay, -America/Goose Bay, America/Grenada, America/Guadeloupe, America/Guyana, -America/Halifax, America/La Paz, America/Manaus, America/Martinique, America/Montserrat, America/Port of Spain, America/Porto Velho, America/Puerto Rico, -America/Santiago, America/Santo Domingo, America/St Kitts, America/St Lucia, America/St Thomas, America/St Vincent, America/Thule, America/Tortola, America/Virgin, -Antarctica/Palmer, -Atlantic/Bermuda, -Atlantic/Stanley, Brazil/West, -Canada/Atlantic, -Chile/Continental, Etc/GMT+4, PRT, SystemV/AST4, -SystemV/AST4ADT
-12600 | -America/St Johns, -CNT, -Canada/Newfoundland
-10800 | AGT, -America/Araguaina, America/Belem, America/Buenos Aires, America/Catamarca, America/Cayenne, America/Cordoba, -America/Fortaleza, -America/Godthab, America/Jujuy, -America/Maceio, America/Mendoza, -America/Miquelon, America/Montevideo, America/Paramaribo, -America/Recife, America/Rosario, -America/Sao Paulo, -BET, -Brazil/East, Etc/GMT+3
 -7200 | America/Noronha, Atlantic/South Georgia, Brazil/DeNoronha, Etc/GMT+2
 -3600 | -America/Scoresbysund, -Atlantic/Azores, Atlantic/Cape Verde, Etc/GMT+1
     0 | Africa/Abidjan, Africa/Accra, Africa/Bamako, Africa/Banjul, Africa/Bissau, Africa/Casablanca, Africa/Conakry, Africa/Dakar, Africa/El Aaiun, Africa/Freetown, Africa/Lome, Africa/Monrovia, Africa/Nouakchott, Africa/Ouagadougou, Africa/Sao Tome, Africa/Timbuktu, America/Danmarkshavn, -Atlantic/Canary, -Atlantic/Faeroe, -Atlantic/Madeira, Atlantic/Reykjavik, Atlantic/St Helena, -Eire, Etc/GMT, Etc/GMT+0, Etc/GMT-0, Etc/GMT0, Etc/Greenwich, Etc/UCT, Etc/UTC, Etc/Universal, Etc/Zulu, -Europe/Belfast, -Europe/Dublin, -Europe/Lisbon, -Europe/London, -GB, -GB-Eire, GMT, GMT0, Greenwich, Iceland, -Portugal, UCT, UTC, Universal, -WET, Zulu
  3600 | Africa/Algiers, Africa/Bangui, Africa/Brazzaville, -Africa/Ceuta, Africa/Douala, Africa/Kinshasa, Africa/Lagos, Africa/Libreville, Africa/Luanda, Africa/Malabo, Africa/Ndjamena, Africa/Niamey, Africa/Porto-Novo, Africa/Tunis, -Africa/Windhoek, -Arctic/Longyearbyen, -Atlantic/Jan Mayen, -CET, -ECT, Etc/GMT-1, -Europe/Amsterdam, -Europe/Andorra, -Europe/Belgrade, -Europe/Berlin, -Europe/Bratislava, -Europe/Brussels, -Europe/Budapest, -Europe/Copenhagen, -Europe/Gibraltar, -Europe/Ljubljana, -Europe/Luxembourg, -Europe/Madrid, -Europe/Malta, -Europe/Monaco, -Europe/Oslo, -Europe/Paris, -Europe/Prague, -Europe/Rome, -Europe/San Marino, -Europe/Sarajevo, -Europe/Skopje, -Europe/Stockholm, -Europe/Tirane, -Europe/Vaduz, -Europe/Vatican, -Europe/Vienna, -Europe/Warsaw, -Europe/Zagreb, -Europe/Zurich, -MET, -Poland
  7200 | -ART, Africa/Blantyre, Africa/Bujumbura, -Africa/Cairo, Africa/Gaborone, Africa/Harare, Africa/Johannesburg, Africa/Kigali, Africa/Lubumbashi, Africa/Lusaka, Africa/Maputo, Africa/Maseru, Africa/Mbabane, Africa/Tripoli, -Asia/Amman, -Asia/Beirut, -Asia/Damascus, -Asia/Gaza, -Asia/Istanbul, -Asia/Jerusalem, -Asia/Nicosia, -Asia/Tel Aviv, CAT, -EET, -Egypt, Etc/GMT-2, -Europe/Athens, -Europe/Bucharest, -Europe/Chisinau, -Europe/Helsinki, -Europe/Istanbul, -Europe/Kaliningrad, -Europe/Kiev, -Europe/Minsk, -Europe/Nicosia, -Europe/Riga, -Europe/Simferopol, -Europe/Sofia, Europe/Tallinn, -Europe/Tiraspol, -Europe/Uzhgorod, Europe/Vilnius, -Europe/Zaporozhye, -Israel, Libya, -Turkey
 10800 | Africa/Addis Ababa, Africa/Asmera, Africa/Dar es Salaam, Africa/Djibouti, Africa/Kampala, Africa/Khartoum, Africa/Mogadishu, Africa/Nairobi, Antarctica/Syowa, Asia/Aden, -Asia/Baghdad, Asia/Bahrain, Asia/Kuwait, Asia/Qatar, Asia/Riyadh, EAT, Etc/GMT-3, -Europe/Moscow, Indian/Antananarivo, Indian/Comoro, Indian/Mayotte, -W-SU
 11224 | Asia/Riyadh87, Asia/Riyadh88, Asia/Riyadh89, Mideast/Riyadh87, Mideast/Riyadh88, Mideast/Riyadh89
 12600 | -Asia/Tehran, -Iran
 14400 | -Asia/Aqtau, -Asia/Baku, Asia/Dubai, Asia/Muscat, -Asia/Tbilisi, -Asia/Yerevan, Etc/GMT-4, -Europe/Samara, Indian/Mahe, Indian/Mauritius, Indian/Reunion, -NET
 16200 | Asia/Kabul
 18000 | -Asia/Aqtobe, Asia/Ashgabat, Asia/Ashkhabad, -Asia/Bishkek, Asia/Dushanbe, Asia/Karachi, Asia/Samarkand, Asia/Tashkent, -Asia/Yekaterinburg, Etc/GMT-5, Indian/Kerguelen, Indian/Maldives, PLT
 19800 | Asia/Calcutta, IST
 20700 | Asia/Katmandu
 21600 | Antarctica/Mawson, Antarctica/Vostok, -Asia/Almaty, Asia/Colombo, Asia/Dacca, Asia/Dhaka, -Asia/Novosibirsk, -Asia/Omsk, Asia/Thimbu, Asia/Thimphu, BST, Etc/GMT-6, Indian/Chagos
 23400 | Asia/Rangoon, Indian/Cocos
 25200 | Antarctica/Davis, Asia/Bangkok, Asia/Hovd, Asia/Jakarta, -Asia/Krasnoyarsk, Asia/Phnom Penh, Asia/Pontianak, Asia/Saigon, Asia/Vientiane, Etc/GMT-7, Indian/Christmas, VST
 28800 | Antarctica/Casey, Asia/Brunei, Asia/Chongqing, Asia/Chungking, Asia/Harbin, Asia/Hong Kong, -Asia/Irkutsk, Asia/Kashgar, Asia/Kuala Lumpur, Asia/Kuching, Asia/Macao, Asia/Manila, Asia/Shanghai, Asia/Singapore, Asia/Taipei, Asia/Ujung Pandang, Asia/Ulaanbaatar, Asia/Ulan Bator, Asia/Urumqi, Australia/Perth, Australia/West, CTT, Etc/GMT-8, Hongkong, PRC, Singapore
 32400 | Asia/Choibalsan, Asia/Dili, Asia/Jayapura, Asia/Pyongyang, Asia/Seoul, Asia/Tokyo, -Asia/Yakutsk, Etc/GMT-9, JST, Japan, Pacific/Palau, ROK
 34200 | ACT, -Australia/Adelaide, -Australia/Broken Hill, Australia/Darwin, Australia/North, -Australia/South, -Australia/Yancowinna
 36000 | -AET, Antarctica/DumontDUrville, -Asia/Sakhalin, -Asia/Vladivostok, -Australia/ACT, Australia/Brisbane, -Australia/Canberra, -Australia/Hobart, Australia/Lindeman, -Australia/Melbourne, -Australia/NSW, Australia/Queensland, -Australia/Sydney, -Australia/Tasmania, -Australia/Victoria, Etc/GMT-10, Pacific/Guam, Pacific/Port Moresby, Pacific/Saipan, Pacific/Truk, Pacific/Yap
 37800 | -Australia/LHI, -Australia/Lord Howe
 39600 | -Asia/Magadan, Etc/GMT-11, Pacific/Efate, Pacific/Guadalcanal, Pacific/Kosrae, Pacific/Noumea, Pacific/Ponape, SST
 41400 | Pacific/Norfolk
 43200 | -Antarctica/McMurdo, -Antarctica/South Pole, -Asia/Anadyr, -Asia/Kamchatka, Etc/GMT-12, Kwajalein, -NST, -NZ, -Pacific/Auckland, Pacific/Fiji, Pacific/Funafuti, Pacific/Kwajalein, Pacific/Majuro, Pacific/Nauru, Pacific/Tarawa, Pacific/Wake, Pacific/Wallis
 45900 | -NZ-CHAT, -Pacific/Chatham
 46800 | Etc/GMT-13, Pacific/Enderbury, Pacific/Tongatapu
 50400 | Etc/GMT-14, Pacific/Kiritimati', null, 'localize/timezone');

            $this->zones = array();
            foreach (explode("\n", $zones) as $zone){
                list($offset, $places) = explode('|', $zone);
                $offset = intval($offset);
                $places = array_map('trim', array_diff(explode(',', $places.','), array('')));
                foreach ($places as $k=>$place){
                    if($place[0] == '-'){
                        $places[$k] = trim($place,'-');
                        $this->dst_zones[] = $places[$k];
                    }
                }
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
        $dst_zones = $TimeZone->dst_zones;
        $Zones = array();
        foreach ($time_zones as $name => $offset){
            $Zones[] = $TimeZone->create($name, $offset, $time_zones, $dst_zones);
        }
        return $Zones;
    }
}

defined('AK_DEFAULT_TIMEZONE') ? null : define('AK_DEFAULT_TIMEZONE', 'UTC');

function_exists('date_default_timezone_set') ?  date_default_timezone_set(AK_DEFAULT_TIMEZONE) : @putenv('TZ='.AK_DEFAULT_TIMEZONE);

if(!defined('AK_UTC_OFFSET')){
    $_AkCurrentZone = new AkTimeZone();
    $_AkCurrentZone = $_AkCurrentZone->create(AK_DEFAULT_TIMEZONE);
    define('AK_UTC_OFFSET', $_AkCurrentZone->utc_offset);
    unset($_AkCurrentZone);
}
?>