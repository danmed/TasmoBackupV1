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
    <tr><th colspan="9"><center><b>TasmoBackup</th></tr>
	    <form action="index.php" method="POST">
                <input type="hidden" name="task" value="discoverall">
                <?php if(isset($_POST['user'])) { echo '<input type="hidden" name="user" value="'.$_POST['user'].'">'; } ?>
                <?php if(isset($_POST['password'])) { echo '<input type="hidden" name="password" value="'.$_POST['password'].'">'; } ?>
        <tr><th><b>ADD</th><th>NAME</th><th><b>IP</b></th></tr>
    </thead>                                                                                                
    <tbody>  

<?php
require 'functions.inc.php';
require 'mqtt.inc.php';

global $settings;

if ($_POST["task"]=="scan") {
    $password='';
    $user='admin';
    if (isset($_POST['user'])) {
        $user=$_POST['user'];
    }
    if (isset($_POST['password'])) {
        $password=$_POST['password'];
    }
    if (isset($_POST['topic'])) {
        $mqtt_topic=$_POST['topic'];
    }
    set_time_limit(0);
    print(str_repeat(" ", 300) . "\n");
    $range = $_POST['range'];
    $range = explode('.', $range);
    foreach ($range as $index=>$octet) {
        $range[$index] = array_map('intval', explode('-', $octet));
    }
    
    // 4 for loops to generate the ip address 4 octets
    for ($octet1=$range[0][0]; $octet1<=(isset($range[0][1])? $range[0][1]:$range[0][0]); $octet1++) {
        for ($octet2=$range[1][0]; $octet2<=(isset($range[1][1])? $range[1][1]:$range[1][0]); $octet2++) {
            for ($octet3=$range[2][0]; $octet3<=(isset($range[2][1])? $range[2][1]:$range[2][0]); $octet3++) {
                for ($octet4=$range[3][0]; $octet4<=(isset($range[3][1])? $range[3][1]:$range[3][0]); $octet4++) {
                    // assemble the IP address
                    $ip = $octet1.".".$octet2.".".$octet3.".".$octet4;
    
                    // initialise the URL
    
                    if (getTasmotaScan($ip, $user, $password)) {
                        if ($status=getTasmotaStatus($ip, $user, $password)) {
                            $name=$status['Status']['FriendlyName'][0];
                            echo "<tr valign='middle'><td><center><input type='checkbox' name='ip[]' value='" . $ip . "'></td>".
                     "<td>" . $name . "</td>".
                     "<td><center><a href='http://" . $ip . "'>" . $ip . "</a></td></tr>";
                        }
                    }

                    if($settings['mqtt_host']) {
                        $mqtt=setupMQTT($settings['mqtt_host'], $settings['mqtt_port'], $settings['mqtt_user'], $settings['mqtt_password']);
                        if(!isset($mqtt_topic)) $mqtt_topic=$settings['mqtt_topic'];
                        $results=getTasmotaMQTTScan($mqtt,$mqtt_topic);
                        if(count($results)>0) {
                            foreach($results as $found) {
                                $ip=$found['ip'];
                                $name='MQTT ';
                                if(isset($found['name'])) $name='MQTT '.$found['name'];
                                echo "<tr valign='middle'><td><center><input type='checkbox' name='ip[]' value='" . $ip . "'></td>".
                                     "<td>" . $name . "</td>".
                                     "<td><center><a href='http://" . $ip . "'>" . $ip . "</a></td></tr>";
                            }
                        }
                    }
                }
            }
        }
    }
}
?>
	    <tr><td colspan="3"><center><input type=submit class='btn-xs btn-success' value='Add Devices'></td></tr>
	    </form>
</tbody>                                                                                          
    </table>                                                                                                
    </div>     
