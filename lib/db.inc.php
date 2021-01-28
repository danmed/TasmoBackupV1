<?php

require_once(__DIR__.'/../data/config.inc.php');

global $db_handle;
global $settings;

if ($DBType=='mysql') {
    $db_handle = new \PDO('mysql:host='.$DBServer.';dbname='.$DBName, $DBUser, $DBPassword);
    $GLOBALS['DBType']='mysql';
}
if ($DBType=='sqlite') {
    if (!isset($DBName)) {
        $DBName = 'data/tasmobackupdb';
    }
    if (substr_compare($DBName,'data/',0,5)==0) {
        $DBName = __DIR__.'/../'.$DBName;
    }
    $db_handle = new \PDO('sqlite:'.$DBName.'.sqlite3');
    $GLOBALS['DBType']='sqlite';
}

if ($db_handle) {
    $stm = $db_handle->prepare("select name,value from settings");
    if($stm && $stm->execute()) {
        while($result=$stm->fetch(PDO::FETCH_ASSOC)) {
            $settings[$result['name']]=$result['value'];
        }
    }
}
if(!isset($settings['backup_folder']))
    $settings['backup_folder']='data/backups/';


function dbSettingsUpdate($name,$value)
{
    global $db_handle;
    global $settings;
    $stm = $db_handle->prepare("REPLACE INTO settings(name,value) VALUES(:name,:value)");
    if(!$stm->execute(array(':value'=>$value,':name'=>$name)))
        return false;
    $settings[$name]=$value;
    return true;
}

function dbDeviceExist($ip=NULL,$mac=NULL)
{
    global $db_handle;
    if(isset($mac)){
	$stm = $db_handle->prepare("select count(*) from devices where mac = :mac");
        $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
        if ($stm->execute()) {
            if ($stm->fetchColumn() > 0 )
	        return true;
        }
    }

    if(isset($ip)) {
	$stm = $db_handle->prepare("select count(*) from devices where ip = :ip");
        $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    }
    if ($stm->execute()) {
        if ($stm->fetchColumn() > 0)
            return true;
    }
    return false;
}

function dbDeviceFind($ip=NULL,$mac=NULL)
{
    global $db_handle;
    if(isset($mac)){
	$stm = $db_handle->prepare("select id from devices where mac = :mac");
        $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
        if ($stm->execute()) {
            if (($data=$stm->fetchColumn()) > 0 )
	        return $data;
        }
    }

    if(isset($ip)) {
	$stm = $db_handle->prepare("select id from devices where ip = :ip");
        $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    }
    if ($stm->execute()) {
        if (($data=$stm->fetchColumn()) > 0)
            return $data;
    }
    return false;
}

function dbDeviceIp($ip)
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices where ip = :ip");
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbDeviceMac($mac)
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices where mac = :mac");
    $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbDeviceId($id)
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices where id = :id");
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetch(PDO::FETCH_ASSOC);
}

function dbDevices()
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices");
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbBackupId($id)
{
    global $db_handle;

    $stm = $db_handle->prepare("select * from backups where id = :id ");
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetch(PDO::FETCH_ASSOC);
}

function dbBackupList($id,$days=0)
{
    global $db_handle;

    $days=intval($days);
    $datecond='';
    if($days>0) {
        $date = date('Y-m-d H:i:s',time()-(86400*$days));
        $datecond = ' and date < "'.$date.'" ';
    }
    $stm = $db_handle->prepare("select * from backups where deviceid = :id ".$datecond." order by date desc");
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbBackupCount($id)
{
    global $db_handle;

    $stm = $db_handle->prepare("select count(*) from backups where deviceid = :id");
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchColumn();
}

function dbBackupTrim($id,$days,$count,$all=false)
{
    global $db_handle;

    $days=intval($days);
    $count=intval($count);
    if($days==0 && $count==0 && !$all)
        return true;

    $result=dbBackupList($id,$days);
    if(!is_array($result))
        return false;
    if(count($result)<1)
        return true;
    if($count>0) {
        $backups=dbBackupCount($id);
        $count=($backups-$count); // Number to save - total backups - Number over age = number to remove
        if($count>count($result))
            $count=count($result);
    } else {
        $count=count($result);
    }
    if($count>0) {
        for(;$count>0;$count--) {
            $backup=array_pop($result);
            unlink($backup['filename']);
            $stm = $db_handle->prepare("delete from backups where id = :id");
            $stm->execute(array(":id" => $backup['id']));
        }
        dbDeviceBackups($id);
    }
}

function dbBackupDel($id)
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from backups where id = :id");
    $stm->bindValue(':id',$id, PDO::PARAM_INT);
    if(!$stm->execute())
        return false;
    $row=$stm->fetch(PDO::FETCH_ASSOC);
    if(isset($row['filename']))
        unlink($row['filename']);
    $stm = $db_handle->prepare("delete from backups where id = :id");
    $stm->bindValue(':id',$id, PDO::PARAM_INT);
    return $stm->execute();
}

