<?php

require_once('data/config.inc.php');

global $db_handle;
global $settings;

if ($DBType=='mysql') {
    $db_handle = new \PDO('mysql:host='.$DBServer.';dbname='.$DBName, $DBUser, $DBPassword);
}
if ($DBType=='sqlite') {
    if (!isset($DBName)) {
        $DBName = 'data/tasmobackupdb';
    }
    $db_handle = new \PDO('sqlite:'.$DBName.'.sqlite3');
}

if (!$db_handle) {
}

if ($DBType=='mysql') {
    $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
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
    value varchar(255) NOT NULL
    ");
}

if ($DBType=='sqlite') {
    $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
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
    value varchar(255) NOT NULL
    ");
}

$stm = $db_handle->prepare("select name,value from settings");
if($stm->execute()) {
    while($result=$stm->fetch(PDO::FETCH_ASSOC)) {
        $settings[$result['name']]=$result['value'];
    }
}

function dbSettingsUpdate($name,$value)
{
    global $db_handle;
    $stm = $db_handle->prepare("UPDATE settings SET value = :value WHERE name = :name");
    if($stm->execute($value,$name))
        return true;
    $stm = $db_handle->prepare("INSERT INTO settings(name,value) VALUES(:name,:value)");
    if($stm->execute($name,$value))
        return true;
    return false;
}

function dbDeviceExist($ip)
{
    global $db_handle;
    $stm = $db_handle->prepare("select count(*) from devices where ip = :ip");
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    if (!$stm->execute()) {
        return false;
    }
    if ($stm->fetchColumn() < 1) {
        return false;
    } else {
        return true;
    }
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

function dbDevices()
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from devices");
    if (!$stm->execute()) {
        return false;
    }
    return $stm->fetchAll(PDO::FETCH_ASSOC);
}

function dbBackupList($name)
{
    global $db_handle;
    $stm = $db_handle->prepare("select * from backups where name = :name order by date desc");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
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

function dbDeviceAdd($name, $ip, $version, $password)
{
    global $db_handle;
    $stm = $db_handle->prepare("INSERT INTO devices (name,ip,version,password) VALUES (:name, :ip, :version, :password)");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->bindValue(':version', $version, PDO::PARAM_STR);
    $stm->bindValue(':password', $password, PDO::PARAM_STR);

    return $stm->execute();
}

function dbDeviceRename($oldip, $name, $ip, $password)
{
    global $db_handle;
    $stm = $db_handle->prepare("UPDATE devices SET name = :name, ip = :ip, password = :password WHERE ip = :oldip");
    $stm->bindValue(':name', $name, PDO::PARAM_STR);
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);
    $stm->bindValue(':pasword', $password, PDO::PARAM_STR);
    $stm->bindValue(':oldip', $oldip, PDO::PARAM_STR);

    return $stm->execute();
}

function dbDeviceDel($ip)
{
    global $db_handle;
    $stm = $db_handle->prepare("delete from devices where ip = :ip");
    $stm->bindValue(':ip', $ip, PDO::PARAM_STR);

    return $stm->execute();
}

function dbNewBackup($id, $name, $version, $date, $noofbackups, $filename)
{
    global $db_handle;
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

    $stm = $db_handle->prepare("UPDATE devices SET version = :version, lastbackup = :date, noofbackups = :noofbackups WHERE id = :id");
    $stm->bindValue(':version', $version, PDO::PARAM_STR);
    $stm->bindValue(':date', $date, PDO::PARAM_STR);
    $stm->bindValue(':noofbackups', $noofbackups, PDO::PARAM_STR);
    $stm->bindValue(':id', $id, PDO::PARAM_INT);
    return $stm->execute();
}
