<?php
class AkArray extends AkType
{
    function inGroupsOf($number, $filler = null)
    {
        $slices = array();
        $start = 0;
        
        while ($slice = array_slice($this->value,$start,$number)) {
            if (count($slice)<$number) {
                for ($i=count($slice);$i<$number;$i++) {
                    $slice[$i] = $filler;
                }
            }
            $slices[]=new AkArray($slice);
            $start+=$number;
        }
        return $slices;
    }
    
    function toSentence($options = array())
    {
        $default_options = array('skip_last_comma'=>true,'connector'=>'and');
        Ak::parseOptions($options,$default_options);
        $parts = array();
        for($i=0;$i<count($this->value);$i++) {
            $separator = ', ';
            if ($i==0) {
                $separator = '';
            } else if ($i+1==count($this->value)) {
                $separator = $options['skip_last_comma']?' ':', ';
                $separator.= $options['connector'].' ';
            }
            
            $parts[]=$separator.$this->value[$i];
        }
        return implode('',$parts);
    }
    
    function size()
    {
        return count($this->value);
    }
}
?>