function dbDevicesListBackups($count)
{
    global $db_handle;
    $stm = $db_handle->prepare("select id from devices where noofbackups > :count ");
    $stm->bindValue(':count', $count, PDO::PARAM_INT);
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbDevicesSort()
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices order by name desc");
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbDeviceAdd($name, $ip, $version, $password, $mac)
{
    global $db_handle;
    $stm = $db_handle->prepare("INSERT INTO devices (name,ip,mac,version,password) VALUES (:name, :ip, :mac, :version, :password)");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
    $stm->bindValue(':version', $version, PDO::PARAM_STR);
    $stm->bindValue(':password', $password, PDO::PARAM_STR);

    return $stm->execute();
}

function dbDeviceRename($oldip, $name, $ip, $password, $mac=NULL)
{
    global $db_handle;
    $stm = $db_handle->prepare("UPDATE devices SET name = :name, ip = :ip, password = :password WHERE ip = :oldip");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->bindValue(':password', $password, PDO::PARAM_STR);
    $stm->bindValue(':oldip', $oldip, PDO::PARAM_STR);

    return $stm->execute();
}

function dbDeviceDel($ip)
{
    global $db_handle;
    $stm = $db_handle->prepare("select id from devices where ip = :ip");
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    if (!$stm->execute())
        return false;
    $id=intval($stm->fetchColumn());
    if($id==0)
        return false;
    dbBackupTrim($id,0,0,true);
    $stm = $db_handle->prepare("delete from devices where id = :id");
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    return $stm->execute();
}

function dbDeviceUpdate($id=NULL,$name=NULL,$ip=NULL,$version=NULL,$password=NULL,$mac=NULL)
{
    global $db_handle;

    $versioncond='';
    if(isset($version))
        $versioncond='version = :version, ';
    $maccond='';
    if(isset($mac))
        $maccond='mac = :mac, ';
    $namecond='';
    if(isset($name))
        $namecond='name = :name, ';
    $ipcond='';
    if(isset($ip))
        $ipcond='ip = :ip, ';
    $passwordcond='';
    if(isset($password))
        $passwordcond='password = :password ';
    if(isset($id)) {
        $stm = $db_handle->prepare('UPDATE devices SET '.$versioncond.$ipcond.$namecond.$maccond.$passwordcond.' WHERE id = :id');
echo "\r\n<!-- Doing id update \r\n".'UPDATE devices SET '.$versioncond.$maccond.$namecond.$passwordcond." WHERE id = $id  -->\r\n";
    } else if(isset($mac) && isset($ip)) {
        $stm = $db_handle->prepare('UPDATE devices SET '.$versioncond.$ipcond.$namecond.$maccond,$passwordcond.' WHERE (mac = :mac ) or (ip = :ip and mac="")');
#    } else if(isset($ip)) {
#        $stm = $db_handle->prepare('UPDATE devices SET '.$versioncond.$maccond.$namecond.$passwordcond.' WHERE ip = :ip AND mac=""');
echo "\r\n<!-- Doing mac update \r\n".'UPDATE devices SET '.$versioncond.$maccond.$namecond.$passwordcond." WHERE ip = $ip AND mac=$mac -->\r\n";
    }
    if(isset($stm)) {
        if(isset($version))
            $stm->bindValue(':version', $version, PDO::PARAM_STR);
        if(isset($password))
            $stm->bindValue(':password', $password, PDO::PARAM_STR);
        if(isset($mac))
            $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
        if(isset($name))
            $stm->bindValue(':name', $name, PDO::PARAM_STR);
        if(isset($ip))
            $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
        if(isset($id))
            $stm->bindValue(':id', $id, PDO::PARAM_INT);
        return $stm->execute();
    }
    return false;
}

function dbDeviceBackups($id,$date=NULL,$version=NULL,$name=NULL,$mac=NULL)
{
    global $db_handle;

    $count = dbBackupCount($id);
    $versioncond='';
    if(isset($version))
        $versioncond='version = :version, ';
    $maccond='';
    if(isset($mac))
        $maccond='mac = :mac, ';
    $namecond='';
    if(isset($name))
        $namecond='name = :name, ';
    $datecond='';
    if(isset($date))
        $datecond='lastbackup = :date, ';
    $stm = $db_handle->prepare("UPDATE devices SET ".$versioncond.$datecond.$namecond.$maccond.' noofbackups = :noofbackups WHERE id = :id');
    if(isset($version))
        $stm->bindValue(':version', $version, PDO::PARAM_STR);
    if(isset($mac))
        $stm->bindValue(':mac', $mac, PDO::PARAM_STR);
    if(isset($name))
        $stm->bindValue(':name', $name, PDO::PARAM_STR);
    if(isset($date))
        $stm->bindValue(':date', $date, PDO::PARAM_STR);
    $stm->bindValue(':noofbackups', $count, PDO::PARAM_STR);
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    return $stm->execute();
}

function dbNewBackup($id, $name, $version, $date, $noofbackups, $filename, $mac=NULL)
{
    global $db_handle;
    if(!isset($version) || strlen($version)<2) { $version='Unknown'; }
    $stm = $db_handle->prepare("INSERT INTO backups(deviceid,name,version,date,filename) VALUES(:deviceid, :name, :version, :date, :filename)");
    $stm->bindValue(':deviceid', $id, PDO::PARAM_INT);
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':version', $version, PDO::PARAM_STR);
    $stm->bindValue(':date', $date, PDO::PARAM_STR);
    $stm->bindValue(':filename', $filename, PDO::PARAM_STR);
    if (!$stm->execute()) {
        trigger_error("insert error: ".$stm->errorInfo()[2], E_USER_NOTICE);
        return false;
    }
    return dbDeviceBackups($id,$date,$version,$name,$mac);
}


