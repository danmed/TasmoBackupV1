<?php
require_once(__DIR__.'/lib/functions.inc.php');

global $db_handle;
global $settings;

$task='';
$password='';
$user='admin';
if(isset($settings['tasmota_username'])) $user=$settings['tasmota_username'];
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

switch(strtolower($task)) {
    case 'discover':
        $show_modal = true;
        $output = '<center>'.addTasmotaDevice($ip, $user, $password).'<br></center>';
        break;
    case 'discoverall':
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
        break;
    case 'edit':
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
        break;
    case 'download':
        $device=dbDeviceId(intval($_POST["id"]));
        $backup=dbBackupId(intval($_POST["backupid"]));
        downloadTasmotaBackup($backup);
        break;
    case 'singlebackup':
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
        break;
    case 'backupall':
        $errorcount = backupAll();

        $show_modal = true;
        if ($errorcount < 1) {
            $output = "All backups completed successfully!";
        } else {
            $output = "<font color='red'><b>Not all backups completed successfully!</b></font>";
        }
        break;
    case 'delete':
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
        break;
    case 'noofbackups':
        $findname = preg_replace('/\s+/', '_', $name);
        $findname = preg_replace('/[^A-Za-z0-9\-]/', '', $findname);
        $directory = $settings['backup_folder'] . $findname;
        $scanned_directory = array_diff(scandir($directory), array('..','.'));
        $out = array();
        foreach ($scanned_directory as $value) {
            $link = strtolower(implode("-", explode(" ", $value)));
            $out[] = '<a href="' . $settings['backup_folder'] . $findname . '/' . $link . '">' . $link . '</a>';
        }
        $output = implode("<br>", $out);

        $show_modal = true;
        break;
    default:
        break;
}

TBHeader(false,true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [['. (isset($settings['sort'])?$settings['sort']:0) .', "asc" ]],
        "pageLength": '. (isset($settings['amount'])?$settings['amount']:100) .',
        "columnDefs": [
            { "type": "ip-address", "targets": [1] },
            { "type": "version", "targets": [3] }
            ],
        "statesave": true,
        "autoWidth": true
} );
} );
',true);
?>
  <body>
    <div class="container-fluid">
    <table class="table table-striped table-bordered" id="status">
    <thead>
      <tr><th colspan="9"><center><b>TasmoBackup <a href="settings.php"><?php
	if(isset($settings['theme']) && $settings['theme']=='dark') { // Enforce Dark mode
	    echo '<img src="images/settings-dark.png">';
	} else if(isset($settings['theme']) && $settings['theme']=='light') { // Enforce Light mode
	    echo '<img src="images/settings.png">';
	} else { // auto mode
	    echo '<picture><source srcset="images/settings-dark.png" media="(prefers-color-scheme: dark">';
	    echo '<source srcset="images/settings.png" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)">';
	    echo '<img src="images/settings.png"></picture>';
	}
?></a></th></tr>
      <tr><th><b>NAME</th><th><center>IP</center></th><th><center>MAC</center></th><th><center>AUTH</center></th><th><center><b>VERSION</b></center></th><th><center>LAST BACKUP</center></th><th><center><b>FILES</b></center></th><th><center><b>BACKUP</b></center></th><th><center>EDIT</center></th><th><center><b>DELETE</b></center></th></tr>
    </thead>
    <tbody>
