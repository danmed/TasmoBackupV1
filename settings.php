<?php

require_once(__DIR__.'/lib/functions.inc.php');

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
    dbSettingsUpdate('mqtt_topic',trim($_POST['mqtt_topic']," \t\n\r\0\v/"));
}
if (isset($_POST['mqtt_topic_format'])) {
    dbSettingsUpdate('mqtt_topic_format',trim($_POST['mqtt_topic_format']," \t\n\r\0\v/"));
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
if (isset($_POST['tasmota_password'])) {
    dbSettingsUpdate('tasmota_password',$_POST['tasmota_password']);
}
if (isset($_POST['autoupdate_name'])) {
    if (in_array(strtolower($_POST['autoupdate_name']),array('y','yes','true','t')))
        dbSettingsUpdate('autoupdate_name','Y');
    else
        dbSettingsUpdate('autoupdate_name','N');
}
if (isset($_POST['autoadd_scan'])) {
    if (in_array(strtolower($_POST['autoadd_scan']),array('y','yes','true','t')))
        dbSettingsUpdate('autoadd_scan','Y');
    else
        dbSettingsUpdate('autoadd_scan','N');
}
if (isset($_POST['theme'])) {
    if (in_array(strtolower($_POST['theme']),array('light','dark','auto')))
        dbSettingsUpdate('theme',strtolower($_POST['theme']));
}
if (isset($_POST['use_topic_as_name'])) {
    if (in_array(strtolower($_POST['use_topic_as_name']),array('y','yes','true','t')))
        dbSettingsUpdate('use_topic_as_name','Y');
    else if (in_array(strtolower($_POST['use_topic_as_name']),array('f','full')))
        dbSettingsUpdate('use_topic_as_name','F');
    else
        dbSettingsUpdate('use_topic_as_name','N');
}
if (isset($_POST['hide_mac_column'])) {
    if (in_array(strtolower($_POST['hide_mac_column']),array('y','yes','true','t')))
        dbSettingsUpdate('hide_mac_column','Y');
    else
        dbSettingsUpdate('hide_mac_column','N');
}


TBHeader('Settings',true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [],
        "pageLength": '. (isset($settings['amount'])?$settings['amount']:25) .',
        "statesave": true,
        "autoWidth": false
} );
} );
',true);
?>
  <body>

    <div class="container-fluid">
        <center><h4><a href="index.php">TasmoBackup</a> - Settings</h4></center>
        <form method='POST' action='settings.php'>
            <table class="table table-striped table-bordered" id="status" >
                <thead>
                    <tr><th>Setting</th><th>Value</th></tr>
                </thead>
                <tbody>
                <tr valign='middle'><td align="right">Sort Column</td><td><select name ="sortoption"><option value="0" <?php if(isset($settings['sort']) && $settings['sort']==0) { echo 'selected="selected"'; } ?>>Name</option><option value="1" <?php if(isset($settings['sort']) && $settings['sort']==1) { echo 'selected="selected"'; } ?>>IP</option><option value="2" <?php if(isset($settings['sort']) && $settings['sort']==2) { echo 'selected="selected"'; } ?>>Auth</option><option value="3" <?php if(isset($settings['sort']) && $settings['sort']==3) { echo 'selected="selected"'; } ?>>Version</option><option value="4" <?php if(isset($settings['sort']) && $settings['sort']==4) { echo 'selected="selected"'; } ?>>Last Backup</option></select></td></tr>
                    <tr valign='middle'><td align="right">Amount of Rows</td><td><input type='text' name='amountoption' value='<?php echo isset($settings['amount'])?$settings['amount']:100; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Theme (light or dark or auto)</td><td><input type="text" name='theme' value='<?php echo isset($settings['theme'])?$settings['theme']:'auto'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Tasmota Default Password for web login on devices</td><td><input type="password" name='tasmota_password' value='<?php if(isset($settings['tasmota_password'])) echo $settings['tasmota_password']; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Update Device Name when doing Backups (Y or N)</td><td><input type="text" name='autoupdate_name' value='<?php echo isset($settings['autoupdate_name'])?$settings['autoupdate_name']:'Y'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Automatically Add New Devices (Y or N)</td><td><input type="text" name='autoadd_scan' value='<?php echo isset($settings['autoadd_scan'])?$settings['autoadd_scan']:'N'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Use MQTT Topic as Device Name (Y or N or F (Full))</td><td><input type="text" name='use_topic_as_name' value='<?php echo isset($settings['use_topic_as_name'])?$settings['use_topic_as_name']:'N'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Hide MAC Address column on index page (Y or N)</td><td><input type="text" name='hide_mac_column' value='<?php echo isset($settings['hide_mac_column'])?$settings['hide_mac_column']:'N'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Host</td><td><input type="text" name='mqtt_host' value='<?php if(isset($settings['mqtt_host'])) echo $settings['mqtt_host']; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Port</td><td><input type="text" name='mqtt_port' value='<?php echo isset($settings['mqtt_port'])?$settings['mqtt_port']:1883; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Username</td><td><input type="text" name='mqtt_user' value='<?php if(isset($settings['mqtt_user'])) echo $settings['mqtt_user']; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Password</td><td><input type="password" name='mqtt_password' value='<?php if(isset($settings['mqtt_password'])) echo $settings['mqtt_password']; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Topic</td><td><input type="text" name='mqtt_topic' value='<?php echo isset($settings['mqtt_topic'])?$settings['mqtt_topic']:'tasmotas'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">MQTT Topic Format</td><td><input type="text" name='mqtt_topic_format' value='<?php echo isset($settings['mqtt_topic_format'])?$settings['mqtt_topic_format']:'%prefix%/%topic%'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Backup-All Min Hours between backups</td><td><input type="text" name='backup_minhours' value='<?php echo isset($settings['backup_minhours'])?$settings['backup_minhours']:'23'; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Backup Max Days Old to keep</td><td><input type="text" name='backup_maxdays' value='<?php echo isset($settings['backup_maxdays'])?$settings['backup_maxdays']:''; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Backup Max Count to keep</td><td><input type="text" name='backup_maxcount' value='<?php echo isset($settings['backup_maxcount'])?$settings['backup_maxcount']:''; ?>'></td></tr>
                    <tr valign='middle'><td align="right">Backup Data Directory</td><td><input type="text" name='backup_folder' value='<?php echo $settings['backup_folder']; ?>'></td></tr>
                </tbody>
                <tfoot>
                    <tr><td>&nbsp;</td><td><button type='submit' class='btn btn-sm btn-success'>Save</button></td></tr>
                </tfoot>
            </table>
        </form>
        <hr>
        <table >
            <tr valign='middle'>
                <td>Export Devices</td>
                <td style="padding-left:8px;">
                    <form method='POST' action='export.php'>
                        <input type="hidden" name="export" value="export">
                        <select name ="sortoption">
                            <option value="0">CSV</option>
                </td>
                <td style="padding-left:8px;"><button type='submit' class='btn btn-sm btn-success'>Submit</button></form></td>
            </tr>
        </table>
    </div>
<?php
TBFooter();
