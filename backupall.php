<?PHP

require_once(__DIR__.'/lib/functions.inc.php');

$errorcount=backupAll(isset($_REQUEST['docker']));

TBHeader(false,false,false,false);
?>
  <body>
    <div class="container-fluid">
<?php
        if(is_array($errorcount)) {
            if($errorcount[0]==0 && $errorcount[1]==0) {
                $output = "All backups are uptodate";
            }
            if($errorcount[0]==0 && $errorcount[1]>0) {
                $output = "All ".$errorcount[1]." backups completed successfully!";
            }
            if($errorcount[0]>0 && $errorcount[1]>0) {
                $output = $errorcount[0]." backups failed out of ".$errorcount[1]." backups attempted.";
            }
        } else {
            if ($errorcount < 1) {
                $output = "All backups completed successfully!";
            } else {
                $output = "<font color='red'><b>Not all backups completed successfully!</b></font>";
            }
        }
?>
    </div>
<?php
TBFooter();
?>
</body>
</html>
