<?php
require_once (__DIR__.'/db.inc.php');

$strJsonFileContents = file_get_contents(__DIR__.'/../HA_addon/config.json');
$array = json_decode($strJsonFileContents, true);
$GLOBALS['VERSION']=$array['version'];

function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1])) {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}

function jsonTasmotaDecode($json)
{
    $data=json_decode($json,true);
    if(json_last_error() == JSON_ERROR_CTRL_CHAR) {
        $data=json_decode(preg_replace('/[[:cntrl:]]/','',$json),true);
    }
    if(json_last_error() !== JSON_ERROR_NONE) {
	$string = substr( $json, strpos( $json, "STATUS = " ) );
        if( strpos( $string, "POWER = " ) !== FALSE ) {
            $string = substr( $string, strpos( $string, "{" ) );
            $string = substr( $string, 0, strrpos( $string, "}" )+1 );
        }
        if( strpos( $string, "ERGEBNIS = " ) !== FALSE ) {
            $string = substr( $string, strpos( $string, "{" ) );
            $string = substr( $string, 0, strrpos( $string, "}" )+1 );
        }
        if( strpos( $string, "RESULT = " ) !== FALSE ) {
            $string = substr( $string, strpos( $string, "{" ) );
            $string = substr( $string, 0, strrpos( $string, "}" )+1 );
        }
        $remove  = [ PHP_EOL, "\n", "STATUS = ", "}STATUS1 = {", "}STATUS2 = {",
            "}STATUS3 = {", "}STATUS4 = {", "}STATUS5 = {", "}STATUS6 = {",
            "}STATUS7 = {", "}in = {", "}STATUS8 = {", "}STATUS9 = {", "}STATUS10 = {",
            "}STATUS11 = {", "STATUS2 = ", ":nan,", ":nan}", ];
        $replace = [ "", "", "", ",", ",", ",", ",", ",", ",", ",", ",", ",",
            ",", ",", ",", "", ":\"NaN\",", ":\"NaN\"}", ];
        $string = str_replace( $remove, $replace, $string );
        //remove everything befor ethe first {
        $string = strstr( $string, '{' );
        $data=json_decode($string,true);
        if(json_last_error() !== JSON_ERROR_NONE) {
            $data=array();
        }
    }
    return $data;
}

function getTasmotaScan($ip, $user, $password)
{
    $url = 'http://'.rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/';
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
    ));
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    if (strpos($data, 'Tasmota') !== false) {
        if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
            addTasmotaDevice($ip, $user, $password, true, false, 0);
        } else {
            return 0;
        }
    }
    if (strpos($data, 'WLED') !== false) {
        if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
            addTasmotaDevice($ip, $user, $password, true, false, 1);
        } else {
            return 1;
        }
    }
    return false;
}

function getTasmotaScanRange($iprange, $user, $password)
{
    global $settings;

    $result=array();
    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
    );
    $range=15;
    if($range > count($iprange)) $range=count($iprange);
    $master = curl_multi_init();
    for($i=0;$i<$range;$i++) {
        $url = 'http://'.rawurlencode($user).':'.rawurlencode($password).'@'. $iprange[$i] . '/';
        $ch = curl_init($url);
        $options[CURLOPT_REFERER]='http://'.$iprange[$i].'/';
        $options[CURLOPT_HTTPHEADER]=array('Origin: http://'.$iprange[$i]);
        curl_setopt_array($ch, $options);
        curl_multi_add_handle($master, $ch);
    }
    $i--;

    do {
        while(($execrun = curl_multi_exec($master, $run)) == CURLM_CALL_MULTI_PERFORM) { ; }
        if($execrun != CURLM_OK) {
            break;
        }
        while($done = curl_multi_info_read($master)) {
            $statusCode = curl_getinfo($done['handle'], CURLINFO_HTTP_CODE);
            $url = parse_url(curl_getinfo($done['handle'], CURLINFO_EFFECTIVE_URL));
            $data = curl_multi_getcontent($done['handle']);
            if ($statusCode == 200) {
                if (strpos($data, 'Tasmota') !== false) {
                    if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
                        addTasmotaDevice($url['host'], $user, $password, true);
                    } else {
                        array_push($result,array($url['host'],0));
                    }
                }
                if (strpos($data, 'WLED') !== false) {
                    if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
                        addTasmotaDevice($url['host'], $user, $password, true, false, 1);
                    } else {
                        array_push($result,array($url['host'],1));
                    }
                }
            }
            unset($data);
            unset($url);
            unset($statusCode);
            if($i<count($iprange)) {
                $url = 'http://'.rawurlencode($user).':'.rawurlencode($password).'@'. $iprange[$i] . '/';
                $ch = curl_init($url);
                $options[CURLOPT_REFERER]='http://'.$iprange[$i].'/';
                $options[CURLOPT_HTTPHEADER]=array('Origin: http://'.$iprange[$i]);
                curl_setopt_array($ch, $options);
                $i++;
                curl_multi_add_handle($master, $ch);
            }
            curl_multi_remove_handle($master, $done['handle']);
            curl_close($done['handle']);
        }
    } while($run);
    curl_multi_close($master);
    return $result;
}

