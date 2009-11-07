<?php
class AkTime extends AkType
{
    function toString($date_format = '')
    {
        if (!empty($date_format)) {
            $format = $date_format.'_';
        } else {
            $format = '';
        }
        $format = Ak::locale($format.'date_time_format');
        if (!$format) {
            $format = Ak::locale('date_time_format');
        }
        return date($format, $this->value);
    }
    
    function ago($val)
    {
        
    }
    function atBeginningOfDay()
    {
        
    }
    function atBeginningOfWeek()
    {
        
    }
    function atBeginningOfQuarter()
    {
        
    }
    function atBeginningOfMonth()
    {
        
    }
    
    function atBeginningOfYear()
    {
        
    }
    
    function atMidnight()
    {
        
    }
    
    function strftime($format)
    {
        return strftime($format, $this->value);
    }
}
?>