<h2 style="margin-top: 30px">Request</h2>
<p><b>Parameters</b>: <?php AkDebug::trace(Ak::delete($Request->getParams(), array(AK_SESSION_NAME, 'ak')), '', '', ''); ?></p>

<p><a href="#" onclick="document.getElementById('session_dump').style.display='block'; return false;">Show session dump</a></p>
<div id="session_dump" style="display:none">
<p>SESSION ID: <?php echo @session_id(); ?></p>
<?php 
AkDebug::trace(array_diff($Request->getSession(), array('')), '', '', ''); 
?></div>

