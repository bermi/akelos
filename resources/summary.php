<html>
        <head>
            <title>Akelos Test Results</title>
    <style type="text/css">
    body {
        font-family: verdana,arial,helvetica;
        color:#000000;
        font-size: 12px;
    }
    table tr td, table tr th {
        font-family: verdana,arial,helvetica;
        font-size: 12px;
    }
    table.details tr th{
        font-family: verdana,arial,helvetica;
        font-weight: bold;
        text-align:left;
        background:#a6caf0;
    }
    table.details tr td{
        background:#eeeee0;
    }

    p {
        line-height:1.5em;
        margin-top:0.5em; margin-bottom:1.0em;
        font-size: 12px;
    }
    h1 {
        margin: 0px 0px 5px;
        font-family: verdana,arial,helvetica;
    }
    h2 {
        margin-top: 1em; margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h3 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h4 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h5 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    h6 {
        margin-bottom: 0.5em;
        font-family: verdana,arial,helvetica;
    }
    .Error {
        font-weight:bold; color:red;
    }
    .Failure {
        font-weight:bold; color:purple;
    }
    .small {
       font-size: 9px;
    }
    a {
      color: #003399;
    }
    a:hover {
      color: #888888;
    }
    </style>
        </head>
<body>
<h1>Akelos Test Results - <?php echo date('Y-m-d H:i:s'); ?></h1>
<hr size="1"/>
<table class="details" width="95%">
<thead>
<tr>
<th>PHP Version</th>
<th>Backend</th>
<th>Tests</th>
<th>Failures</th>
<th>Errors</th>
<th>Time</th>
<th>Details</th>
</tr>
</thead>
<?php

foreach ($environments as $environment) {
    ?>
<tr<?php echo !empty($environment['class'])?' class="'.$environment['class'].'"':''; ?>>
<td><?php echo $environment['php']; ?></td>
<td><?php echo $environment['backend']; ?></td>
<td><?php echo $environment['tests']; ?></td>
<td><?php echo $environment['failures']; ?></td>
<td><?php echo $environment['errors']; ?></td>
<td><?php echo $environment['time']; ?></td>
<td><a href="<?php echo $environment['details']; ?>">Details</a></td>
</tr>
    
    <?php
}
?>
</table>

</body>
</html>