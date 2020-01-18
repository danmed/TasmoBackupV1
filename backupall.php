<?PHP

require "db.inc.php";

GLOBAL $db_handle;

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

    $stm = $db_handle->prepare("select * from devices order by id asc");
    $stm->execute();
    $errorcount = 0;
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC))
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
            $stm2 = $db_handle->prepare("UPDATE devices SET version = :version, lastbackup = :date, noofbackups = :noofbackups WHERE  id = :id");
            $stm2->bindValue(':version', $version, PDO::PARAM_STR);
            $stm2->bindValue(':date', $date, PDO::PARAM_STR);
            $stm2->bindValue(':noofbackups', $noofbackups, PDO::PARAM_INT);
            $stm2->bindValue(':id', $id, PDO::PARAM_INT);
            $stm2->execute();
            if (!$stm2->execute())
            {
                $errorcount ++;
            }
        }
        else
        {
        }
    }
