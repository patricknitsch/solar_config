#!/usr/bin/php
<?php

/*************************************************************
//  (c) Patrick Nitsch
//  PIKO BA (DXS) 07/2025
*************************************************************/
$Startzeit = time(); // Timestamp festhalten
require_once($Pfad."/phpinc/funktionen.inc.php");
if (!isset($funktionen)) {
  $funktionen = new funktionen();
}
$WorkingDir = pathinfo($argv[0])['dirname'];
$funktionen->log_schreiben("---------------- Started PIKO_BA.php at " . time() . " --------------------\n", "    ",6);

$Tracelevel = 8; //  1 bis 10  10 = Debug
$funktionen = new funktionen();
$aktuelleDaten = array();
$tmpData = array();

$aktuelleDaten["zentralerTimestamp"] = $Startzeit;

$URL[0] = "http://" . $WR_IP . "/api/dxs.json?
dxsEntries=33556736
&dxsEntries=67109120
&dxsEntries=33555201
&dxsEntries=33555202
&dxsEntries=33555203
&dxsEntries=33555457
&dxsEntries=33555458
&dxsEntries=33555459
&dxsEntries=33555713
&dxsEntries=33555714
&dxsEntries=33555715
&dxsEntries=67110400
&dxsEntries=67109377
&dxsEntries=67109378
&dxsEntries=67109379
&dxsEntries=67109633
&dxsEntries=67109634
&dxsEntries=67109635
&dxsEntries=67109889
&dxsEntries=67109890
&dxsEntries=67109891
&dxsEntries=251658754
&dxsEntries=251658753
&dxsEntries=16780032
&dxsEntries=251658496";

foreach ($URL as &$URLEntry) {

  $URLEntry = str_replace(array("\r","\n"), "", $URLEntry);

  $ch = curl_init($URLEntry);
  curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
  curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 9);
  curl_setopt($ch, CURLOPT_TIMEOUT, 100);
  curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
  for ($i = 1; $i < 4; $i++) {
    $result = curl_exec($ch);
    $rc_info = curl_getinfo($ch);
    if ($rc_info["http_code"] == 200) {
      break;
    }
    $funktionen->log_schreiben("no connection to Piko server. retry in 6 seconds. Attempt: " . $i, "    ",5);
    $funktionen->log_schreiben(var_export($rc_info, 1), "    ",5);
    sleep(6);
  }
  if ($i >= 4) {
    $funktionen->log_schreiben("---------------- Could not connect to Piko server - Exited PIKO_BA.php -------------------", "    ",6);
    exit;
  }

  $json = json_decode($result);

  foreach ($json->dxsEntries as $entry)
  {
    $tmpData[$entry->dxsId] = $entry->value;
  }
}

// Allgemein
$aktuelleDaten["Aktuell_AC"] = floatval($tmpData["67109120"]); //ID_Ausgangsleistung

$aktuelleDaten["Aktuell_DC"] = floatval($tmpData["33556736"]); //ID_Eingangsleistung

$aktuelleDaten["Frequency"] = floatval($tmpData["67110400"]); //ID_Frequency

$aktuelleDaten["Yield_Day"] = floatval($tmpData["251658754"]); //ID_Yield_Day

$aktuelleDaten["Yield_Total"] = floatval($tmpData["251658753"]); //ID_Yield_Total

$aktuelleDaten["Inverter"] = floatval($tmpData["16780032"]); //ID_Inverter

$aktuelleDaten["Uptime"] = floatval($tmpData["251658496"]); //ID_Uptime

// DC
$aktuelleDaten["DC_Spannung_1"] = floatval($tmpData["33555202"]); //ID_DC1Spannung 

$aktuelleDaten["DC_Spannung_2"] = floatval($tmpData["33555458"]); //ID_DC2Spannung

$aktuelleDaten["DC_Spannung_3"] = floatval($tmpData["33555714"]); //ID_DC3Spannung

$aktuelleDaten["DC_Strom_1"] = floatval($tmpData["33555201"]); //ID_DC1Strom

$aktuelleDaten["DC_Strom_2"] = floatval($tmpData["33555457"]); //ID_DC2Strom

$aktuelleDaten["DC_Strom_3"] = floatval($tmpData["33555713"]); //ID_DC3Strom

$aktuelleDaten["DC_Power_1"] = floatval($tmpData["33555203"]); //ID_DC1Power

$aktuelleDaten["DC_Power_2"] = floatval($tmpData["33555459"]); //ID_DC2Power

$aktuelleDaten["DC_Power_3"] = floatval($tmpData["33555715"]); //ID_DC3Power

// AC
$aktuelleDaten["AC_Spannung_R"] = floatval($tmpData["67109378"]); //ID_P1Spannung

$aktuelleDaten["AC_Spannung_S"] = floatval($tmpData["67109634"]); //ID_P2Spannung

$aktuelleDaten["AC_Spannung_T"] = floatval($tmpData["67109890"]); //ID_P3Spannung

$aktuelleDaten["AC_Strom_R"] = floatval($tmpData["67109377"]); //ID_P1Strom

$aktuelleDaten["AC_Strom_S"] = floatval($tmpData["67109633"]); //ID_P2Strom

$aktuelleDaten["AC_Strom_T"] = floatval($tmpData["67109889"]); //ID_P3Strom

$aktuelleDaten["AC_Leistung_R"] = floatval($tmpData["67109379"]); //ID_P1Leistung

$aktuelleDaten["AC_Leistung_S"] = floatval($tmpData["67109635"]); //ID_P2Leistung

$aktuelleDaten["AC_Leistung_T"] = floatval($tmpData["67109891"]); //ID_P3Leistung

  /*********************************************************************
  //  Alle ausgelesenen Daten werden hier bei Bedarf als mqtt Messages
  //  an den mqtt-Broker Mosquitto gesendet.
  **************************************************************************/
  if ($MQTT and strtoupper($MQTTAuswahl) != "OPENWB") {
    $funktionen->log_schreiben("MQTT Daten zum [ $MQTTBroker ] senden.", "   ", 1);
    require ($Pfad."/mqtt_senden.php");
  }

$funktionen->log_schreiben("---------------- Stopped PIKO_BA.php --------------------", "    ",6);
return;
?>
