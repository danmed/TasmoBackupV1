<?php

require_once(__DIR__.'/phpMQTT.php');


global $mqtt_found;

function setupMQTT($server, $port=1883, $user, $password)
{
	global $mqtt_found;

	$mqtt_found = array();
    $mqtt = new Bluerhinos\phpMQTT($server, $port, 'TasmoBackup');

	// turn on debugging
//	$mqtt->debug = true;

    if(!$mqtt->connect(true, NULL, $user, $password))
        return false;

    return $mqtt;
}

// what on earth does the $slim parameter mean?
function getTasmotaMQTTScan($mqtt, $topic, $user=false, $password=false, $slim=false)
{
    global $mqtt_found, $settings;

    $topics['+/stat/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['+/stat/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['+/stat/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    $topics['stat/+/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
    $topics['stat/+/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
    $topics['stat/+/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');

    if (isset($settings['mqtt_topic_format'])) {
        $custom_topic = str_replace(array('%prefix%','%topic%'), array('stat','+'), $settings['mqtt_topic_format']);
        $topics[$custom_topic.'/STATUS'] = array('qos' => 0, 'function' => 'collectMQTTStatus');
        $topics[$custom_topic.'/STATUS2'] = array('qos' => 0, 'function' => 'collectMQTTStatus2');
        $topics[$custom_topic.'/STATUS5'] = array('qos' => 0, 'function' => 'collectMQTTStatus5');
    }
    $mqtt->subscribe($topics, $qos = 0);

	// HomeAssistant swapped
	$mqtt->publish($topic.'/cmnd/STATUS','0');
	if ($topic == 'tasmotas')
		$mqtt->publish('sonoffs/cmnd/STATUS','0');
	if (isset($settings['mqtt_topic_format'])) {
		$topic_prefix = str_replace(array('%prefix%','%topic%'), array('cmnd', $topic), $settings['mqtt_topic_format']);
		$mqtt->publish($topic_prefix . '/STATUS','0');
		if($topic == 'tasmotas')
			$topic_prefix = str_replace(array('%prefix%','%topic%'), array('cmnd','sonoffs'), $settings['mqtt_topic_format']);
			$mqtt->publish($topic_prefix . '/STATUS','0'); 
	}
	$mqtt->publish('cmnd/'.$topic.'/STATUS','0');
	if ($topic == 'tasmotas')
		$mqtt->publish('cmnd/sonoffs/STATUS','0');

	// Default
	$mqtt->publish($topic.'/cmnd/STATUS','5');
	if ($topic == 'tasmotas')
		$mqtt->publish('sonoffs/cmnd/STATUS','5');

	if(isset($settings['mqtt_topic_format'])) {
		$topic_prefix = str_replace(array('%prefix%','%topic%'), array('cmnd', $topic), $settings['mqtt_topic_format']);
		$mqtt->publish($topic_prefix . '/STATUS','5');
		if($topic == 'tasmotas')
			$topic_prefix = str_replace(array('%prefix%','%topic%'), array('cmnd','sonoffs'), $settings['mqtt_topic_format']);
			$mqtt->publish($topic_prefix . '/STATUS','5');
	}

	$mqtt->publish('cmnd/'.$topic.'/STATUS','5');
	if($topic == 'tasmotas')
		$mqtt->publish('cmnd/sonoffs/STATUS','5');

	$start_ts = time();
	while($mqtt->proc(false)) {
		$current_ts = time();
		# if after 5 seconds, then break out and end
		if ($current_ts - $start_ts >= 5) {
			break;
		}
	}

	$mqtt->close();

    $results = [];
    foreach($mqtt_found as $found) {
        if(isset($found['status5'])) {
            $status = jsonTasmotaDecode($found['status5']);
            if(isset($status['StatusNET']['IP']))		// < 5.12.0
                $tmp['ip'] = $status['StatusNET']['IP'];
            if(isset($status['StatusNET']['IPAddress']))	// >= 5.12.0
                $tmp['ip'] = $status['StatusNET']['IPAddress'];
            if(isset($status['StatusNET']['Mac']))
                $tmp['mac'] = $status['StatusNET']['Mac'];
            if(isset($found['status'])) {
                $status = jsonTasmotaDecode($found['status']);
                if ($status['Status']['DeviceName'] && strlen(preg_replace('/\s+/', '', $status['Status']['DeviceName']))>0)
                    $tmp['name'] = $status['Status']['DeviceName'];
                else if ($status['Status']['FriendlyName'][0])
                    $tmp['name'] = $status['Status']['FriendlyName'][0];
            }
            if (isset($settings['autoadd_scan']) && $settings['autoadd_scan'] == 'Y') {
                addTasmotaDevice($tmp['ip'], $user, $password);
            } else {
                $results[] = $tmp;
            }
        }
    }
    return $results;
}


function collectMQTTStatus($topic, $msg)
{
    global $mqtt_found, $settings;

    //Get Name
    $topics = explode('/', $topic);
    $name = false;
	// stat/+ = name is index 1
	// +/stat = name is index 0
	// $settings['mqtt_topic_format'] = name is ??
    if($topics[0] == 'stat') {
        $name = $topics[1];
    } elseif($topics[1] == 'stat') {
        $name = $topics[0];
	} elseif(isset($settings['mqtt_topic_format'])) {
		$topic_format_parts = explode('/', $settings['mqtt_topic_format']);
		$idx = array_search('%topic%', $topic_format_parts);
		$name = $topics[$idx];
	}

    if($name) {
        $mqtt_found[$name]['status'] = $msg;
    }
    return;
}

function collectMQTTStatus2($topic, $msg)
{
    global $mqtt_found, $settings;

    //Get Version
    $topics = explode('/', $topic);
    $name = false;
    if($topics[0] == 'stat') {
        $name = $topics[1];
    } elseif($topics[1] == 'stat') {
        $name = $topics[0];
	} elseif(isset($settings['mqtt_topic_format'])) {
		$topic_format_parts = explode('/', $settings['mqtt_topic_format']);
		$idx = array_search('%topic%', $topic_format_parts);
		$name = $topics[$idx];
	}
    if($name) {
        $mqtt_found[$name]['status2'] = $msg;
    }
    return;
}

function collectMQTTStatus5($topic, $msg)
{
    global $mqtt_found, $settings;

    //Get IP
    $topics = explode('/', $topic);
    $name = false;
    if($topics[0] == 'stat') {
        $name = $topics[1];
    } elseif($topics[1] == 'stat') {
        $name = $topics[0];
	} elseif(isset($settings['mqtt_topic_format'])) {
		$topic_format_parts = explode('/', $settings['mqtt_topic_format']);
		$idx = array_search('%topic%', $topic_format_parts);
		$name = $topics[$idx];
	}
    if($name) {
        $mqtt_found[$name]['status5'] = $msg;
    }
    return;
}

