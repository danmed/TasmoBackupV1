<?php

require_once('data/config.inc.php');

global $db_handle;

if($DBType=='mysql') {
  $db_handle = new \PDO( 'mysql:host='.$DBServer.';dbname='.$DBName, $DBUser, $DBPassword);
}
if($DBType=='sqlite') {
  if(!isset($DBName)) { $DBName = 'data/tasmobackupdb'; }
  $db_handle = new \PDO( 'sqlite:'.$DBName.'.sqlite3');
}

if(!$db_handle) {
}

if($DBType=='mysql') {
  $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id int(11) AUTO_INCREMENT PRIMARY KEY NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
    version varchar(128) NOT NULL,
    lastbackup datetime DEFAULT NULL,
    noofbackups int(11) DEFAULT NULL,
    password varchar(128) DEFAULT NULL )
    ");
}

if($DBType=='sqlite') {
  $db_handle->exec("CREATE TABLE IF NOT EXISTS devices (
    id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
    name varchar(128) NOT NULL,
    ip varchar(64) NOT NULL,
    version varchar(128) NOT NULL,
    lastbackup datetime DEFAULT NULL,
    noofbackups INTEGER DEFAULT NULL,
    password varchar(128) DEFAULT NULL )
    ");
}

