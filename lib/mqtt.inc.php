<?php

require_once(__DIR__.'/phpMQTT.php');


GLOBAL $mqtt_found;
$mqtt_found=[];

function setupMQTT($server, $port=1883, $user, $password)
{
    $mqtt = new phpMQTT($server, $port, 'TasmoBackup');

    if(!$mqtt->connect(true, NULL, $user, $password))
        return false;
    return $mqtt;
}

function getTasmotaMQTTScan($mqtt,$topic,$user=false,$password=false,$slim=false)
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
        if($i==10 && !$slim) {
            // HomeAssistant swapped
            $mqtt->publish($topic.'/cmnd/STATUS','0');
            if($topic=='tasmotas')
                $mqtt->publish('sonoffs/cmnd/STATUS','0');
        }
        if($i==60 && !$slim) {
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat',$topic),$settings['mqtt_topic_format']).'/STATUS','0');
                if($topic=='tasmotas')
                    $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','sonoffs'),$settings['mqtt_topic_format']).'/STATUS','0'); 
            }
        }
        if($i==110 && !$slim) {
            $mqtt->publish('cmnd/'.$topic.'/STATUS','0');
            if($topic=='tasmotas')
                $mqtt->publish('cmnd/sonoffs/STATUS','0');
        }

        if($i==210) {
            // Default
            $mqtt->publish($topic.'/cmnd/STATUS','5');
            if($topic=='tasmotas')
                $mqtt->publish('sonoffs/cmnd/STATUS','5');
        }
        if($i==260) {
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat',$topic),$settings['mqtt_topic_format']).'/STATUS','5');
                if($topic=='tasmotas')
                    $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','sonoffs'),$settings['mqtt_topic_format']).'/STATUS','5');
            }
        }
        if($i==320) {
            $mqtt->publish('cmnd/'.$topic.'/STATUS','5');
            if($topic=='tasmotas')
                $mqtt->publish('cmnd/sonoffs/STATUS','5');
        }
        while($mqtt->proc(false)) {};
        usleep(30000);
    }
    $results=[];
    foreach($mqtt_found as $found) {
        if(isset($found['status5'])) {
            $status=jsonTasmotaDecode($found['status5']);
            if(isset($status['StatusNET']['IP']))		// < 5.12.0
                $tmp['ip']=$status['StatusNET']['IP'];
            if(isset($status['StatusNET']['IPAddress']))	// >= 5.12.0
                $tmp['ip']=$status['StatusNET']['IPAddress'];
            if(isset($status['StatusNET']['Mac']))
                $tmp['mac']=$status['StatusNET']['Mac'];
            if(isset($found['status'])) {
                $status=jsonTasmotaDecode($found['status']);
                if ($status['Status']['DeviceName'] && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                    $tmp['name']=$status['Status']['DeviceName'];
                else if ($status['Status']['FriendlyName'][0])
                    $tmp['name']=$status['Status']['FriendlyName'][0];
            }
            if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
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
    $name=false;
    if(isset($topic))
        $name=substr($topic,0,strrpos($topic,'/'));
    if($name) {
        $mqtt_found[$name]['status']=$msg;
    }
    return;
}

function collectMQTTStatus2($topic, $msg)
{
    GLOBAL $mqtt_found;

    //Get Version
    $name=false;
    if(isset($topic))
        $name=substr($topic,0,strrpos($topic,'/'));
    if($name) {
        $mqtt_found[$name]['status2']=$msg;
    }
    return;
}

function collectMQTTStatus5($topic, $msg)
{
    GLOBAL $mqtt_found;

    //Get IP
    $name=false;
    if(isset($topic))
        $name=substr($topic,0,strrpos($topic,'/'));
    if($name) {
        $mqtt_found[$name]['status5']=$msg;
    }
    return;
}

