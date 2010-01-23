<?php

$_ht = file_get_contents(AK_BASE_DIR.'/test/fixtures/public/.htaccess');
if(strstr('# RewriteBase /test/fixtures/public',$_ht)){
    $_ht = str_replace('# RewriteBase /test/fixtures/public','RewriteBase ${rewrite.url}',$_ht);
    $fh = fopen(AK_BASE_DIR.'/test/fixtures/public/.htaccess', 'w+');
    fwrite($fh, $_ht);
    fclose($fh);
}

@unlink(AK_BASE_DIR.'/test/.htaccess'); // This .htacces is to protect test/ from being accesed 


?>