<?php
require (__DIR__.'/db.inc.php');

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
    $url = 'http://'.$user.':'.$password.'@'. $ip . '/';
    $ch = curl_init($url);
    curl_setopt_array($ch, array(
        CURLOPT_FOLLOWLOCATION => false,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_RETURNTRANSFER => true,
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
            addTasmotaDevice($ip, $user, $password);
        } else {
            return true;
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
        CURLOPT_TIMEOUT => 15,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_RETURNTRANSFER => true,
    );
    $range=15;
    if($range > count($iprange)) $range=count($iprange);
    $master = curl_multi_init();
    for($i=0;$i<$range;$i++) {
        $url = 'http://'.$user.':'.$password.'@'. $iprange[$i] . '/';
        $ch = curl_init($url);
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
                        addTasmotaDevice($url['host'], $user, $password);
                    } else {
                        array_push($result,$url['host']);
                    }
                }
            }
            unset($data);
            unset($url);
            unset($statusCode);
            if($i<count($iprange)) {
                $url = 'http://'.$user.':'.$password.'@'. $iprange[$i++] . '/';
                $ch = curl_init($url);
                curl_setopt_array($ch, $options);
                curl_multi_add_handle($master, $ch);
            }
            curl_multi_remove_handle($master, $done['handle']);
            curl_close($done['handle']);
        }
    } while($run);
    curl_multi_close($master);
    return $result;
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
    return jsonTasmotaDecode($data);
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
    return jsonTasmotaDecode($data);
}

function restoreTasmotaBackup($ip, $user, $password, $filename)
{
    $url = 'http://'.$user.':'.$password."@".$ip.'/u2';

    $cfile = new CURLFile($filename,'application/octet-stream','config.dmp');
    $fields = array('u2' => $cfile);

    $ch = curl_init($url);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $fields);
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_HTTPHEADER,array('Content-Type: multipart/form-data'));
    $result=curl_exec($ch);
    $err = curl_errno($ch);
    $statusCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if (!$err && $statusCode == 200) {
        return true;
    }
    return false;
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
    curl_setopt($ch, CURLOPT_TIMEOUT, 40);
    curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
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

function backupSingle($id, $name, $ip, $user, $password)
{
    global $settings;

    $backupfolder = $settings['backup_folder'];

    if ($status2=getTasmotaStatus2($ip, $user, $password)) {
        $version = $status2['StatusFWR']['Version'];
    } else {
        // Device is offline
        return true;
    }

    if (!isset($settings['autoupdate_name']) || (isset($settings['autoupdate_name']) && $settings['autoupdate_name']=='Y')) {
        if ($status=getTasmotaStatus($ip, $user, $password)) {
            $name=$status['Status']['FriendlyName'][0];
        }
    }

    $savename = preg_replace('/\s+/', '_', $name);
    $savename = preg_replace('/[^A-Za-z0-9_\-]/', '', $savename);
    if (!file_exists($backupfolder . $savename)) {
        $oldmask = umask(0);
        mkdir($backupfolder . $savename, 0777, true);
        umask($oldmask);
    }
    $date = date('Y-m-d H:i:s');
    $savedate = preg_replace('/(\s+|:)/', '_', $date);
    $savedate = preg_replace('/[^A-Za-z0-9_\-]/', '', $savedate);

    $saveto = $backupfolder . $savename . "/" . $savedate . ".dmp";

    if (getTasmotaBackup($ip, $user, $password, $saveto)) {
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
        if (!dbNewBackup($id, $name, $version, $date, 1, $saveto)) {
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
    $stm = $db_handle->prepare("select * from devices where lastbackup < :date or lastbackup is NULL ");
    $stm->execute(array(":date" => date('Y-m-d H:i:s',time()-(3600*$hours))));
    $errorcount = 0;
    while ($db_field = $stm->fetch(PDO::FETCH_ASSOC)) {
        if (backupSingle($db_field['id'], $db_field['name'], $db_field['ip'], 'admin', $db_field['password'])) {
            $errorcount++;
        } else {
            backupCleanup($db_field['id']);
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


function TBHeader($name=false,$favicon=true,$init=false,$track=true,$redirect=false)
{
    global $settings;
//if(isset($settings['theme']) && $settings['theme']=='dark')
//    echo '<html lang="en" class="theme-dark">';
//else
    echo '<html lang="en">';
?>
<head>
<?php 
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
/*
<style>
:root {
<?php if(isset($settings['theme']) && $settings['theme']=='dark') { ?>
  --background-color: #111;
  --page-background: #212121;
  --text-color: #ededed;
  --color-alpha: #50a8d8;
<?php } else { ?>
  --background-color: #ededed;
  --page-background: #fff;
  --text-color: #212121;
  --color-alpha: #c3423f;
<?php } ?>
}
@media (prefers-color-scheme: dark) {
  :root {
    --background-color: #111;
    --page-background: #212121;
    --text-color: #ededed;
    --color-alpha: #50a8d8;
  }
}
@media (prefers-color-scheme: light) {
  :root {
    --background-color: #ededed;
    --page-background: #fff;
    --text-color: #212121;
    --color-alpha: #c3423f;
  }
}
body {
  background-color: var(--background-color);
  color: var(--text-color);
}
.container {
  background-color: var(--page-background);
}
.text--alpha {
  color: var(--color-alpha);
}
</style>

<?php 
*/
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
  <link rel="stylesheet" href="resources/bootstrap.min.css">
  <script src="resources/jquery.min.js"></script>
  <script src="resources/bootstrap.min.js"></script>
<?php if($init!==false) { ?>
  <link rel="stylesheet" type="text/css" href="resources/datatables.min.css"/>
  <script type="text/javascript" src="resources/datatables.min.js"></script>
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

