<?php
require_once(AK_LIB_DIR.DS.'AkType.php');

class Test_AkNumber extends AkUnitTest
{
    public function test_constructor_default()
    {
        $now = time();
        $number = new AkNumber(3);
        $val = $number->seconds();
        $val = $val->fromNow();
        $val = $val->getValue();
        $this->assertTrue($now+4>=$val);
        $this->assertTrue($now-1<=$val);
    }
    
    public function test_constructor_magic_string()
    {
        $now = time();
        $number = &AkT(1,'year.from.now');
        $oneyear = 365*24*60*60;
        $this->assertTrue($now+1+$oneyear>=$number);
        $this->assertTrue($now-1+$oneyear<=$number);
    }
    
    
    public function test_time_units()
    {
        $this->assertEqual(20,AkT(20,'seconds'));
        $this->assertEqual(1200,AkT(20,'minutes'));
        $this->assertEqual(72000,AkT(20,'hours'));
        $this->assertEqual(1728000,AkT(20,'days'));
        $this->assertEqual(12096000,AkT(20,'weeks'));
        $this->assertEqual(12096000,AkT(10,'fortnights'));
        $this->assertEqual(51840000,AkT(20,'months'));
        $this->assertEqual(630720000,AkT(20,'years'));
    }
    public function test_byte_units()
    {
        $this->assertEqual(20,AkT(20,'bytes'));
        $this->assertEqual(20480,AkT(20,'kilobytes'));
        $this->assertEqual(20971520,AkT(20,'megabytes'));
        $this->assertEqual(21474836480,AkT(20,'gigabytes'));
        $this->assertEqual(21990232555520,AkT(20,'terabytes'));
        $this->assertEqual(22517998136852480,AkT(20,'petabytes'));
        //$this->assertEqual(1152921504606846976,AkT(1,'exabytes'));
    }
    public function test_years_from_now()
    {
        $now = time();
        $number = &AkT(1,'year.from.now');
        $oneyear = 365*24*60*60;
        $this->assertTrue($now+1+$oneyear>=$number);
        $this->assertTrue($now-1+$oneyear<=$number);
        
        $now = time();
        $number = &AkT(1,'years.from.now');
        $oneyear = 365*24*60*60;
        $this->assertTrue($now+1+$oneyear>=$number);
        $this->assertTrue($now-1+$oneyear<=$number);
        
        $now = time();
        $number = &AkT(2,'years.from.now');
        $oneyear = 365*24*60*60;
        $this->assertTrue($now+1+$oneyear*2>=$number);
        $this->assertTrue($now-1+$oneyear*2<=$number);
    }
    
    public function test_years_ago()
    {
        $now = time();
        $number = &AkT(1,'year.ago');
        $oneyear = 365*24*60*60;
        $this->assertTrue($now+1-$oneyear>=$number);
        $this->assertTrue($now-1-$oneyear<=$number);
        
        $now = time();
        $number = &AkT(1,'years.ago');
        $this->assertTrue($now+1-$oneyear>=$number);
        $this->assertTrue($now-1-$oneyear<=$number);
        
        $now = time();
        $number = &AkT(2,'years.ago');
        $this->assertTrue($now+1-$oneyear*2>=$number);
        $this->assertTrue($now-1-$oneyear*2<=$number);
    }
    
    public function test_months_from_now()
    {
        $now = time();
        $number = &AkT(1,'month.from.now');
        $onemonth = 30*24*60*60;
        $this->assertTrue($now+1+$onemonth>=$number);
        $this->assertTrue($now-1+$onemonth<=$number);
        
        $now = time();
        $number = &AkT(1,'months.from.now');
        $this->assertTrue($now+1+$onemonth>=$number);
        $this->assertTrue($now-1+$onemonth<=$number);
        
        $now = time();
        $number = &AkT(2,'months.from.now');
        $this->assertTrue($now+1+$onemonth*2>=$number);
        $this->assertTrue($now-1+$onemonth*2<=$number);
    }
    
    public function test_months_ago()
    {
        $now = time();
        $number = &AkT(1,'month.ago');
        $onemonth = 30*24*60*60;
        $this->assertTrue($now+1-$onemonth>=$number);
        $this->assertTrue($now-1-$onemonth<=$number);
        
        $now = time();
        $number = &AkT(1,'months.ago');
        $this->assertTrue($now+1-$onemonth>=$number);
        $this->assertTrue($now-1-$onemonth<=$number);
        
        $now = time();
        $number = &AkT(2,'months.ago');
        $this->assertTrue($now+1-$onemonth*2>=$number);
        $this->assertTrue($now-1-$onemonth*2<=$number);
    }
    
