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
	/** 
	 * Last transaction id used
	 */
	private $_trid;

	/** 
	 * Hostname/IP to controller RTU-to-TCP bridge
	 */	
	private $_host;
	
	/** 
	 * Port to controller RTU-to-TCP bridge (normally 502)
	 */	
	private $_port;
	
	
	/** 
	 * Debug bool to toggle debug output
	 */	
	private $_debug = false;
	
	/** 
	 * Network tcp socket
	 */	
	private $_socket;
	
	//private $_sec;
	//private $_usec;
	//private $_waitusec = 0; // usecs to wait between payloads
	
	/** 
	 * Array with which function codes we have implemented
	 */	
	private $_ImplementedModbusFunctionCodes = array(1, 2, 3, 5);
	
	/** 
	 * Constructor
	 * 
	 * @param string $host Host/IP to host
	 * @param int $port TCP-port to host (502 usually)
	 */	
	public function __construct($host, $port=502){
		$_trid=0;
		$this->_host = $host; 
		$this->_port = $port;  
		
		
		if (!extension_loaded('sockets')) {
			if($this->_debug) echo "[--] rPHPModbus() cannot initialize, required sockets extension not loaded\n";
			throw new Exception( "rPHPModbus() cannot initialize, required sockets extension not loaded" );
		}
		//$this->_sec = 30;
		//$this->_usec = 30;
	}
	
	/** 
	 * Enables/disables debug output
	 * 
	 * @param bool $enabled Debug enabled
	 */	
	public function Debug($enabled){
		$this->_debug=$enabled;
	}
	
	/** 
	 * Connects the socket to specified host
	 */	
	public function Connect(){
		// Error checking
		if(!$this->_host ||  !$this->_port){
			if($this->_debug) echo "[--] Connect() failed, missing hostname/IP\n";
			throw new Exception( "Connect() failed, missing hostname/IP" );
		}
		
		// Create socket
		$this->_socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);
		if(!$this->_socket){
			if($this->_debug) echo "[--] Connect() failed, unable to create AF_INET TCP Stream socket\n";
			throw new Exception( "Connect() failed, unable to create AF_INET TCP Stream socket" );
		}

		// Set timeout
		$timeout = array( 'sec'=>1, 'usec'=>500000 );
		if(!socket_set_option($this->_socket, SOL_SOCKET, SO_SNDTIMEO, $timeout)){
			if($this->_debug) echo "[--] Connect() failed, unable to set timeoutoption on socket\n";
			throw new Exception( "Connect() failed, unable to set timeoutoption on socket" );
		}
		
		// Connect socket
		$result = socket_connect($this->_socket, $this->_host, $this->_port);
		
		// Check if successful
		if ($result === false) {
			$err = socket_strerror(socket_last_error($this->_socket));
			if($this->_debug) echo "[--] socket_connect() failed in rPHPModbus->Connect(): ($err)\n";
			throw new Exception( "socket_connect() failed in Connect().\nReason: ($err)" );
		}else{
			if($this->_debug) echo "[++] Successfully connected to host {$this->_host}\n";
		}
		
	}
	
	/** 
	 * Disconnects the socket to specified host
	 */	
	public function Disconnect(){
		socket_close($this->_socket);
		if($this->_debug) echo "[++] Disconnected Socket\n";
	}
	
	/** 
	 * Attempt create and send a modbus packet, and parse result 
	 * 
	 * @param int $modbus_unit_identifier Unitidentifier when using multiple bus-es on same TCP/IP bridge. Normally '1'.
	 * @param int $modbus_function_code Modbus function code, ie. '2'
	 * @param string $data Frame data in ascii hex, ie. '06000001'
	 *
	 * @returns array response packet in parsed array
	 */	
	public function DoModbusQuery($modbus_unit_identifier, $modbus_function_code, $data){
		if($this->_debug) echo "[ii] ------------- Starting new transaction -------------\n";
		$modbus_transaction_id = $this->_GetNextTransactionId();
		$modbus_protocol_identifier = 0;
		$payload = $this->_CreateModbusTCPPacket($modbus_transaction_id, $modbus_protocol_identifier, $modbus_unit_identifier, $modbus_function_code, $data);
		//usleep($this->_waitusec);
		$result_raw = $this->_DoModbusPoll($payload);
		$result = $this->_ParseModbusResult($result_raw, $payload);
		if($this->_debug) echo "[ii] ------------- Ending transaction -------------------\n";
		return $result;
	}

	/** 
	 *
	 *
	 *
	 *
	 */
	private function _CreateModbusTCPPacket($transaction_id, $protocol_identifier, $unit_identifier, $modbus_function_code, $data){
		$remaining_bytes = strlen($data)/2 + 2;
		
		if(!in_array($modbus_function_code, $this->_ImplementedModbusFunctionCodes)){
			if($this->_debug) echo "[--] Modbus function code '{$modbus_function_code}' not implemented, aborting\n";
			throw new Exception("Modbus function code '{$modbus_function_code}' not implemented, aborting");
		}
		
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
	 */
	public function DoModbusFunction_01ReadCoilStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 1, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	/** 
	 *
	 */
	public function DoModbusFunction_02ReadInputStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 2, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	/** 
	 *
	 */
	public function DoModbusFunction_03ReadHoldingRegisters($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 3, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	/** 
	 *
	 */
	public function DoModbusFunction_05WriteSingleCoil($slave_address, $addr_hi, $addr_lo, $value_hi, $value_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 5, $addr_hi, $addr_lo, $value_hi, $value_lo);
	}
	
	/** 
	 *
	 */
	private function _DoModbusFunction_Basic($slave_address, $function, $addr_hi, $addr_lo, $points_hi, $points_lo){
		$payload = "{$addr_hi}{$addr_lo}{$points_hi}{$points_lo}";
		if(strlen($payload) != 8){
			throw new Exception("Malformed _DoModbusFunction_Basic() parm_number length, should be 8: length='".strlen($payload)."', data='{$payload}'");
		}
		return $this->DoModbusQuery($slave_address, $function, $payload);
	}
  
	/**
	 * Do the actual socket send/recieve
	 *
	 * @parm string $request the requestpackage, raw
	 * @return Raw returnpacket
	 */
	private function _DoModbusPoll($request){
		if($this->_debug) echo "[ii] Sending packet .... \n";
		
		if(!$this->_socket){
			throw new Exception( "[!!] _DoModbusPoll() failed.\nReason: Socket not connected");
		}
		
		$payload = $request;
		$length = strlen($payload);
		
		// Send data
		while (true) {
			$sent = socket_write($this->_socket, $payload, $length);
			if ($sent === false) {
				break;
			}
			if ($sent < $length) {
				$payload = substr($payload, $sent);
				$length -= $sent;
			}else{
				break;
			}
		}
		
		//Read data
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
			if($this->_debug) echo "[--] Invalid response in _DoModbusPoll() (sent: '0x{$out_transaction }', got: '0x{$in_transaction}')\n";
			throw new Exception( "Got invalid transaction id in _DoModbusPoll() (sent: '0x{$out_transaction }', got: '0x{$in_transaction}')\n");
		}
		
		return $result;
	}

	/**
	 * Get the next usable TransactionId
	 *
	 * @return string ascii hex transactionid
	 */
	private function _GetNextTransactionId(){
		$this->trid++;
		if($this->trid == 15) $this->trid++; // bug in smarthouse modbus/tcp wrapper, so we avoid 0x0f transaction id
		if($this->trid == 255) $this->trid = 0; // wrap
		$trid_to_send = self::Convert10to16($this->trid);
		if($this->_debug) echo "[++] Got next TransactionId: '0x{$trid_to_send}'\n";
		return $trid_to_send;
	}

	/**
	 * Convert result to Readable (not in use)
	 *
	 * @parm string $result
	 * @return string
	 */
	private function _GetHexvalueFromModbusResult($result){
		$raw = str_split(self::ConvertStrToHex($result),2);
		if($this->_debug) echo "[->] ".self::ConvertStrToHex($result)."\n";
		$hvalue = $raw[count($raw)-1];
		return $hvalue;
	}
	
	/**
	 * Parse and qualitycheck the result
	 *
	 * @parm string $result
	 * @parm string $request
	 * @return array
	 */
	private function _ParseModbusResult($result, $request){
		$p = self::ConvertStrToHex($result);
		$packet['header']['trid'] 						= substr($p,0,4);
		$packet['header']['protoid'] 					= substr($p,4,4);
		$packet['header']['remaining_bytes'] 	= substr($p,8,4);
		
		$packet['frame']['unit'] 							= substr($p,12,2);
		$packet['frame']['function_code'] 		= substr($p,14,2);
	
		if(!in_array($packet['frame']['function_code'], $this->_ImplementedModbusFunctionCodes)){
			if($this->_debug) echo "[--] Modbus function code '{$modbus_function_code}' not implemented, aborting\n";
			throw new Exception("Modbus function code '{$modbus_function_code}' not implemented, aborting");
		}

		
		// TODO: Rewrite this for more effective/common parsing needs
		switch(hexdec($packet['frame']['function_code'])){
			
			case 1:  // 01 Read Coil Status
				$packet['frame']['byte_count'] 	= substr($p, 16, 2);
				$to_parse 											= substr($p, 18);
				$register_size 									= 2;
			break;
			
			case 2:  // 02 Read Input Status
				$packet['frame']['byte_count'] 	= substr($p, 16, 2);
				$to_parse 											= substr($p, 18);
				$register_size 									= 2;
			break;
			
			case 3: // 03 Read Holding Registers
				$packet['frame']['byte_count'] 	= substr($p, 16, 2);
				$to_parse 											= substr($p, 18);
				$register_size 									= 4;
			break;
			
			case 5:  // 05 Write Single Coil
				$packet['frame']['byte_count'] 	= 0;
				$to_parse 											= substr($p, 16);
				$register_size 									= 2;
			break;
			
			default:
				// THIS SHOULD NOT BE POSSIBLE WITH THIS APPROACH :(
				throw new Exception("Cannot parse function_code '{$packet['frame']['function_code']}', NOT IMPLEMENTED!");
			break;
		}
		
		$packet['frame']['register'] = array();
		while(strlen($to_parse)>0){
			$packet['frame']['register'][] 	= substr($to_parse, 0, $register_size);
			$to_parse = substr($to_parse, $register_size);
		}

		
		
		



		$datapacket = @implode("",$packet['frame']['register']);
		
		if($this->_debug) echo "[++] Got Modbus Packet:\n";
		if($this->_debug) echo "   header:  TransactionIdentifier=[{$packet['header']['trid']}] ProtocolIdentifier=[{$packet['header']['protoid']}] RemainingBytes=[{$packet['header']['remaining_bytes']}]\n";
		if($this->_debug) echo "   frame :  ByteCount=[{$packet['frame']['byte_count']}] UnitIdentifier=[{$packet['frame']['unit']}] FunctionCode=[{$packet['frame']['function_code']}]\n";
		if($this->_debug) echo "            Data=[{$datapacket}]\n";
		//print_r($packet);
		return $packet;
	}

	/**
	 * Convert input to ascii hex
	 *
	 * @parm string $string
	 * @return string
	 */
	public static function ConvertStrToHex($string){
		$hex='';
		for ($i=0; $i < strlen($string); $i++){
			if(ord($string[$i]) < 15){
				$hex .= "0";
			}
			$hex .= dechex(ord($string[$i]));
		}
		return $hex;
	}
	
	/**
	 * Convert input to ascii hex
	 *
	 * @parm string $string
	 * @parm int $bytes
	 * @return string
	 */
	public static function Convert10to16($input,$bytes=2){
		return str_pad(dechex((int)$input), $bytes*2, "0", STR_PAD_LEFT);
	}

	/**
	 *
	 *
	 */	
	public static function GetBitFromHex($input){
		$parts = str_split($input);
		$ret = "";
		foreach($parts as $part){
			$ret .= str_pad(decbin(hexdec($part{0})),4,0,STR_PAD_LEFT);
		}
		return $ret;
	}

}

?>