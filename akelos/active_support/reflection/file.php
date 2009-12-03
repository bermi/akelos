<?php

class AkReflectionFile extends AkReflection
{
    public
    $filename,
    $classes    =   array(),
    $functions  =   array(),
    $interfaces =   array(),
    $requires   =   array(),
    $includes   =   array();

    public function __construct($file_name)
    {
        $this->filename = $file_name;
        if (file_exists($file_name)) {
            $this->parse(file_get_contents($file_name));
        } else {

        }
        $this->_parseDefinitions();
    }

    public function getClasses($options = null)
    {
        if ($options == null) {
            return $this->classes;
        } else if (is_array($options)) {
            $default_options = array();
            $available_options = array('visibility','tags');
            $parameters = array('available_options'=>$available_options);
            Ak::parseOptions($options,$default_options,$parameters);
            $returnClasses = array();
            foreach ($this->classes as $class) {
                if (isset($options['visibility']) && $class->getVisibility()!=$options['visibility']) {
                    continue;
                }
                if (isset($options['tags'])) {
                    $options['tags']=!is_array($options['tags'])?array($options['tags']):$options['tags'];
                    $docBlock = $method->getDocBlock();
                    foreach($options['tags'] as $tag) {
                        if ($docBlock->getTag($tag)==false) continue;
                    }
                }
                $returnClasses[] = $class;

            }
            return $returnClasses;
        }
    }

    public function getFunctions()
    {
        return $this->functions;
    }
    public function getInterfaces()
    {
        return $this->interfaces;
    }

    public function getIncludes()
    {
        return $this->includes;
    }

    public function getRequires()
    {
        return $this->requires;
    }

    protected function _parseDefinitions()
    {
        foreach($this->definitions as $key=>$definition) {
            if (isset($definition['type'])) {
                switch ($definition['type']) {
                    case 'class':
                        $this->classes[] = new AkReflectionClass($definition);
                        break;
                }
            } else {
                switch ($key) {
                    case 'require_once':
                    case 'require':
                        $this->requires = array_merge($this->requires,$definition);
                        break;
                    case 'include_once':
                    case 'include':
                        $this->includes = array_merge($this->includes,$definition);
                        break;
                }
            }
        }
    }
}
