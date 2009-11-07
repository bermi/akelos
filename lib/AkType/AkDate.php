<?php
/**
 * see http://dev.rubyonrails.org/browser/branches/2-1-caching/activesupport/lib/active_support/core_ext/date/calculations.rb
 * and
 * 
 *
 */
class AkDate extends AkType
{
    function yesterday()
    {
        
    }
    
    function tomorrow()
    {
        
    }
    
    function ago($seconds = 0)
    {
        
    }
    
    function since($seconds = 0)
    {
        
    }
    
    function beginningOfday()
    {
        
    }
    
    function endOfDay()
    {
        
    }
    
    function plusWithDuration($duration)
    {
        
    }
    
    function minusWithDuration($duration)
    {
        
    }
    
    function advance($options = array())
    {
        $available_options = array('day','days',
                                   'week','weeks',
                                   'fortnight','fortnights',
                                   'month','months',
                                   'year','years'
                                   );
    }
    
    function change($options = array())
    {
        $available_options = array('year','month','day');
    }
}
?>