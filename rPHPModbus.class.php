<?php


class rPHPModbus {
	private $_trid;
	
	private $_host;
	private $_port;
	
	private $_sec;
	private $_usec;
	
	private $_debug = false;
	
	private $_socket;
	
	private $_waitusec = 0; // usecs to wait between payloads
	
	public function rPHPModbus($host, $port=502){
		$_trid=0;
		$this->_host = $host; 
		$this->_port = $port;  
		$this->_sec = 30;
		$this->_usec = 30;
	}
	
	public function Debug($e){
		$this->_debug=$e;
	}
	
	public function Connect(){
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

		$result = socket_connect($this->_socket, $this->_host, $this->_port);
		if ($result === false) {
			throw new Exception( "[!!] socket_connect() failed in Connect().\nReason: ($result) " . socket_strerror(socket_last_error($this->_socket)));
		} else {
			if($this->_debug) echo "[OK] Successfully connected to host {$this->_host}\n";
		}
	}
	
	public function DoModbusQuery($modbus_function_code, $data){


		$modbus_transaction_id = $this->_GetNextTransactionId();
	//	if($this->_debug) echo "[AA] ID={$modbus_transaction_id}\n";
		
		$modbus_protocol_identifier = 0;
		$modbus_unit_identifier=1;

		$payload = $this->_CreateModbusTCPPayload($modbus_transaction_id, $modbus_protocol_identifier, $modbus_unit_identifier, $modbus_function_code, $data);

	//	if($this->_debug) echo "[AA] PAYLOAD={$payload}\n";
		
		usleep($this->_waitusec);

		$hexres = $this->_DoModbusPoll($payload);
	//	if($this->_debug) echo "[AA] HEXRES={$hexres}\n";
		
		$res = $this->_GetHexvalueFromModbusResult($hexres);
		if($this->_debug) echo "[AA] Result={$res}\n";
		return $res;
	}
	/*
	public function DoModbusQuery($function_code, $data){
		$id = $this->_GetNextTransactionId();
		if($this->_debug) echo "[AA] ID={$id}\n";
	}
	*/
	
	public function Disconnect(){
		socket_close($this->_socket);
	}
	
	/*
	private function _CreateAnalinkModbusTCPPayload($modbus_transaction_id, $modbus_function_code, $analink_function_index, $extradata="00"){
		$modbus_protocol_identifier = 0;
		$modbus_unit_identifier=1;
		$payload = $this->_CreateModbusTCPPayload($modbus_transaction_id, $modbus_protocol_identifier, $modbus_unit_identifier, $modbus_function_code, "ff00{$analink_function_index}{$extradata}");
		return $payload;
	}
	*/

	private function _CreateModbusTCPPayload($transaction_id, $protocol_identifier, $unit_identifier, $modbus_function_code, $data_bytes){
		$remaining_bytes = strlen($data_bytes/2) + 4;
		
		$hexbytecount = str_pad(dechex($remaining_bytes), 4, "0", STR_PAD_LEFT);
		$protocol_identifier = str_pad(dechex($protocol_identifier), 4, "0", STR_PAD_LEFT);
		$unit_identifier = str_pad(dechex($unit_identifier), 2, "0", STR_PAD_LEFT);
		$modbus_function_code = str_pad(dechex($modbus_function_code), 2, "0", STR_PAD_LEFT);


		if($this->_debug) echo "[MB] Transaction Identifier=[{$transaction_id}] Protocol Identifier=[{$protocol_identifier}] Length Field=[{$hexbytecount}] Unit Identifier=[{$unit_identifier}] Function code=[{$modbus_function_code}] Data bytes=[{$data_bytes}]\n";
		
		$header = "{$transaction_id}{$protocol_identifier}{$hexbytecount}{$unit_identifier}{$modbus_function_code}";
		
		if(strlen($header) != 16){
			// Malformed TCP Frame "header" length
			throw new Exception("Malformed Modbus TCP Frame Header length, should be 16: length='".strlen($header)."', data='{$header}'");
		}
		
		$payload = pack("H*","{$header}{$data_bytes}");
		return $payload;
	}

	private function _DoModbusPoll($payload){
		if(!$this->_socket){
			throw new Exception( "[!!] DoModbusAnalinkRequest() failed.\nReason: Socket not connected");
		}
		$length = strlen($payload);
		// send data
		while (true) {
				$sent = socket_write($this->_socket, $payload, $length);
				if ($sent === false) {
						break;
				}
				if ($sent < $length) {
						$payload = substr($payload, $sent);
						$length -= $sent;
				} else {
						break;
				}
		}
		//read data
		$result = socket_read ($this->_socket, 4096);
		if($result === FALSE){
			throw new Exception( "[!!] Could not read server response in DoModbusAnalinkRequest()\n");
		}
		if($this->_debug) echo "[IN] {$result}\n";
		return $result;
	}

	private function _GetNextTransactionId(){
		$this->trid++;
		if($this->trid == 15) $this->trid++; // bug in smarthouse modbus/tcp wrapper, so we avoid 0x0f transaction id
		if($this->trid == 255) $this->trid = 0; // wrap
		$trid_to_send = str_pad(dechex($this->trid), 4, "0", STR_PAD_LEFT);
		if($this->_debug) echo "[DD] transaction_id='{$trid_to_send}'\n";
		return $trid_to_send;
	}

	// Convert result to Readable
	private function _GetHexvalueFromModbusResult($result){
		$raw = str_split($this->_ConvertStrToHex($result),2);
		if($this->_debug) echo "[->] ".$this->_ConvertStrToHex($result)."\n";
		$hvalue = $raw[count($raw)-1];
		return $hvalue;
	}

	// Convert input to hex
	private function _ConvertStrToHex($string){
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
			if(ord($string[$i]) < 15)
			  $hex .= "0";
      $hex .= dechex(ord($string[$i]));
				
    }
    return $hex;
	}
//////////////////// Dupline Spesific Functions (By Function ID)

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
	
	
	
	
	// Button press
	// start numreg  bb b1 b2 b3 b4
	// ff00  0002    04 00 36 00 01
	// ff00  0002    04 00 30 00 01
  // ff00  0002    04 00 30 00 00 1
	// ff00  0002    04 00 25 00 01
}

?>