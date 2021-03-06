<?php

/*
examples.php - PHP classes for communicating with Modbus TCP controllers
This file is part of rPHPModbus.
Please see rPHPModbus.class.php for more info.

This file shows function which can be used for general modbus buses, and 
additionally Carlo Gavazzi Smart-House solutions (Dupline). 
*/

// Formatting (<pre>)
if (php_sapi_name() != 'cli')
    echo "<pre>";

// We use the rPHPDupline class here since we want Dupline functions in addition
// to "clean" Modbus functions.
require_once ("rPHPDupline.class.php");
$al = new rPHPDupline("172.20.100.4");

// Enable debugging
$al->Debug(true);

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
// Carlo Gavazzi Smart-House specific examples: (Dupline/Analink)
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

// Get Dupline Output Status for channels A1-08
//$table = $al->ReadFullDuplineOutputStatusTable();
//echo "[ii] Dupline Output Address A5 is {$table['A5']}\n\n";

// Get Dupline Input Status for channels A1-08
//$table = $al->ReadFullDuplineInputStatusTable();
//echo "[ii] Dupline Input Address A2 is {$table['A2']}\n\n";

// Set Dupline Channel A1 to 1
//$al->DuplineByChannel_SetSingleOutputBit("A1",true);

// Simulate (buttonpress) on Channel A1 (100msec seems like the fastest possible toggle)
//$al->ToggleDuplineOutputChannel("A1",200);

// Get RegisterAddress offset for Dupline Channel I3:
//$v = $al->GetRegisterAddressOffsetByDuplineAddress("I3");
//echo "[ii] Dupline  Address I3 has register address offset '{$v}'\n\n";

// Dupline (Analink) heating level (normal) by function_id from a BEW-TEMDIS based temperature control unit
//$t = $al->SetHeatingPointByFunctionId_BEWTEMDIS(11, 21.0);
//echo "[ii] Termostat normal in function 11 is {$t} C\n\n";

// Dupline (Analink) heating level (power saving) by function_id from a BEW-TEMDIS based temperature control unit
//$t = $al->SetHeatingPointByFunctionId_BEWTEMDIS(11, 20.5, true);
//echo "[ii] Termostat normal in function 11 is {$t} C\n\n";

// Get Dupline (Analink) temperature by function_id from a BEW-TEMDIS based temperature control unit
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(9);
//echo "[ii] Termostat normal in function 11 is {$temperature} C\n\n";

// Get Dupline (Analink) temperature by function_id from a BEW-TEMDIS based temperature control unit
//$temperature = $al->GetTermostatByFunctionId_BEWTEMDIS(9, true);
//echo "[ii] Termostat powersave in function 11 is {$temperature} C\n\n";

//$data = $al->DoModbusFunction_43ReadDeviceIdentification(1, "4","0");
//print_r($data);

//$output_enabled = $al->DuplineByChannel_GetOutputStatus("A5");
//echo "Dupline Output Channel A5 is " . ($output_enabled ? "HIGH" : "LOW" ). "\n";

//$input_enabled = $al->DuplineByChannel_GetInputStatus("A1");
//echo "Dupline input Channel A1 is " . ($input_enabled ? "HIGH" : "LOW") . "\n";

//$data = $al->DuplineByChannel_GetAnalinkValue("N1");
//echo "Analink value of channel N1 is hex='{$data}', dec='".hexdec($data)."'\n";

//$al->DuplineByChannel_SetSingleOutputBit("A1",false);

//Toggle LED function 55 (simulate button push to input)
//$al->ToggleDuplineFunctionOutput(55);

// Get temperature from BEW-TEMDIS thermostat module, this is done by function, ref section 6.2 in pdf "Smart-House  Modbus Protocol.pdf"
//$func_num = 9;
//$temp = $al->GetTemperatureByFunctionId_BEWTEMDIS($func_num);
//echo "The temperature on function {$func_num} is '{$temp}'\n";

// Get temperature from BSI-TEMANA thermostat module, this is done by function, ref section 6.2 in pdf "Smart-House  Modbus Protocol.pdf"
//$func_num = 9;
//$temp = $al->GetTemperatureByFunctionId_BSITEMANA($func_num);
//echo "The temperature on function {$func_num} is '{$temp}'\n";


$al->Disconnect();

// Done, formatting
if (php_sapi_name() != 'cli')
    echo "</pre>";

?>
