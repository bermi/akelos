<?php

include_once AK_ACTIVE_SUPPORT_DIR.DS.'error_handlers'.DS.'error_functions.php';

# This file is part of the Akelos Framework
# (Copyright) 2004-2010 Bermi Ferrer bermi a t bermilabs com
# See LICENSE and CREDITS for details

class AkExceptionDispatcher
{
    private $rescue_templates = array(
    'NoMatchingRouteException'              => 'routing_error',
    'UnknownActionException'                => 'unknown_action',
    'ForbiddenActionException'              => 'unknown_action',
    'Exception'                             => 'exception',
    'DispatchException'                     => 'exception',
    'MissingTemplateException'              => 'missing_template',
    'ControllerException'                   => 'exception',
    'CookieOverflowException'               => 'exception',
    );
    
    public function __construct($consider_all_requests_local = false){
        $this->consider_all_requests_local = $consider_all_requests_local;
        $this->Request = new AkRequest();
    }

    public function renderException($Exception){
        $this->logError($Exception);

        if($this->consider_all_requests_local || $this->isLocalRequest()){
            $this->rescueActionLocally($Exception);
        }else{
            $this->rescueActionInPublic($Exception);
        }

    }

    /**
    * Render detailed diagnostics for unhandled exceptions rescued from
    *  a controller action.
    */
    public function rescueActionLocally($Exception) {
        $exception_class_name = get_class($Exception);
        if(!isset($this->rescue_templates[$exception_class_name])){
            AkError::handle($Exception);
        }

        AkConfig::rebaseApp(AK_ACTION_PACK_DIR.DS.'rescues');
        $Template = new AkActionView();
        $Template->registerTemplateHandler('tpl','AkPhpTemplateHandler');
        $Template->Request = $this->Request;
        $file = $this->rescue_templates[$exception_class_name];
        $body = $Template->render(array('file' => $file, 'layout' => 'layouts/exception', 'locals' => array('Exception'=>$Exception, 'Template' => $Template, 'Request' => $this->Request)));
        AkConfig::leaveBase();

        $this->render($this->getStatusCode($Exception), $body);
    }

    /**
    * Attempts to render a static error page based on the
    * <tt>$this->getStatusCode</tt> thrown, or just return headers if no such file
    * exists. At first, it will try to render a localized static page.
    * For example, if a 500 error is being handled Rails and locale is :da,
    * it will first attempt to render the file at <tt>public/500.da.html</tt>
    * then attempt to render <tt>public/500.html</tt>. If none of them exist,
    * the body of the response will be left empty.
    */
    public function rescueActionInPublic($Exception){
        $status = $this->getStatusCode($Exception);
        $locale_path = AkConfig::getDir('public').'/'.$status.'_'.Ak::lang().'.html';
        $path = AkConfig::getDir('public').'/'.$status.'.html';

        if(file_exists($locale_path)){
            $this->render($status, file_get_contents($locale_path));
        }elseif(file_exists($path)){
            $this->render($status, file_get_contents($path));
        }else{
            $this->render($status, '');
        }
    }

    /**
    * True if the Request came from localhost, 127.0.0.1.
    */
    public function isLocalRequest(){
        return $this->Request->isLocal();
    }

    public function getStatusCode($Exception){
        return isset($Exception->status) ? $Exception->status : 404;
    }

    public function render($status, $body){
        $Response = new AkResponse();
        $Response->addHeader(array('Status' => $status, 'Content-Type' => 'text/html'));
        $Response->body = $body;
        $Response->outputResults();
    }

    public function logError($Exception){
        if(!$Logger = Ak::getLogger()){
            return;
        }
        $message = $Exception->getMessage();

        $original_faltal_setting    = AkConfig::getOption('logger.exit_on_fatal',   true);
        $original_display_setting   = AkConfig::getOption('logger.display_message', true);
        $original_mail_setting      = AkConfig::getOption('logger.send_mails',      true);
        AkConfig::setOption('logger.exit_on_fatal',     false);
        AkConfig::setOption('logger.display_message',   false);
        AkConfig::setOption('logger.send_mails',        false);

        $Logger->fatal("\n".get_class($Exception).(empty($message)?'':': ('.$message.")")."\n  ");
        AkConfig::setOption('logger.exit_on_fatal',     $original_faltal_setting);
        AkConfig::setOption('logger.display_message',   $original_display_setting);
        AkConfig::setOption('logger.send_mails',        $original_mail_setting);
    }
}

