<p><code>Akelos <?php echo AKELOS_VERSION;?> root: <?php echo AK_FRAMEWORK_DIR;?></code></p>

<div id="traces">

<h2>Trace</h2>
<h4>in <?php echo $Exception->getFile(); ?> line <?php echo $Exception->getLine(); ?></h4>

<a href="#" onclick="document.getElementById('Full-Trace').style.display='none';document.getElementById('Application-Trace').style.display='block';; return false;">Application Trace</a> |
<a href="#" onclick="document.getElementById('Application-Trace').style.display='none';document.getElementById('Full-Trace').style.display='block';; return false;">Full Trace</a> 
<div id="Application-Trace" style="display: block; background:#eee;padding:10px;margin-top:10px;">
<?php 
$result = '';
$line = $Exception->getLine();
foreach (ak_get_application_included_files($Exception->getFile()) as $type => $files){
    $result .= "<h4>$type</h4>";
    $result .= "<ul>";
    foreach ($files as $k => $file){
        $result .= "<li style='margin:0;padding:0;'>".
                (empty($file['original_path'])? ($file['path']) : ('<strong>'.$file['path'].'</strong>').
                " <a href='#".md5($file['original_path']).'-'.$line."' onclick='element_$k = document.getElementById(\"ak_debug_$k\"); element_$k.style.display = (element_$k.style.display == \"none\"?\"block\":\"none\");'>show source</a>
                <div id='ak_debug_$k' style='display:none;'>".ak_highlight_file($file['original_path'], $line)."</div><div style='clear:both;'></div>").
        "</li>";
    }
    $result .= "</ul><div style='clear:both;'></div>";
}
echo $result;
?>
</div>
  
<div id="Full-Trace" style="display: none;">
    <pre><code>
<?php echo $Exception->getTraceAsString(); ?>
    </code></pre>
</div>
  
</div>