function dbUpgrade()
{
    global $db_handle;

if ($GLOBALS['DBType']=='mysql') {
    $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
    mac varchar(32) NOT NULL,
    version varchar(128) NOT NULL,
    lastbackup datetime DEFAULT NULL,
    noofbackups int(11) DEFAULT NULL,
    password varchar(128) DEFAULT NULL )
    ");

    $db_handle->exec("CREATE TABLE IF NOT EXISTS backups (
    id bigint(20) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    deviceid int(11) NOT NULL,
    name varchar(128) NOT NULL,
    version varchar(128) NOT NULL,
    date datetime DEFAULT NULL,
    filename varchar(1080),
    data text,
    INDEX (deviceid,date) )
    ");

    $db_handle->exec("CREATE TABLE IF NOT EXISTS settings (
    name varchar(128) PRIMARY KEY NOT NULL,
    value varchar(255) NOT NULL )
    ");

    $stm=$db_handle->prepare("SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS WHERE TABLE_SCHEMA='".$GLOBALS['DBName']."' AND TABLE_NAME='devices' AND COLUMN_NAME='mac';");
    $stm->execute();
    $cnt=intval($stm->fetchColumn());
    if($cnt<1) {
        $db_handle->exec("ALTER TABLE devices ADD COLUMN mac varchar(32) NOT NULL DEFAULT '' AFTER ip;");
    }

}

if ($GLOBALS['DBType']=='sqlite') {
    $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
    mac varchar(32) NOT NULL,
    version varchar(128) NOT NULL,
    lastbackup datetime DEFAULT NULL,
    noofbackups INTEGER DEFAULT NULL,
    password varchar(128) DEFAULT NULL )
    ");

    $db_handle->exec("CREATE TABLE IF NOT EXISTS backups (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    deviceid INTEGER NOT NULL,
    name varchar(128) NOT NULL,
    version varchar(128) NOT NULL,
    date datetime DEFAULT NULL,
    filename varchar(1080),
    data text )
    ");

    $db_handle->exec("CREATE INDEX IF NOT EXISTS backupsdeviceid
    ON backups(deviceid, date)
    ");

    $db_handle->exec("CREATE TABLE IF NOT EXISTS settings (
    name varchar(128) PRIMARY KEY NOT NULL,
    value varchar(255) NOT NULL )
    ");

    @$db_handle->exec("ALTER TABLE devices ADD COLUMN mac varchar(32) NOT NULL DEFAULT ''");
}
}

