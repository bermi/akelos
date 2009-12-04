<?php

class SourceAnalyzer
{
    public $dir = AK_FRAMEWORK_DIR;
    public $base_dir = AK_FRAMEWORK_DIR;
    public $db = false;

    public function analyze($file_or_dir)
    {
        $path = $this->base_dir.DS.$file_or_dir;
        if(is_file($path)){
            $this->storeFileForIndexing($file_or_dir);
            $this->indexFile($file_or_dir);
        }elseif (is_dir($path)){
            $this->storeFilesForIndexing($this->base_dir.DS.$file_or_dir);
            $this->indexFiles();
        }else{
            trigger_error('Could not find '.$path);
        }
    }

    public function storeFilesForIndexing($dir = null)
    {
        $files = $this->getSourceFileDetails($dir);
        foreach ($files as $k=>$filename){
            $this->storeFileForIndexing($filename);
        }
    }

    public function storeFileForIndexing($file_path)
    {
        $FileInstance = new File(array('init'=>false));
        if($this->db) $FileInstance->setConnection($this->db);
        $FileInstance->init();

        $file_details = array(
        'path' => $file_path,
        'body' => file_get_contents($this->base_dir.DS.$file_path),
        'hash' => md5_file($this->base_dir.DS.$file_path),
        'has_been_analyzed' => false
        );
        if($SourceFile = $FileInstance->findFirstBy('path', $file_path)){
            if(!$file_details['has_been_analyzed']){
                $this->log('File '.$file_details['path'].' is stored but not indexed.');
            }
            $file_details['has_been_analyzed'] = $SourceFile->hash == $file_details['hash'] && $SourceFile->get('has_been_analyzed');
            if(!$file_details['has_been_analyzed']){
                $this->log('File '.$file_details['path'].' marked for reanalizing');
                $SourceFile->setAttributes($file_details);
                $SourceFile->save();
            }else{
                $this->log('File '.$file_details['path'].' is up to date');
            }

        }else{
            $this->log('Storing file '.$file_details['path']);
            $SourceFile = new File(array('init'=>false));
            if($this->db) $SourceFile->setConnection($this->db);
            $SourceFile->init();
            $SourceFile->setAttributes($file_details);
            $SourceFile->save();
        }
    }

    public function getSourceFileDetails($dir = null)
    {
        static $dir_cache;
        $this->dir = empty($dir) ? $this->dir : $dir;
        if(!isset($dir_cache[$this->dir])){
            $dir_cache[$this->dir] = $this->_transverseDir($this->dir);
        }
        return $dir_cache[$this->dir];
    }


    public function _transverseDir($path)
    {
        $this->log('Transversing directory '.$path);

        $result = array();

        $path = rtrim($path, '/\\');
        if(is_file($path)){
            $result = array($path);
        }elseif(is_dir($path)){
            if ($id_dir = opendir($path)){
                while (false !== ($file = readdir($id_dir))){
                    if ($file != "." && $file != ".." && $file != '.svn'){
                        if(!is_dir($path.DS.$file)){
                            $result[md5_file($path.DS.$file)] = ltrim(str_replace($this->base_dir, '', $path.DS.$file), '/');
                        }else{
                            $result = array_merge($result, $this->_transverseDir($path.DS.$file));
                        }
                    }
                }
                closedir($id_dir);
            }
        }

        return array_reverse($result);
    }

    public function importCategories($categories)
    {
        Ak::import('category');
        $CategoryInstance = new Category(array('init'=>false));
        if($this->db) $CategoryInstance->setConnection($this->db);
        $CategoryInstance->init();
        foreach ($categories as $category_name=>$related){
            if(!$Category = $CategoryInstance->findFirstBy('name', $category_name)){
                $Category = new Category(array('init'=>false));
                if($this->db) $Category->setConnection($this->db);
                $Category->init();
                $Category->setAttributes(array('name'=>$category_name));
                if($Category->save()){
                    $this->log('Created new category: '.$category_name);
                }
            }
            if(!empty($related['relations'])){
                foreach ($related['relations'] as $related_category){
                    if(!$RelatedCategory = $CategoryInstance->findFirstBy('name', $related_category)){
                        $RelatedCategory = new Category(array('init'=>false));
                        if($this->db) $Category->setConnection($this->db);
                        $Category->init();
                        $Category->setAttributes(array('name'=>$related_category));
                        $RelatedCategory->save();
                    }
                    $this->log('Relating category '.$related_category.' with '.$category_name);
                    $Category->related_category->add($RelatedCategory);
                }
            }
        }
    }

    public function getFileDetails($file_contents)
    {
        $parsed = $this->getParsedArray($file_contents);
        return $parsed['details'];
    }

    public function getParsedArray($file_contents)
    {
        static $current;
        $k = md5($file_contents);
        if(!isset($current[$k])){
            $current = array();
            $SourceParser = new SourceParser($file_contents);
            $current[$k] = $SourceParser->parse();
        }
        return $current[$k];
    }

    public function indexFiles()
    {
        $FileInstance = new File(array('init'=>false));
        if($this->db) $FileInstance->setConnection($this->db);
        $FileInstance->init();
        if($UnIndexedPages = $FileInstance->findAllBy('has_been_analyzed', false)){

            $ComponentInstance = new Component(array('init'=>false));
            if($this->db) $ComponentInstance->setConnection($this->db);
            $ComponentInstance->init();
            $ClassInstance = new AkelosClass(array('init'=>false));
            if($this->db) $ClassInstance->setConnection($this->db);
            $ClassInstance->init(array('init'=>false));

            foreach (array_keys($UnIndexedPages) as $k){
                $this->log('Analyzing file '.$UnIndexedPages[$k]->path);

                $Component = $ComponentInstance->updateComponentDetails($UnIndexedPages[$k], $this);
                $Classes = $ClassInstance->updateClassDetails($UnIndexedPages[$k], $Component, $this);

                if(!empty($Classes)){
                    //Ak::debug($Classes);
                }

                $UnIndexedPages[$k]->set('has_been_analyzed', true);
                $UnIndexedPages[$k]->save();
            }
        }
    }

    public function indexFile($file_path)
    {
        $file_path = trim($file_path, '/');
        $FileInstance = new File(array('init'=>false));
        if($this->db) $FileInstance->setConnection($this->db);
        $FileInstance->init();

        if($UnIndexedPage = $FileInstance->findFirstBy('path AND has_been_analyzed', $file_path, false)){
            $ComponentInstance = new Component(array('init'=>false));
            if($this->db) $ComponentInstance->setConnection($this->db);
            $ComponentInstance->init();
            $ClassInstance = new AkelosClass(array('init'=>false));
            if($this->db) $ClassInstance->setConnection($this->db);
            $ClassInstance->init();
            $this->log('Analyzing file '.$UnIndexedPage->path);

            $Component = $ComponentInstance->updateComponentDetails($UnIndexedPage, $this);
            $Classes = $ClassInstance->updateClassDetails($UnIndexedPage, $Component, $this);

            if(!empty($Classes)){
                //Ak::debug($Classes);
            }

            $UnIndexedPage->set('has_been_analyzed', true);
            $UnIndexedPage->save();
        }
    }

    public function log($message)
    {
        echo " $message\n";
    }

}
