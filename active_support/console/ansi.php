<?php

defined('AK_AVOID_ANSI_CONSOLE_COLORS') || define('AK_AVOID_ANSI_CONSOLE_COLORS', false);

class AkAnsiColor
{
    /**
     * Returns a text escaped with an ANSI code.
     * 
     * By default there are 3 styles defined
     * 
     * * error      \033[41;37;1m%s\033[0m  Red background, White foreground and bold text
     * * info       \033[32;1m%s\033[0m     Green foreground and bold text
     * * comment    \033[33m%s\033[0m       Yellow foreground
     * 
     * You can create more styles by setting the configuration option ansi_styles
     * 
     * <code>
     *  AkConfig::setOption('ansi_styles', array('stylename' => '\033[33m%s\033[0m'))
     * </code>
     * 
     * ANSI codes can be used on console scripts.
     * 
     * It can also be disabled by defining AK_AVOID_ANSI_CONSOLE_COLORS to true.
     * 
     * More details on color codes at http://en.wikipedia.org/wiki/ANSI_escape_code
     *
     * @param unknown_type $text
     * @param unknown_type $style_name
     * @return unknown
     */
    static public function style($text, $style_name, $padding = 5)
    {
        if(!AkAnsiColor::isSupported()){
            return $text;
        }
        
        $styles = array_merge(array(
        'error'     => "\033[41;37;1m%s\033[0m",
        'warning'   => "\033[30;43;1m%s\033[0m",
        'success'   => "\033[42;37;1;8m%s\033[0m",
        'info'      => "\033[32;1m%s\033[0m",
        'comment'   => "\033[33;1m%s\033[0m",
        ), AkConfig::getOption('ansi_styles', array()));

        return isset($styles[$style_name]) ? sprintf($styles[$style_name], $text) : $text;        
    }
    
    static public function isSupported()
    {
        return !(AK_AVOID_ANSI_CONSOLE_COLORS || (AkConfig::getOption('avoid_ansi_console_colors', false)) || !(AK_WIN ? !getenv('ANSICON') : true));
    }
}