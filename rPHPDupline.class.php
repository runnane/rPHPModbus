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
	
	Please see rPHPModbus.class.php for more info.
	
*/

require_once("rPHPModbus.class.php");

class rPHPDupline extends rPHPModbus {
	
	/** 
	 *
	 */
	public function __construct($host, $port=502) { 
		parent::__construct($host, $port); 
	} 

	/** 
	 *
	 */
	public function DuplineByFunction_ReadOutputStatus($function_id, $param_number, $param_index){
		$param				= "{$param_number}{$param_index}";
		$function_id	= self::Convert10to16($function_id, 2);
		
		$addr_hi		= "ff";
		$addr_lo		= substr($function_id,0,2);
		$points_hi	= substr($function_id,2,2);
		$points_lo	= $param;
	
		$result			= $this->DoModbusFunction_01ReadCoilStatus(1, $addr_hi, $addr_lo, $points_hi, $points_lo);
		$data				= implode("",$result['frame']['register']);
		return $data;
	}
	
	/** 
	 *
	 */
	public function DuplineByFunction_ReadValue($function_id, $param_number, $param_index){
		$param 				= "{$param_number}{$param_index}";
		$function_id 	= self::Convert10to16($function_id, 2);
		
		$addr_hi 		= "ff";
		$addr_lo 		= substr($function_id,0,2);
		$points_hi 	= substr($function_id,2,2);
		$points_lo 	= $param;
		$result = $this->DoModbusFunction_02ReadInputStatus(1, $addr_hi, $addr_lo, $points_hi, $points_lo);
		$data = implode("",$result['frame']['register']);
		return $data;
	}
	
	/** 
	 *
	 */
	public function DuplineByFunction_ReadMultipleRegisters($function_id, $param_number, $param_index){
		$param 				= "{$param_number}{$param_index}";
		$function_id 	= self::Convert10to16($function_id, 2);
		
		$addr_hi 		= "ff";
		$addr_lo 		= substr($function_id,0,2);
		$points_hi 	= substr($function_id,2,2);
		$points_lo 	= $param;
		
		$result = $this->DoModbusFunction_03ReadHoldingRegisters(1, $addr_hi, $addr_lo, $points_hi, $points_lo);
		$data = implode("",$result['frame']['register']);
		return $data;
	}
	
	/** 
	 *
	 */
	public function Dupline_SetSingleOutputBit($duplineaddr, $data){
		
		$register_address = 1500 + $this->GetRegisterAddressOffsetByDuplineAddress($duplineaddr);
		
		$addr_hi 		= substr($register_address,0,2);
		$addr_lo 		= substr($register_address,2,2);
		$points_hi 	= substr($data,0,2);
		$points_lo 	= substr($data,2,2);
		
		$result = $this->DoModbusFunction_05WriteSingleCoil(1, $addr_hi, $addr_lo, $points_hi, $points_lo);
		$data = implode("",$result['frame']['register']);
		return $result;
	}

	/**
	 * Get Dupline (Analink) temperature by function_id
	 * Tested with BEW-TEMDIS (ELKO Temperature Controller)
	 * 
	 * @param int $function_id Decimal function number of the Temperature function
	 * 
	 * @return float The current temperature for the BEW-TEMDIS
	 */
	public function GetTemperatureByFunctionId_BEWTEMDIS($function_id){
		if(!$function_id){
			throw new Exception("Missing functionId");
		}
		
		$result = $this->DuplineByFunction_ReadValue($function_id, 0, 0);
		$value = hexdec($result);
		if($value>1) $value -= 1.0;
		$value = number_format(($value*50)/255,1);
		return $value;
	}
	
