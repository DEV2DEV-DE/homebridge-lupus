<?php

  // Weitere Informationen:
  // https://community.symcon.de/t/alarmanlage-lupusec-xt2-plus-auslesen/39121
  // https://forum.iobroker.net/topic/5436/lupusec-alarmanlage-einbinden
  // https://github.com/schmupu/ioBroker.lupusec

  // Quick & Dirty: Grundlegende Tests mit der Anlage

  $url = "https://192.168.xxx.xxx";
  $username = "<your_username>";
  $password = "<yourPassword>";
  
  // Zentrale Funktion zur Anmeldung an der Zentrale
  function Login() {
  global $username;
  global $password;
  global $url;
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_TIMEOUT, 30); //timeout after 30 seconds
    curl_setopt($ch, CURLOPT_HTTPAUTH, CURLAUTH_ANY);
    curl_setopt($ch, CURLOPT_USERPWD, "$username:$password");
    curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
    curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, false);
    return $ch;
  }
  
  // JSON lesbar formatieren
  function PrettyPrintJSON($value) {
    $result = str_replace("	", "", $value);
    $json = json_decode($result, true);
    return json_encode($json, JSON_PRETTY_PRINT);
  }
  
  // Generische Funktion zum Lesen eines JSON aus der Anlage
  function GenericReadJSON($endpoint) {
  global $url;
    $ch = Login();
    // Sensorliste lesen
    $resource = $url.$endpoint;
    curl_setopt($ch, CURLOPT_URL,$resource);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec ($ch);
    
    // Umwandeln von JSON in ein Array
    header('Content-Type: application/json');
    echo PrettyPrintJSON($result);
    curl_close($ch);
  }
  
  // Liefert eine Liste aller Sensoren
  function GetSensorList() {
    GenericReadJSON("/action/deviceListGet");
  }
  
  // Liefert eine Liste aller angelernten Funkschalter
  function GetSensorListPSS() {
    GenericReadJSON("/action/deviceListPSSGet");
  }
  
  // Liefert die Log-Einträge
  function GetLogList($type) {
  global $url;
    $ch = Login();
    switch ($type) {
      case 1: $logliste = "$url/action/loggerListGet";
    	break;
      case 2: $logliste = "$url/action/logsGet";
    	break;
      case 3: $logliste = "$url/action/reportEventListGet";
    	break;
    default:
      die("Invalid log type");
      break;
    }
    // Log lesen
    curl_setopt($ch, CURLOPT_URL, $logliste);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec ($ch);
    
    // Umwandeln von JSON in ein Array
    header('Content-Type: application/json');
    echo PrettyPrintJSON($result);
    curl_close($ch);
  }
  
  // Zugriffstoken für POST holen
  function GetAccessToken($handle) {
  global $url;
    // Token erhalten
    $edit = "$url/action/tokenGet";
    curl_setopt($handle, CURLOPT_URL, $edit);
    curl_setopt($handle, CURLOPT_RETURNTRANSFER, 1);
    $result = curl_exec($handle);
    $json = json_decode($result, true);
    return $json['message'];
  }
  
  // Modus der Anlage schalten (scharf/unscharf)
  function SetMode($area, $mode) {
  global $url;
    $ch = Login();
    $token = GetAccessToken($ch);
    // Area 1 schalten
    $edit = "$url/action/panelCondPost";
    curl_setopt($ch, CURLOPT_URL, $edit);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, ("area=$area&mode=$mode"));
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_HTTPHEADER, array('X-Requested-With: XMLHttpRequest','X-Token: '.$token));
    $result = curl_exec($ch);
    echo $result;
    curl_close($ch);
  }
  
  // Statuswerte der Zentrale lesen
  function GetPanelCond() {
    GenericReadJSON("/action/panelCondGet");
  }
  
  switch ($_GET['action']) {
    case 'list': GetSensorList();
    break; 
    case 'pss': GetSensorListPSS();
    break; 
    case 'log': GetLogList($_GET['log']);
    break; 
    case 'arm': SetMode($_GET['area'], 1);
    break; 
    case 'disarm': SetMode($_GET['area'], 0);
    break; 
    case 'panel': GetPanelCond();
    break; 
  default:
  	echo "Action missing!";
  	break;
  }

?>
