<?PHP
if (!file_exists('data/backups')) {
    mkdir('data/backups', 0777, true);
    echo "Backup location created";
}
if (!file_exists('data/tasmobackup.db')) {
	copy('tasmobackup.db', 'data/tasmobackup.db')
 echo "Database created";
}
?>
