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
         * @param type $host
         * @param type $port
         */
	public function __construct($host, $port=502) { 
		parent::__construct($host, $port); 
	} 

	/**
         * 
         * @param type $function_id
         * @param type $param_number
         * @param type $param_index
         * @return type
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
         * @param type $function_id
         * @param type $param_number
         * @param type $param_index
         * @return type
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
         * @param type $function_id
         * @param type $param_number
         * @param type $param_index
         * @return type
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
         * @param type $duplineaddr
         * @param type $data
         * @return type
         */
	public function Dupline_SetSingleOutputBit($duplineaddr, $data){
		$dupline_start_addr = 5376;	// From "Smart-House  Modbus Protocol.pdf", section 5.4
		
		$register_address = $dupline_start_addr + $this->GetRegisterAddressOffsetByDuplineAddress($duplineaddr);
		$register_address 	= self::Convert10to16($register_address, 2);

		$addr_hi 		= substr($register_address,0,2);
		$addr_lo 		= substr($register_address,2,2);
		$points_hi 	= substr($data,0,2);
		$points_lo 	= substr($data,2,2);
		
		$result = $this->DoModbusFunction_05WriteSingleCoil(1, $addr_hi, $addr_lo, $points_hi, $points_lo);
		$data = implode("",$result['frame']['register']);
		return $result;
	}
	
        /**
         * 
         * @param type $function_id
         * @param type $number_of_registers
         * @param type $param_number
         * @param type $param_index
         * @param type $register_value
         * @return type
         */
	public function DuplineByFunction_PresetMultipleRegisters($function_id, $number_of_registers, $param_number, $param_index, $register_value){
		$function_id 	= self::Convert10to16($function_id, 2);
		$values[]  = $function_id;
	
		// byte 1+2
		$register_address = "FF00";
		
		// byte 3
		$param_number = str_pad(dechex($param_number), 2, "0", STR_PAD_LEFT);
		$values[]  = $param_number;
		
		// byte 4
		if($param_index !== NULL){
			$param_index = str_pad(dechex($param_index), 2, "0", STR_PAD_LEFT);
			$values[]  = $param_index;
		}
			
		$packet = implode("",$values) . $register_value;
		//echo "[Packet] register_address=[{$register_address}] function_id=[{$function_id}] param_number=[{$param_number}] param_index=[{$param_index}] register_value=[{$register_value}]\n";
		//echo "         packet=[{$packet}]\n";
		return $this->DoModbusFunction_16WriteMultipleRegisters(1, $register_address, strlen($register_value)/2, $packet);
	}

	/**
	 * Get Dupline (Analink) temperature by function_id
	 * Tested with BEW-TEMDIS (ELKO Temperature Controller)
	 * 
	 * @param int $function_id Decimal function number of the Temperature function
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
         * @param type $function_id
         * @param type $temperature
         * @param type $energysaving
         * @return boolean
         */
	public function SetHeatingPointByFunctionId_BEWTEMDIS($function_id, $temperature, $energysaving = 0){
		$temperature = str_pad(dechex(intval($temperature*10)), 8, "0", STR_PAD_LEFT);
		$es = $energysaving ? "01" : "00";
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($function_id, 4, $es, "00", $temperature);
		return true;
	}

	
	/**
         * 
         * @param type $function_id
         * @return type
         * @throws Exception
         */
	public function GetBitValueByFunctionId($function_id){
		if(!$function_id){			throw new Exception("Missing functionId");		}
		$hexresult = $this->DuplineByFunction_ReadOutputStatus($function_id, 0, 0);
		$value = hexdec($hexresult);
		return $value;
	}

	/**
         * 
         * @return type
         */
	public function ReadFullDuplineOutputStatusTable(){
		$packet = $this->DoModbusFunction_03ReadHoldingRegisters(1,"00","00","00","08");
		return $this->ParseFullDuplineTable($packet);
	}

	/**
         * 
         * @return type
         */
	public function ReadFullDuplineInputStatusTable(){
		$packet = $this->DoModbusFunction_03ReadHoldingRegisters(1,"00","10","00","08");
		return $this->ParseFullDuplineTable($packet);
	}
	
	/**
         * 
         * @param type $dupline_address
         * @param type $msecdelay
         * @return boolean
         * @throws Exception
         */
	public function ToggleDuplineOutputChannel($dupline_address, $msecdelay=500){
		if(!$dupline_address){
			throw new Exception("Missing dupline address");
		}
		// Toggle 
		$this->Dupline_SetSingleOutputBit($dupline_address, "0001");
		usleep($msecdelay * 1000);	// wait $msecdelay msec
		$this->Dupline_SetSingleOutputBit($dupline_address, "0000");
		return true;
	}
	
	
	/**
         * 
         * @param type $packet
         * @return type
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
	
	
	public function SetFunctionBit($Sensor_Index, $parm1, $parm2, $value){
		$hexresult = $this->DuplineByFunction_PresetMultipleRegisters($Sensor_Index, 65280, 0x2, $parm1, $parm2, $value);
		$value = hexdec($hexresult);
		return $value;
	}
	*/

}


?>