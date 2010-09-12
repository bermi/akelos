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

    static function display($message, $style = null, $padding = 0) {
        echo AkAnsiColor::style($message, $style, $padding);
    }

    static function displayError($message, $fatal = false) {
        AkConsole::display($message, 'error');
        $fatal && die("\n");
    }

    static function displayInfo($message) {
        AkConsole::display($message, 'info');
    }

    static function displaySuccess($message) {
        AkConsole::display($message, 'success');
    }

    static function displayWarning($message) {
        AkConsole::display($message, 'warning');
    }

    static function displayComment($message) {
        AkConsole::display($message, 'comment');
    }
}