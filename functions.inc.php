<?php
require "db.inc.php";

function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function getTasmotaScan($ip, $user, $password)
{
    $url = 'http://'.$user.':'.$password.'@'. $ip . '/';
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 2);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    if (strpos($data, 'Tasmota') !== false) {
        return true;
    }
    return false;
}

function getTasmotaStatus($ip, $user, $password)
{
    //Get Name
    $url = 'http://' .$user.':'.$password.'@'. $ip . '/cm?cmnd=status&user='.$user.'&password=' . $password;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    return json_decode($data, true);
}

function getTasmotaStatus2($ip, $user, $password)
{
    //Get Version
    $url = 'http://' . $user.':'.$password.'@'. $ip . '/cm?cmnd=status%202&user='.$user.'&password=' . $password;
    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 15);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    return json_decode($data, true);
}

function getTasmotaBackup($ip, $user, $password, $filename)
{
    //Get Backup
    $url = 'http://'.$user.':'.$password."@".$ip.'/dl';

    $fp = fopen($filename, 'w+');
    if ($fp === false) {
        return false;
    }

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_FILE, $fp);
    curl_setopt($ch, CURLOPT_TIMEOUT, 20);
    curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    fclose($fp);
    curl_close($ch);
          
    if (!$err && $statusCode == 200) {
        return true;
    }
    return false;
}

function backupSingle($id, $name, $ip, $user, $password)
{
    global $db_handle;

    $backupfolder = 'data/backups/';

    $savename = preg_replace('/\s+/', '_', $name);
    $savename = preg_replace('/[^A-Za-z0-9\-]/', '', $savename);
    if (!file_exists($backupfolder . $savename)) {
        $oldmask = umask(0);
        mkdir($backupfolder . $savename, 0777, true);
        umask($oldmask);
    }
    $date = date('Y-m-d H:i:s');
    $savedate = preg_replace('/\s+/', '_', $date);
    $savedate = preg_replace('/[^A-Za-z0-9\-]/', '', $savedate);

    if ($status2=getTasmotaStatus2($ip, $user, $password)) {
        $version = $status2['StatusFWR']['Version'];

        $saveto = $backupfolder . $savename . "/" . $savedate . ".dmp";

        if (getTasmotaBackup($ip, $user, $password, $saveto)) {
            $directory = $backupfolder . $savename . "/";

            // Initialize filecount variavle
            $filecount = 0;

            $files2 = glob($directory . "*");

            if ($files2) {
                $noofbackups = count($files2);
                #echo $noofbackups;
            }

            if (!dbNewBackup($id, $name, $version, $date, $noofbackups, $saveto)) {
                return true;
            }
            return false;
        }
        return true;
    } else {
        // Device is offline
        return true;
    }
    return false;
}

function backupAll()
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices order by id asc");
    $stm->execute();
    $errorcount = 0;
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC)) {
        if (backupSingle($db_field['id'], $db_field['name'], $db_field['ip'], 'admin', $db_field['password'])) {
            $errorcount++;
        }
    }
    return $errorcount;
}

function addTasmotaDevice($ip, $user, $password)
{
    if (getTasmotaScan($ip, $user, $password)) {
        if (dbDeviceExist($ip)) {
            return $ip.': This device already exists in the database!';
        } else {
            if ($status=getTasmotaStatus($ip, $user, $password)) {
                if ($status2=getTasmotaStatus2($ip, $user, $password)) {
                    $name=$status['Status']['FriendlyName'][0];
                    $version=$status2['StatusFWR']['Version'];
                    if (dbDeviceAdd($name, $ip, $version, $password)) {
                        return $ip. ': ' . $name . ' Added Successfully!';
                    }
                    return $ip.': '. $name . ' Error adding device to database.';
                }
                return $ip.': Device not responding to status2 request.';
            }
            return $ip.': Device not responding to status request.';
        }
    }
    return $ip.': Device not found.';
}
