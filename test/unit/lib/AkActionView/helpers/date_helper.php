<?php

require_once('_HelpersUnitTester.php');
require_once(AK_LIB_DIR.DS.'AkActionView'.DS.'helpers'.DS.'date_helper.php');

class DateHelperTests extends HelpersUnitTester 
{    
    function setUp()
    {
        $this->Person = &new MockAkActiveRecord($this);
        $this->Person->setReturnValue('get', '1978-06-16 04:37:00', array('date'));
        $this->date_helper = new DateHelper(array('person'=>&$this->Person));
        $this->date = '1978-06-16 12:20:30';
    }

    function test_distance_of_time_in_words()
    {
        $this->assertEqual(DateHelper::distance_of_time_in_words('1978-06-16','2006-01-18'), '10078 days');
        $this->assertEqual(DateHelper::distance_of_time_in_words('1779-12-01','1780-01-01'), '31 days');
        $this->assertEqual(DateHelper::distance_of_time_in_words('1779-12-01','1780-01-01 17:18:53', true), '32 days');
        $this->assertEqual(DateHelper::distance_of_time_in_words('1780-01-01 17:18:14', '1780-01-01 17:18:53' , true), 'half a minute');
        $this->assertEqual(DateHelper::distance_of_time_in_words('1780-01-01 17:18:42', '1780-01-01 17:18:53' , true), 'less than 20 seconds');
        $this->assertEqual(DateHelper::distance_of_time_in_words('1780-01-01 17:18:14', '1780-01-01 17:18:53'), '1 minute');

        /**
         * According to the Gregorian calendar, which is the civil calendar in use today, 
         * years evenly divisible by 4 are leap years, with the exception of centurial years 
         * that are not evenly divisible by 400. Therefore, the years 1700, 1800, 1900 and 2100 
         * are not leap years, but 1600, 2000, and 2400 are leap years.
        */
        $this->assertEqual(DateHelper::distance_of_time_in_words('2100-02-01', '2100-03-01'), '28 days');
        $this->assertEqual(DateHelper::distance_of_time_in_words('2096-02-01', '2096-03-01'), '29 days');
        $this->assertEqual(DateHelper::distance_of_time_in_words('2000-02-01', '2000-03-01'), '29 days');

        $this->assertEqual(DateHelper::distance_of_time_in_words('2100-02-01 01:00:00', '2100-02-01 02:50:00'), 'about 2 hours');
        $this->assertEqual(DateHelper::distance_of_time_in_words('2100-02-01 01:00:00', '2100-02-01 02:50:00'), 'about 2 hours');
    }

    function test_time_ago_in_words()
    {
        $this->assertEqual(DateHelper::time_ago_in_words('2000-02-01 01:00:00'), DateHelper::distance_of_time_in_words('2000-02-01 01:00:00', Ak::time()));
    }

    function test_distance_of_time_in_words_to_now()
    {
        $this->assertEqual(DateHelper::distance_of_time_in_words_to_now('2000-02-01 01:00:00'), DateHelper::distance_of_time_in_words('2000-02-01 01:00:00', Ak::time()));
    }

    function test_date_select()
    {
        $this->assertEqual($this->date_helper->date_select('person', 'date'), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_date_select_1.txt'));
        $this->assertEqual($this->date_helper->date_select('person','date',array('include_blank'=>true,'discard_day'=>true,'order'=>array('month','year'))), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_date_select_2.txt'));
    }

    function test_datetime_select()
    {
        $this->assertEqual($this->date_helper->datetime_select('person', 'date'), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_date_select_3.txt'));
    }

    function test_select_date()
    {
        $this->assertEqual(DateHelper::select_date($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_date.txt'));
    }

    function test_select_datetime()
    {
        $this->assertEqual(DateHelper::select_datetime($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_datetime.txt'));
        $this->assertEqual(DateHelper::select_datetime($this->date, array('include_seconds' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_datetime_seconds.txt'));
    }

    function test_select_time()
    {
        $this->assertEqual(DateHelper::select_time($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_time.txt'));
        $this->assertEqual(DateHelper::select_time($this->date, array('include_seconds' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_time_seconds.txt'));
    }

    function test_select_second()
    {
        $this->assertEqual(DateHelper::select_second($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_second.txt'));
    }

    function test_select_minute()
    {
        $this->assertEqual(DateHelper::select_minute($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_minute.txt'));
    }

    function test_select_hour()
    {
        $this->assertEqual(DateHelper::select_hour($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_hour.txt'));
    }

    function test_select_day()
    {
        $this->assertEqual(DateHelper::select_day($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_day.txt'));
    }

    function test_select_month()
    {
        $this->assertEqual(DateHelper::select_month($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_month.txt'));
        $this->assertEqual(DateHelper::select_month($this->date, array('use_month_numbers' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_month_numbers.txt'));
        $this->assertEqual(DateHelper::select_month($this->date, array('add_month_numbers' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_month_add_numbers.txt'));
        $this->assertEqual(DateHelper::select_month($this->date, array('add_month_numbers' => true, 'use_short_month' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_month_add_numbers_short_month.txt'));
        $this->assertEqual(DateHelper::select_month($this->date, array('use_short_month' => true)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_month_short_month.txt'));
    }

    function test_select_year()
    {
        $this->assertEqual(DateHelper::select_year($this->date), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_year.txt'));
        $this->assertEqual(DateHelper::select_year($this->date, array('start_year' => 1950, 'end_year' => 2010)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_year_options.txt'));
        $this->assertEqual(DateHelper::select_year($this->date, array('start_year' => 2010, 'end_year' => 1950)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_year_options_reverse.txt'));
        $this->assertEqual(DateHelper::select_year($this->date, array('start_year' => 1980, 'end_year' => 1990)), file_get_contents(AK_TEST_HELPERS_DIR.DS.'date_helper_select_year_options_out_of_range.txt'));
    }

    function test_locale_date_time()
    {
        $this->assertEqual(DateHelper::locale_date_time($this->date), '1978-06-16 12:20:30');
        $this->assertEqual(DateHelper::locale_date($this->date), '1978-06-16');
    }
}

ak_test('DateHelperTests');

?>