rPHPModbus
=========
by Jon Tungland // @runnane // www.runnane.no

Disclaimer
-----------

I am not responsible for any problems, crashes, failures or pain this piece of software will cause. Use it on your own risk.

About
-----------
rPHPModbus is a set of PHP classes to communicate with modbus masters. For now, function codes 1-6 and 16 are implemented.
In addition, rPHPDupline, a PHP class for communicating with the Carlo Gavazzi Dupline(r) Smart-House solution is attached.
Tested on debian6 amd64 against Carlo Gavazzi Smart-House WinCE Based Controller BH8-CTRLX-230 running fw 3.02.04.

Installation
-----------
Sync it with ``` https://github.com/runnane/rPHPModbus.git rPHPModbus ```
See examples.php for how to use it.


Todo/Known bugs:
-----------
* Modbus Exception Handling
* Implementing Modbus function code 15, 22, 23
* Better request/response packaging (make objects?) rModbusRequest/rModbusResponse ?
* phpDoc
* Check compability with other archs/os-es

References Modbus:
-----------
* http://en.wikipedia.org/wiki/Modbus
* http://modbus.org/docs/Modbus_Application_Protocol_V1_1b3.pdf
* http://modbus.org/docs/Modbus_Messaging_Implementation_Guide_V1_0b.pdf

References Dupline:
-----------
* http://www.smartbuilding.no/assets/po_sh_ver-1-33_0112.pdf (Norwegian)
* http://www.smart-house.it/download/smart-house-datasheet.zip
* http://www.smart-house.it/download/Manual%20Hlp_ENG.pdf
