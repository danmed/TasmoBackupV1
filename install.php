<?PHP
$old = umask(0);
if (!file_exists('data/backups')) {
    mkdir('data/backups', 0777, true);
    umask($old);
    echo "Backup location created<br>";
}
if (!file_exists('data/tasmobackup.db')) {
        copy('tasmobackup.db', 'data/tasmobackup.db');
 echo "Database created<br>";
}
if (!file_exists('data/config.inc.php.example')) {
    copy('config.inc.php.example', 'data/config.inc.php');
    umask($old);
    echo "Config file created";
}

?>
