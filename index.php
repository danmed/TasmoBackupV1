<!DOCTYPE html>                                                                                              
<?PHP
include "config.inc.php";
$device = $_GET["ip"];
$task   = $_GET["task"];

function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}
?>  

<?PHP
if ($task == "discover") {
    
    $ch = curl_init($device);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $text = curl_exec($ch);
    
    if (strpos($text, 'Tasmota') !== false) {
        
        
        
        
        //Get Version
        $url = 'http://' . $device . '/cm?cmnd=status%202';
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $version = getBetween($data, '"Version":"', '"');
        
        //Get Name
        $url = 'http://' . $device . '/cm?cmnd=status';
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $name = getBetween($data, 'FriendlyName":["', '"');
        $name = str_replace("'", "", $name);
        
        
        $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
        $db_found  = mysqli_select_db($db_handle, $DBName);
        $SQL       = "select * from devices order by id asc";
        
        $sql = "INSERT INTO devices (name,ip,version) VALUES ('$name', '$device', '$version')";
        
        if (mysqli_query($db_handle, $sql)) {
            echo "<center><b>Tasmota Device Added Successfully!</b><center>";
        } else {
            echo $sql;
            echo "Error updating record: " . mysqli_error($db_handle) . "<br>";
        }
    }
    
    else {
        print "Does not appear to be a Tasmota device!!";
    }
}
?> 


<?PHP
// SINGLE BACKUP ROUTINE
if ($task == "singlebackup") {
    $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
    $db_found  = mysqli_select_db($db_handle, $DBName);
    $SQL       = "select * from devices where ip = '" . $device . "'";
    $result    = mysqli_query($db_handle, $SQL);
    while ($db_field = mysqli_fetch_assoc($result)) {
        
        $name     = $db_field['name'];
        $savename = preg_replace('/\s+/', '_', $name);
        $savename = preg_replace('/[^A-Za-z0-9\-]/', '', $savename);
        if (!file_exists('backups/' . $savename)) {
            $oldmask = umask(0);
            mkdir('backups/' . $savename, 0777, true);
            umask($oldmask);
        }
        $backupurl = $device . "/dl";
        $date      = date('Y-m-d H:i:s');
        $savedate  = preg_replace('/\s+/', '_', $date);
        $savedate  = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);
        
        $url = 'http://' . $device . '/cm?cmnd=status%202';
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $version = "'" . getBetween($data, '"Version":"', '"') . "'";
        
        
        $saveto = "backups/" . $savename . "/" . $savedate . ".dmp";
        
        
        $fp = fopen($saveto, 'w+');
        if ($fp === false) {
            throw new Exception('Could not open: ' . $saveto);
        }
        
        $ch = curl_init($backupurl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if ($statusCode == 200) {
            
            $directory = "backups/" . $savename . "/";
            
            // Initialize filecount variavle 
            $filecount = 0;
            
            $files2 = glob($directory . "*");
            
            if ($files2) {
                $noofbackups = count($files2);
                #echo $noofbackups;
            }
            
            $sql2 = "UPDATE devices SET version = $version, lastbackup = '$date', noofbackups = '$noofbackups' WHERE ip = '$device'";
            
            if (mysqli_query($db_handle, $sql2)) {
                echo "<center><b>" . $name . " backed up successfully</b><br></center>";
            } else {
                echo $sql2;
                echo "<center><b>Error updating record: " . mysqli_error($db_handle) . "<br>";
            }
            
        } else {
            echo "<center><b>Status Code: " . $statusCode . "/b></center>";
        }
        
        
    }
}
?> 

<?PHP
if ($task == "backupall") {
    $db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
    $db_found  = mysqli_select_db($db_handle, $DBName);
    $SQL       = "select * from devices order by id asc";
    $result    = mysqli_query($db_handle, $SQL);
    while ($db_field = mysqli_fetch_assoc($result)) {
        $id       = $db_field['id'];
        $ip       = $db_field['ip'];
        $name     = $db_field['name'];
        $savename = preg_replace('/\s+/', '_', $name);
        $savename = preg_replace('/[^A-Za-z0-9\-]/', '', $savename);
        if (!file_exists('backups/' . $savename)) {
            $oldmask = umask(0);
            mkdir('backups/' . $savename, 0777, true);
            umask($oldmask);
        }
        $backupurl = $ip . "/dl";
        $date      = date('Y-m-d H:i:s');
        $savedate  = preg_replace('/\s+/', '_', $date);
        $savedate  = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);
        
        $url = 'http://' . $ip . '/cm?cmnd=status%202';
        $ch  = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $version = "'" . getBetween($data, '"Version":"', '"') . "'";
        
        
        $saveto = "backups/" . $savename . "/" . $savedate . ".dmp";
        
        
        $fp = fopen($saveto, 'w+');
        if ($fp === false) {
            throw new Exception('Could not open: ' . $saveto);
        }
        
        $ch = curl_init($backupurl);
        curl_setopt($ch, CURLOPT_FILE, $fp);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        curl_exec($ch);
        if (curl_errno($ch)) {
            throw new Exception(curl_error($ch));
        }
        
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        curl_close($ch);
        fclose($fp);
        
        if ($statusCode == 200) {
            
            $directory = "backups/" . $savename . "/";
            
            // Initialize filecount variavle 
            $filecount = 0;
            
            $files2 = glob($directory . "*");
            
            if ($files2) {
                $noofbackups = count($files2);
                #echo $noofbackups;
            }
            
            $sql2 = "UPDATE devices SET version = $version, lastbackup = '$date', noofbackups = '$noofbackups' WHERE id = '$id'";
            
            if (mysqli_query($db_handle, $sql2)) {
                
            } else {
                echo $sql2;
                echo "Error updating record: " . mysqli_error($db_handle) . "<br>";
            }
            
        } else {
            echo "Status Code: " . $statusCode;
        }
        
        
    }
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
                                                                                                            
<title>Tasmo Backup</title>                                                                              
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
        "order": [[0, "desc" ]]                                                                              
} );            
} );                                                                                                        
                                                                                                            
        </script>                                                                                            
</head>                                                                                                      
                                                                                                            
  <body><font size="2">                                                                                      
                                                  
    <div class="container">                                                                                  
    <table class="table table-striped table-bordered" id="status">                                          
    <thead>                                                                                                  
    <tr><th colspan="7"><center><b>Tasmo Backup</th></tr>                                                
        <tr><th><b>NAME</th><th>IP</th><th><b>VERSION</th><th>LAST BACKUP</th><th><b># BACKUPS</th><th><b>BACKUP</b></th></tr>
    </thead>                                                                                                
    <tbody>  
<?PHP
$relcount  = 1;
$db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
$db_found  = mysqli_select_db($db_handle, $DBName);

if ($db_found) {
    $SQL    = "select * from devices order by id asc";
    $result = mysqli_query($db_handle, $SQL);
    while ($db_field = mysqli_fetch_assoc($result)) {
        $id              = $relcount;
        $name            = $db_field['name'];
        $ip              = $db_field['ip'];
        $version         = $db_field['version'];
        $lastbackup      = $db_field['lastbackup'];
        $numberofbackups = $db_field['noofbackups'];
        
        print "<tr><td>" . $name . "</td><td>" . $ip . "</td><td>" . $version . "</td><td>" . $lastbackup . "</td><Td>" . $numberofbackups . "</td><td><a href='index.php?task=singlebackup&ip=" . $ip . "'>BACKUP</a></td></tr>";
        $relcount = $relcount + 1;
    }
    
    mysqli_close($db_handle);
    
}
?>                                                                                                          
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     
