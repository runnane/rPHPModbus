<?php
/*
	examples.php - PHP classes for communicating with Modbus TCP controllers
	This file is part of rPHPModbus.
	
	
*/

require_once("rPHPDupline.class.php");

$al = new rPHPDupline("172.20.100.4");
$al->Debug(true);
$al->Connect();

//	$t = $al->SetFunctionBit(38,1,0,1);
//	$t = $al->SetFunctionBit(38,0,0,1);
	
//	$al->DoButtonPress(48,1);
//	$t = $al->SetFunctionBit(48,0,0,1);

	$t = $al->DuplineByFunction_PresetMultipleRegisters(37,65280,2,0,NULL,1);
/*

	$t = $al->GetCurrentTemperature($id);
	echo "[NOW] {$name} er {$t} grader\n";
	
	$t = $al->GetTermostatNormal($id);
	echo "[DAY] {$name} normal termostat er {$t} grader\n";
	
	$t = $al->GetTermostatPowerSave($id);
	echo "[NIG] {$name} power save termostat er {$t} grader\n";
	
	$t = $al->GetBitValue($id);
	echo "[NIG] {$name} status is {$t}\n";
*/

// Apne garasjeport:  (nr 47)
//$al->DoButtonPress(47);

// Kontor: 11

	
//	$t = $al->GetTermostatNormal(11);
//	echo "termostat er {$t} grader\n";

//	usleep(500000); //500msec
/*
	$t = $al->SetHeatingPoint(11, 20.0);
	
	usleep(500000); //500msec
	$t = $al->SetHeatingPoint(11, 20.0, true);

	usleep(500000); //500msec
	$t = $al->GetTermostatNormal(11);
	echo "termostat er {$t} grader\n";

	usleep(500000); //500msec
	$t = $al->GetTermostatNormal(11,true);
	echo "termostat er {$t} grader\n";
*/
// Garasjeport: 47
//$al->DoButtonPress(47,1);

/*
foreach($termo as $id => $name){
	
	$t = $al->GetCurrentTemperature($id);
	echo "[NOW] {$name} er {$t} grader\n";
	
	$t = $al->GetTermostatNormal($id);
	echo "[DAY] {$name} normal termostat er {$t} grader\n";
	
	$t = $al->GetTermostatPowerSave($id);
	echo "[NIG] {$name} power save termostat er {$t} grader\n";
	
	$t = $al->GetBitValue($id);
	echo "[NIG] {$name} status is {$t}\n";
	
}
*/
/*

	$t = $al->GetBitValue(37);
	echo "[BIT] Utelys status is {$t}\n";
*/
/*
$t = $al->GetCurrentTemperature("10");
echo "Kjokken er {$t} grader\n";
*/
// 02 = get current temp
// 11 = kontor
// 00 = ok
/*
$hexresult = $al->DoAnalinkQuery("02","11","00");

echo " raw: {$hexresult} \n";
$value = hexdec($hexresult);
if($value>1) $value -= 1.0;
$value = number_format(($value*50)/255,1);

echo " real: {$value} \n";
*/

$al->Disconnect();

?>