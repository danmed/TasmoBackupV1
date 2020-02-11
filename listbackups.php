<!DOCTYPE html>
<?php
require "functions.inc.php";

global $db_handle;
global $settings;

if (isset($_POST["name"])) {
    $name = $_POST["name"];
}
if (isset($_POST["id"])) {
    $id = intval($_POST["id"]);
}
if (isset($_POST["delbackup"])) {
	$show_modal = true;
    	$output = '<center>'.dbBackupDel($id).'<br></center>';
}

?>
<html lang="en">
<head>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-116906-4"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-116906-4');
</script>

<title>TasmoBackup</title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <link rel="stylesheet" href="resources/bootstrap.min.css">
  <script src="resources/jquery.min.js"></script>
  <script src="resources/bootstrap.min.js"></script>
  <link rel="stylesheet" type="text/css" href="resources/datatables.min.css"/>
  <script type="text/javascript" src="resources/datatables.min.js"></script>
<script type="text/javascript" class="init">
$(document).ready(function() {
        $('#status').DataTable({
        "order": [[0, "desc" ]],
        "pageLength": <?php echo $settings['amount']; ?>,
        "statesave": true,
        "autoWidth": true
} );
} );

        </script>
</head>

  <body><font size="2">

    <div class="container">
    <table class="table table-striped table-bordered" id="status">
    <thead>
	    <tr><th colspan="3"><center><b><a href="index.php"><?php echo $name; ?></a></th></tr>
        <tr><th><b>DATE</b></th><th><b>VERSION</b></th><th><b>FILE</b></th></tr>
    </thead>
    <tbody>
<?php

    $backups = dbBackupList($id);
    foreach ($backups as $db_field) {
        $id = $db_field['id'];
        $version = $db_field['version'];
        $date = $db_field['date'];
        $filename = $db_field['filename'];


        echo "<tr valign='middle'><td>" . $date . "</td><td><center>" . $version . "</td><td><a href='" . $filename . "'>DOWNLOAD</a></td><td><center><form action='listbackups.php' method='POST'><input type='hidden' name='delbackup' value='delbackup'><input type='hidden' name='id' value='" . $id . "'><input type='submit' value='Delete' class='btn-xs btn-danger'></form></tr>";
    }

?>
           </tbody>
    </table>
    </div>
	  
  </div>
</div>
	  
	  </body>
	  </html>
