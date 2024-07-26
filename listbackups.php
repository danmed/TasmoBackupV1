<?php
require_once(__DIR__.'/lib/functions.inc.php');

global $db_handle;
global $settings;

if (isset($_POST["name"])) {
    $name = $_POST["name"];
}
if (isset($_POST["id"])) {
    $id = intval($_POST["id"]);
}
if (isset($_POST["task"])) {
    switch(strtolower($_POST["task"])) {
        case 'delbackup':
            dbBackupDel(intval($_POST["backupid"]));
            dbDeviceBackups($id);
            break;
        case 'restorebackup':
            $device=dbDeviceId($id);
            $backup=dbBackupId(intval($_POST["backupid"]));
            restoreTasmotaBackup($device['ip'],'admin',$device['password'],$backup['filename']);
            break;
    }
}

TBHeader('List Backups',true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [[0, "desc" ]],
        "pageLength": '. (isset($settings['amount'])?$settings['amount']:25) .',
        "columnDefs": [
            { "type": "version", "targets": [2] }
            ],
        "statesave": true,
        "autoWidth": false
} );
} );
',true);
?>
  <body>

    <div class="container-fluid">
	<center><h4><a href="index.php">TasmoBackup</a> - Listing for <?php echo $name; ?></h4></center>
    <table class="table table-striped table-bordered" id="status">
    <thead>
		    <tr><th><b>DATE</b></th><th><center><b>NAME</b></center></th><th><center><b>VERSION</b></center></th><th><center><b>FILE</b></center></th><th><center><b>DELETE</b><center></th><th><center><b>RESTORE</b></center></th></tr>
    </thead>
    <tbody>
<?php

    $device = dbDeviceId($id);
    $type=0;
    if(isset($device['type']))
        $type=intval($device['type']);

    $backups = dbBackupList($id);
    foreach ($backups as $db_field) {
        $backupid = $db_field['id'];
        $name = $db_field['name'];
        $version = $db_field['version'];
        $date = $db_field['date'];
        $filename = $db_field['filename'];

        if(($pos=strpos($version,'('))>0) {
            $ver=substr($version,0,$pos);
            $tag=substr($version,$pos);
            $version=$ver.' <small>'.$tag.'</small>';
        }
?>
<tr valign='middle'>
  <td><?php echo $date; ?></td>
  <td><center><?php echo $name; ?></center></td>
  <td><center><?php echo $version; ?></center></td>
  <td><center>
    <form action='index.php' method='POST'>
    <input type='hidden' name='task' value='download'>
    <input type='hidden' name='backupid' value='<?php echo $backupid; ?>'>
    <input type='hidden' name='id' value='<?php echo $id; ?>'>
    <button type='submit' class='btn btn-sm btn-success'>Download</button>
    </form>
  </center></td>
  <td><center>
    <form action='listbackups.php' method='POST'>
    <input type='hidden' name='task' value='delbackup'>
    <input type='hidden' name='backupid' value='<?php echo $backupid; ?>'>
    <input type='hidden' name='id' value='<?php echo $id; ?>'>
    <input type='hidden' name='name' value='<?php echo $name; ?>'>
    <button type='submit' onclick='return window.confirm("Are you sure you want to delete <?php echo $filename; ?>");' class='btn-sm btn-danger'>Delete</button>
    </form>
  </center></td>
<?php
        if(intval($type)===0) {
?>  <td><center>
    <form action='listbackups.php' method='POST'>
    <input type='hidden' name='task' value='restorebackup'>
    <input type='hidden' name='backupid' value='<?php echo $backupid; ?>'>
    <input type='hidden' name='id' value='<?php echo $id; ?>'>
    <input type='hidden' name='name' value='<?php echo $name; ?>'>
    <button type='submit' onclick='return window.confirm("Are you sure you want to restore <?php echo $filename; ?> to this device");' class='btn btn-sm btn-danger'>Restore</button>
    </form>
  </center></td>
<?php
        } else { echo '<td>&nbsp;</td>'; }
        echo "\r\n</tr>\r\n";
    }

?>
    </tbody>
    </table>
    </div>
	  
<?php TBFooter();
?>
</body>
</html>
