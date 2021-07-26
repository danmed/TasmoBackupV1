<?php
require_once(__DIR__.'/lib/functions.inc.php');
require_once(__DIR__.'/lib/mqtt.inc.php');

global $settings;

TBHeader('Scan',true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [[1, "asc" ]],
        "pageLength": '. (isset($settings['amount'])?$settings['amount']:100) .',
        "statesave": true,
        "autoWidth": true
} );
} );
',true,((isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y')?1:false));

?>
  <body>
<script language="Javascript">
function toggle(source) {
  checkboxes = document.getElementsByName('ip[]');
  for(var i=0, n=checkboxes.length;i<n;i++) {
    checkboxes[i].checked = source.checked;
  }
}
</script>
    <div class="container-fluid">
	    <form action="index.php" method="POST">
                <input type="hidden" name="task" value="discoverall">
                <?php if(isset($_POST['user'])) { echo '<input type="hidden" name="user" value="'.$_POST['user'].'">'; } ?>
                <?php if(isset($_POST['password'])) { echo '<input type="hidden" name="password" value="'.$_POST['password'].'">'; } ?>
    <table class="table table-striped table-bordered" id="status">
    <thead>
    <tr><th colspan="3"><center><b><a href="index.php">TasmoBackup</a> - Scan Results</b></center></th></tr>
    <tr><th><b>ADD</b></th><th><b>NAME</b></th><th><b>IP</b></th></tr>
    </thead>
    <tbody>
<?php

$password='';
$user='admin';
if(isset($settings['tasmota_username'])) $user=$settings['tasmota_username'];
if (isset($_POST['user'])) {
    $user=$_POST['user'];
}
if (isset($_POST['password'])) {
    $password=$_POST['password'];
}
if (isset($_POST['mqtt_topic'])) {
    $mqtt_topic=$_POST['mqtt_topic'];
}

if ($_POST["task"]=="scan") {
    set_time_limit(0);
    print(str_repeat(" ", 300) . "\n");
    $range = $_POST['range'];
    $range = explode('.', $range);
    foreach ($range as $index=>$octet) {
        $range[$index] = array_map('intval', explode('-', $octet));
    }

    $iprange=array();
    // 4 for loops to generate the ip address 4 octets
    for ($octet1=$range[0][0]; $octet1<=(isset($range[0][1])? $range[0][1]:$range[0][0]); $octet1++) {
        for ($octet2=$range[1][0]; $octet2<=(isset($range[1][1])? $range[1][1]:$range[1][0]); $octet2++) {
            for ($octet3=$range[2][0]; $octet3<=(isset($range[2][1])? $range[2][1]:$range[2][0]); $octet3++) {
                for ($octet4=$range[3][0]; $octet4<=(isset($range[3][1])? $range[3][1]:$range[3][0]); $octet4++) {
                    // assemble the IP address
                    array_push($iprange,$octet1.".".$octet2.".".$octet3.".".$octet4);

                }
            }
        }
    }
    // initialise the URL

    if ($ipresult=getTasmotaScanRange($iprange, $user, $password)) {
        for($i=0;$i<count($ipresult);$i++) {
            $ip=$ipresult[$i];
            if ($status=getTasmotaStatus($ip, $user, $password)) {
                if ($status['Status']['Topic'])
                    $name=$status['Status']['Topic'];
                if(!isset($settings['use_topic_as_name'] || !$settings['use_topic_as_name']) {
                    if ($status['Status']['DeviceName'] && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                        $name=$status['Status']['DeviceName'];
                    else if ($status['Status']['FriendlyName'][0])
                        $name=$status['Status']['FriendlyName'][0];
                }
                echo "<tr valign='middle'><td><center><input type='checkbox' name='ip[]' value='" . $ip . "'></center></td>".
                     "<td>" . $name . "</td>".
                     "<td><center><a href='http://" . $ip . "'>" . $ip . "</a></center></td></tr>";
            }
        }
    }
}

if ($_POST["task"]=="mqtt") {
    if(isset($settings['mqtt_host']) && isset($settings['mqtt_port']) && strlen($settings['mqtt_host'])>1) {
        $mqtt=setupMQTT($settings['mqtt_host'], $settings['mqtt_port'], $settings['mqtt_user'], $settings['mqtt_password']);
        if(!isset($mqtt_topic)) $mqtt_topic=$settings['mqtt_topic'];
        $results=getTasmotaMQTTScan($mqtt,$mqtt_topic,$user,$password,false);
        if(count($results)>0) {
            foreach($results as $found) {
                $ip=$found['ip'];
                $name='Unknown';
                if(isset($found['name'])) $name=$found['name'];
                echo "<tr valign='middle'><td><center><input type='checkbox' name='ip[]' value='" . $ip . "'></center></td>".
                     "<td>" . $name . "</td>".
                     "<td><center><a href='http://" . $ip . "'>" . $ip . "</a></center></td></tr>";
            }
        }
    }
}
?>
</tbody>
    <tr><td colspan="3">&nbsp;</td></tr>
    <tr><td><center><input type='checkbox' name="select-all" id="select-all" onClick="toggle(this)"></center></td><td>Select All</td><td>&nbsp;</td></tr>
    <tr><td colspan="3"><center><input type=submit class='btn-xs btn-success' value='Add Devices'></center></td></tr>
    </table>
    </form>
    </div>
<?php
TBFooter();
?>
</body>
</html>