function getTasmotaStatus($ip, $user, $password, $type=0)
{
    //Get Name
    $url = 'http://' .rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/cm?cmnd=status%200&user='.rawurlencode($user).'&password=' . rawurlencode($password);
    if(intval($type)===1)
        $url = 'http://' .rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/json';
    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    $json=jsonTasmotaDecode($data);
    if(isset($json["Status"]))
        return $json;
    if(isset($json["info"]))
        return $json;
    sleep(1);
    $data=getTasmotaOldStatus($ip, $user, $password);
    if(isset($data['Status'])) {
        $json["Status"]=$data["Status"];
    }
    return $json;
}

function getTasmotaOldStatus($ip, $user, $password)
{
    //Get Name
    $url = 'http://' .rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/cm?cmnd=status&user='.rawurlencode($user).'&password=' . rawurlencode($password);
    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    return jsonTasmotaDecode($data);
}

function getTasmotaStatus2($ip, $user, $password)
{
    //Get Version
    $url = 'http://' . rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/cm?cmnd=status%202&user='.rawurlencode($user).'&password=' . rawurlencode($password);
    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    return jsonTasmotaDecode($data);
}

function getTasmotaStatus5($ip, $user, $password)
{
    //Get Mac
    $url = 'http://' . rawurlencode($user).':'.rawurlencode($password).'@'. $ip . '/cm?cmnd=status%205&user='.rawurlencode($user).'&password=' . rawurlencode($password);
    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 30,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);
    $data = curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($err || $statusCode != 200) {
        return false;
    }
    return jsonTasmotaDecode($data);
}

function restoreTasmotaBackup($ip, $user, $password, $filename)
{
    $url = 'http://'.rawurlencode($user).':'.rawurlencode($password)."@".$ip.'/u2';

    $cfile = new CURLFile($filename,'application/octet-stream','config.dmp');
    $fields = array('u2' => $cfile);

    $options = array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 60,
        CURLOPT_CONNECTTIMEOUT => 12,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $fields,
        CURLOPT_HTTPHEADER => array('Content-Type: multipart/form-data'),
        CURLOPT_ENCODING => "",
        CURLOPT_REFERER => 'http://'.$ip.'/',
        CURLOPT_HTTPHEADER => array('Origin: http://'.$ip, 'Expect:'),
    );
    $ch = curl_init($url);
    curl_setopt_array($ch, $options);

    $result=curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$err && $statusCode == 200) {
        return true;
    }
    return false;
}

function downloadTasmotaBackup($backup)
{
    if(file_exists($backup['filename'])) {
        $filename = $backup['name'] . '-' . $backup['version'] . '-' . $backup['date'];
        $filename = preg_replace('/(\s+|:|\.|\()/', '_', $filename);
        $filename = preg_replace('/[^A-Za-z0-9_\-]/', '', $filename);
        header("Cache-Control: no-cache private",true);
        header("Content-Description: Backup ".$backup['name']);
        header('Content-disposition: attachment; filename="'.$filename.'.dmp"',true);
        header("Content-Type: application/octet-stream",true);
        header("Content-Transfer-Encoding: binary",true);
        header('Content-Length: '. filesize($backup['filename']),true);
        readfile($backup['filename']);
        exit(0);
    }
    return false;
}

