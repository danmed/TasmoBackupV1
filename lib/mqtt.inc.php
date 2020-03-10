<?php

require(__DIR__.'/phpMQTT.php');


GLOBAL $mqtt_found;
$mqtt_found=[];

function setupMQTT($server, $port=1883, $user, $password)
{
    $mqtt = new phpMQTT($server, $port, 'TasmoBackup');

    if(!$mqtt->connect(true, NULL, $user, $password))
        return false;
    return $mqtt;
}

function getTasmotaMQTTScan($mqtt,$topic,$user=false,$password=false)
{
    GLOBAL $mqtt_found,$settings;

    $topics['+/stat/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['+/stat/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['+/stat/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    $topics['stat/+/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['stat/+/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['stat/+/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');

    if(isset($settings['mqtt_topic_format'])) {
        $custom_topic=str_replace(array('%prefix%','%topic%'),array('stat','+'),$settings['mqtt_topic_format']);
        $topics[$custom_topic.'/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
        $topics[$custom_topic.'/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
        $topics[$custom_topic.'/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    }
    $mqtt->subscribe($topics);

    for($i=0; $i<1000; $i++) {
        if($i==10) {
            // HomeAssistant swapped
            $mqtt->publish($topic.'/cmnd/STATUS','0');
        }
        if($i==60) {
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','+'),$settings['mqtt_topic_format']).'/STATUS','0');
            }
        }
        if($i==110) {
            $mqtt->publish('cmnd/'.$topic.'/STATUS','0');
        }

        if($i==210) {
            // Default
            $mqtt->publish($topic.'cmnd/STATUS','5');
        }
        if($i==260) {
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','+'),$settings['mqtt_topic_format']).'/STATUS','5');
            }
        }
        if($i==320) {
            $mqtt->publish('cmnd/'.$topic.'/STATUS','5');
        }
        while($mqtt->proc(false)) {};
        usleep(10000);
    }
    $results=[];
    foreach($mqtt_found as $found) {
        if(isset($found['status5'])) {
            $status=json_decode($found['status5'],true);
            if(isset($status['StatusNET']['IP']))		// < 5.12.0
                $tmp['ip']=$status['StatusNET']['IP'];
            if(isset($status['StatusNET']['IPAddress']))	// >= 5.12.0
                $tmp['ip']=$status['StatusNET']['IPAddress'];
            if(isset($found['status'])) {
                $status=json_decode($found['status'],true);
                $tmp['name']=$status['Status']['FriendlyName'][0];
            }
            if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']) {
                addTasmotaDevice($tmp['ip'], $user, $password);
            } else {
                $results[]=$tmp;
            }
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
    }
    return;
}

