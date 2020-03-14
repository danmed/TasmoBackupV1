<?PHP

require(__DIR__.'/lib/functions.inc.php');

$errorcount=backupAll(isset($_REQUEST['docker']));
