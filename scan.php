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
    <tr><th colspan="9"><center><b>TasmoBackup</th></tr>                                                
        <tr><th><b>NAME</th><th>IP</th><th><b>ADD</b></th></tr>
    </thead>                                                                                                
    <tbody>  

<?php 
include "data/config.inc.php";
function getBetween($content, $start, $end)
{
    $r = explode($start, $content);
    if (isset($r[1]))
    {
        $r = explode($end, $r[1]);
        return $r[0];
    }
    return '';
}
if($_POST["task"]=="scan")
{
set_time_limit(0);
print(str_repeat(" ", 300) . "\n");
$range = $_POST['range'];
$range = explode('.', $range );
foreach( $range as $index=>$octet )
	$range[$index] = array_map( 'intval', explode('-',$octet) );
	
// 4 for loops to generate the ip address 4 octets
for( $octet1=$range[0][0]; $octet1<=(($range[0][1])? $range[0][1]:$range[0][0]); $octet1++ )
for( $octet2=$range[1][0]; $octet2<=(($range[1][1])? $range[1][1]:$range[1][0]); $octet2++ )
for( $octet3=$range[2][0]; $octet3<=(($range[2][1])? $range[2][1]:$range[2][0]); $octet3++ )
for( $octet4=$range[3][0]; $octet4<=(($range[3][1])? $range[3][1]:$range[3][0]); $octet4++ )
{
	// assemble the IP address
	$ip = $octet1.".".$octet2.".".$octet3.".".$octet4;
	
	// initialise the URL
	
	$ch = curl_init("http://" . $ip);
    	curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    	$text = curl_exec($ch);
	
	// print the result for that IP address
	
	if (strpos($text, 'Tasmota') !== false)
		{ 
        //Get Version
        $url = 'http://' . $ip . '/cm?cmnd=status%202';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $version = getBetween($data, '"Version":"', '"');
        //Get Name
        $url = 'http://' . $ip . '/cm?cmnd=status';
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 5);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $data = curl_exec($ch);
        curl_close($ch);
        $name = getBetween($data, 'FriendlyName":["', '"');
        $name = str_replace("'", "", $name);
		$db_handle = mysqli_connect($DBServer, $DBUser, $DBPassword);
        	$db_found = mysqli_select_db($db_handle, $DBName);
       		$check = mysqli_query($db_handle, "select * from devices where ip = '$ip'");
        	$checkrows = mysqli_num_rows($check);
	        if ($checkrows < 1)
	        	{
            print "<tr valign='middle'><td>" . $name . "</td><td><center><a href='http://" . $ip . "'>" . $ip . "</a></form></td><td><center><form method='POST' action='index.php' target='_blank'><input type='hidden' value='discover' name='task'><input type='hidden' name='ip' value='" . $ip . "'><input type='submit' value='Add' class='btn-xs btn-success'></form></td></tr>";
      //$sql = "INSERT INTO devices (name,ip,version) VALUES ('$name', '$ip', '$version')";
			//$result = mysqli_query($db_handle, $sql);
			}
		}
} 
}
?>

</tbody>                                                                                          
    </table>                                                                                                
    </div>     
