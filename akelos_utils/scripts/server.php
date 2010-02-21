<?php

defined('DS')           || define('DS', DIRECTORY_SEPARATOR);
defined('AK_BASE_DIR')  || define('AK_BASE_DIR', str_replace(DS.'akelos'.DS.'active_support'.DS.'utils'.DS.'scripts'.DS.'server.php','',__FILE__));

$_app_config_file = AK_BASE_DIR.DS.'config'.DS.'config.php';

$public_dir = AkConfig::getDir('public');

include_once(AK_CONTRIB_DIR.DS.'appserver-in-php'.DS.'autoload.php');


class AkAppServerFunctionHandler{
    public function __call($method, $args){
    }

    public function puts($content){
        global $_puts;
        $_puts .= $content;
    }

    public function header($string, $replace = null, $http_response_code = null){
        global $_headers, $_status;
        if(strstr($string,':')){
            $parts = explode(':', $string, 2);
            if(preg_match('/^[a-zA-Z\- ]+$/', $parts[0])){
                $_headers[] = ucfirst(strtolower($parts[0]));
                $_headers[] = $parts[1];
            }
        }elseif (preg_match('/^HTTP\/1\.1 (\d+)$/', $string, $matches)){
            $string = (int)$matches[1];
        }
    }
}

Ak::setStaticVar('AppServer.SessionHandler',    new AkAppServerFunctionHandler());
Ak::setStaticVar('AppServer.HeadersHandler',    new AkAppServerFunctionHandler());
Ak::setStaticVar('AppServer.PutsHandler',       new AkAppServerFunctionHandler());

$_headers = array();
$_puts = '';
$_status = 200;
$counter = 0;

$app = new \MFS\AppServer\Middleware\URLMap\URLMap(array( '/' => function($context = null){
    global $counter, $_headers, $_puts, $_status;
    $counter++;
    $_puts = '';
    $_status = 200;

    //AkConfig::setOption('Request.remote_ip', AK_REMOTE_IP);

    ob_start();
    $_headers = array('Server','Akelos (via AppServer)');
    $Dispatcher = new AkDispatcher();
    $Response = $Dispatcher->dispatchAppServer($context);

    if(count($_headers) == 2){
        foreach((array)$Response->getHeaders() as $k => $v){
            $_headers[] = $k;
            $_headers[] = $v;
        }
    }

    $extra_content = ob_get_clean();

    return array($_status, $_headers, (empty($_puts) && is_string($Response->body) ? $Response->body : $_puts).$extra_content);
},
));

echo "Akelos dev server listening at 127.0.0.1:".$_SERVER['SERVER_PORT']."\n\n";
$handler = new \MFS\AppServer\DaemonicHandler('tcp://127.0.0.1:'.$_SERVER['SERVER_PORT'], 'HTTP');

// serving app
$handler->serve($app);


echo "\nBye!\n";