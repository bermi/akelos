<?php

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkStringToDouble
{
    public function convert() {
        return doubleval($this->source);
    }
}


