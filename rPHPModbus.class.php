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
}

?>