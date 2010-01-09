<?php

class AkMimeType
{
    static function getRegistered() {
        // we register a dictionary: mime_type => our_type
        // this is an ordered list, the first entry has top priority
        // say a client accepts different mime_types with the same 'q'uality-factor
        // we then won't just pop his first one, but our best match within this group
        // http://www.iana.org/assignments/media-types/

        $mime_types = AkConfig::getOption('mime_types', array(
        'text/html'                         => 'html',
        'application/xhtml+xml'             => 'html',
        'application/xml'                   => 'xml',
        'text/xml'                          => 'xml',
        'text/plain'                        => 'txt',
        'text/css'                          => 'css',
        'text/calendar'                     => 'ics',
        'text/csv'                          => 'csv',
        'text/javascript'                   => 'js',
        'application/javascript'            => 'js',
        'application/x-javascript'          => 'js',
        'application/json'                  => 'json',
        'text/x-json'                       => 'json',
        'application/jsonrequest'           => 'json',
        'application/rss+xml'               => 'rss',
        'application/atom+xml'              => 'atom',
        'application/x-yaml'                => 'yaml',
        'text/yaml'                         => 'yaml',
        '*/*'                               => 'html',
        'application/x-www-form-urlencoded' => 'html',
        'multipart/form-data'               => 'html',
        'default'                           => 'html',
        ));

        return $mime_types;
    }
    
    static function register($content_type, $extension){
        $mime_types = AkMimeType::getRegistered();
        $mime_types[$content_type] = $extension;
        AkConfig::setOption('_core_mime_types', $mime_types);
    }

    static function unregister($content_type){
        $mime_types = AkMimeType::getRegistered();
        unset($mime_types[$content_type]);
        AkConfig::setOption('_core_mime_types', $mime_types);
    }

    static function isFormatRegistered($format){
        return in_array($format, AkMimeType::getRegistered());
    }

    static function lookupMimeType($type = null) {
        $mime_types = AkMimeType::getRegistered();

        if ($type === null) {
            return $mime_types;
        } else {
            return isset($mime_types[$type])?$mime_types[$type]:null;
        }
    }


    static function getAccepts() {
        $accept_header = isset($_SERVER['HTTP_ACCEPT'])?$_SERVER['HTTP_ACCEPT']:'';
        $accepts = array();
        foreach (explode(',',$accept_header) as $index=>$acceptable){
            $mime_struct = AkMimeType::parseMimeType($acceptable);
            if (empty($mime_struct['q'])) $mime_struct['q'] = '1.0';

            //we need the original index inside this structure
            //because usort happily rearranges the array on equality
            //therefore we first compare the 'q' and then 'i'
            $mime_struct['i'] = $index;
            $accepts[] = $mime_struct;
        }
        usort($accepts,array('AkMimeType','sortAcceptHeader'));

        //we throw away the old index
        foreach ($accepts as $array){
            unset($array['i']);
        }
        return $accepts;
    }

    static function getMethod() {
        return strtolower(isset($_SERVER['REQUEST_METHOD']) ? $_SERVER['REQUEST_METHOD'] : 'get');
    }

    static function getFormat($requestPath) {
        $method = AkMimeType::getMethod();
        $format = AkMimeType::lookupMimeType('default');

        if (!empty($format) && preg_match('/^([^\.]+)\.([a-zA-z0-9\.]+)$/', $requestPath, $matches)) {
            $format = isset($matches[2])?strtolower($matches[2]):null;
            $orgformat = $format;
            if ($format == 'htm') {
                $format = 'html';
            }
            $requestPath = preg_replace('/^(.*)\.'.$orgformat.'$/','\1',$requestPath);
        } else if ($method=='get' || $method == 'delete') {
            $format = AkMimeType::bestMimeType();
        } else if ($method=='post' || $method == 'put') {
            $format = AkMimeType::lookupMimeType(AkMimeType::getContentType());
        }

        if (empty($format)) {
            $format = AkMimeType::lookupMimeType('default');

        }
        return array($format, $requestPath);
    }

    static function sortAcceptHeader($a, $b) {
        //preserve the original order if q is equal
        return $a['q'] == $b['q'] ? ($a['i'] > $b['i']) : ($a['q'] < $b['q']);
    }

    static function parseMimeType($mime_type) {
        @list($type,$parameter_string) = explode(';',$mime_type,2);
        $mime_type_struct = array();
        if ($parameter_string){
            foreach (explode(';',$parameter_string) as $parameter){
                if (strstr($parameter,'=')){
                    list($key,$value) = explode('=',$parameter);
                    $mime_type_struct[$key] = $value;
                }else{
                    $mime_type_struct[] = $parameter;
                }
            }
        }
        $mime_type_struct['type'] = trim($type);
        return $mime_type_struct;
    }
    
    static function getMimeType($acceptables) {
        // we group by 'quality'
        $grouped_acceptables = array();
        foreach ($acceptables as $acceptable){
            $grouped_acceptables[$acceptable['q']][] = $acceptable['type'];
        }

        foreach ($grouped_acceptables as $q=>$array_with_acceptables_of_same_quality){
            foreach (AkMimeType::getRegistered() as $mime_type=>$our_mime_type){
                foreach ($array_with_acceptables_of_same_quality as $acceptable){
                    if ($mime_type == $acceptable){
                        return $our_mime_type;
                    }
                }
            }
        }
        return AkMimeType::lookupMimeType('default');
    }

    static function getContentType() {
        if (empty($_SERVER['CONTENT_TYPE'])) return false;
        $mime_type_struct = AkMimeType::parseMimeType($_SERVER['CONTENT_TYPE']);
        return $mime_type_struct['type'];
    }

    static function bestMimeType() {
        return AkMimeType::getMimeType(AkMimeType::getAccepts());
    }
}

