<?php

class AkMailPart extends AkMailBase
{


    public function prepareHeadersForRendering($options = array())
    {
        if($this->isMultipart()){
            $this->filterHeaders(array('only' => array('Content-Type')));
        }else{
            $this->filterHeaders();
        }
    }

    public function filterHeaders($options = array())
    {
        $default_options = array(

        'only' => empty($options['include']) ? array(
        'Content-Type',
        'Content-Transfer-Encoding',
        'Content-Id',
        'Content-Disposition',
        'Content-Description',

        ) : array()
        );

        $options = array_merge($default_options, $options);

        if(!empty($options['only'])){
            $headers = $this->getHeaders();
            $this->headers = array();
            foreach ($options['only'] as $allowed_header){
                if(isset($headers[$allowed_header])){
                    $this->headers[$allowed_header] = $headers[$allowed_header];
                }
            }
        }elseif (!empty($options['except'])){
            $this->headers = array();
            foreach ($options['except'] as $skip_header){
                unset($this->headers[$skip_header]);
            }
        }
    }
}

?>