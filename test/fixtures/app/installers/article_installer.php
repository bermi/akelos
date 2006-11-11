<?php

class ArticleInstaller extends AkInstaller
{
    function install()
    {
        $this->createTable('articles', '
        id integer max=10 auto increment primary,
        en_headline string 50,
        es_headline string 50,        
        en_body text,
        es_body text,
        en_excerpt_limit integer,
        es_excerpt_limit integer'
        );
    }

    function uninstall()
    {
        $this->dropTable('articles');
    }
}

?>