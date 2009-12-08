<?php

defined('AK_RECODE_UTF8_ON_CONSOLE_TO') || define('AK_RECODE_UTF8_ON_CONSOLE_TO', false);
define('AK_PROMT',fopen("php://stdin","r"));

$join_command = false;
$promt_line = ">>> ";

while(true){
    if(empty($__promt_for_command)){
        $__promt_for_command = true;
        echo "\nWelcome to the Akelos Framework Interactive Console\n\n>> ";
    }

    $command = ($join_command ? $command : '').fgets(AK_PROMT,25600);

    if(substr(trim($command,"\n\r "), -1) == '\\'){
        $command = rtrim($command, "\\\n\r");
        $join_command = true;
        echo "... ";
        continue;
    }else{
        $join_command = false;
    }

    switch (trim(strtolower($command),"\n\r\t ();")) {
        case 'exit':
        case 'die':
        fclose(AK_PROMT);
        exit;
        break;

        case '':
        echo "... ";
        break;

        case '<':
        $command = $last_command;
        echo "running command: ".$command;

        default:

        $last_command = $command;

        $_script_name = array_shift(explode(' ',trim($command).' '));

        $_script_file_name = AK_WIN ? $_script_name : AK_SCRIPT_DIR.DS.$_script_name;

        if (file_exists($_script_file_name)){

            $command = trim(substr(trim($command),strlen($_script_name)));
                echo "\n";
                passthru((AK_WIN ? 'php -q ':'').$_script_file_name.' '.escapeshellcmd($command));
                echo "\n>>> ";

        }else{

            ob_start();
            eval($command);
            $result = ob_get_contents();
            ob_end_clean();

            $result = strstr($result,": eval()") ?
            strip_tags(array_shift(explode(': eval()',$result))) :
            $result;

            Ak::file_add_contents(AK_LOG_DIR.DS.'command_line.log',$promt_line.$command."\n".$result."\n");
            echo empty($result) ? $promt_line : "\n".
            (AK_RECODE_UTF8_ON_CONSOLE_TO ? Ak::recode($result, AK_RECODE_UTF8_ON_CONSOLE_TO) : $result).
            "\n\n$promt_line";
        }
        break;
    }
}

fclose(AK_PROMT);

