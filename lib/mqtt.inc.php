<?php

require_once(__DIR__.'/phpMQTT2.php');


GLOBAL $mqtt_found;
$mqtt_found=[];

function setupMQTT($server, $port=1883, $user, $password)
{
    $mqtt = new phpMQTT($server, $port, 'TasmoBackup');
    //$mqtt = new Bluerhinos\phpMQTT($server, $port, 'TasmoBackup');

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

    $step1=$step2=$step3=$step4=$step5=$step6=true;
    $ts=time();
    while(($i=time()-$ts)<10) {
        if($step1 && !$slim) {
            $step1=false;
            // HomeAssistant swapped
            $mqtt->publish($topic.'/cmnd/STATUS','0');
            if($topic=='tasmotas')
                $mqtt->publish('sonoffs/cmnd/STATUS','0');
        }
        if($step2 && $i>0.60 && !$slim) {
            $step2=false;
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat',$topic),$settings['mqtt_topic_format']).'/STATUS','0');
                if($topic=='tasmotas')
                    $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','sonoffs'),$settings['mqtt_topic_format']).'/STATUS','0'); 
            }
        }
        if($step3 && $i>1.10 && !$slim) {
            $step3=false;
            $mqtt->publish('cmnd/'.$topic.'/STATUS','0');
            if($topic=='tasmotas')
                $mqtt->publish('cmnd/sonoffs/STATUS','0');
        }

        if($step4 && $i>2.10) {
            $step4=false;
            // Default
            $mqtt->publish($topic.'/cmnd/STATUS','5');
            if($topic=='tasmotas')
                $mqtt->publish('sonoffs/cmnd/STATUS','5');
        }
        if($step5 && $i>2.60) {
            $step5=false;
            if(isset($settings['mqtt_topic_format'])) {
                $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat',$topic),$settings['mqtt_topic_format']).'/STATUS','5');
                if($topic=='tasmotas')
                    $mqtt->publish(str_replace(array('%prefix%','%topic%'),array('stat','sonoffs'),$settings['mqtt_topic_format']).'/STATUS','5');
            }
        }
        if($step6 && $i>3.20) {
            $step6=false;
            $mqtt->publish('cmnd/'.$topic.'/STATUS','5');
            if($topic=='tasmotas')
                $mqtt->publish('cmnd/sonoffs/STATUS','5');
        }
        $mqtt->proc(false);
        usleep(30000);
    }
    $results=[];
    foreach($mqtt_found as $topic => $found) {
        $status=array('Topic'=>$topic);
        if(isset($found['status5'])) {
            $status=array_merge(jsonTasmotaDecode($found['status5']));
            if(isset($status['StatusNET']['IP']))		// < 5.12.0
                $tmp['ip']=$status['StatusNET']['IP'];
            if(isset($status['StatusNET']['IPAddress']))	// >= 5.12.0
                $tmp['ip']=$status['StatusNET']['IPAddress'];
            if(isset($status['StatusNET']['Mac']))
                $tmp['mac']=$status['StatusNET']['Mac'];
            if(isset($found['status'])) {
                $status=array_merge($status,jsonTasmotaDecode($found['status']));
                if(isset($settings['use_topic_as_name']) && $settings['use_topic_as_name']=='F')
                    $tmp['name']=trim(str_replace(array('/stat','stat/'),array('',''),$topic)," \t\r\n\v\0/");
                else {
                    if ($status['Status']['Topic'])
                        $tmp['name']=$status['Status']['Topic'];
                }
                if(!isset($settings['use_topic_as_name']) || $settings['use_topic_as_name']=='N') {
                    if ($status['Status']['DeviceName'] && strlen(preg_replace('/\s+/', '',$status['Status']['DeviceName']))>0)
                        $tmp['name']=$status['Status']['DeviceName'];
                    else if ($status['Status']['FriendlyName'][0])
                        $tmp['name']=$status['Status']['FriendlyName'][0];
                }
            }
            if(isset($found['status2'])) {
                $status=array_merge($status,jsonTasmotaDecode($found['status2']));
            }
            if (isset($settings['autoadd_scan']) && $settings['autoadd_scan']=='Y') {
                addTasmotaDevice($tmp['ip'], $user, $password,false,$status);
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

