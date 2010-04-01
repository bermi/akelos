<?php

class AkConsole
{
    /**
     * Promts for a variable on console scripts
     */
    static function promptUserVar($message, $options = array()) {
        $f = fopen("php://stdin","r");
        $default_options = array(
        'default' => null,
        'optional' => false,
        );
        $options = is_string($options) ? array('default' => $options) : $options;
        $options = array_merge($default_options, $options);

        echo "\n".$message.(empty($options['default'])?'': ' ['.$options['default'].']').': ';
        $user_input = fgets($f, 25600);
        $value = trim($user_input,"\n\r\t ");
        $value = empty($value) ? $options['default'] : $value;
        if(empty($value) && empty($options['optional'])){
            echo "\n\nThis setting is not optional.";
            fclose($f);
            return AkConsole::promptUserVar($message, $options);
        }
        fclose($f);
        return empty($value) ? $options['default'] : $value;
    }
}