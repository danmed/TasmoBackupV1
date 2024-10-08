<?php
global $settings;
global $db_upgrade;

$db_upgrade = true;

require_once(__DIR__.'/lib/functions.inc.php');

TBHeader('Upgrade',true,false,true,10);

?>
  <body>
    <div class="container-fluid">
Upgrade Complete
    </div>
<?php
TBFooter();
?>
</body>
</html>

