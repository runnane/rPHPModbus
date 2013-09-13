<?php
/*
	rPHPModbus.class.php - PHP classes for communicating with Modbus TCP controllers
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


class rPHPModbus {
	private $_trid;
	
	private $_host;
	private $_port;
	
	private $_sec;
	private $_usec;
	
	private $_debug = false;
	
	private $_socket;
	
	private $_waitusec = 0; // usecs to wait between payloads
	
	public function __construct($host, $port=502){
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
			$err = socket_strerror(socket_last_error($this->_socket));
			if($this->_debug) echo "[--] socket_connect() failed in rPHPModbus->Connect(): ($err)\n";
			throw new Exception( "socket_connect() failed in Connect().\nReason: ($err)" );
		} else {
			if($this->_debug) echo "[++] Successfully connected to host {$this->_host}\n";
		}
	}
	
	public function DoModbusQuery($modbus_unit_identifier, $modbus_function_code, $data){
		if($this->_debug) echo "[ii] ------------- Starting new transaction -------------\n";
		$modbus_transaction_id = $this->_GetNextTransactionId();
		$modbus_protocol_identifier = 0;
		$payload = $this->_CreateModbusTCPPacket($modbus_transaction_id, $modbus_protocol_identifier, $modbus_unit_identifier, $modbus_function_code, $data);
		usleep($this->_waitusec);
		$result_raw = $this->_DoModbusPoll($payload);
		$result = $this->_ParseModbusResult($result_raw, $payload);
		if($this->_debug) echo "[ii] ------------- Ending transaction -------------------\n";
		return $result;
	}
	
	public function Disconnect(){
		socket_close($this->_socket);
		if($this->_debug) echo "[++] Disconnected Socket\n";
	}
	
	
	public function DoModbusFunction_01ReadCoilStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 1, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	public function DoModbusFunction_02ReadInputStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 2, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	public function DoModbusFunction_03ReadHoldingRegisters($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 3, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	private function _DoModbusFunction_Basic($slave_address, $function, $addr_hi, $addr_lo, $points_hi, $points_lo){
		$payload = "{$addr_hi}{$addr_lo}{$points_hi}{$points_lo}";
		if(strlen($payload) != 8){
			throw new Exception("Malformed _DoModbusFunction_Basic() parm_number length, should be 8: length='".strlen($payload)."', data='{$payload}'");
		}
		if($this->_debug) echo "[++] Address/Points: '{$payload }'\n";
		return $this->DoModbusQuery($slave_address, $function, $payload);
	}
	
	
	private function _CreateModbusTCPPacket($transaction_id, $protocol_identifier, $unit_identifier, $modbus_function_code, $data){
		$remaining_bytes = strlen($data)/2 + 2;
		
		$hexbytecount = self::Convert10to16($remaining_bytes,2);
		$protocol_identifier = self::Convert10to16($protocol_identifier);
		$unit_identifier = self::Convert10to16($unit_identifier,1);
		$modbus_function_code = self::Convert10to16($modbus_function_code,1);

		if($this->_debug) echo "[++] Creating Modbus Packet:\n";
		if($this->_debug) echo "   header: TransactionIdentifier=[{$transaction_id}] ProtocolIdentifier=[{$protocol_identifier}] RemainingBytes=[{$hexbytecount}]\n";
		if($this->_debug) echo "   frame : UnitIdentifier=[{$unit_identifier}] FunctionCode=[{$modbus_function_code}]\n";
		if($this->_debug) echo "           Data=[{$data}]\n";
		
		$header = "{$transaction_id}{$protocol_identifier}{$hexbytecount}{$unit_identifier}{$modbus_function_code}";
		
		if(strlen($header) != 16){
			if($this->_debug) echo "[--] Malformed Modbus TCP Frame Header length, should be 16: length='".strlen($header)."', data='{$header}'\n";
			throw new Exception("Malformed Modbus TCP Frame Header length, should be 16: length='".strlen($header)."', data='{$header}'");
		}
		
		$packet = pack("H*","{$header}{$data}");
		return $packet;
	}
  
	/**
	 * 
	 *
	 * @return returnpacket raw
	 */
	private function _DoModbusPoll($request){
		if($this->_debug) echo "[ii] Sending packet .... \n";
		if(!$this->_socket){
			throw new Exception( "[!!] DoModbusAnalinkRequest() failed.\nReason: Socket not connected");
		}
		$payload = $request;
		
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
		if($this->_debug) echo "[ii] Waiting for response ... \n";
		$result = socket_read ($this->_socket, 4096);
		if($result === FALSE){
			if($this->_debug) echo "[--] Could not read server response in _DoModbusPoll()\n";
			throw new Exception( "Could not read server response in _DoModbusPoll()\n");
		}

		// Check transaction_id of packet
		$out_transaction = substr(self::ConvertStrToHex($payload),0,2);
		$in_transaction = substr(self::ConvertStrToHex($result),0,2);

		if($out_transaction == $in_transaction){
			if($this->_debug) echo "[++] Got correct response _DoModbusPoll()\n";
		}else{
			if($this->_debug) echo "[--] Invalid response in _DoModbusPoll() (sent: '{$out_transaction }', got: '{$in_transaction}')\n";
			throw new Exception( "Got invalid transaction id in _DoModbusPoll() (sent: '{$out_transaction }', got: '{$in_transaction}')\n");
		}
		
		return $result;
	}

	private function _GetNextTransactionId(){
		$this->trid++;
		if($this->trid == 15) $this->trid++; // bug in smarthouse modbus/tcp wrapper, so we avoid 0x0f transaction id
		if($this->trid == 255) $this->trid = 0; // wrap
		$trid_to_send = self::Convert10to16($this->trid);
		if($this->_debug) echo "[++] Got next TransactionId: '0x{$trid_to_send}'\n";
		return $trid_to_send;
	}

	// Convert result to Readable
	private function _GetHexvalueFromModbusResult($result){
		$raw = str_split(self::ConvertStrToHex($result),2);
		if($this->_debug) echo "[->] ".self::ConvertStrToHex($result)."\n";
		$hvalue = $raw[count($raw)-1];
		return $hvalue;
	}
	
	private function _ParseModbusResult($result, $request){
		$p = self::ConvertStrToHex($result);
		$packet['header']['trid'] 						= substr($p,0,4);
		$packet['header']['protoid'] 					= substr($p,4,4);
		$packet['header']['remaining_bytes'] 	= substr($p,8,4);
		
		$packet['frame']['unit'] 							= substr($p,12,2);
		$packet['frame']['function_code'] 		= substr($p,14,2);

		$packet['frame']['byte_count'] 				= substr($p,16,2);
		
		$to_parse = substr($p,18);

		switch(hexdec($packet['frame']['function_code'])){
			case 1: //01 Read Coil Status
				while(strlen($to_parse)>1){
					$packet['frame']['register'][] 			= substr($to_parse, 0, 2);
					$to_parse= substr($to_parse, 2 );
				}
			break;
			case 2: // 02 Read Input Status
				while(strlen($to_parse)>1){
					$packet['frame']['register'][] 			= substr($to_parse, 0, 2);
					$to_parse= substr($to_parse, 2 );
				}
			break;
			case 3: // 03 Read Holding Registers
				while(strlen($to_parse)>1){
					$packet['frame']['register'][] 			= substr($to_parse, 0, 4);
					$to_parse= substr($to_parse, 4 );
				}
			
			break;
			default:
				throw new Exception("Cannot parse function_code '{$packet['frame']['function_code']}', NOT IMPLEMENTED!");
			break;
		}
		
		
		if($this->_debug) echo "[++] Got Modbus Packet:\n";
		if($this->_debug) echo "   header:  TransactionIdentifier=[{$packet['header']['trid']}] ProtocolIdentifier=[{$packet['header']['protoid']}] RemainingBytes=[{$packet['header']['remaining_bytes']}]\n";
		if($this->_debug) echo "   frame :  ByteCount=[{$packet['frame']['byte_count']}] UnitIdentifier=[{$packet['frame']['unit']}] FunctionCode=[{$packet['frame']['function_code']}]\n";
		if($this->_debug) echo "            Data=[".implode("",$packet['frame']['register'])."]\n";
		//print_r($packet);
		return $packet;
	}

	// Convert input to hex
	public static function ConvertStrToHex($string){
    $hex='';
    for ($i=0; $i < strlen($string); $i++){
			if(ord($string[$i]) < 15)
			  $hex .= "0";
      $hex .= dechex(ord($string[$i]));
				
    }
    return $hex;
	}
	
	
	public static function Convert10to16($input,$bytes=2){
		return str_pad(dechex((int)$input), $bytes*2, "0", STR_PAD_LEFT);
	}
}

?>