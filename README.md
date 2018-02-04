NUR für Symcon auf Rasberry !!!!!

IPS-Heizungsteuerung per Raspberry Pi2 oder 3 für Vaillant Thermen über 
die Schnittstelle 7-8-9 per PWM.

Dallas-Bus für DS18x20 wird direkt am GPIO betrieben.
Dazu muss auf dem Pi die GPIO's eingerichtet werden, für 1wire und PWM Pin.
An den PWM Pin muss dann die "Eigenbauplatine! angeschlossen werden, die die Kesseltempeartur an 7-8-9 erzeugt.
Bitte in den docs Ordner schauen, wegen der Hardware Einrichtung. Dort sind alle Beispiele vorhanden. 


Dallas Bus an GPIO 5 und 6 (Pin 29 und 31)
PWM an GPIO 12 (Pin32)
GPIO's für in (GPIO 20 und 21) / out (GPIO  27 22 23 24 25)

Die ersten 12 Pin's vom GPIO halte ich frei, für das Homematic Modul als CCU Ersatz.

ACHTUNG, dieses Modul ist nur auf dem Raspberry so lauffähig, da direkt auf das Pi-Filesystem zugegriffen wird.


