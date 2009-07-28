<?php

class AkRequestMimeType extends AkObject
{
    
    function _lookupMimeType($type = null)
    {
        static $mime_types = array( 
                'text/html'                => 'html', 
                'application/xhtml+xml'    => 'html', 
                'application/xml'          => 'xml', 
                'text/xml'                 => 'xml', 
                'text/javascript'          => 'js', 
                'application/javascript'   => 'js', 
                'application/x-javascript' => 'js', 
                'application/json'         => 'json',
                'text/x-json'              => 'json', 
                'application/rss+xml'      => 'rss', 
                'application/atom+xml'     => 'atom', 
                '*/*'                      => 'html', 
                //'application/x-www-form-urlencoded' => 'www-form', 
                //'application/x-www-form-urlencoded' => 'www-form',
                'default'                  => 'html', 
            );
            
            if ($type === null) {
                return $mime_types;
            } else {
                return isset($mime_types[$type])?$mime_types[$type]:null;
            }
    }
    function getAccepts()
    {
        $accept_header = isset($_SERVER['HTTP_ACCEPT'])?$_SERVER['HTTP_ACCEPT']:'';
        $accepts = array();
        foreach (explode(',',$accept_header) as $index=>$acceptable){ 
                 $mime_struct = AkRequestMimeType::_parseMimeType($acceptable); 
                 if (empty($mime_struct['q'])) $mime_struct['q'] = '1.0'; 
                  
                 //we need the original index inside this structure  
                 //because usort happily rearranges the array on equality 
                 //therefore we first compare the 'q' and then 'i' 
                 $mime_struct['i'] = $index; 
                 $accepts[] = $mime_struct; 
             } 
             usort($accepts,array('AkRequestMimeType','_sortAcceptHeader')); 
              
             //we throw away the old index 
             foreach ($accepts as $array){ 
                 unset($array['i']); 
             } 
             return $accepts; 
    }
    function getMethod()
    {
        return strtolower(isset($_SERVER['REQUEST_METHOD'])?$_SERVER['REQUEST_METHOD']:'get');
    }
    function getFormat($requestPath)
    {
        $method = AkRequestMimeType::getMethod();
        $format = AkRequestMimeType::_lookupMimeType('default');

        if (preg_match('/^([^\.]+)\.([a-zA-z0-9\.]+)$/', $requestPath, $matches)) {
            $format = isset($matches[2])?strtolower($matches[2]):null;
            $orgformat = $format;
            if ($format == 'htm') {
                $format = 'html';
            }
            $requestPath = preg_replace('/^(.*)\.'.$orgformat.'$/','\1',$requestPath);
        } else if ($method=='get' || $method == 'delete') {
            $format = AkRequestMimeType::_bestMimeType();
        } else if ($method=='post' || $method == 'put') {
            $format = AkRequestMimeType::_lookupMimeType(AkRequestMimeType::getContentType());
        }
        
        if (empty($format)) {
            $format = AkRequestMimeType::_lookupMimeType('default');
            
        }
        return array($format, $requestPath);
    }
    
    function _sortAcceptHeader($a,$b) 
    { 
         //preserve the original order if q is equal 
        return $a['q'] == $b['q'] ? ($a['i'] > $b['i']) : ($a['q'] < $b['q']); 
    } 
    function _parseMimeType($mime_type) 
    { 
        @list($type,$parameter_string) = explode(';',$mime_type); 
        $mime_type_struct = array(); 
        if ($parameter_string){ 
            parse_str($parameter_string,$mime_type_struct); 
        } 
        $mime_type_struct['type'] = trim($type); 
        return $mime_type_struct; 
    }
    function _getMimeType($acceptables)
    { 
        // we group by 'quality' 
        $grouped_acceptables = array(); 
        foreach ($acceptables as $acceptable){ 
            $grouped_acceptables[$acceptable['q']][] = $acceptable['type']; 
        } 
         
         
        foreach ($grouped_acceptables as $q=>$array_with_acceptables_of_same_quality){ 
            foreach (AkRequestMimeType::_lookupMimeType() as $mime_type=>$our_mime_type){ 
                foreach ($array_with_acceptables_of_same_quality as $acceptable){ 
                    if ($mime_type == $acceptable){ 
                        return $our_mime_type; 
                    } 
                } 
            } 
        } 
        return AkRequestMimeType::_lookupMimeType('default'); 
    } 
    function getContentType() 
    { 
        if (empty($_SERVER['CONTENT_TYPE'])) return false; 
        $mime_type_struct = AkRequestMimeType::_parseMimeType($_SERVER['CONTENT_TYPE']); 
        return $mime_type_struct['type']; 
    } 
 
    function _bestMimeType() 
    { 
        return AkRequestMimeType::_getMimeType(AkRequestMimeType::getAccepts()); 
    } 
}
?>