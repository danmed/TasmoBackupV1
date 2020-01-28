<!DOCTYPE html>                                                                                              
<?php

require "functions.inc.php";

global $db_handle;
global $settings;

if ($_POST["sort"]!="") {
    dbSettingsUpdate('sort',$_POST["sortoption"]);
}

if ($_POST["amount"]!="") {
    dbSettingsUpdate('amount',$_POST["amountoption"]);
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
                                                                                                            
<title>TasmoBackup - Settingsa</title>                                                                              
  <meta charset="utf-8">                                                                                    
  <meta name="viewport" content="width=device-width, initial-scale=1">                                      
  <link rel="stylesheet" href="resources/bootstrap.min.css">      
  <script src="resources/jquery.min.js"></script>                  
  <script src="resources/bootstrap.min.js"></script>                
  <link rel="stylesheet" type="text/css" href="resources/datatables.min.css"/>
  <script type="text/javascript" src="resources/datatables.min.js"></script>
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
    <tr><th colspan="3"><center><b><a href="index.php">TasmoBackup</a></th></tr>                                                
        <tr><th><b>Setting</th><th>Value</th><th>SUBMIT</th></tr>
    </thead>                                                                                                
    <tbody>  

<tr valign='middle'><td>Sort Column</td><td><form method='POST' action='settings.php'><center><input type="hidden" name="sort" value="sort"><select name ="sortoption"><option value="0">Name</option><option value="1">IP</option><option value="2">Auth</option><option value="3">Version</option><option value="4">Last Backup</option></select></td><td><center><input type='submit' value='Submit' class='btn-xs btn-success'></form></td></tr>
<tr valign='middle'><td>Amount of Rows</td><td><center><form method='POST' action='settings.php'><input type="hidden" name="amount" value="amount"><input type='text' name='amountoption' value='<?php echo $settings['amount']; ?>'></td><td><center><input type='submit' value='Submit' class='btn-xs btn-success'></form></td></tr>
<tr valign='middle'><td>Export Devices</td><td><center><form method='POST' action='export.php'><input type="hidden" name="export" value="export"><select name ="sortoption"><option value="0">CSV</option></td><td><center><input type='submit' value='Submit' class='btn-xs btn-success'></form></td></tr>
           </tbody>                                                                                          
    </table>                                                                                                
    </div>     
<br><br>
<div style='text-align:right;font-size:11px;'><hr/><a href='https://github.com/danmed/TasmoBackupV1' target='_blank' style='color:#aaa;'>TasmoBackup 1.01 by Dan Medhurst</a></div>
