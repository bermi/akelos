<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

/**
 * see http://dev.rubyonrails.org/browser/branches/2-1-caching/activesupport/lib/active_support/core_ext/date/calculations.rb
 * 
 * @todo Implement all methods
 */
class AkDate extends AkType
{
    public function yesterday() {

    }

    public function tomorrow() {

    }

    public function ago($seconds = 0) {

    }

    public function since($seconds = 0) {

    }

    public function beginningOfday() {

    }

    public function endOfDay() {

    }

    public function plusWithDuration($duration) {

    }

    public function minusWithDuration($duration) {

    }

    public function advance($options = array()) {
        $available_options = array('day','days',
        'week','weeks',
        'fortnight','fortnights',
        'month','months',
        'year','years'
        );
    }

    public function change($options = array()) {
        $available_options = array('year','month','day');
    }
}
