<!DOCTYPE html>
<?php
require "lib/functions.inc.php";

if (isset($_POST['ip'])) {
    $ip = $_POST['ip'];
}

TBHeader('Edit Device',true,'
$(document).ready(function() {
        $(\'#status\').DataTable({
        "order": [[0, "asc" ]],
        "pageLength": 25,
        "statesave": true,
        "autoWidth": true
} );
} );
',true);
?>
  <body>
    <div class="container">
    <table class="table table-striped table-bordered" id="status">
    <thead>
      <tr><th colspan="4"><center><b><a href="index.php">TasmoBackup</a></th></tr>
        <tr><th><b>NAME</th><th>IP</th><th>AUTH</th><th>SUBMIT</th></tr>
    </thead>
    <tbody>

<?php
$relcount = 1;

    $devices = dbDeviceIp($ip);
    foreach ($devices as $db_field) {
        $id = $relcount;
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        $password = $db_field['password']; ?>
?>
<tr valign='middle'>
<form method='POST' action='index.php'>
<input type='hidden' name='task' value='edit'>
<input type='hidden' name='oldip' value='<?php echo $ip; ?>'>
  <td><center><input type='text' name='name' value='<?php echo $name; ?>'></center></td>
  <td><center><input type='text' name='ip' value='<?php echo $ip; ?>'></center></td>
  <td><center><input type='password' name='password' value='<?php echo $password; ?>'></center></td>
  <td><center><input type='submit' value='Submit' class='btn-xs btn-success'></center></td>
</form>
</tr>
<?php
        $relcount ++;
    }

?>
           </tbody>
    </table>
    </div>
<?php
TBFooter();
