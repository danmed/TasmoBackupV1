<!DOCTYPE html>
<?php
require "lib/functions.inc.php";

global $db_handle;
global $settings;

if (isset($_POST["name"])) {
    $name = $_POST["name"];
}
if (isset($_POST["id"])) {
    $id = intval($_POST["id"]);
}
if (isset($_POST["delbackup"])) {
	dbBackupDel($_POST["backupid"]);
	dbDeviceBackups($id);
}

TBHeader('List Backups',true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [[0, "desc" ]],
        "pageLength": '. isset($settings['amount'])?$settings['amount']:25 .',
        "statesave": true,
        "autoWidth": true
} );
} );
',true);
?>
  <body><font size="2">

    <div class="container">
    <table class="table table-striped table-bordered" id="status">
    <thead>
	    <tr><th colspan="4"><center><b><a href="index.php"><?php echo $name; ?></a></th></tr>
		    <tr><th><b>DATE</b></th><th><b>NAME</b></th><th><b>VERSION</b></th><th><b>FILE</b></th><th><b>DELETE</b></th></tr>
    </thead>
    <tbody>
<?php

    $backups = dbBackupList($id);
    foreach ($backups as $db_field) {
        $backupid = $db_field['id'];
        $name = $db_field['name'];
        $version = $db_field['version'];
        $date = $db_field['date'];
        $filename = $db_field['filename'];

?>
<tr valign='middle'>
  <td><?php echo $date; ?></td>
  <td><center><?php echo $name; ?></center></td>
  <td><center><?php echo $version; ?></center></td>
  <td><a href='<?php echo $filename; ?>'>DOWNLOAD</a></td>
  <td><center>
    <form action='listbackups.php' method='POST'>
    <input type='hidden' name='delbackup' value='delbackup'>
    <input type='hidden' name='backupid' value='<?php echo $backupid; ?>'>
    <input type='hidden' name='id' value='<?php echo $id; ?>'>
    <input type='hidden' name='name' value='<?php echo $name; ?>'>
    <input type='submit' value='Delete' onclick='return window.confirm(\"Are you sure you want to delete <?php echo $filename; ?>\");'class='btn-xs btn-danger'>
    </form>
  </td>
</tr>
<?php
    }

?>
    </tbody>
    </table>
    </div>
	  
<?php TBFooter();
?>
</body>
</html>
