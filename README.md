rPHPModbus - PHP classes for communicating with Modbus TCP controllers
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


-----------------


1. About:

 rPHPModbus is a set of PHP classes to communicate with modbus bus nodes. For now, function codes 1, 2 and 3 are implemented.
 In addition, rPHPDupline, a PHP class for communicating with the Carlo Gavazzi Analink/Dupline(r) Smart-House solution.

2. Howto:	

 See examples.php for now.

3. Todo:

 * Modbus Exception Handling
 * Implementing Modbus function codes 4, 5, 6, 15, 16
 * Better request/response packaging (make objects?) rModbusRequest/rModbusResponse ?
 * phpDoc

4. References/ More reading:

 * http://en.wikipedia.org/wiki/Modbus
 * http://modbus.org/docs/Modbus_Application_Protocol_V1_1b3.pdf
 * http://modbus.org/docs/Modbus_Messaging_Implementation_Guide_V1_0b.pdf