<?php
require 'lib/functions.inc.php';
require 'lib/mqtt.inc.php';

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
',true);

?>
  <body>

    <div class="container">
	    <form action="index.php" method="POST">
                <input type="hidden" name="task" value="discoverall">
                <?php if(isset($_POST['user'])) { echo '<input type="hidden" name="user" value="'.$_POST['user'].'">'; } ?>
                <?php if(isset($_POST['password'])) { echo '<input type="hidden" name="password" value="'.$_POST['password'].'">'; } ?>
    <table class="table table-striped table-bordered" id="status">
    <thead>
    <tr><th colspan="3"><center><b>TasmoBackup</b></th></tr>
    <tr><th><b>ADD</b></th><th><b>NAME</b></th><th><b>IP</b></th></tr>
    </thead>
    <tbody>
<?php

$password='';
$user='admin';
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

    // 4 for loops to generate the ip address 4 octets
    for ($octet1=$range[0][0]; $octet1<=(isset($range[0][1])? $range[0][1]:$range[0][0]); $octet1++) {
        for ($octet2=$range[1][0]; $octet2<=(isset($range[1][1])? $range[1][1]:$range[1][0]); $octet2++) {
            for ($octet3=$range[2][0]; $octet3<=(isset($range[2][1])? $range[2][1]:$range[2][0]); $octet3++) {
                for ($octet4=$range[3][0]; $octet4<=(isset($range[3][1])? $range[3][1]:$range[3][0]); $octet4++) {
                    // assemble the IP address
                    $ip = $octet1.".".$octet2.".".$octet3.".".$octet4;

                    // initialise the URL

                    if (getTasmotaScan($ip, $user, $password)) {
                        if ($status=getTasmotaStatus($ip, $user, $password)) {
                            $name=$status['Status']['FriendlyName'][0];
                            echo "<tr valign='middle'><td><center><input type='checkbox' name='ip[]' value='" . $ip . "'></center></td>".
                     "<td>" . $name . "</td>".
                     "<td><center><a href='http://" . $ip . "'>" . $ip . "</a></center></td></tr>";
                        }
                    }

                }
            }
        }
    }
}

if ($_POST["task"]=="mqtt") {
    if(isset($settings['mqtt_host']) && isset($settings['mqtt_port']) && strlen($settings['mqtt_host'])>4) {
        $mqtt=setupMQTT($settings['mqtt_host'], $settings['mqtt_port'], $settings['mqtt_user'], $settings['mqtt_password']);
        if(!isset($mqtt_topic)) $mqtt_topic=$settings['mqtt_topic'];
        $results=getTasmotaMQTTScan($mqtt,$mqtt_topic);
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
	    <tr><td colspan="3"><center><input type=submit class='btn-xs btn-success' value='Add Devices'></center></td></tr>
    </table>
    </form>
    </div>
<?php
TBFooter();
?>
</body>
</html>
