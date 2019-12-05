 <?PHP
include "config.inc.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getBetween($content,$start,$end){
    $r = explode($start, $content);
    if (isset($r[1])){
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}


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
    $url      = $ip . "/dl";
    $date     = date('Y-m-d H:i:s');
    $savedate = preg_replace('/\s+/', '_', $date);
    $savedate = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);
    
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
    
    $ch = curl_init($url);
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
            echo $noofbackups;
        }
        
        $sql2 = "UPDATE devices SET version = $version, lastbackup = '$date', noofbackups = '$noofbackups' WHERE id = '$id'";
        
        if (mysqli_query($db_handle, $sql2)) {
            echo "Record updated successfully";
        } else {
        	echo $sql2;
            echo "Error updating record: " . mysqli_error($db_handle) . "<br>";
        }
        
    } else {
        echo "Status Code: " . $statusCode;
    }
    
    
}

?> 
