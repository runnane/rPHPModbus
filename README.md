rPHPModbus
=========
by Jon Tungland // @runnane // www.runnane.no

Disclaimer
-----------

I am not responsible for any problems, crashes, failures or pain this piece of software will cause. Use it on your own risk.

About
-----------
 rPHPModbus is a set of PHP classes to communicate with modbus bus nodes. For now, function codes 1, 2 and 3 are implemented.
 In addition, rPHPDupline, a PHP class for communicating with the Carlo Gavazzi Analink/Dupline(r) Smart-House solution.
 
Installation
-----------
Sync it with ``` git clone https://bitbucket.org/runnane/rphpmodbus.git rPHPModbus ```
See examples.php for how to use it.


Todo/Known bugs:
-----------
* Modbus Exception Handling
* Implementing Modbus function codes 4, 5, 6, 15, 16
* Better request/response packaging (make objects?) rModbusRequest/rModbusResponse ?
* phpDoc

References/more reading:
-----------
* http://en.wikipedia.org/wiki/Modbus
* http://modbus.org/docs/Modbus_Application_Protocol_V1_1b3.pdf
* http://modbus.org/docs/Modbus_Messaging_Implementation_Guide_V1_0b.pdf