    public function test_weeks_from_now()
    {
        $now = time();
        $number = &AkT(1,'week.from.now');
        $oneweek = 7*24*60*60;
        $this->assertTrue($now+1+$oneweek>=$number);
        $this->assertTrue($now-1+$oneweek<=$number);
        
        $now = time();
        $number = &AkT(1,'weeks.from.now');
        $this->assertTrue($now+1+$oneweek>=$number);
        $this->assertTrue($now-1+$oneweek<=$number);
        
        $now = time();
        $number = &AkT(2,'weeks.from.now');
        $this->assertTrue($now+1+$oneweek*2>=$number);
        $this->assertTrue($now-1+$oneweek*2<=$number);
    }
    
    public function test_weeks_ago()
    {
        $now = time();
        $number = &AkT(1,'week.ago');
        $oneweek = 7*24*60*60;
        $this->assertTrue($now+1-$oneweek>=$number);
        $this->assertTrue($now-1-$oneweek<=$number);
        
        $now = time();
        $number = &AkT(1,'weeks.ago');
        $this->assertTrue($now+1-$oneweek>=$number);
        $this->assertTrue($now-1-$oneweek<=$number);
        
        $now = time();
        $number = &AkT(2,'weeks.ago');
        $this->assertTrue($now+1-$oneweek*2>=$number);
        $this->assertTrue($now-1-$oneweek*2<=$number);
    }
    
    public function test_days_from_now()
    {
        $now = time();
        $number = &AkT(1,'day.from.now');
        $oneday = 1*24*60*60;
        $this->assertTrue($now+1+$oneday>=$number);
        $this->assertTrue($now-1+$oneday<=$number);
        
        $now = time();
        $number = &AkT(1,'days.from.now');
        $this->assertTrue($now+1+$oneday>=$number);
        $this->assertTrue($now-1+$oneday<=$number);
        
        $now = time();
        $number = &AkT(2,'days.from.now');
        $this->assertTrue($now+1+$oneday*2>=$number);
        $this->assertTrue($now-1+$oneday*2<=$number);
    }
    
    public function test_days_ago()
    {
        $now = time();
        $number = &AkT(1,'day.ago');
        $oneday = 1*24*60*60;
        $this->assertTrue($now+1-$oneday>=$number);
        $this->assertTrue($now-1-$oneday<=$number);
        
        $now = time();
        $number = &AkT(1,'days.ago');
        $this->assertTrue($now+1-$oneday>=$number);
        $this->assertTrue($now-1-$oneday<=$number);
        
        $now = time();
        $number = &AkT(2,'days.ago');
        $this->assertTrue($now+1-$oneday*2>=$number);
        $this->assertTrue($now-1-$oneday*2<=$number);
    }
    
    public function test_hours_from_now()
    {
        $now = time();
        $number = &AkT(1,'hour.from.now');
        $onehour = 1*60*60;
        $this->assertTrue($now+1+$onehour>=$number);
        $this->assertTrue($now-1+$onehour<=$number);
        
        $now = time();
        $number = &AkT(1,'hours.from.now');
        $this->assertTrue($now+1+$onehour>=$number);
        $this->assertTrue($now-1+$onehour<=$number);
        
        $now = time();
        $number = &AkT(2,'hours.from.now');
        $this->assertTrue($now+1+$onehour*2>=$number);
        $this->assertTrue($now-1+$onehour*2<=$number);
    }
    
    public function test_hours_ago()
    {
        $now = time();
        $number = &AkT(1,'hour.ago');
        $onehour = 1*60*60;
        $this->assertTrue($now+1-$onehour>=$number);
        $this->assertTrue($now-1-$onehour<=$number);
        
        $now = time();
        $number = &AkT(1,'hours.ago');
        $this->assertTrue($now+1-$onehour>=$number);
        $this->assertTrue($now-1-$onehour<=$number);
        
        $now = time();
        $number = &AkT(2,'hours.ago');
        $this->assertTrue($now+1-$onehour*2>=$number);
        $this->assertTrue($now-1-$onehour*2<=$number);
    }
    
    public function test_minutes_from_now()
    {
        $now = time();
        $number = &AkT(1,'minute.from.now');
        $oneminute = 1*60;
        $this->assertTrue($now+1+$oneminute>=$number);
        $this->assertTrue($now-1+$oneminute<=$number);
        
        $now = time();
        $number = &AkT(1,'minutes.from.now');
        $this->assertTrue($now+1+$oneminute>=$number);
        $this->assertTrue($now-1+$oneminute<=$number);
        
        $now = time();
        $number = &AkT(2,'minutes.from.now');
        $this->assertTrue($now+1+$oneminute*2>=$number);
        $this->assertTrue($now-1+$oneminute*2<=$number);
    }
    
