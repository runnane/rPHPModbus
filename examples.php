<?php
/*
	examples.php - PHP classes for communicating with Modbus TCP controllers
	This file is part of rPHPModbus.
	Please see rPHPModbus.class.php for more info.
	
	This file shows function which can be used for general modbus buses, and 
	additionally Carlo Gavazzi Smart-House solutions (Dupline). 
	
*/

// FOrmatting (<pre>)
if(php_sapi_name() != 'cli') echo "<pre>";


// We use the rPHPDupline class here since we want Dupline functions in addition
// to "clean" Modbus functions.
require_once("rPHPDupline.class.php");
$al = new rPHPDupline("172.20.100.4");

// Enable debugging
$al->Debug(TRUE);

// Connect
$al->Connect();

//////////////////////////////////////////////////////////
// Example of 01 Read Coil Status
//////////////////////////////////////////////////////////

// Get 1 coil status starting with address 0x0504 (Clean Modbus code)
//$packet = $al->DoModbusFunction_01ReadCoilStatus(1,"05","04","00","01");
//echo "[ii] Coil Status 0x0504 is {$packet['frame']['register'][0]}\n\n";

//////////////////////////////////////////////////////////
// Example of 02 Read Input Status
//////////////////////////////////////////////////////////

// Get 1 input status starting with address 0x0600 (Clean Modbus code)
//$packet = $al->DoModbusFunction_02ReadInputStatus(1,"06","00","00","01");
//echo "[ii] Input Status 0x0600 is {$packet['frame']['register'][0]}\n\n";


//////////////////////////////////////////////////////////
// Example of 03 Read Multiple Registers
//////////////////////////////////////////////////////////

// Get 1 holding registers starting with address 0x0000 (Clean Modbus code)
//$packet = $al->DoModbusFunction_03ReadHoldingRegisters(1,"00","00","00","01");
//echo "[ii] Contents of Holding Register 0x0000 is {$packet['frame']['register'][0]}\n\n";


//////////////////////////////////////////////////////////
// Carlo Gavazzi Smart-House spesific examples: (Dupline/Analink)
//////////////////////////////////////////////////////////

// Get the termostat heating level (normal) form a BEW-TEMDIS based temperature control unit function
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(11);
//echo "[ii] Termostat normal in function 11 is {$temperature} C\n\n";

// Get the termostat heating level (power-saving) form a BEW-TEMDIS based temperature control unit function
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(11, 1);
//echo "[ii] Termostat energysaving in function 11 is {$temperature} C\n\n";

// Get a bitvalue (0/1) from a Dupline function. I.e. on/off light function.
//$status = $al->GetBitValueByFunctionId(37);
//echo "[ii] Function 37 has status {$status}\n\n";

// Get Dupline Output Status for addresses A1-08
//$table = $al->ReadFullDuplineOutputStatusTable();
//echo "[ii] Dupline Output Address A5 is {$table['A5']}\n\n";

// Get Dupline Input Status for addresses A1-08
//$table = $al->ReadFullDuplineInputStatusTable();
//echo "[ii] Dupline Input Address A2 is {$table['A2']}\n\n";

// Set Dupline Adress A1 to 1
//$al->Dupline_SetSingleOutputBit("A1","0001");
		
// Simulate (buttonpress) on A1 (100msec seems like the fastest possible toggle)
//$al->ToggleDuplineOutputChannel("A1",200);

// Get RegisterAddress offset for Dupline Address i3:
//$v = $al->GetRegisterAddressOffsetByDuplineAddress("i3");
//echo "[ii] Dupline  Address I3 has register address offset '{$v}'\n\n";

// Dupline (Analink) heating level (normal) by function_id from a BEW-TEMDIS based temperature control unit
//$t = $al->SetHeatingPointByFunctionId_BEWTEMDIS(11, 21.0);

// Dupline (Analink) heating level (power saving) by function_id from a BEW-TEMDIS based temperature control unit
//$t = $al->SetHeatingPointByFunctionId_BEWTEMDIS(11, 20.5, true);

// Get Dupline (Analink) temperature by function_id from a BEW-TEMDIS based temperature control unit
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(11);
//echo "[ii] Termostat normal in function 11 is {$temperature} C\n\n";

// Get Dupline (Analink) temperature by function_id from a BEW-TEMDIS based temperature control unit
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(11, true);
//echo "[ii] Termostat powersave in function 11 is {$temperature} C\n\n";





$al->Disconnect();


//////////////////////////////////////////////////////////
// UNFINISHED TESTS
//////////////////////////////////////////////////////////
/*
// Simulate a button push with specified delay on toggle.
// Function number 47 (0x2f)
$al->DoButtonPress(47,200);

$t = $al->SetFunctionBit(38,1,0,1);
$t = $al->SetFunctionBit(38,0,0,1);
$t = $al->SetFunctionBit(48,0,0,1);

$t = $al->DuplineByFunction_PresetMultipleRegisters(37, 65280, 2, 0, NULL, 1);


// Open garage gate:  (function 47)
$al->DoButtonPress(47);


usleep(500000); //500msec
$t = $al->SetHeatingPoint(11, 20.0, true);

usleep(500000); //500msec
$t = $al->GetTermostatNormal(11);

usleep(500000); //500msec
$t = $al->GetTermostatNormal(11,true);

*/

// Done, formatting
if(php_sapi_name() != 'cli') echo "</pre>";

?>