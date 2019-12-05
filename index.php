<!DOCTYPE html>                                                                                              
<?PHP                                                                                                        
include "config.inc.php";                                                                                    
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
                                                                                                             
<title>Tasmo Backup</title>                                                                              
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
        "order": [[0, "desc" ]]                                                                              
} );            
} );                                                                                                         
                                                                                                             
        </script>                                                                                            
</head>                                                                                                      
                                                                                                             
  <body><font size="2">                                                                                      
                                                   
    <div class="container">                                                                                  
    <table class="table table-striped table-bordered" id="status">                                           
    <thead>                                                                                                  
    <tr><th colspan="6"><center><b>Tasmo Backup</th></tr>                                                
        <tr><th><b>DBID</th><th><b>NAME</th><th>IP</th><th><b>VERSION</th><th>LAST BACKUP</th><th><b>NUMBER OF BACKUPS</th></tr>
    </thead>                                                                                                 
    <tbody>  
<?PHP                                                                                                        
$relcount = 1;                                                                                               
$db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);                                                
$db_found  = mysqli_select_db($db_handle, $DBName);                                                          
                                                                                                             
if ($db_found) {                                                                                             
    $SQL    = "select * from devices order by id asc";                                                       
    $result = mysqli_query($db_handle, $SQL);                                                                
    while ($db_field = mysqli_fetch_assoc($result)) {                                                        
        $id = $relcount;                                                                                     
        $name  = $db_field['name'];                                                                        
        $ip  = $db_field['ip'];                                                                          
        $version  = $db_field['version'];                                                                    
        $lastbackup = $db_field['lastbackup'];                                                                   
        $numberofbackups = $db_field['noofbackups'];                                                                           
        
        print "<tr><td>" . $id . "</td><td>" . $name . "</td><td>" . $ip . "</td><td>" . $version . "</td><td>" . $lastbackup ."</td><Td>" . $numberofbackups ."</td></tr>";
        $relcount = $relcount + 1;                                                                           
}                                                                                                            
                                                                                                             
    mysqli_close($db_handle);                                                                                
                                                                                                             
}                                                                                                            
?>                                                                                                           
           </tbody>                                                                                          
    </table>                                                                                                 
    </div>                                                                                                   
                                                                                                             
