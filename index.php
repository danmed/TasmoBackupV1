<!DOCTYPE html>                                                                                              
<?PHP
require "db.inc.php";
include "data/settings.inc.php";

GLOBAL $db_handle;

$task="";
if(isset($_POST["task"])) $task = $_POST["task"];
if(isset($_POST["password"])) $password = $_POST["password"];
if(isset($_POST["ip"])) $device = $ip = $_POST["ip"];
if(isset($_POST["name"])) $name = $_POST["name"];

if(isset($password) && $password!="") {
  $device = "admin:".$password."@".$ip;
}

function backupSingle($db_handle,$id,$name,$ip,$user,$password)
{
        $savename = preg_replace('/\s+/', '_', $name);
        $savename = preg_replace('/[^A-Za-z0-9\-]/', '', $savename);
        if (!file_exists('data/backups/' . $savename))
        {
            $oldmask = umask(0);
            mkdir('data/backups/' . $savename, 0777, true);
            umask($oldmask);
        }
        $backupurl = "http://admin:" . $password . "@" . $ip . "/dl";
        $date = date('Y-m-d H:i:s');
        $savedate = preg_replace('/\s+/', '_', $date);
        $savedate = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);

        $url = 'http://' . $ip . '/cm?cmnd=status%202&user=admin&password=' . $password;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        $error = curl_errno($ch);
        curl_close($ch);
        
        if(!$error) {

          $version = "'" . getBetween($data, '"Version":"', '"') . "'";

          $saveto = "data/backups/" . $savename . "/" . $savedate . ".dmp";

          $fp = fopen($saveto, 'w+');
          if ($fp === false)
          {
            throw new Exception('Could not open: ' . $saveto);
          }

          $ch = curl_init($backupurl);
          curl_setopt($ch, CURLOPT_FILE, $fp);
          curl_setopt($ch, CURLOPT_TIMEOUT, 20);
          curl_exec($ch);
          $error = curl_errno($ch);
          $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
          fclose($fp);
          curl_close($ch);
          
          if (!$error && $statusCode == 200)
          {

            $directory = "data/backups/" . $savename . "/";

            // Initialize filecount variavle
            $filecount = 0;

            $files2 = glob($directory . "*");

            if ($files2)
            {
                $noofbackups = count($files2);
                #echo $noofbackups;
                
            }

            $stm2 = $db_handle->prepare("UPDATE devices SET version = :version, lastbackup = :date, noofbackups = :noofbackups WHERE id = :id");
            $stm2->bindValue(':version', $version, PDO::PARAM_STR);
            $stm2->bindValue(':date', $date, PDO::PARAM_STR);
            $stm2->bindValue(':noofbackups', $noofbackups, PDO::PARAM_STR);
            $stm2->bindValue(':id', $id, PDO::PARAM_INT);

            if (!$stm2->execute())
            {

                return true;
            }

          }
          else
          {
              return true;
          }

        } else {
          // Device is offline
          return true;
        }
        return false;
}



function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1]))
    {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

if ($task == "discover")
{

    $ch = curl_init("http://" . $device);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $text = curl_exec($ch);

    if (strpos($text, 'Tasmota') !== false)
    {

        //Get Version
        $url = 'http://' . $ip . '/cm?cmnd=status%202&user=admin&password=' . $password;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $version = getBetween($data, '"Version":"', '"');

        //Get Name
        $url = 'http://' . $ip . '/cm?cmnd=status&user=admin&password=' . $password;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $name = getBetween($data, 'FriendlyName":["', '"');
        $name = str_replace("'", "", $name);

        $stm = $db_handle->prepare("select count(*) from devices where ip = :ip");
        $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
        $stm->execute();
        if ($stm->fetchColumn() < 1)
        {
            #$SQL       = "select * from devices order by id asc";
            $stm2 = $db_handle->prepare("INSERT INTO devices (name,ip,version,password) VALUES (:name, :ip, :version, :password)");
            $stm2->bindValue(':name', $name, PDO::PARAM_STR);
            $stm2->bindValue(':ip', $ip, PDO::PARAM_STR);
            $stm2->bindValue(':version', $version, PDO::PARAM_STR);
            $stm2->bindValue(':password', $password, PDO::PARAM_STR);

            if ($stm2->execute())
            {
                $show_modal = 1;
                $output = "<center><b>" . $name . " Added Successfully!</b><center>";
            }
            else
            {
                $show_modal = 1;
                $output = "Error updating record: " . mysqli_error($db_handle) . "<br>";
            }
        }
        else
        {
            $show_modal = 1;
            $output = "This device already exists in the database!";
        }
    }

    else
    {
        $show_modal = 1;
        $output = "Does not appear to be a Tasmota device!!";
    }
}

if ($task == "edit")
{
$old_ip = $_POST['oldip'];
$old_name = $_POST['oldname'];

$old_folder = preg_replace('/\s+/', '', $old_name);
$old_folder = "data/" . $old_folder;

$new_folder = preg_replace('/\s+/', '', $name);
$new_folder = "data/" . $new_folder;
$old_folder = realpath("/" . $old_folder);
$new_folder = realpath("/" . $new_folder);


    $stm = $db_handle->prepare("UPDATE devices SET name = :name, ip = :ip, password = :password WHERE ip = :oldip");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->bindValue(':pasword', $password, PDO::PARAM_STR);
    $stm->bindValue(':oldip', $old_ip, PDO::PARAM_STR);
    

            if ($stm->execute())
            {
                $show_modal = 1;
		if($name !== $old_name)
			{
				if(file_exists(realpath("/" . $old_folder)))
					{
echo $old_folder . "<br>";
echo $new_folder . "<br>";
					rename($old_folder, $new_folder);
					}
			}
                $output = "<center><b>" . $name . " updated up successfully</b><br></center>";
            }
            else
            {
                $show_modal = 1;
                echo "<center><b>Error updating record: " . mysqli_error($db_handle) . "<br>";
            }

}

// SINGLE BACKUP ROUTINE
if ($task == "singlebackup")
{
    $stm = $db_handle->prepare("select * from devices where ip = :ip");
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->execute();
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC))
    {

        if(backupSingle($db_handle,$db_field['id'],$db_field['name'],$db_field['ip'],'admin',$db_field['password']))
        {
            $show_modal = 1;
            $output = "<center><b>Backup failed</b></center>";
        }

    }
}

