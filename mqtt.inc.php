<?php

require('lib/phpMQTT.php');


GLOBAL $mqtt_found;
$mqtt_found=[];

function setupMQTT($server, $port=1883, $user, $password)
{
    $mqtt = new phpMQTT($server, $port, 'TasmoBackup');

    if(!$mqtt->connect(true, NULL, $user, $password))
        return false;
    return $mqtt;
}

function getTasmotaMQTTScan($mqtt,$topic)
{
    GLOBAL $mqtt_found;

    $topics['+/stat/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['+/stat/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['+/stat/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    $topics['stat/+/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['stat/+/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['stat/+/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    $mqtt->subscribe($topics);

    for($i=0; $i<1000; $i++) {
        if($i==10) {
            // HomeAssistant swapped
            $mqtt->publish($topic.'/cmnd/STATUS','0');
        }
        if($i==110) {
            $mqtt->publish($topic.'/cmnd/STATUS','5');
        }

        if($i==220) {
            // Default
            $mqtt->publish('cmnd/'.$topic.'/STATUS','0');
        }
        if($i==330) {
            $mqtt->publish('cmnd/'.$topic.'/STATUS','5');
        }
        while($mqtt->proc(false)) {};
        usleep(10000);
    }
    $results=[];
    foreach($mqtt_found as $found) {
        if(isset($found['status5'])) {
            $status=json_decode($found['status5'],true);
            $tmp['ip']=$status['StatusNET']['IPAddress'];
            if(isset($found['status'])) {
                $status=json_decode($found['status'],true);
                $tmp['name']=$status['Status']['FriendlyName'][0];
            }
            $results[]=$tmp;
        }
    }
    return $results;
}


function collectMQTTStatus($topic, $msg)
{
    GLOBAL $mqtt_found;

    //Get Name
    $topics=explode('/',$topic);
    $name=false;
    if($topics[0]=='stat')
        $name=$topics[1];
    if($topics[1]=='stat')
        $name=$topics[0];
    if($name) {
        $mqtt_found[$name]['status']=$msg;
        echo "status0: $name\t$msg\n";
    }
    return;    
}

function collectMQTTStatus2($topic, $msg)
{
    GLOBAL $mqtt_found;

    //Get Version
    $topics=explode('/',$topic);
    $name=false;
    if($topics[0]=='stat')
        $name=$topics[1];
    if($topics[1]=='stat')
        $name=$topics[0];
    if($name) {
        $mqtt_found[$name]['status2']=$msg;
        echo "status2: $name\t$msg\n";
    }
    return;
}

function collectMQTTStatus5($topic, $msg)
{
    GLOBAL $mqtt_found;

    //Get IP
    $topics=explode('/',$topic);
    $name=false;
    if($topics[0]=='stat')
        $name=$topics[1];
    if($topics[1]=='stat')
        $name=$topics[0];
    if($name) {
        $mqtt_found[$name]['status5']=$msg;
        echo "status5: $name\t$msg\n";
    }
    return;    
}

