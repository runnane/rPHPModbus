<?php
/*
	rPHPDupline.class.php - PHP classes for communicating with Modbus TCP controllers
	Copyright (C) 2013 Jon Tungland
	
	This file is part of rPHPModbus.
	
	rPHPModbus is free software: you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation, either version 3 of the License, or
	(at your option) any later version.
	
	rPHPModbus is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
	
	You should have received a copy of the GNU General Public License
	along with rPHPModbus.  If not, see <http://www.gnu.org/licenses/>.
*/

require_once("rPHPModbus.class.php");

class rPHPDupline extends rPHPModbus {
	
	
	public function DuplineByFunction_ReadOutputStatus($function_id, $param_number){
		$function_id = str_pad(dechex($function_id), 4, "0", STR_PAD_LEFT);
		$hexresult = $this->DoModbusQuery(1, "ff{$function_id}{$param_number}");
		return $hexresult;
	}
	
	public function DuplineByFunction_ReadValue($function_id, $param_number){
		$function_id = str_pad(dechex($function_id), 4, "0", STR_PAD_LEFT);
		$hexresult = $this->DoModbusQuery(2, "ff{$function_id}{$param_number}");
		return $hexresult;
	}
	
	public function DuplineByFunction_ReadMultipleRegisters($function_id, $param_number=1, $param_index=0){
		$function_id = str_pad(dechex($function_id), 4, "0", STR_PAD_LEFT);
		$param = "{$param_number}{$param_index}";
		if(strlen($param) != 2){
			throw new Exception("Malformed DuplineByFunction_ReadMultipleRegisters() parm_number length, should be 2: length='".strlen($param)."', data='{$param}'");
		}
		$hexresult = $this->DoModbusQuery(3, "ff{$function_id}{$param}");
		return $hexresult;
	}
	public function Modbus_PresetMultipleRegisters($function_id, $register_address, $number_of_registers){
		
	}


	public function DuplineByFunction_PresetMultipleRegisters($function_id, $register_address, $number_of_registers, $parm_number, $parm_index, $register_value){
		$byte_count = $number_of_registers*2;
		$length = $byte_count;
		$rest = $byte_count;
		
		$number_of_registers = str_pad(dechex($number_of_registers), 4, "0", STR_PAD_LEFT);
		$byte_count = str_pad(dechex($byte_count), 2, "0", STR_PAD_LEFT);
		$function_id = str_pad(dechex($function_id), 4, "0", STR_PAD_LEFT);
		
		// byte 1+2
		$register_address = str_pad(dechex($register_address), 4, "0", STR_PAD_LEFT);
		$rest -=2;
		
		// byte 3
		$parm_number = str_pad(dechex($parm_number), 2, "0", STR_PAD_LEFT);
		$rest -=1;
		
		// byte 4
		if($parm_index !== NULL){
			$parm_index = str_pad(dechex($parm_index), 2, "0", STR_PAD_LEFT);
			$rest -=1;
		}
			
		$register_value = str_pad(dechex($register_value), $rest*2, "0", STR_PAD_LEFT);
		
		if($this->_debug) echo "[Packet] register_address=[{$register_address}] number_of_registers=[{$number_of_registers}] byte_count=[{$byte_count}] function_id=[{$function_id}] parm_number=[{$parm_number}] parm_index=[{$parm_index}] register_value=[{$register_value}]\n";
		$hexresult = $this->DoModbusQuery(16, "{$register_address}{$number_of_registers}{$byte_count}{$function_id}{$parm_number}{$parm_index}{$register_value}");
	}
	
//////////////////// Spesific Module Functions

	public function GetCurrentTemperature($Sensor_Index){
		$hexresult = $this->DuplineByFunction_ReadValue($Sensor_Index, "00");
		$value = hexdec($hexresult);
		if($value>1) $value -= 1.0;
		$value = number_format(($value*50)/255,1);
		return $value;
	}
	
	public function GetTermostatNormal($Sensor_Index, $energysaving=false){
		$es = $energysaving ? 0x1 : 0x0;
		$hexresult = $this->DuplineByFunction_ReadMultipleRegisters($Sensor_Index, $es, 0x0);
		$value = hexdec($hexresult)/10.0;
		return $value;
	}
	

	public function GetBitValue($Sensor_Index){
		$hexresult = $this->DuplineByFunction_ReadOutputStatus($Sensor_Index, "00");
		$value = hexdec($hexresult);
		return $value;
	}
	
	public function DoButtonPress($Sensor_Index, $waittime=0.5){
		// Toggle 
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x2, 0x0, NULL, 0x1);
		usleep($waittime * 1000 * 100); // wait 500msec
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x2, 0x0, NULL, 0x0);
		return true;
	}
	
	public function SetHeatingPoint($Sensor_Index, $temperature, $energysaving=0){
		$sp = intval($temperature*10);
		$es = $energysaving ? 0x1 : 0x0;
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x4, $es, 0x0, $sp);
		return true;
	}
	
	public function SetBit($adr=false,$data=false){
		$hexresult = $this->DoModbusQuery(5, "{$adr}{$data}");
		$value = hexdec($hexresult);
		return $value;
	}
	
	public function SetFunctionBit($Sensor_Index, $parm1, $parm2, $value){
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x2, $parm1, $parm2, $value);
		$value = hexdec($hexresult);
		return $value;
	}

//////////////////// Dupline Spesific Functions (By Function ID)

	
	
	
	
	// Button press
	// start numreg  bb b1 b2 b3 b4
	// ff00  0002    04 00 36 00 01
	// ff00  0002    04 00 30 00 01
  // ff00  0002    04 00 30 00 00 1
	// ff00  0002    04 00 25 00 01

}


?>