	/**
	 * Get Dupline (Analink) temperature by function_id
	 * Tested with BEW-TEMDIS (ELKO Temperature Controller)
	 * 
	 * @param int $function_id Decimal function number of the Temperature function
	 * 
	 * @return float The current temperature for the BEW-TEMDIS
	 */
	public function GetTermostatByFunctionId_BEWTEMDIS($function_id, $energysaving=false){
		if(!$function_id){			throw new Exception("Missing functionId");		}
		$es = $energysaving ? 1 : 0;
		$result = $this->DuplineByFunction_ReadMultipleRegisters($function_id, $es, 0);
		$value = hexdec($result)/10.0;
		return $value;
	}
	
	/** 
	 *
	 */
	public function GetBitValueByFunctionId($function_id){
		if(!$function_id){			throw new Exception("Missing functionId");		}
		$hexresult = $this->DuplineByFunction_ReadOutputStatus($function_id, 0, 0);
		$value = hexdec($hexresult);
		return $value;
	}

	/** 
	 *
	 */
	public function ReadFullDuplineOutputStatusTable(){
		$packet = $this->DoModbusFunction_03ReadHoldingRegisters(1,"00","00","00","08");
		return $this->ParseFullDuplineTable($packet);
	}

	/** 
	 *
	 */
	public function ReadFullDuplineInputStatusTable(){
		$packet = $this->DoModbusFunction_03ReadHoldingRegisters(1,"00","10","00","08");
		return $this->ParseFullDuplineTable($packet);
	}
	
	/** 
	 *
	 */
	public function ToggleDuplineOutputChannel($dupline_address, $msecdelay=500){
		if(!$dupline_address){
			throw new Exception("Missing dupline address");
		}
		// Toggle 
		$table = $this->Dupline_SetSingleOutputBit($dupline_address, "0001");
		usleep($msecdelay * 1000);	// wait $msecdelay msec
		$table = $this->Dupline_SetSingleOutputBit($dupline_address, "0000");
		return true;
	}
	
	/** 
	 *
	 */
	private function ParseFullDuplineTable($packet){
		$binstr = self::GetBitFromHex(implode("",$packet['frame']['register']));
		$i=0;
		for($grp = 65; $grp <= 80; $grp += 2){

			for($chan = 8; $chan > 0; $chan--){
				$output[chr($grp+1) . $chan] =  $binstr{$i};
				$i++;
			}
			for($chan = 8; $chan > 0; $chan--){
				$output[chr($grp) . $chan] =  $binstr{$i};
				$i++;
			}
		}
		ksort($output);
		return $output;
	}
	
	/**
	 * This function accepts a Dupline Address (ie. A3 or E1) and converts it
	 * to the offset from the register address A1 has. It does not account
	 * for other offsets than ++ increments. (Limit/On/Off/Delays)
	 *
	 * @parm string $dupline_address The Dupline Address
	 * @returns number The register address offset relative to A1. 
	 */
	public function GetRegisterAddressOffsetByDuplineAddress($dupline_address){
		return (ord(strtoupper($dupline_address{0}))-65)*8 + (((int)$dupline_address{1})-1);
	}
	

	/*
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
		$hexresult = $this->DoModbusQuery(1, 16, "{$register_address}{$number_of_registers}{$byte_count}{$function_id}{$parm_number}{$parm_index}{$register_value}");
	}
	*/

	/*
	*/
	
	
	
	/*
	public function DoButtonPress($function_id, $waittime=0.5){
		if(!$function_id){
			throw new Exception("Missing functionId");
		}
		// Toggle 
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($function_id, 65280, 0x2, 0x0, NULL, 0x1);
		usleep($waittime * 1000 * 100); // wait 500msec
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($function_id, 65280, 0x2, 0x0, NULL, 0x0);
		return true;
	}
	
	public function SetHeatingPoint($Sensor_Index, $temperature, $energysaving=0){
		$sp = intval($temperature*10);
		$es = $energysaving ? 0x1 : 0x0;
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x4, $es, 0x0, $sp);
		return true;
	}
	
	public function SetFunctionBit($Sensor_Index, $parm1, $parm2, $value){
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x2, $parm1, $parm2, $value);
		$value = hexdec($hexresult);
		return $value;
	}
	*/

}


?>