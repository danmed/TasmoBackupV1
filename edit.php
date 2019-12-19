<!DOCTYPE html>                                                                                              
<?PHP
include "data/config.inc.php";

$ip = $_POST["ip"];

?>
<html lang="en">                                                                                            
<head>                                                                                                      
<!-- Global site tag (gtag.js) - Google Analytics -->                                                        
<script async src="https://www.googletagmanager.com/gtag/js?id=UA-116906-4"></script>                        
<script>                                                                                                    
  window.dataLayer = window.dataLayer || [];                                                                
  function gtag(){dataLayer.push(arguments);}                                                                
  gtag('js', new Date());                                                                                    
                                                                                                            
  gtag('config', 'UA-116906-4');                                                                            
</script>                                                                                                    
                                                                                                            
<title>TasmoBackup</title>                                                                              
  <meta charset="utf-8">                                                                                    
  <meta name="viewport" content="width=device-width, initial-scale=1">                                      
  <link rel="stylesheet" href="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/css/bootstrap.min.css">      
  <script src="https://ajax.googleapis.com/ajax/libs/jquery/3.2.0/jquery.min.js"></script>                  
  <script src="https://maxcdn.bootstrapcdn.com/bootstrap/3.3.7/js/bootstrap.min.js"></script>                
  <link rel="stylesheet" type="text/css" href="https://cdn.datatables.net/v/bs4/dt-1.10.16/datatables.min.css"/>
  <script type="text/javascript" src="https://cdn.datatables.net/v/bs4/dt-1.10.16/datatables.min.js"></script>
<script type="text/javascript" class="init">                                                                
$(document).ready(function() {                                                                              
        $('#status').DataTable({                                                                            
        "order": [[0, "asc" ]],
        "pageLength": 25,
        "statesave": true,
        "autoWidth": true
} );            
} );                                                                                                        
                                                                                                            
        </script>                                                                                            
</head>                                                                                                      
                                                                                                            
  <body><font size="2">                                                                                      
                                                  
    <div class="container">                                                                                  
    <table class="table table-striped table-bordered" id="status">                                          
    <thead>                                                                                                  
    <tr><th colspan="4"><center><b>TasmoBackup</th></tr>                                                
        <tr><th><b>NAME</th><th>IP</th><th>AUTH</th><th>SUBMIT</th></tr>
    </thead>                                                                                                
    <tbody>  

<?PHP
$relcount = 1;
$db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
$db_found = mysqli_select_db($db_handle, $DBName);

if ($db_found)
{
    $SQL = "select * from devices where ip = '$ip'";
    $result = mysqli_query($db_handle, $SQL);
    while ($db_field = mysqli_fetch_assoc($result))
    {
        $id = $relcount;
        $name = $db_field['name'];
        $ip = $db_field['ip'];
        $password = $db_field['password'];

?>



<?PHP
        print "<tr valign='middle'><td><form method='POST' action='index.php'><input type='hidden' name='name' value='" . $name . "'><input type='hidden' name='task' value='edit'><input type='hidden' name='oldip' value='" . $ip . "'><input type='hidden' name='oldip' value='" . $ip . "'>" . $name . "</td><td><center><input type='text' name='ip' value='" . $ip . "'></td><td><center><center><input type='password' name='password' value='" . $password . "'></td><td><center><input type='submit' value='Submit' class='btn-xs btn-success'></form></td></tr>";
        $relcount = $relcount + 1;
    }

    mysqli_close($db_handle);

}
?>                                                                                                          
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://bit.ly/tasmobackup' target='_blank' style='color:#aaa;'>TasmoBackup 0.2 by Dan Medhurst</a></div>
