<?PHP

require(__DIR__.'/lib/functions.inc.php');

$errorcount=backupAll(isset($_REQUEST['docker']));

TBHeader(false,false,false,false);
?>
  <body>
    <div class="container">
<?php
        if ($errorcount < 1) {
            $output = "All backups completed successfully!";
        } else {
            $output = "<font color='red'><b>Not all backups completed successfully!</b></font>";
        }
?>
    </div>
<?php
TBFooter();
?>
</body>
</html>
