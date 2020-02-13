<!DOCTYPE html>
<?php
require "functions.inc.php";

global $db_handle;
global $settings;

$task='';
$password='';
$user='admin';
if (isset($_POST["task"])) {
    $task = $_POST["task"];
}
if (isset($_POST["user"])) {
    $user = $_POST["user"];
}
if (isset($_POST["password"])) {
    $password = $_POST["password"];
}
if (isset($_POST["ip"])) {
    $device = $ip = $_POST["ip"];
}
if (isset($_POST["name"])) {
    $name = $_POST["name"];
}

if ($task == "discover") {
    $show_modal = true;
    $output = '<center>'.addTasmotaDevice($ip, $user, $password).'<br></center>';
}

if ($task == "discoverall") {
    $show_modal = true;
    $output = '<center>';
    if (!is_array($ip)) {
        $output .= "You didn't select any devices.<br>";
    } else {
        foreach($ip as $i) {
            $output .= addTasmotaDevice($i, $user, $password).'<br>';
        }
    }
    $output .= '</center>';
}


if ($task == "edit") {
    if (isset($_POST['oldip'])) {
        $old_ip = $_POST['oldip'];
    }
    if (isset($_POST['oldname'])) {
        $old_name = $_POST['oldname'];
    }

    if (isset($old_ip) && isset($ip)) {
        if (dbDeviceRename($old_ip, $name, $ip, $password)) {
            $show_modal = true;
            $output = "<center><b>" . $name . " updated up successfully</b><br></center>";
        } else {
            $show_modal = true;
            echo "<center><b>Error updating record for ".$old_ip." ".$name." <br>";
        }
    }
}

// SINGLE BACKUP ROUTINE
if ($task == "singlebackup") {
    $show_modal = true;
    $output = "<center><b>Device not found: ".$ip."</b></center>";

    $devices = dbDeviceIp($ip);
    if ($devices!==false) {
        foreach ($devices as $db_field) {
            if (backupSingle($db_field['id'], $db_field['name'], $db_field['ip'], 'admin', $db_field['password'])) {
                $show_modal = true;
                $output = "<center><b>Backup failed</b></center>";
            } else {
                $show_model = true;
                $output = "Backup completed successfully!";
            }
        }
    }
}

if ($task == "backupall") {
    $errorcount = backupAll();

    $show_modal = true;
    if ($errorcount < 1) {
        $output = "All backups completed successfully!";
    } else {
        $output = "<font color='red'><b>Not all backups completed successfully!</b></font>";
    }
}

if ($task == "delete") {
    $show_modal = true;
    try {
        if (dbDeviceDel($ip)) {
            $output = $name . " deleted successfully from the database.";
            } else {
            $output = "Error deleting  " . $ip;
        }
    } catch (PDOException $e) {
        $output = "Error deleting  " . $ip . " : " . $e->getMessage();
    }
}

if ($task == "noofbackups") {
    $findname = preg_replace('/\s+/', '_', $name);
    $findname = preg_replace('/[^A-Za-z0-9\-]/', '', $findname);
    $directory = $settings['backup_folder'] . $findname;
    $scanned_directory = array_diff(scandir($directory), array(
        '..',
        '.'
    ));

    $out = array();
    foreach ($scanned_directory as $value) {
        $link = strtolower(implode("-", explode(" ", $value)));
        $out[] = '<a href="' . $settings['backup_folder'] . $findname . '/' . $link . '">' . $link . '</a>';
    }
    $output = implode("<br>", $out);

    $show_modal = true;
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
        "order": [[<?php echo isset($settings['sort'])?$settings['sort']:0; ?>, "asc" ]],
        "pageLength": <?php echo isset($settings['amount'])?$settings['amount']:100; ?>,
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
	    <tr><th colspan="9"><center><b>TasmoBackup <a href="settings.php"><img src="settings.png"></a></th></tr>
        <tr><th><b>NAME</th><th>IP</th><th>AUTH</th><th><b>VERSION</th><th>LAST BACKUP</th><th><b>FILES</th><th><b>BACKUP</b></th><th>EDIT</th><th><b>DELETE</b></th></tr>
    </thead>
    <tbody>
<?php

    $devices = dbDevicesSort();
    foreach ($devices as $db_field) {
        $id = $db_field['id'];
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        $version = $db_field['version'];
        $lastbackup = $db_field['lastbackup'];
        $numberofbackups = $db_field['noofbackups'];
        $password = $db_field['password'];

        echo "<tr valign='middle'><td>" . $name . "</td><td><center><a href='http://" . $ip . "' target='_blank'>" . $ip . "</a></td><td><center><img src='" . (strlen($password) > 0 ? 'lock.png' : 'lock-open-variant.png') . "'></td><td><center>" . $version . "</td><td><center>" . $lastbackup . "</td><Td><center><form method='POST' action='listbackups.php'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='" . $id . "' name='id'><input type='submit' value='" . $numberofbackups . "' class='btn-xs btn-info'></form></td><td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='singlebackup' name='task'><input type='submit' value='Backup' class='btn-xs btn-success'></form></td><td><center><form method='POST' action='edit.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='edit' name='task'><input type='submit' value='Edit' class='btn-xs btn-warning'></form></td><td><center><form method='POST' id='deleteform' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='delete' name='task'><input type='submit' onclick='return window.confirm("Are you sure you want to delete this?");' value='Delete' class='btn-xs btn-danger'></form></td></tr>";
    }

?>
           </tbody>
    </table>
    </div>

<center><form method='POST' action='index.php'><input type='hidden' value='backupall' name='task'><input type='submit' value='Backup All' class='btn-xs btn-success'></form><br>
  <form method='POST' action='index.php'><input type='hidden' value='discover' name='task'><input type="text" name="ip" placeholder="ip address"><input type="password" name="password" placeholder="password"><input type='submit' value='Add' class='btn-xs btn-danger'></form>
<form method="POST" action="scan.php"><input type=text name=range placeholder="192.168.1.1-255"><input type="password" name="password" placeholder="password"><input type=hidden name=task value=scan><input type=submit value=Discover class='btn-xs btn-danger'></form>
<form method="POST" action="scan.php"><input type=text name=mqtt_topic value='<?php echo isset($settings['mqtt_topic'])?$settings['mqtt_topic']:'tasmotas'; ?>'><input type="password" name="password" placeholder="password"><input type=hidden name=task value=mqtt><input type=submit value="MQTT Discover" class='btn-xs btn-danger'></form>
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://github.com/danmed/TasmoBackupV1' target='_blank' style='color:#aaa;'>TasmoBackup 1.02 by Dan Medhurst</a></div>

    <?php
if (isset($show_modal) && $show_modal):
?>
   <script type='text/javascript'>
    $(document).ready(function(){
    $('#myModal').modal('show');
    });
    </script>
<?php
endif;
?>


<!-- Modal -->
<div id="myModal" class="modal fade" role="dialog">
  <div class="modal-dialog">

    <!-- Modal content-->
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal">&times;</button>
        <h4 class="modal-title">TasmoBackup</h4>
      </div>
      <div class="modal-body">
        <p><center>
          <?php if (isset($output)) {
    echo $output;
} ?>
          <br>
          <?php if (isset($output2)) {
    echo $output2;
} ?>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
