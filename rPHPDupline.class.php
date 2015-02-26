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

require_once ("rPHPModbus.class.php");

class rPHPDupline extends rPHPModbus {

    /**
     * Create new object
     * 
     * @param type $host
     * @param type $port
     */
    public function __construct($host, $port = 502) {
        parent::__construct($host, $port);
    }

    /****************************************************************/
    /****** Do actions by Dupline functions                **********/
    /****************************************************************/

    /**
     *  Get output status of a Dupline function, wrapper for 01 Read Coil Status modbus function 
     * 
     * @param type $function_id
     * @param type $param_number
     * @param type $param_index
     * @return type
     */
    public function DuplineByFunction_ReadOutputStatus($function_id, $param_number,
        $param_index) {
        $param = "{$param_number}{$param_index}";
        $function_id = self::Convert10to16($function_id, 2);

        $addr_hi = "ff";
        $addr_lo = substr($function_id, 0, 2);
        $points_hi = substr($function_id, 2, 2);
        $points_lo = $param;

        $result = $this->DoModbusFunction_01ReadCoilStatus(1, $addr_hi, $addr_lo, $points_hi,
            $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data;
    }

    /**
     * Get value of a Dupline function, wrapper for 02 Read Input Status modbus function 
     * 
     * @param type $function_id
     * @param type $param_number
     * @param type $param_index
     * @return type
     */
    public function DuplineByFunction_ReadValue($function_id, $param_number, $param_index) {
        $param = "{$param_number}{$param_index}";
        $function_id = self::Convert10to16($function_id, 2);

        $addr_hi = "ff";
        $addr_lo = substr($function_id, 0, 2);
        $points_hi = substr($function_id, 2, 2);
        $points_lo = $param;

        $result = $this->DoModbusFunction_02ReadInputStatus(1, $addr_hi, $addr_lo, $points_hi,
            $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data;
    }

    /**
     * Get value of a Dupline function, wrapper for 03 Read Holding Registers modbus function 
     * 
     * @param type $function_id
     * @param type $param_number
     * @param type $param_index
     * @return type
     */
    public function DuplineByFunction_ReadMultipleRegisters($function_id, $param_number,
        $param_index) {
        $param = "{$param_number}{$param_index}";
        $function_id = self::Convert10to16($function_id, 2);

        $addr_hi = "ff";
        $addr_lo = substr($function_id, 0, 2);
        $points_hi = substr($function_id, 2, 2);
        $points_lo = $param;

        $result = $this->DoModbusFunction_03ReadHoldingRegisters(1, $addr_hi, $addr_lo,
            $points_hi, $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data;
    }

    /**
     * Set value of a Dupline function, wrapper for 16 Write Multiple Registers modbus function 
     * 
     * @param type $function_id
     * @param type $number_of_registers
     * @param type $param_number
     * @param type $param_index
     * @param type $register_value
     * @return type
     */
    public function DuplineByFunction_PresetMultipleRegisters($function_id, $param_number,
        $param_index, $register_value) {
        $function_id = self::Convert10to16($function_id, 2);
        $values[] = $function_id;
        $data_bytes = strlen($register_value) / 2;

        // byte 1+2
        $register_address = "FF00";

        // byte 3
        $param_number = str_pad(dechex($param_number), 2, "0", STR_PAD_LEFT);
        $values[] = $param_number;

        // byte 4
        if ($param_index !== null) {
            $param_index = str_pad(dechex($param_index), 2, "0", STR_PAD_LEFT);
            $values[] = $param_index;

        } else {
            $data_bytes += 1; // hack, bad implemented modbus :(
        }

        $packet = implode("", $values) . $register_value;
        //echo "[Packet] register_address=[{$register_address}] function_id=[{$function_id}] param_number=[{$param_number}] param_index=[{$param_index}] register_value=[{$register_value}]\n";
        //echo "         data_bytes=[{$data_bytes}] packet=[{$packet}]\n";
        return $this->DoModbusFunction_16WriteMultipleRegisters(1, $register_address, $data_bytes,
            $packet);
    }

    /****************************************************************/
    /****** Do actions by Dupline channels                 **********/
    /****************************************************************/

    /**
     * Set bitvalue of a Dupline channel, wrapper for 05 Write Single Coil
     * 
     * @param type $dupline_channel_address
     * @param type $data
     * @return type
     */
    public function DuplineByChannel_SetSingleOutputBit($dupline_channel_address, $boolean) {
        $dupline_start_addr = 5376; // From "Smart-House  Modbus Protocol.pdf", section 5.4

        $register_address = self::Convert10to16($dupline_start_addr + $this->
            GetRegisterAddressOffsetByDuplineAddress($dupline_channel_address), 2);

        $rdata = self::Convert10to16($boolean ? 1 : 0);

        $addr_hi = substr($register_address, 0, 2);
        $addr_lo = substr($register_address, 2, 2);
        $points_hi = substr($rdata, 0, 2);
        $points_lo = substr($rdata, 2, 2);

        $result = $this->DoModbusFunction_05WriteSingleCoil(1, $addr_hi, $addr_lo, $points_hi,
            $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data;
    }

    /**
     * Set bitvalue of a Dupline channel, wrapper for 01 Read Coil Status
     * 
     * @param type $dupline_channel_address
     * @return type
     */
    public function DuplineByChannel_GetOutputStatus($dupline_channel_address) {
        $dupline_start_addr = 1280; // From "Smart-House  Modbus Protocol.pdf", section 5.1
        $register_address = self::Convert10to16($dupline_start_addr + $this->
            GetRegisterAddressOffsetByDuplineAddress($dupline_channel_address), 2);

        $addr_hi = substr($register_address, 0, 2);
        $addr_lo = substr($register_address, 2, 2);

        $points_hi = self::Convert10to16(0, 1);
        $points_lo = self::Convert10to16(1, 1);

        $result = $this->DoModbusFunction_01ReadCoilStatus(1, $addr_hi, $addr_lo, $points_hi,
            $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data == "01";
    }

    /**
     * Get bitvalue of a Dupline channel, wrapper for 02 Read Input Channel
     * 
     * @param type $dupline_channel_address
     * @return type
     */
    public function DuplineByChannel_GetInputStatus($dupline_channel_address) {
        $dupline_start_addr = 1536; // From "Smart-House  Modbus Protocol.pdf", section 5.1
        $register_address = self::Convert10to16($dupline_start_addr + $this->
            GetRegisterAddressOffsetByDuplineAddress($dupline_channel_address), 2);

        $addr_hi = substr($register_address, 0, 2);
        $addr_lo = substr($register_address, 2, 2);

        $points_hi = self::Convert10to16(0, 1);
        $points_lo = self::Convert10to16(1, 1);

        $result = $this->DoModbusFunction_02ReadInputStatus(1, $addr_hi, $addr_lo, $points_hi,
            $points_lo);
        $data = implode("", $result['frame']['register']);
        return $data == "01";
    }

    /**
     * 
     * @param type $dupline_channel_address
     * @return type
     */
    public function DuplineByChannel_GetAnalinkValue($dupline_channel_address) {
        $dupline_start_addr = 256; // From "Smart-House  Modbus Protocol.pdf", section 5.1
        $register_address = self::Convert10to16($dupline_start_addr + $this->
            GetRegisterAddressOffsetByDuplineAddress($dupline_channel_address), 2);

        $addr_hi = substr($register_address, 0, 2);
        $addr_lo = substr($register_address, 2, 2);


        $packet = $this->DoModbusFunction_03ReadHoldingRegisters(1, $addr_hi, $addr_lo,
            "00", "01");
        $data = implode("", $packet['frame']['register']);
        return $data;
    }

    /****************************************************************/
    /****** Specific functions for Dupline units/functions **********/
    /****************************************************************/

    /**
     * Get Dupline (Analink) temperature by function_id
     * Tested with BSI-TEMANA
     * 
     * @param int $function_id Decimal function number of the Temperature function
     * @return float The current temperature for the BSI-TEMANA
     */
    public function GetTemperatureByFunctionId_BSITEMANA($function_id) {
        if (!$function_id) {
            throw new Exception("Missing functionId");
        }

        $result = $this->DuplineByFunction_ReadValue($function_id, 0, 0);
        $dvalue = hexdec($result);

        if ($dvalue >= 2)
            $dvalue -= 2.0;
        else
            if ($dvalue == 1)
                $dvalue -= 1.0;
        return number_format(($dvalue * 0.3524) - 29.997, 1);
    }

    /**
     * Get Dupline (Analink) temperature by function_id
     * Tested with BEW-TEMDIS (ELKO Temperature Controller)
     * 
     * @param int $function_id Decimal function number of the Temperature function
     * @return float The current temperature for the BEW-TEMDIS
     */
    public function GetTemperatureByFunctionId_BEWTEMDIS($function_id) {
        if (!$function_id) {
            throw new Exception("Missing functionId");
        }

        $result = $this->DuplineByFunction_ReadValue($function_id, 0, 0);
        $dvalue = hexdec($result);

        if ($dvalue >= 1)
            $dvalue -= 1.0;
        if ($dvalue == 0)
            return 0.0;
        return number_format(($dvalue * 50) / 255, 1);
    }

    /**
     * Get Dupline (Analink) temperature by function_id
     * Tested with BEW-TEMDIS (ELKO Temperature Controller)
     * 
     * @param int $function_id Decimal function number of the Temperature function
     * @return float The current temperature for the BEW-TEMDIS
     */
    public function GetTermostatByFunctionId_BEWTEMDIS($function_id, $energysaving = false) {
        if (!$function_id) {
            throw new Exception("Missing functionId");
        }
        $es = $energysaving ? 1 : 0;
        $result = $this->DuplineByFunction_ReadMultipleRegisters($function_id, $es, 0);
        $value = hexdec($result) / 10.0;
        return $value;
    }

    /**
     * 
     * @param type $function_id
     * @param type $temperature
     * @param type $energysaving
     * @return boolean
     */
    public function SetHeatingPointByFunctionId_BEWTEMDIS($function_id, $temperature,
        $energysaving = 0) {
        $temperature = str_pad(dechex(intval($temperature * 10)), 8, "0", STR_PAD_LEFT);
        $es = $energysaving ? "01" : "00";
        $this->DuplineByFunction_PresetMultipleRegisters($function_id, $es, "00", $temperature);
        return true;
    }

    /**
     * 
     * @param type $function_id
     * @return type
     * @throws Exception
     */
    public function GetBitValueByFunctionId($function_id) {
        if (!$function_id) {
            throw new Exception("Missing functionId");
        }
        $hexresult = $this->DuplineByFunction_ReadOutputStatus($function_id, 0, 0);
        $value = hexdec($hexresult);
        return $value;
    }

    /**
     * 
     * @return type
     */
    public function ReadFullDuplineOutputStatusTable() {
        $packet = $this->DoModbusFunction_03ReadHoldingRegisters(1, "00", "00", "00",
            "08");
        return $this->_ParseFullDuplineTable($packet);
    }

    /**
     * 
     * @return type
     */
    public function ReadFullDuplineInputStatusTable() {
        $packet = $this->DoModbusFunction_03ReadHoldingRegisters(1, "00", "10", "00",
            "08");
        return $this->_ParseFullDuplineTable($packet);
    }

    /**
     * 
     * @param type $dupline_address
     * @param type $msecdelay
     * @return boolean
     * @throws Exception
     */
    public function ToggleDuplineOutputChannel($dupline_address, $msecdelay = 500) {
        if (!$dupline_address) {
            throw new Exception("Missing dupline address");
        }
        $this->DuplineByChannel_SetSingleOutputBit($dupline_address, true);
        usleep($msecdelay * 1000); // wait $msecdelay msec
        $this->DuplineByChannel_SetSingleOutputBit($dupline_address, false);
        return true;
    }

    /**
     * 
     * @param type $function_id
     * @param type $msecdelay
     * @return boolean
     * @throws Exception
     */
    public function ToggleDuplineFunctionOutput($function_id, $msecdelay = 500) {
        if (!$function_id) {
            throw new Exception("Missing functionId");
        }

        $this->DuplineByFunction_PresetMultipleRegisters($function_id, "00", null, "01");
        usleep($msecdelay * 1000); // wait $msecdelay msec
        $this->DuplineByFunction_PresetMultipleRegisters($function_id, "00", null, "00");
        return true;
    }

    /**
     * 
     * @param type $packet
     * @return type
     */
    private function _ParseFullDuplineTable($packet) {
        $binstr = self::GetBitFromHex(implode("", $packet['frame']['register']));
        $i = 0;
        for ($grp = 65; $grp <= 80; $grp += 2) {
            for ($chan = 8; $chan > 0; $chan--) {
                $output[chr($grp + 1) . $chan] = $binstr{$i};
                $i++;
            }
            for ($chan = 8; $chan > 0; $chan--) {
                $output[chr($grp) . $chan] = $binstr{$i};
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
    public function GetRegisterAddressOffsetByDuplineAddress($dupline_address) {
        return (ord(strtoupper($dupline_address{0})) - 65) * 8 + (((int)$dupline_address{
            1}) - 1);
    }

}

?>
