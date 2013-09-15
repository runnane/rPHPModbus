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
	
        /**
         *
         * @var int Socket timeout, seconds
         */
	private $_sec;
        
        /**
         *
         * @var int Socket timeout, microseconds
         */
	private $_usec;
        
        
	//private $_waitusec = 0; // usecs to wait between payloads
	
	/** 
	 * Array with which function codes we have implemented
	 */	
	private $_ImplementedModbusFunctionCodes = array(1, 2, 3, 4, 5, 6, 16, 43);
	
	/** 
	 * Constructor
	 * 
	 * @param string $host Host/IP to host
	 * @param int $port TCP-port to host (502 usually)
	 */	
	public function __construct($host, $port=502){
		$this->_trid=0;
		$this->_host = $host; 
		$this->_port = $port;  
		
		if (!extension_loaded('sockets')) {
			if($this->_debug) echo "[--] rPHPModbus() cannot initialize, required sockets extension not loaded\n";
			throw new Exception( "rPHPModbus() cannot initialize, required sockets extension not loaded" );
		}
		$this->_sec = 1;
		$this->_usec = 500000;
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
         * @throws Exception
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
		$timeout = array( 'sec' => $this->_sec, 'usec' => $this->_usec );
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
		$result_raw = $this->_DoModbusPoll($payload);
		$result = $this->_ParseModbusResult($result_raw, $payload);
		if($this->_debug) echo "[ii] ------------- Ending transaction -------------------\n";
		return $result;
	}

	/**
         * 
         * @param type $transaction_id
         * @param type $protocol_identifier
         * @param type $unit_identifier
         * @param type $modbus_function_code
         * @param type $data
         * @return type
         * @throws Exception
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
		if($this->_debug) echo "   frame : UnitIdentifier=[{$unit_identifier}]\n";
                if($this->_debug) echo "           FunctionCode=[{$modbus_function_code}]\n";
		if($this->_debug) echo "           Data=[{$data}]\n";
		
		$header = "{$transaction_id}{$protocol_identifier}{$hexbytecount}{$unit_identifier}{$modbus_function_code}";
		
                /*
		if(strlen($header) != 16){
			if($this->_debug) echo "[--] Malformed Modbus TCP Frame Header length, should be 16: length='".strlen($header)."', data='{$header}'\n";
			throw new Exception("Malformed Modbus TCP Frame Header length, should be 16: length='".strlen($header)."', data='{$header}'");
		}
		*/
		$packet = pack("H*","{$header}{$data}");
		return $packet;
	}

	
	/**
         * Modbus function 1 (0x01) Read Coil Status
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $points_hi
         * @param type $points_lo
         * @return type
         */
	public function DoModbusFunction_01ReadCoilStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 1, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	/**
         * Modbus function 2 (0x02) Read Input Status
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $points_hi
         * @param type $points_lo
         * @return type
         */
	public function DoModbusFunction_02ReadInputStatus($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 2, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
	/**
         * Modbus function 3 (0x03) Read Holding Registers
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $points_hi
         * @param type $points_lo
         * @return type
         */
	public function DoModbusFunction_03ReadHoldingRegisters($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 3, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
	
        /**
         * Modbus function 4 (0x04) Read Input Registers
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $points_hi
         * @param type $points_lo
         * @return type
         */
        public function DoModbusFunction_04ReadInputRegisters($slave_address, $addr_hi, $addr_lo, $points_hi, $points_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 4, $addr_hi, $addr_lo, $points_hi, $points_lo);
	}
        
	/**
         * Modbus function 5 (0x05) Write Single Coil
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $value_hi
         * @param type $value_lo
         * @return type
         */
	public function DoModbusFunction_05WriteSingleCoil($slave_address, $addr_hi, $addr_lo, $value_hi, $value_lo){
		return $this->_DoModbusFunction_Basic($slave_address, 5, $addr_hi, $addr_lo, $value_hi, $value_lo);
	}

        /**
         * Modbus function 6 (0x06) Write Single Register
         * 
         * @param type $slave_address
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $value_hi
         * @param type $value_lo
         * @return type
         */
        public function DoModbusFunction_06WriteSingleRegister($slave_address, $addr_hi, $addr_lo, $value_hi, $value_lo){
            return $this->_DoModbusFunction_Basic($slave_address, 6, $addr_hi, $addr_lo, $value_hi, $value_lo);
	}

        /**
         * Modbus function 15 (0x0f) Write Multiple Coils (NOT IMPLEMENTED)
         * 
         * @throws Exception
         */
        public function DoModbusFunction_15WriteMultipleCoils(){
            throw new Exception("NOT IMPLEMENTED");
        }
        
	/**
         * Modbus function 16 (0x10) Write Multiple Registers
         * 
         * @param type $slave_address
         * @param type $starting_address
         * @param type $quantity_of_registers
         * @param type $register_values
         * @return type
         * @throws Exception
         */
	public function DoModbusFunction_16WriteMultipleRegisters($slave_address, $starting_address, $quantity_of_registers, $register_values){
		$quantity = self::Convert10to16($quantity_of_registers,2);
		$byte_count = self::Convert10to16($quantity_of_registers*2,1);
		
		$subheader = "{$starting_address}{$quantity}{$byte_count}";
		$payload = "{$subheader}{$register_values}";
		
		if(strlen($subheader) != 10){
			throw new Exception("Malformed DoModbusFunction_16WriteMultipleRegisters() subheader length, should be 10: length='".strlen($subheader)."', data='{$subheader}'");
		}
		if($this->_debug) echo "[++] slave_address='{$slave_address}', quantity='{$quantity}', byte_count='{$byte_count}', register_values='{$register_values}' \n";

		return $this->DoModbusQuery($slave_address, 16, $payload);
	} 
        
        /**
         * Modbus function 22 (0x16) Mask Write Register (NOT IMPLEMENTED)
         * 
         * @throws Exception
         */
        public function DoModbusFunction_22MaskWriteRegister(){
             throw new Exception("NOT IMPLEMENTED");
	}
        
        /**
         * Modbus function 23 (0x17) Read/Write Multiple Registers (NOT IMPLEMENTED)
         * 
         * @throws Exception
         */
        public function DoModbusFunction_23ReadWRiteMultipleRegisters(){
             throw new Exception("NOT IMPLEMENTED");
	}
        
        /**
         * 
         * @param type $device_id_code
         * @param type $object_id
         * @return type
         * @throws Exception
         */
        public function DoModbusFunction_43ReadDeviceIdentification($slave_address, $device_id_code, $object_id){
  		$mei_type = "0e";
 		$dev_id = self::Convert10to16($device_id_code,1);
		$obj_id = self::Convert10to16($object_id,1);
		
		$payload = "{$mei_type}{$dev_id}{$obj_id}";
		
		if(strlen($payload) != 6){
			throw new Exception("Malformed DoModbusFunction_43ReadDeviceIdentification() payload length, should be 6: length='".strlen($payload)."', data='{$payload}'");
		}
                
		if($this->_debug) echo "[++] mei_type='{$mei_type}', dev_id='{$dev_id}', obj_id='{$obj_id}' \n";

		return $this->DoModbusQuery($slave_address, 43, $payload);
	}
       
        /**
         * Base method for modbus functions 1-6 that uses 2x2 byte data sets (addr hi/lo + content hi/lo)
         * 
         * @param type $slave_address
         * @param type $function
         * @param type $addr_hi
         * @param type $addr_lo
         * @param type $points_hi
         * @param type $points_lo
         * @return type
         * @throws Exception
         */
	private function _DoModbusFunction_Basic($slave_address, $function, $addr_hi, $addr_lo, $points_hi, $points_lo){
		$payload = "{$addr_hi}{$addr_lo}{$points_hi}{$points_lo}";
		if(strlen($payload) != 8){
			throw new Exception("Malformed _DoModbusFunction_Basic() payload length, should be 8: length='".strlen($payload)."', data='{$payload}'");
		}
		return $this->DoModbusQuery($slave_address, $function, $payload);
        }
 
        /**
         * Do the actual socket send/recieve
         * 
         * @param type $request the requestpackage, raw
         * @return type
         * @throws Exception
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
	 * Parse and qualitycheck the result
	 *
	 * @parm string $result
	 * @parm string $request
	 * @return array
	 */
	private function _ParseModbusResult($result, $request){
		$p = self::ConvertStrToHex($result);
		
		$header = substr($p,0,12);
		$frame = substr($p,12);
		$packet = array();
                
		if($this->_debug) echo "[ii] Got packet: header=[{$header}] frame=[{$frame}]\n";
		$packet['header']['trid'] 		= substr($header,0,4);
		$packet['header']['protoid'] 		= substr($header,4,4);
		$packet['header']['remaining_bytes'] 	= substr($header,8,4);
		
		$packet['frame']['unit'] 		= substr($frame,0,2);
		$packet['frame']['function_code'] 	= substr($frame,2,2);
		
		$modbus_function_code = hexdec($packet['frame']['function_code']);
	
		if(!in_array($modbus_function_code, $this->_ImplementedModbusFunctionCodes)){
			if($this->_debug) echo "[--] Modbus function code '{$modbus_function_code}' not implemented, aborting\n";
			throw new Exception("Modbus function code '{$modbus_function_code}' not implemented, aborting");
		}
		
		// TODO: Rewrite this for more effective/common parsing needs
		switch($modbus_function_code){
			
			case 1:  // 01 Read Coil Status
				$packet['frame']['byte_count'] 	= substr($frame, 4, 2);
				$to_parse 			= substr($frame, 6);
				$register_size 			= 2;
			break;
			
			case 2:  // 02 Read Input Status
				$packet['frame']['byte_count'] 	= substr($frame, 4, 2);
				$to_parse 			= substr($frame, 6);
				$register_size 			= 2;
			break;
			
			case 3: // 03 Read Holding Registers
				$packet['frame']['byte_count'] 	= substr($frame, 4, 2);
				$to_parse 			= substr($frame, 6);
				$register_size 			= 4;
			break;
                    
			case 4: // 04 Read Input Registers
				$packet['frame']['byte_count'] 	= substr($frame, 4, 2);
				$to_parse 			= substr($frame, 6);
				$register_size 			= 4;
			break;
                    
			case 5:  // 05 Write Single Coil
				$packet['frame']['byte_count'] 	= 0;
				$to_parse 			= substr($frame, 4);
				$register_size 			= 2;
			break;
                    
                        case 6:  // 06 Write Single Register
				$packet['frame']['byte_count'] 	= 0;
				$to_parse 			= substr($frame, 4);
				$register_size 			= 2;
			break;

			case 16:  // 16 Write Multiple Registers
				$packet['frame']['byte_count'] 	= 0;
				$to_parse 			= substr($frame, 4);
				$register_size 			= 4;
			break;
                        case 43:  // 43 Read Device Identification
				$packet['frame']['byte_count'] 	= 0;
				$to_parse 			= substr($frame, 2);
				$register_size 			= 2;
			break;
			
			default:
                                // Functions for exceptions (0x81+) will get here until exception handling is implemented.
				throw new Exception("Cannot parse function_code '{$modbus_function_code}', NOT IMPLEMENTED!");
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
         * @param type $input
         * @return type
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