function getTasmotaBackup($ip, $user, $password, $filename, $type=0)
{
    //Get Backup

    if(intval($type)===0) { // Tasmota
        $fp = fopen($filename, 'w+');
        if ($fp === false) {
            return false;
        }
        $url = 'http://'.rawurlencode($user).':'.rawurlencode($password)."@".$ip.'/dl';
        $options = array(
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
            CURLOPT_ENCODING => "",
            CURLOPT_REFERER => 'http://'.$ip.'/',
            CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        curl_setopt($ch,CURLOPT_FILE,$fp);
        curl_exec($ch);
        $err = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        fclose($fp);
        curl_close($ch);

        if (!$err && $statusCode == 200) {
            return true;
        }
    } else if(intval($type)===1) { // WLED
        $url = 'http://'.rawurlencode($user).':'.rawurlencode($password)."@".$ip.'/edit?download=cfg.json';
        $options = array(
            CURLOPT_FOLLOWLOCATION => false,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_CONNECTTIMEOUT => 12,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERAGENT => 'TasmoBackup '.$GLOBALS['VERSION'],
            CURLOPT_ENCODING => "",
            CURLOPT_REFERER => 'http://'.$ip.'/',
            CURLOPT_HTTPHEADER => array('Origin: http://'.$ip),
        );
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $cfg = curl_exec($ch);
        $err = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($err || $statusCode !== 200)
            return false;

        $url = 'http://'.rawurlencode($user).':'.rawurlencode($password)."@".$ip.'/edit?download=presets.json';
        $ch = curl_init($url);
        curl_setopt_array($ch, $options);
        $presets = curl_exec($ch);
        $err = curl_errno($ch);
        $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if($err || $statusCode !== 200)
            return false;

        $zip = new ZipArchive;
        if($zip->open($filename, ZipArchive::CREATE) === FALSE)
            return false;
        if($zip->addFromString('cfg.json', $cfg) === FALSE)
            return false;
        if($zip->addFromString('presets.json', $presets) === FALSE)
            return false;
        $zip->close();
        return true;
    }

    return false;
}

function backupCleanup($id)
{
    global $settings;

    $backupfolder = $settings['backup_folder'];

    $days=0;
    $count=0;
    if(isset($settings['backup_maxdays']))
        $days=intval($settings['backup_maxdays']);
    if(isset($settings['backup_maxcount']))
        $count=intval($settings['backup_maxcount']);
    if($days>0 || $count>0)
        return dbBackupTrim($id,$days,$count);
    return true;
}

function backupSingle($id, $name, $ip, $user, $password, $type=0)
{
    global $settings;

    $backupfolder = $settings['backup_folder'];

    if ($status=getTasmotaStatus($ip, $user, $password, $type)) {
        if(intval($type)===0) { // Tasmota
            if (!isset($status['StatusFWR'])) {
                sleep(1);
                if ($status2=getTasmotaStatus2($ip, $user, $password)) {
                    $status['StatusFWR']=$status2['StatusFWR'];
                } else
                    return true; // Device Offline
            }
	    if (!isset($status['StatusNET'])) {
                sleep(1);
                if ($status5=getTasmotaStatus5($ip, $user, $password)) {
                    $status['StatusNET']=$status5['StatusNET'];
                } else
                    return true; // Device Offline
            }
        }
        if(intval($type)===1) { // WLED
            if (!isset($status['info']['ver']))
                return true;
        }
    } else {
        return true; // Device Offline
    }

    if(intval($type)===0) { // Tasmota
        $version = $status['StatusFWR']['Version'];
        $mac = strtoupper($status['StatusNET']['Mac']);

        if (!isset($settings['autoupdate_name']) || (isset($settings['autoupdate_name']) && $settings['autoupdate_name']=='Y')) {
            if(isset($settings['use_topic_as_name']) && $settings['use_topic_as_name']=='F') {
            // Empty
            } else {
                if (isset($status['Status']['Topic']))
                    $name=$status['Status']['Topic'];
                if(!isset($settings['use_topic_as_name']) || $settings['use_topic_as_name']=='N') {
                    if (isset($status['Status']['DeviceName']) && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                        $name=$status['Status']['DeviceName'];
                    else if (isset($status['Status']['FriendlyName'][0]))
                        $name=$status['Status']['FriendlyName'][0];
                }
            }
        }
    } else if (intval($type)===1) { // WLED
        if(isset($status['info']['name']))
            $name=trim($status['info']['name']);
        if(isset($status['info']['ver']))
            $version=trim($status['info']['ver']);
        if(isset($status['info']['mac']))
            $mac=implode(':',str_split(str_replace(array('.',':'),array('',''),trim($status['info']['mac'])),2));
    }

    $savename = preg_replace('/\s+/', '_', $name);
    $savename = preg_replace('/[^A-Za-z0-9_\-]/', '', $savename);
    $savemac = preg_replace('/[^A-Za-z0-9_\-]/','', $mac);
    if (!file_exists($backupfolder . $savename)) {
        $oldmask = umask(0);
        mkdir($backupfolder . $savename, 0777, true);
        umask($oldmask);
    }
    $date = date('Y-m-d H:i:s');
    $savedate = preg_replace('/(\s+|:)/', '_', $date);
    $savedate = preg_replace('/[^A-Za-z0-9_\-]/', '', $savedate);

    $ext='.dmp';
    if(intval($type)===1) $ext='.zip';


    $saveto = $backupfolder . $savename . "/" . $savemac . "-" . $savedate . $ext;

    sleep(1);
    if (getTasmotaBackup($ip, $user, $password, $saveto, $type)) {
        $directory = $backupfolder . $savename . "/";
/*
        // Initialize filecount variavle
        $filecount = 0;

        $files2 = glob($directory . "*");

        if ($files2) {
            $noofbackups = count($files2);
            #echo $noofbackups;
        }
*/
        if (!dbNewBackup($id, $name, $version, $date, 1, $saveto, $mac, $type)) {
            return true;
        }
        return false;
    }
    return false;
}

function backupAll($docker=false)
{
    global $db_handle;
    global $settings;

    $hours=0;
    if(isset($settings['backup_minhours']))
        $hours=intval($settings['backup_minhours']);
    if($docker && $hours==0)
        return false;
    if ($docker && isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') { // auto scan on schedule
        if(isset($settings['mqtt_host']) && isset($settings['mqtt_port']) && strlen($settings['mqtt_host'])>1) {
            require_once(__DIR__.'/mqtt.inc.php');
            $mqtt=setupMQTT($settings['mqtt_host'], $settings['mqtt_port'], $settings['mqtt_user'], $settings['mqtt_password']);
            $username='admin';
            if(isset($settings['tasmota_username'])) $username=$settings['tasmota_username'];
            $password='';
            if(isset($settings['tasmota_password'])) $password=$settings['tasmota_password'];
            if($mqtt) getTasmotaMQTTScan($mqtt,$settings['mqtt_topic'],$username,$password,true);
        }
    }
    $stm = $db_handle->prepare("select * from devices where lastbackup < :date or lastbackup is NULL ");
    $stm->execute(array(":date" => date('Y-m-d H:i:s',time()-(3600*$hours))));
    $errorcount = 0;
    $totalcount = 0;
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC)) {
        $totalcount++;
        if (backupSingle($db_field['id'], $db_field['name'], $db_field['ip'], 'admin', $db_field['password'], $db_field['type'])) {
            $errorcount++;
        } else {
            backupCleanup($db_field['id']);
        }
    }
    return array($errorcount,$totalcount);
}