if ($task == "backupall")
{
    $stm = $db_handle->prepare("select * from devices order by id asc");
    $stm->execute();
    $errorcount = 0;
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC))
    {
        if(backupSingle($db_handle,$db_field['id'],$db_field['name'],$db_field['ip'],'admin',$db_field['password']))
        {
          $errorcount++;
        }
    }

    $show_modal = true;
    if ($errorcount < 1)
    {

        $output = "All backups completed successfully!";

    }
    else
    {
        $output = "<font color='red'><b>Not all backups completed successfully!</b></font>";
    }
}

if ($task == "delete")
{
    $show_modal = true;
    try {
      $stm = $db_handle->prepare("delete from devices where ip = :ip");
      $stm->bindValue(':ip', $ip, PDO::PARAM_STR);

      $stm->execute();

        $output = $name . " deleted successfully from the database.";
        $output2 = "<br><font color='red'><b><i>!!NO BACKUPS WERE DELETED. PLEASE DO THIS MANUALLY!!</i></b>";

    } catch(PDOException $e) {
        $output = "Error deleting  " . $name . " : " . $e->getMessage();
    }
}

if ($task == "noofbackups")
{

    $findname = preg_replace('/\s+/', '_', $name);
    $findname = preg_replace('/[^A-Za-z0-9\-]/', '', $findname);
    $directory = "data/backups/" . $findname;
    $scanned_directory = array_diff(scandir($directory) , array(
        '..',
        '.'
    ));

    $out = array();
    foreach ($scanned_directory as $value)
    {
        $link = strtolower(implode("-", explode(" ", $value)));
        $out[] = '<a href="data/backups/' . $findname . '/' . $link . '">' . $link . '</a>';
    }
    $output = implode("<br>", $out);

    $show_modal = 1;

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
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">      
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>                  
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>                
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.16/datatables.min.css"/>
  <script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.16/datatables.min.js"></script>
<script type="text/javascript" class="init">                                                                
$(document).ready(function() {                                                                              
        $('#status').DataTable({                                                                            
        "order": [[<?PHP echo $sort; ?>, "asc" ]],
        "pageLength": <?PHP echo $amount; ?>,
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
<?PHP
$relcount = 1;

    $stm = $db_handle->prepare("select * from devices order by name desc");
    $stm->execute();
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC))
    {
        $id = $relcount;
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        $version = $db_field['version'];
        $lastbackup = $db_field['lastbackup'];
        $numberofbackups = $db_field['noofbackups'];
        $password = $db_field['password'];

        print "<tr valign='middle'><td>" . $name . "</td><td><center><a href='http://" . $ip . "' target='_blank'>" . $ip . "</a></td><td><center><img src='" . (strlen($password) > 0 ? 'lock.png' : 'lock-open-variant.png') . "'></td><td><center>" . $version . "</td><td><center>" . $lastbackup . "</td><Td><center><form method='POST' action='index.php'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='noofbackups' name='task'><input type='submit' value='" . $numberofbackups . "' class='btn-xs btn-info'></form></td><td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='singlebackup' name='task'><input type='submit' value='Backup' class='btn-xs btn-success'></form></td><td><center><form method='POST' action='edit.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='edit' name='task'><input type='submit' value='Edit' class='btn-xs btn-warning'></form></td><td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='delete' name='task'><input type='submit' value='Delete' class='btn-xs btn-danger'></form></td></tr>";
        $relcount = $relcount + 1;
    }

?>                                                                                                          
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     

<center><form method='POST' action='index.php'><input type='hidden' value='backupall' name='task'><input type='submit' value='Backup All' class='btn-xs btn-success'></form><br>
  <form method='POST' action='index.php'><input type='hidden' value='discover' name='task'><input type="text" name="ip" placeholder="ip address"><input type="password" name="password" placeholder="password"><input type='submit' value='Add' class='btn-xs btn-danger'></form>
<form method="POST" action="scan.php"><input type=text name=range placeholder="192.168.1.1-255"><input type=hidden name=task value=scan><input type=submit value=Discover class='btn-xs btn-danger'></form>
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://bit.ly/tasmobackup' target='_blank' style='color:#aaa;'>TasmoBackup 0.2 by Dan Medhurst</a></div>

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
          <?PHP if(isset($output)) echo $output; ?>
          <br>
          <?PHP if(isset($output2)) echo $output2; ?>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
