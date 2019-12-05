<?PHP
include "config.inc.php";
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

$ip = $_GET["ip"];

$ch = curl_init($ip);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$text = curl_exec($ch);

if (strpos($text, 'Tasmota') !== false) {
    
    
    
    
    //Get Version
    $url = 'http://' . $ip . '/cm?cmnd=status%202';
    $ch  = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 5);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    curl_close($ch);
    $version = getBetween($data, '"Version":"', '"');
    
    //Get Name
    $url = 'http://' . $ip . '/cm?cmnd=status';
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
    
    $sql = "INSERT INTO devices (name,ip,version) VALUES ('$name', '$ip', '$version')";
    
    if (mysqli_query($db_handle, $sql)) {
        echo "Tasmota Device Added Successfully!";
    } else {
        echo $sql;
        echo "Error updating record: " . mysqli_error($db_handle) . "<br>";
    }
}

else {
    print "Does not appear to be a Tasmota device!!";
}
?> 
