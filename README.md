rPHPModbus
=========
by Jon Tungland // @runnane // www.runnane.no

Disclaimer
-----------

I am not responsible for any problems, crashes, failures or pain this piece of software will cause. Use it on your own risk.

About
-----------
rPHPModbus is a set of PHP classes to communicate with modbus bus nodes. For now, function codes 1, 2, 3, 5 and 16 are implemented.
In addition, rPHPDupline, a PHP class for communicating with the Carlo Gavazzi Analink/Dupline(r) Smart-House solution is attached.
Tested on debian6 amd64 against Carlo Gavazzi Smart-House WinCE Based Controller BH8-CTRLX-230 running fw 3.02.04.
 
Installation
-----------
Sync it with ``` git clone https://bitbucket.org/runnane/rphpmodbus.git rPHPModbus ```
See examples.php for how to use it.


Todo/Known bugs:
-----------
* Modbus Exception Handling
* Implementing Modbus function codes 4, 6, 15
* Better request/response packaging (make objects?) rModbusRequest/rModbusResponse ?
* phpDoc
* Check compability with other archs/os-es

References/more reading:
-----------
* http://en.wikipedia.org/wiki/Modbus
* http://modbus.org/docs/Modbus_Application_Protocol_V1_1b3.pdf
* http://modbus.org/docs/Modbus_Messaging_Implementation_Guide_V1_0b.pdf
