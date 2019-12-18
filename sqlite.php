<!DOCTYPE html>                                                                                              
<?PHP
include "data/config.inc.php";
if($_POST["password"]!="")
{$device = "admin:" . $_POST["password"] . "@" . $_POST["ip"];}
else
{$device = $_POST["ip"];}
$task = $_POST["task"];
$password = $_POST["password"];
$ip = $_POST["ip"];
$name = $_POST["name"];

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
?>  

<?PHP
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

        $db = new SQLite3('data/tasmobackup.db');
        $rows = $db->query("SELECT COUNT(*) as count FROM devices where ip = '$ip'");
        $row = $rows->fetchArray();
        $checkrows = $row['count'];
        if ($checkrows < 1)
        {
            $db->exec("INSERT INTO devices (name,ip,version,password) VALUES ('$name', '$ip', '$version', '$password'");

            if (!$db)
            {
                $show_modal = 1;
                $output = "<center><b>" . $name . " Added Successfully!</b><center>";
            }
            else
            {
                $show_modal = 1;
                $output = "Error updating record: " . $error . "<br>";
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
?> 


<?PHP
// SINGLE BACKUP ROUTINE
if ($task == "singlebackup")
{
    $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
    $db_found = mysqli_select_db($db_handle, $DBName);
    $SQL = "select * from devices where ip = '" . $device . "'";
    $result = mysqli_query($db_handle, $SQL);
    while ($db_field = mysqli_fetch_assoc($result))
    {

        $name = $db_field['name'];
        $password = $db_field['password'];
        $savename = preg_replace('/\s+/', '_', $name);
        $savename = preg_replace('/[^A-Za-z0-9\-]/', '', $savename);
        if (!file_exists('data/backups/' . $savename))
        {
            $oldmask = umask(0);
            mkdir('data/backups/' . $savename, 0777, true);
            umask($oldmask);
        }
        $backupurl = "http://admin:" . $password . "@" . $device . "/dl";
        $date = date('Y-m-d H:i:s');
        $savedate = preg_replace('/\s+/', '_', $date);
        $savedate = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);

        $url = 'http://' . $device . '/cm?cmnd=status%202&user=admin&password=' . $password;
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
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
        if (curl_errno($ch))
        {
            throw new Exception(curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($statusCode == 200)
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

            $sql2 = "UPDATE devices SET version = $version, lastbackup = '$date', noofbackups = '$noofbackups' WHERE ip = '$device'";

            if (mysqli_query($db_handle, $sql2))
            {
                $show_modal = 1;
                $output = "<center><b>" . $name . " backed up successfully</b><br></center>";
            }
            else
            {
                $show_modal = 1;
                echo "<center><b>Error updating record: " . mysqli_error($db_handle) . "<br>";
            }

        }
        else
        {
            $show_modal = 1;
            $output = "<center><b>Status Code: " . $statusCode . "/b></center>";
        }

    }
}
?> 

<?PHP
if ($task == "backupall")
{
    $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
    $db_found = mysqli_select_db($db_handle, $DBName);
    $SQL = "select * from devices order by id asc";
    $result = mysqli_query($db_handle, $SQL);
    $errorcount = 0;
    while ($db_field = mysqli_fetch_assoc($result))
    {
        $id = $db_field['id'];
        $ip = $db_field['ip'];
        $password = $db_field['password'];
        $name = $db_field['name'];
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
        curl_close($ch);
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
        if (curl_errno($ch))
        {
            throw new Exception(curl_error($ch));
        }

        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        curl_close($ch);
        fclose($fp);

        if ($statusCode == 200)
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

            $sql2 = "UPDATE devices SET version = $version, lastbackup = '$date', noofbackups = '$noofbackups' WHERE id = '$id'";

            if (mysqli_query($db_handle, $sql2))
            {

            }
            else
            {

                $errorcount = $errorcount + 1;
            }

        }
        else
        {

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
?> 

<?PHP
if ($task == "delete")
{
    $show_modal = true;
    $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
    $db_found = mysqli_select_db($db_handle, $DBName);
    $SQLDELETE = "delete from devices where ip = '$device'";

    if (mysqli_query($db_handle, $SQLDELETE))
    {

        $output = $name . " deleted successfully from the database.";
        $output2 = "<br><font color='red'><b><i>!!NO BACKUPS WERE DELETED. PLEASE DO THIS MANUALLY!!</i></b>";

    }
    else
    {
        $output = "Error deleting  " . $name . " : " . mysqli_error($db_handle);
    }
}
?>

<?PHP
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
        "order": [[0, "asc" ]],
        "pageLength": 25,
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
    <tr><th colspan="8"><center><b>TasmoBackup</th></tr>                                                
        <tr><th><b>NAME1</th><th>IP</th><th><b>VERSION</th><th>LAST BACKUP</th><th><b># BACKUPS</th><th><b>BACKUP</b></th><th><b>DELETE</b></th></tr>
    </thead>                                                                                                
    <tbody>  
<?PHP
$relcount = 1;
$db = new SQLite3('data/tasmobackup.db');

if ($db_found)
{
    $res = $db->query('select * from devices order by name desc');
    
    while ($db_field = $res->fetchArray())
    {
        $id = $relcount;
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        $version = $db_field['version'];
        $lastbackup = $db_field['lastbackup'];
        $numberofbackups = $db_field['noofbackups'];
        print "<tr valign='middle'><td>" . $name . "</td><td><center><a href='http://" . $ip . "'>" . $ip . "</a></td><td><center>" . $version . "</td><td><center>" . $lastbackup . "</td><Td><center><form method='POST' action='index.php'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='noofbackups' name='task'><input type='submit' value='" . $numberofbackups . "' class='btn-xs btn-info'></form></td><td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='singlebackup' name='task'><input type='submit' value='Backup' class='btn-xs btn-success'></form></td><td><center><form method='POST' action='index.php'><input type='hidden' value='" . $ip . "' name='ip'><input type='hidden' value='" . $name . "' name='name'><input type='hidden' value='delete' name='task'><input type='submit' value='Delete' class='btn-xs btn-danger'></form></td></tr>";
        $relcount = $relcount + 1;
    }

}
?>                                                                                                          
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     

<center><form method='POST' action='index.php'><input type='hidden' value='backupall' name='task'><input type='submit' value='Backup All' class='btn-xs btn-success'></form><br>
  <form method='POST' action='sqlite.php'><input type='hidden' value='discover' name='task'><input type="text" name="ip" placeholder="ip address"><input type="text" name="password" placeholder="password"><input type='submit' value='Discover' class='btn-xs btn-danger'></form>
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://bit.ly/tasmobackup' target='_blank' style='color:#aaa;'>TasmoBackup 0.2 by Dan Medhurst</a></div>

    <?php
if ($show_modal):
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
          <?PHP echo $output; ?>
          <br>
          <?PHP echo $output2; ?>
        </p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-default" data-dismiss="modal">Close</button>
      </div>
    </div>

  </div>
</div>
