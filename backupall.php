<?PHP

include "data/config.inc.php";
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
?> 