<?php
	$github_tasmota_release_data = getGithubTasmotaReleaseData();

    $list_model='';
    $now=time();
    $lastbackup_green=0;
    $lastbackup_red=0;
    $lastbackup_yellow=0;
    if(isset($settings['backup_minhours']) && $settings['backup_minhours']>0) {
        $lastbackup_green=$now-(intval($settings['backup_minhours'])*3600*2.2);
        $lastbackup_red=$now-(intval($settings['backup_minhours'])*3600*8);
    }    
    $devices = dbDevicesSort();
    foreach ($devices as $db_field) {
        $id = $db_field['id'];
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        if(isset($db_field['mac'])) {
            $mac = $db_field['mac'];
            $mac_display = $mac;
        } else {
            $mac = '';
            $mac_display = '&nbsp;';
        }
        $version = $db_field['version'];
        $lastbackup = $db_field['lastbackup'];
        $numberofbackups = $db_field['noofbackups'];
        $password = $db_field['password'];

	$color='';
        if($lastbackup_green>0 && isset($lastbackup) && strlen($lastbackup)>10) {
            $ts=strtotime($lastbackup);
            if($ts<$lastbackup_red && $ts>0)
                $color='bgcolor="red"';
            if($ts>$lastbackup_red)
                $color='bgcolor="yellow"';
            if($ts>$lastbackup_green)
                $color=''; //    $color='bgcolor="green"';
	}
	
        echo "<tr valign='middle'><td onclick=\"deviceModal('#myModaldevice".$id."');\">" . $name . "</td><td><center><a href='http://" . $ip . "' target='_blank'>" . $ip . "</a></td><td><center>" . $mac_display . "</center></td><td><center>";
	if(isset($settings['theme']) && $settings['theme']=='dark') { // Enforce Dark mode
	    echo "<img src='" . (strlen($password) > 0 ? 'images/lock-dark.png' : 'images/lock-open-variant-dark.png') . "'>";
	} else if(isset($settings['theme']) && $settings['theme']=='light') { // Enforce Light mode
	    echo "<img src='" . (strlen($password) > 0 ? 'images/lock.png' : 'images/lock-open-variant.png') . "'>";
	} else { // auto mode
	    if(strlen($password) >0) {
		echo '<picture><source srcset="images/lock-dark.png" media="(prefers-color-scheme: dark">';
		echo '<source srcset="images/lock.png" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)">';
		echo '<img src="images/lock.png"></picture>';
	    } else {
		echo '<picture><source srcset="images/lock-open-variant-dark.png" media="(prefers-color-scheme: dark">';
		echo '<source srcset="images/lock-open-variant.png" media="(prefers-color-scheme: light), (prefers-color-scheme: no-preference)">';
		echo '<img src="images/lock-open-variant.png"></picture>';
	    }
	}
        $ver=$version;
        $tag='';
        if(($pos=strpos($version,'('))>0) {
            $ver=substr($version,0,$pos);
            $tag=substr($version,$pos);
            $version=$ver.' <small>'.$tag.'</small>';

			$release_html_url = "";
			if ( $tag == "(tasmota)" ) {
				$github_tag_name = 'v' . $ver;
				foreach ( $github_tasmota_release_data as $release => $values ) {
					$release_html_url = $values['html_url'];
					if ( $values['tag_name'] == $github_tag_name ) {
						break;
					}
				}
			} else {
				// default to the Tasmota documentation if a custom "version" is in use
				$release_html_url = "https://tasmota.github.io/docs/";
			}
        }
	echo "</center></td><td><center><a href='" . $release_html_url . "'>" . $version . "</a></center></td><td $color><center>" . $lastbackup . "</center></td>";
	echo "<td><center><form method='POST' action='listbackups.php'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='" . $id . "' name='id'><input type='submit' value='" . $numberofbackups . "' class='btn-xs btn-info'></form></center></td>";
	echo "<td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='singlebackup' name='task'><input type='submit' value='Backup' class='btn-xs btn-success'></form></center></td>";
	echo "<td><center><form method='POST' action='edit.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='edit' name='task'><input type='submit' value='Edit' class='btn-xs btn-warning'></form></center></td>";
	echo "<td><center><form method='POST' id='deleteform' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='delete' name='task'><input type='submit' onclick='return window.confirm(\"Are you sure you want to delete " . $name . "\");' value='Delete' class='btn-xs btn-danger'></form></center></td></tr>\r\n";

        $list_model.='<div id="myModaldevice'.$id.'" class="modal fade" role="dialog"><div class="modal-dialog"><div class="modal-content"><div class="modal-header"><h4 class="modal-title">'.$name.'</h4><button type="button" class="close" data-dismiss="modal">&times;</button></div><div class="modal-body"><p><pre>'."\r\n";
	$list_model.=sprintf("%14s: %s\r\n%14s: %s\r\n%14s: %s\r\n%14s: %s","Name",$name,"IP",$ip,"MAC",$mac,"Version",$ver);
	if(isset($tag))
            $list_model.=sprintf("\r\n%14s: %s","BuildTag",$tag);
        $list_model.=sprintf("\r\n%14s: %s\r\n","Last Backup",$lastbackup);
        $list_model.='</pre></p></div><div class="modal-footer"><button type="button" class="btn btn-default" data-dismiss="modal">Close</button></div></div></div></div>'."\r\n";
    }

?>
           </tbody>
    </table>

<center><form method='POST' action='index.php'><input type='hidden' value='backupall' name='task'><input type='submit' value='Backup All' class='btn-xs btn-success'></form><br>
<form method="POST" action="scan.php"><input type=text name=range placeholder="192.168.1.1-255"><input type="password" name="password" placeholder="password" <?php if(isset($settings['tasmota_password'])) { echo 'value="'.$settings['tasmota_password'].'" '; } ?>><input type=hidden name=task value=scan><input type=submit value=Discover class='btn-xs btn-danger'></form>
<?php if(isset($settings['mqtt_host']) && isset($settings['mqtt_port']) && strlen($settings['mqtt_host'])>1) {
?>
<form method="POST" action="scan.php"><input type=text name=mqtt_topic value='<?php echo isset($settings['mqtt_topic'])?$settings['mqtt_topic']:'tasmotas'; ?>'><input type="password" name="password" placeholder="password" <?php if(isset($settings['tasmota_password'])) { echo 'value="'.$settings['tasmota_password'].'" '; } ?>><input type=hidden name=task value=mqtt><input type=submit value="MQTT Discover" class='btn-xs btn-danger'></form>
<?php
}

TBFooter();
echo '</div>';

if(isset($list_model)) {
    echo $list_model;
?>
<script type='text/javascript'>
function deviceModal(modalId) {
  $(modalId).modal('show');
}
</script>
<?php
}

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
