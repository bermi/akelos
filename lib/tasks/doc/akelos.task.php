<?php

$doc_db_file = AK_DOCS_DIR.DS.'akelos'.DS.'doc.sqlite';
$db = Ak::db('sqlite://'.urlencode($doc_db_file).'/?persist');

$dir_iterator = new RecursiveDirectoryIterator(AK_ACTION_MAILER_DIR);
$iterator = new RecursiveIteratorIterator($dir_iterator, RecursiveIteratorIterator::SELF_FIRST);
// could use CHILD_FIRST if you so wish

foreach ($iterator as $file) {
    if($file->isFile()){
        echo $file, "\n";
        $original_file = file_get_contents($file);
        $Reflection = new AkReflectionFile($file);
        foreach ($Reflection->getClasses() as $Class) {
            echo  'class: '.$Class->getName()."\n";

            $defined_methods = array(
            'public' => array(),
            'protected' => array(),
            'private' => array(),
            );

            foreach ($Class->getMethods() as $Method){
                $name = $Method->getName();
                $visibility = $Method->getVisibility();
                if($visibility == 'public' && $name[0] == '_' && !in_array($name, array('__construct', '__destruct', '__call', '__get', '__set'))){
                    $visibility = 'protected';
                    $content =  $Method->toString(4, null, array('visibility' => 'protected'));
                }else{
                    if($name[0] != '_' && $visibility != 'public'){
                        $name = ltrim($name, '_');
                        $content = $Method->toString(4, $name);
                    }else{
                        $content = $Method->toString(4);
                    }
                }
                $defined_methods[$visibility][$name] = $content;
                /*
                foreach ($Method->getParams() as $Param){
                echo  '       param: '.$Param."\n";
                }
                */
            }

            $sorted_methods = '';

            foreach ($defined_methods as $visibility => $methods){
                foreach ($methods as $method){
                    echo "\n".$method."\n";
                }
            }
        }
    }
}


