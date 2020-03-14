<?php
require(__DIR__.'/lib/functions.inc.php');

global $settings;

dbUpgrade();

TBHeader('Upgrade',true,false,true,10);

?>
  <body>
    <div class="container">
Upgrade Complete
    </div>
<?php
TBFooter();
?>
</body>
</html>

