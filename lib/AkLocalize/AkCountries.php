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

class AkCountries
{
    function getCountriesDescriptions()
    {
        return explode("\n", Ak::t("ALA|Åland Islands
AFG|Afghanistan
ALB|Albania
DZA|Algeria
ASM|American Samoa
AND|Andorra
AGO|Angola
AIA|Anguilla
ATG|Antigua and Barbuda
ARG|Argentina
ARM|Armenia
ABW|Aruba
AUS|Australia
AUT|Austria
AZE|Azerbaijan
BHS|Bahamas
BHR|Bahrain
BGD|Bangladesh
BRB|Barbados
BLR|Belarus
BEL|Belgium
BLZ|Belize
BEN|Benin
BMU|Bermuda
BTN|Bhutan
BOL|Bolivia
BIH|Bosnia and Herzegovina
BWA|Botswana
BRA|Brazil
VGB|British Virgin Islands
BRN|Brunei Darussalam
BGR|Bulgaria
BFA|Burkina Faso
BDI|Burundi
CIV|Côte d'Ivoire
KHM|Cambodia
CMR|Cameroon
CAN|Canada
CPV|Cape Verde
CYM|Cayman Islands
CAF|Central African Republic
TCD|Chad
CHL|Chile
CHN|China
COL|Colombia
COM|Comoros
COD|Congo, Democratic Republic
COG|Congo
COK|Cook Islands
CRI|Costa Rica
HRV|Croatia
CUB|Cuba
CYP|Cyprus
CZE|Czech Republic
DNK|Denmark
DJI|Djibouti
DOM|Dominican Republic
DMA|Dominica
ECU|Ecuador
EGY|Egypt
SLV|El Salvador
GNQ|Equatorial Guinea
ERI|Eritrea
EST|Estonia
ETH|Ethiopia
FRO|Faeroe Islands
FLK|Falkland Islands (Malvinas)
FJI|Fiji
FIN|Finland
FRA|France
GUF|French Guiana
PYF|French Polynesia
GAB|Gabon
GMB|Gambia
GEO|Georgia
DEU|Germany
GHA|Ghana
GIB|Gibraltar
GRC|Greece
GRL|Greenland
GRD|Grenada
GLP|Guadeloupe
GUM|Guam
GTM|Guatemala
GNB|Guinea-Bissau
GIN|Guinea
GUY|Guyana
HTI|Haiti
HND|Honduras
HKG|Hong Kong
HUN|Hungary
ISL|Iceland
IND|India
IDN|Indonesia
IRN|Iran (Islamic Republic of)
IRQ|Iraq
IRL|Ireland
IOM|Isle of Man
ISR|Israel
ITA|Italy
JAM|Jamaica
JPN|Japan
JOR|Jordan
KAZ|Kazakhstan
KEN|Kenya
KIR|Kiribati
PRK|Korea, Republic of
KOR|Korea, South
KWT|Kuwait
KGZ|Kyrgyzstan
LAO|Lao People's Democratic Republic
LVA|Latvia
LBN|Lebanon
LSO|Lesotho
LBR|Liberia
LBY|Libyan Arab Jamahiriya
LIE|Liechtenstein
LTU|Lithuania
LUX|Luxembourg
MAC|Macao
MKD|Macedonia
MDG|Madagascar
MWI|Malawi
MYS|Malaysia
MDV|Maldives
MLI|Mali
MLT|Malta
MHL|Marshall Islands
MTQ|Martinique
MRT|Mauritania
MUS|Mauritius
MYT|Mayotte
MEX|Mexico
FSM|Micronesia
MCO|Monaco
MNG|Mongolia
MSR|Montserrat
MAR|Morocco
MOZ|Mozambique
MMR|Myanmar
NAM|Namibia
NRU|Nauru
NPL|Nepal
ANT|Netherlands Antilles
NLD|Netherlands
NCL|New Caledonia
NZL|New Zealand
NIC|Nicaragua
NGA|Nigeria
NER|Niger
NIU|Niue
NFK|Norfolk Island
MNP|Northern Mariana Islands
NOR|Norway
PSE|Occupied Palestinian Territory
OMN|Oman
PAK|Pakistan
PLW|Palau
PAN|Panama
PNG|Papua New Guinea
PRY|Paraguay
PER|Peru
PHL|Philippines
PCN|Pitcairn
POL|Poland
PRT|Portugal
PRI|Puerto Rico
QAT|Qatar
REU|Réunion
MDA|Republic of Moldova
ROU|Romania
RUS|Russian Federation
RWA|Rwanda
SHN|Saint Helena
KNA|Saint Kitts and Nevis
LCA|Saint Lucia
SPM|Saint Pierre and Miquelon
VCT|Saint Vincent and the Grenadines
WSM|Samoa
SMR|San Marino
STP|Sao Tome and Principe
SAU|Saudi Arabia
SEN|Senegal
SCG|Serbia and Montenegro
SYC|Seychelles
SLE|Sierra Leone
SGP|Singapore
SVK|Slovakia
SVN|Slovenia
SLB|Solomon Islands
SOM|Somalia
ZAF|South Africa
ESP|Spain
LKA|Sri Lanka
SDN|Sudan
SUR|Suriname
SJM|Svalbard and Jan Mayen Islands
SWZ|Swaziland
SWE|Sweden
CHE|Switzerland
SYR|Syrian Arab Republic
TJK|Tajikistan
TZA|Tanzania
THA|Thailand
TLS|Timor-Leste
TGO|Togo
TKL|Tokelau
TON|Tonga
TTO|Trinidad and Tobago
TUN|Tunisia
TUR|Turkey
TKM|Turkmenistan
TCA|Turks and Caicos Islands
TUV|Tuvalu
UGA|Uganda
UKR|Ukraine
ARE|United Arab Emirates
GBR|United Kingdom
VIR|United States Virgin Islands
USA|United States
URY|Uruguay
UZB|Uzbekista
VUT|Vanuatu
VAT|Vatican City State (Holy See)
VEN|Venezuela
VNM|Viet Nam
WLF|Wallis and Futuna Islands
ESH|Western Sahara
YEM|Yemen
ZMB|Zambia
ZWE|Zimbabwe",array(),'localize/countries'));
    }

    function all()
    {
        $countries_array = array();
        foreach (AkCountries::getCountriesDescriptions() as $country_string){
            list($code,$country) = explode('|',$country_string);
            $countries_array[$country] = $code;
        }
        return $countries_array;
    }
}

?>