function addTasmotaDevice($ip, $user, $password, $verified=false, $status=false, $type=null)
{
    global $settings;

    if(!$verified || !isset($type)) {
        if (($type=getTasmotaScan($ip, $user, $password))===false) {
            return $ip.': Device not found.';
        }
    }
    if (!dbDeviceExist($ip)) {
        if ($status===false)
            $status=getTasmotaStatus($ip, $user, $password, $type);
        if (isset($status) && $status) {
            if(intval($type)===0) { // Tasmota
                if (!isset($status['StatusNET'])) {
                    sleep(1);
                    if ($status5=getTasmotaStatus5($ip, $user, $password))
                        $status['StatusNET']=$status5['StatusNET'];
                    else 
                        return $ip.': Device not responding to status5 request.';
                }
                if(!isset($status['StatusFWR'])) {
                    sleep(1);
                    if ($status2=getTasmotaStatus2($ip, $user, $password))
                        $status['StatusFWR']=$status2['StatusFWR'];
                    else
                        return $ip.': Device not responding to status2 request.';
                }
                if(isset($settings['use_topic_as_name']) && $settings['use_topic_as_name']=='F' && isset($status['Topic'])) {
                    $name=trim(str_replace(array('/stat','stat/'),array('',''),$status['Topic'])," \t\r\n\v\0/");;
                } else {
                    if (isset($status['Status']['Topic']))
                        $name=$status['Status']['Topic'];
                    if(!isset($settings['use_topic_as_name']) || $settings['use_topic_as_name']=='N') {
                        if (isset($status['Status']['DeviceName']) && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                            $name=$status['Status']['DeviceName'];
                        else if ($status['Status']['FriendlyName'][0])
                            $name=$status['Status']['FriendlyName'][0];
                    }
                }
                if (isset($status['StatusFWR']['Version']))
                    $version=$status['StatusFWR']['Version'];
                if (isset($status['StatusNET']['Mac']))
                    $mac=strtoupper($status['StatusNET']['Mac']);
            } else if (intval($type)===1) { // WLED
                if(isset($status['info']['name']))
                    $name=trim($status['info']['name']);
                if(isset($status['info']['ver']))
                    $version=trim($status['info']['ver']);
                if(isset($status['info']['mac']))
                    $mac=implode(':',str_split(str_replace(array('.',':'),array('',''),trim($status['info']['mac'])),2));
            }
            if (($id=dbDeviceFind($ip,$mac))>0) {
                if (!isset($settings['autoupdate_name']) || (isset($settings['autoupdate_name']) && $settings['autoupdate_name']=='Y'))
                    $newname=$name;
                if(dbDeviceUpdate($id,$newname,$ip,$version,$password,$mac,$type))
                    return $ip.': ' . $name . ' infomation has been updated!';
                else
                    return $ip.': ' . $name . ' already exists in the database!';
            } else {
                if (dbDeviceAdd($name, $ip, $version, $password, $mac, $type)) {
                    return $ip.': ' . $name . ' Added Successfully!';
                }
            }
            return $ip.': '. $name . ' Error adding device to database.';
        }
        return $ip.': Device not responding to status request.';
    } else { // Update device metadata, but only if scanned via mqtt as not to add more overhead
        if (isset($status) && $status) {
            if(intval($type)===0) {
                if(isset($settings['use_topic_as_name']) && $settings['use_topic_as_name']=='F' && isset($status['Topic'])) {
                    $name=trim(str_replace(array('/stat','stat/'),array('',''),$status['Topic'])," \t\r\n\v\0/");;
                } else {
                    if (isset($status['Status']['Topic']))
                        $name=$status['Status']['Topic'];
                    if(!isset($settings['use_topic_as_name']) || $settings['use_topic_as_name']=='N') {
                        if (isset($status['Status']['DeviceName']) && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                            $name=$status['Status']['DeviceName'];
                        else if (isset($status['Status']['FriendlyName'][0]))
                            $name=$status['Status']['FriendlyName'][0];
                    }
                }
                if (isset($status['StatusFWR']['Version']))
                    $version=$status['StatusFWR']['Version'];
                if (isset($status['StatusNET']['Mac']))
                    $mac=strtoupper($status['StatusNET']['Mac']);
            } else if (intval($type)===1) { // WLED
                if(isset($status['info']['name']))
                    $name=trim($status['info']['name']);
                if(isset($status['info']['ver']))
                    $version=trim($status['info']['ver']);
                if(isset($status['info']['mac']))
                    $mac=implode(':',str_split(str_replace(array('.',':'),array('',''),trim($status['info']['mac'])),2));
            }
            if (($id=dbDeviceFind($ip,$mac))>0) {
                if (!isset($settings['autoupdate_name']) || (isset($settings['autoupdate_name']) && $settings['autoupdate_name']=='Y') && isset($name))
                    $newname=$name;
                if(dbDeviceUpdate($id,isset($newname)?$newname:NULL,$ip,isset($version)?$version:NULL,$password,isset($mac)?$mac:NULL,$type))
                    return $ip.': ' . (isset($name)?$name:'') . ' infomation has been updated!';
                else
                    return $ip.': ' . (isset($name)?$name:'') . ' already exists in the database!';
            }
        }
    }
    return $ip.': This device already exists in the database!';
}


function TBHeader($name=false,$favicon=true,$init=false,$track=true,$redirect=false)
{
    global $settings;
    echo '<!DOCTYPE html><html lang="en"><head>';
if($redirect!==false && $redirect>0) {
    echo '<meta http-equiv="refresh" content="'.$redirect.';url=index.php" />';
}
if($favicon) {
?>
<link rel="shortcut icon" href="favicon.ico">
<link rel="icon" sizes="16x16 32x32 64x64" href="favicon.ico">
<link rel="icon" type="image/png" sizes="196x196" href="favicon/192.png">
<link rel="icon" type="image/png" sizes="160x160" href="favicon/160.png">
<link rel="icon" type="image/png" sizes="96x96" href="favicon/96.png">
<link rel="icon" type="image/png" sizes="64x64" href="favicon/64.png">
<link rel="icon" type="image/png" sizes="32x32" href="favicon/32.png">
<link rel="icon" type="image/png" sizes="16x16" href="favicon/16.png">
<link rel="apple-touch-icon" href="favicon/57.png">
<link rel="apple-touch-icon" sizes="114x114" href="favicon/114.png">
<link rel="apple-touch-icon" sizes="72x72" href="favicon/72.png">
<link rel="apple-touch-icon" sizes="144x144" href="favicon/144.png">
<link rel="apple-touch-icon" sizes="60x60" href="favicon/60.png">
<link rel="apple-touch-icon" sizes="120x120" href="favicon/120.png">
<link rel="apple-touch-icon" sizes="76x76" href="favicon/76.png">
<link rel="apple-touch-icon" sizes="152x152" href="favicon/152.png">
<link rel="apple-touch-icon" sizes="180x180" href="favicon/180.png">
<?php }

if($track) { ?>
<!-- Global site tag (gtag.js) - Google Analytics -->
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-116906-4"></script>
<script>
  window.dataLayer = window.dataLayer || [];
  function gtag(){dataLayer.push(arguments);}
  gtag('js', new Date());

  gtag('config', 'UA-116906-4');
</script>
<?php } ?>
<title>TasmoBackup<?php if($name!==false) { echo ': '.$name; } ?></title>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
<?php if(isset($settings['theme']) && $settings['theme']=='dark') { // Enforce Dark mode
?>
  <link rel="stylesheet" href="resources/bootstrap.dark.min.css">
<?php } else if(isset($settings['theme']) && $settings['theme']=='light') { // Enforce Light mode
?>
  <link rel="stylesheet" href="resources/bootstrap.min.css">
<?php } else { // AutoDetect
?>
<script>
  // If `prefers-color-scheme` is not supported, fall back to light mode.
  if (window.matchMedia('(prefers-color-scheme: dark)').media === 'not all') {
    document.documentElement.style.display = 'none';
    document.head.insertAdjacentHTML(
        'beforeend',
        '<link rel="stylesheet" href="resources/bootstrap.min.css" onload="document.documentElement.style.display = \'\'">'
    );
  }
</script>
  <link rel="stylesheet" href="resources/bootstrap.min.css" media="(prefers-color-scheme: no-reference), (prefers-color-scheme: light)">
  <link rel="stylesheet" href="resources/bootstrap.dark.min.css" media="(prefers-color-scheme: dark)">
<?php } ?>
  <script src="resources/jquery.min.js"></script>
  <script src="resources/bootstrap.min.js"></script>
<?php if($init!==false) { ?>
  <script type="text/javascript" src="resources/datatables.min.js"></script>
  <script type="text/javascript" src="resources/sorting.min.js"></script>
  <link rel="stylesheet" type="text/css" href="resources/datatables.min.css"/>
<script type="text/javascript" class="init">
<?php echo $init; ?>
</script>
<?php } ?>
</head>
<?php
}

function TBFooter()
{
    global $VERSION;
?>
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://github.com/danmed/TasmoBackupV1' target='_blank' style='color:#aaa;'>TasmoBackup <?php echo $GLOBALS['VERSION']; ?> by Dan Medhurst</a></div>
<?php
}

function getGithubTasmotaReleaseData()
{
    global $settings;

    // put the releases json in the backup_folder
    $backupfolder = $settings['backup_folder'];
    $get_new_version = false;

    $github_tasmota_release_data_file = $backupfolder . "/github-tasmota-release-data.json";
    // check if file exists
    if ( file_exists($github_tasmota_release_data_file) === true ) {
        $_mtime = filemtime($github_tasmota_release_data_file);
        $yesterday = strtotime("-1 days");
        if ( $_mtime <= $yesterday ) {
            $get_new_version = true;
        }
    } else {
        $get_new_version = true;
    }

    if ( $get_new_version ) {
        // get Tasmota release data from github - but only if it is the next calendar day
        $opts = [
            'http' => [
                'method' => 'GET',
                'header' => [
                    'User-Agent: PHP'
                ]
            ]
        ];
        $context = stream_context_create($opts);
        $github_tasmota_version_release_details = file_get_contents("https://api.github.com/repos/arendst/Tasmota/releases", false, $context);
        file_put_contents($github_tasmota_release_data_file, $github_tasmota_version_release_details);
    } else {
        $github_tasmota_version_release_details = file_get_contents($github_tasmota_release_data_file);
    }
    $github_tasmota_release_data = json_decode($github_tasmota_version_release_details, true);

    return $github_tasmota_release_data;
}
