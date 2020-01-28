<!DOCTYPE html>                                                                                              
<?php
require "functions.inc.php";

global $db_handle;
global $settings;

if (isset($_POST["name"])) {
    $name = $_POST["name"];
}
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
        "order": [[0, "desc" ]],
        "pageLength": <?php echo $settings['amount']; ?>,
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
	    <tr><th colspan="9"><center><b><a href="index.php"><?php echo $name; ?></a></th></tr>                                                
        <tr><th><b>DATE</th><th>VERSION</th><th>FILE</th></tr>
    </thead>                                                                                                
    <tbody>  
<?php
$relcount = 1;

    $devices = dbBackupList($name);
    foreach ($devices as $db_field) {
        $id = $relcount;
        $version = $db_field['version'];
        $date = $db_field['date'];
        $filename = $db_field['filename'];


        echo "<tr valign='middle'><td>" . $date . "</td><td><center>" . $version . "</td><td><a href='" . $filename . "'>DOWNLOAD</a></td></tr>";
        $relcount = $relcount + 1;
    }

?>                                                                                                          
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     
	  </body>
	  </html>
