<?php

include_once(AK_LIB_DIR.DS.'AkLocalize'.DS.'AkTimeZone.php');

class AkTestTime { function now(){} }
ak_generate_mock('AkTestTime');


class TimeZoneTestCase extends AkUnitTest
{
    public function setup()
    {
        $this->MockTime = new MockAkTestTime($this);
        $this->MockTime->setReturnValue('now','Sun Jul 25 14:49:00 UTC 2004');
        $this->timestamp = gmmktime(14,49,00,7,25,2004);
    }

    public function test_should_return_positive_formatted_offset()
    {
        $zone = $this->_createTimeZone("Test", 4200);
        $this->assertEqual("+01:10", $zone->getFormattedOffset());
    }

    public function test_should_return_negative_formatted_offset()
    {
        $zone = $this->_createTimeZone("Test", -4200);
        $this->assertEqual("-01:10", $zone->getFormattedOffset());
    }

    public function test_now()
    {
        $Zone = $this->_createTimeZone("Test", 4200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(gmmktime(15, 59,00,7,25,2004), $Zone->now());
    }

    public function test_now_with_dst_on_winter()
    {
        $Zone = $this->_createTimeZone("Australia/Sydney");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual($Zone->dateTime(), '2004-07-26 00:49:00');
    }

    public function test_now_with_dst_on_summer()
    {
        $Zone = $this->_createTimeZone("Europe/Madrid");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual($Zone->time(), '16:49');
    }

    public function test_now_with_dst_on_summer_on_Canada_Saskatchewan()
    {
        $Zone = $this->_createTimeZone("Canada/Saskatchewan");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual($Zone->dateTime(), '2004-07-25 08:49:00');
    }

    public function test_now_with_dst_on_summer_on_America_Chicago()
    {
        $Zone = $this->_createTimeZone("America/Chicago");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual($Zone->dateTime(), '2004-07-25 09:49:00');
    }

    public function test_today()
    {
        $Zone = $this->_createTimeZone("Test", 43200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(Ak::getDate(mktime(0,0,0,7,26,2004), Ak::locale('date_format')), $Zone->today());
    }

    public function test_should_adjust_negative()
    {
        $Zone = $this->_createTimeZone("Test", -4200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(mktime(23,55,0,7,24,2004), $Zone->adjust(mktime(1,5,0,7,25,2004)));
    }

    public function test_should_adjust_positive()
    {
        $Zone = $this->_createTimeZone("Test", 4200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(mktime(1,5,0,7,26,2004), $Zone->adjust(mktime(23,55,0,7,25,2004)));
    }

    public function test_should_unadjust()
    {
        $Zone = $this->_createTimeZone("Test", 4200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(mktime(23,55,0,7,24,2004), $Zone->unadjust(mktime(1,5,0,7,25,2004)));
    }


    public function test_should_unadjust_with_dst_on_summer()
    {
        $Zone = $this->_createTimeZone("Europe/Madrid");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(mktime(14,49,00,7,25,2004), $Zone->unadjust(mktime(16,49,00,7,25,2004)));
    }


    public function test_should_unadjust_with_dst_on_winter()
    {
        $Zone = $this->_createTimeZone("Australia/Sydney");
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual(mktime(14,49,00,7,25,2004), $Zone->unadjust(mktime(00,49,00,7,26,2004)));
    }

    public function test_should_compare_timezones()
    {
        $Zone1 = $this->_createTimeZone("Test", 4200);
        $Zone2 = $this->_createTimeZone("Test", 5600);
        $Zone1->_timestamp = $Zone2->_timestamp = $this->timestamp;
        $this->assertTrue($Zone1->now() < $Zone2->now());

        $Zone1 = $this->_createTimeZone("Able", 10000);
        $Zone2 = $this->_createTimeZone("Zone", 10000);
        $Zone1->_timestamp = $Zone2->_timestamp = $this->timestamp;
        $this->assertTrue($Zone1->compare($Zone2) == -1);

        $this->assertTrue($Zone1->compare($Zone1) == 0);
    }

    public function test_should_compare_zones_at_the_same_offset_one_of_them_using_dst()
    {
        $Zone1 = $this->_createTimeZone("Africa/Ceuta"); // has dst
        $Zone2 = $this->_createTimeZone("Africa/Malabo");
        $Zone1->_timestamp = $Zone2->_timestamp = $this->timestamp;
        $this->assertTrue($Zone1->now() > $Zone2->now());
        $this->assertTrue($Zone1->compare($Zone2) == -1);
    }

    public function test_to_string()
    {
        $Zone = $this->_createTimeZone("Test", 4200);
        $Zone->_timestamp = $this->timestamp;
        $this->assertEqual("(GMT+01:10) Test", $Zone->toString());
    }

    public function test_should_be_sorted()
    {
        $Zones =& AkTimeZone::all();
        foreach (range(1,count($Zones)-1) as $i){
            $this->assertTrue($Zones[$i-1]->compare($Zones[$i]) == -1);
        }
    }
    
    public function _createTimeZone()
    {
        $args = func_get_args();
        $TimeZone = new AkTimeZone();
        return call_user_func_array(array($TimeZone,'create'), $args);
    }
}

?>