    public function test_minutes_ago()
    {
        $now = time();
        $number = &AkT(1,'minute.ago');
        $oneminute = 1*60;
        $this->assertTrue($now+1-$oneminute>=$number);
        $this->assertTrue($now-1-$oneminute<=$number);
        
        $now = time();
        $number = &AkT(1,'minutes.ago');
        $this->assertTrue($now+1-$oneminute>=$number);
        $this->assertTrue($now-1-$oneminute<=$number);
        
        $now = time();
        $number = &AkT(2,'minutes.ago');
        $this->assertTrue($now+1-$oneminute*2>=$number);
        $this->assertTrue($now-1-$oneminute*2<=$number);
    }
    
    public function test_seconds_from_now()
    {
        $now = time();
        $number = &AkT(1,'second.from.now');
        $onesecond = 1;
        $this->assertTrue($now+1+$onesecond>=$number);
        $this->assertTrue($now-1+$onesecond<=$number);
        
        $now = time();
        $number = &AkT(1,'seconds.from.now');
        $this->assertTrue($now+1+$onesecond>=$number);
        $this->assertTrue($now-1+$onesecond<=$number);
        
        $now = time();
        $number = &AkT(2,'seconds.from.now');
        $this->assertTrue($now+1+$onesecond*2>=$number);
        $this->assertTrue($now-1+$onesecond*2<=$number);
    }
    
    public function test_seconds_ago()
    {
        $now = time();
        $number = &AkT(1,'second.ago');
        $onesecond = 1;
        $this->assertTrue($now+1-$onesecond>=$number);
        $this->assertTrue($now-1-$onesecond<=$number);
        
        $now = time();
        $number = &AkT(1,'seconds.ago');
        $this->assertTrue($now+1-$onesecond>=$number);
        $this->assertTrue($now-$onesecond<=$number);
        
        $now = time();
        $number = &AkT(2,'seconds.ago');
        $this->assertTrue($now+1-$onesecond*2>=$number);
        $this->assertTrue($now-1-$onesecond*2<=$number);
    }
    
    public function test_to_date()
    {
        $now = date('Y-m-d H:i:s');
        $date = &AkT(0,'now.toDate');
        $this->assertEqual($now,$date);
    }
    
    public function test_ordinalize()
    {
        $number = &AkT(1);
        $expect = '1st';
        $this->assertEqual($expect,$number->ordinalize());
        $number = &AkT(1,'ordinalize');
        $this->assertEqual($expect,$number);
        
        $number = &AkT(2);
        $expect = '2nd';
        $this->assertEqual($expect,$number->ordinalize());
        $number = &AkT(2,'ordinalize');
        $this->assertEqual($expect,$number);
        
        $number = &AkT(3);
        $expect = '3rd';
        $this->assertEqual($expect,$number->ordinalize());
        $number = &AkT(3,'ordinalize');
        $this->assertEqual($expect,$number);
        
        $number = &AkT(4);
        $expect = '4th';
        $this->assertEqual($expect,$number->ordinalize());
        $number = &AkT(4,'ordinalize');
        $this->assertEqual($expect,$number);
    }
    
    public function test_quantify()
    {
        $number = &AkT(1);
        $expected = '1 Comment';
        $this->assertEqual($expected, $number->quantify('Comment'));
        
        $number = &AkT(0);
        $expected = '0 Comments';
        $this->assertEqual($expected, $number->quantify('Comment'));
        
        $number = &AkT(10);
        $expected = '10 Comments';
        $this->assertEqual($expected, $number->quantify('Comment'));
        
        $quantity = &AkT(10,'quantify(Comment)');
        /**
         * //TODO: add sintags for templates:
         * {(10.days.from.now)} or {(#{comment_count}.quantify(Comment))}
         */
        $expected = '10 Comments';
        $this->assertEqual($expected, $quantity);
    }
    
    public function test_until()
    {
        $now = time();
        $until = $now + AkT(20,'minutes');
        $until_date = date('Y-m-d H:i:s', $until);
        $result_timestamp = AkT(20,'minutes.until('.$until_date.')');
        $this->assertEqual($now,$result_timestamp);
        $result_date = date('Y-m-d H:i:s', $result_timestamp);
    }
    
    public function test_since()
    {
        $now = time();
        $since = $now - AkT(20,'minutes');
        $since_date = date('Y-m-d H:i:s', $since);
        $result_timestamp = AkT(20,'minutes.since('.$since_date.')');
        $this->assertEqual($now,$result_timestamp);
        $result_date = date('Y-m-d H:i:s', $result_timestamp);
    }
}
ak_test('Test_AkNumber');