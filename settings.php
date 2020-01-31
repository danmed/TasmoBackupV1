<!DOCTYPE html>
<?php

require "functions.inc.php";

global $db_handle;
global $settings;

if (isset($_POST["sortoption"])) {
    dbSettingsUpdate('sort',intval($_POST["sortoption"]));
}

if (isset($_POST["amountoption"])) {
    dbSettingsUpdate('amount',intval($_POST["amountoption"]));
}
if (isset($_POST['mqtt_host'])) {
    dbSettingsUpdate('mqtt_host',$_POST['mqtt_host']);
}
if (isset($_POST['mqtt_port'])) {
    dbSettingsUpdate('mqtt_port',intval($_POST['mqtt_port']));
}
if (isset($_POST['mqtt_user'])) {
    dbSettingsUpdate('mqtt_user',$_POST['mqtt_user']);
}
if (isset($_POST['mqtt_password'])) {
    dbSettingsUpdate('mqtt_password',$_POST['mqtt_password']);
}
if (isset($_POST['mqtt_topic'])) {
    dbSettingsUpdate('mqtt_topic',$_POST['mqtt_topic']);
}
if (isset($_POST['backup_minhours'])) {
    dbSettingsUpdate('backup_minhours',intval($_POST['backup_minhours']));
}
if (isset($_POST['backup_maxdays'])) {
    dbSettingsUpdate('backup_maxdays',intval($_POST['backup_maxdays']));
}
if (isset($_POST['backup_maxcount'])) {
    dbSettingsUpdate('backup_maxcount',intval($_POST['backup_maxcount']));
}
if (isset($_POST['backup_folder'])) {
    dbSettingsUpdate('backup_folder',$_POST['backup_folder']);
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

<title>TasmoBackup - Settingsa</title>
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
        "order": [],
        "pageLength": 25,
        "statesave": true,
        "autoWidth": true
} );
} );

        </script>
</head>

  <body><font size="2">

    <div class="container">
    <form method='POST' action='settings.php'>
    <table class="table table-striped table-bordered" id="status">
    <thead>
    <tr><th colspan="2"><center><b><a href="index.php">TasmoBackup</a></th></tr>
        <tr><th><b>Setting</th><th>Value</th></tr>
    </thead>
    <tbody>

<tr valign='middle'><td>Sort Column</td><td><center><select name ="sortoption"><option value="0" <?php if($settings['sort']==1) { echo 'selected="selected"'; } ?>>Name</option><option value="1">IP</option><option value="2" <?php if($settings['sort']==2) { echo 'selected="selected"'; } ?>>Auth</option><option value="3" <?php if($settings['sort']==3) { echo 'selected="selected"'; } ?>>Version</option><option value="4" <?php if($settings['sort']==4) { echo 'selected="selected"'; } ?>>Last Backup</option></select></td></tr>
<tr valign='middle'><td>Amount of Rows</td><td><center><input type='text' name='amountoption' value='<?php echo isset($settings['amount'])?$settings['amount']:100; ?>'></td></tr>
<tr valign='middle'><td>MQTT Host</td><td><center><input type="text" name='mqtt_host' value='<?php if(isset($settings['mqtt_host'])) echo $settings['mqtt_host']; ?>'></td></tr>
<tr valign='middle'><td>MQTT Port</td><td><center><input type="text" name='mqtt_port' value='<?php echo isset($settings['mqtt_port'])?$settings['mqtt_port']:1883; ?>'></td></tr>
<tr valign='middle'><td>MQTT Username</td><td><center><input type="text" name='mqtt_user' value='<?php if(isset($settings['mqtt_user'])) echo $settings['mqtt_user']; ?>'></td></tr>
<tr valign='middle'><td>MQTT Password</td><td><center><input type="text" name='mqtt_password' value='<?php if(isset($settings['mqtt_password'])) echo $settings['mqtt_password']; ?>'></td></tr>
<tr valign='middle'><td>MQTT Topic</td><td><center><input type="text" name='mqtt_topic' value='<?php echo isset($settings['mqtt_topic'])?$settings['mqtt_topic']:'tasmotas'; ?>'></td></tr>
<tr valign='middle'><td>Backup All Min Hours</td><td><center><input type="text" name='backup_minhours' value='<?php echo isset($settings['backup_minhours'])?$settings['backup_minhours']:'23'; ?>'></td></tr>
<tr valign='middle'><td>Backup Max Days Old</td><td><center><input type="text" name='backup_maxdays' value='<?php echo isset($settings['backup_maxdays'])?$settings['backup_maxdays']:''; ?>'></td></tr>
<tr valign='middle'><td>Backup Max Count</td><td><center><input type="text" name='backup_maxcount' value='<?php echo isset($settings['backup_maxcount'])?$settings['backup_maxcount']:''; ?>'></td></tr>
<tr valign='middle'><td>Backup Data Directory</td><td><center><input type="text" name='backup_folder' value='<?php echo $settings['backup_folder']; ?>'></td></tr>

<tr><td>&nbsp;</td><td><input type='submit' value='Save' class='btn-xs btn-success'></td></tr>
           </tbody>
    </table>
    <table>
    </form>
<tr valign='middle'><td>Export Devices</td><td><center><form method='POST' action='export.php'><input type="hidden" name="export" value="export"><select name ="sortoption"><option value="0">CSV</option></td><td><center><input type='submit' value='Submit' class='btn-xs btn-success'></form></td></tr>
    </table>
    </div>
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://github.com/danmed/TasmoBackupV1' target='_blank' style='color:#aaa;'>TasmoBackup 1.01 by Dan Medhurst</a></div>
