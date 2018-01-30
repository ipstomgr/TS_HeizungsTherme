#!/bin/sh
# Input-Ports (Taster)
for Port in  20 21
  do
  echo "$Port" > /sys/class/gpio/export
  echo "in" >/sys/class/gpio/gpio${Port}/direction
  chmod 660 /sys/class/gpio/gpio${Port}/direction
  chmod 660 /sys/class/gpio/gpio${Port}/value
done

# Output-Ports (Relais)
for Port in 27 22 23 24 25
  do
  echo "$Port" > /sys/class/gpio/export
  echo "out" >/sys/class/gpio/gpio${Port}/direction
  echo "1" >/sys/class/gpio/gpio${Port}/value
  chmod 660 /sys/class/gpio/gpio${Port}/direction
  chmod 660 /sys/class/gpio/gpio${Port}/value
done


#PWM für Spannungssteuerung Therme
echo "0" > /sys/class/pwm/pwmchip0/export
echo "1000000" > /sys/class/pwm/pwmchip0/pwm0/period
echo "657000" > /sys/class/pwm/pwmchip0/pwm0/duty_cycle
echo "inversed" > /sys/class/pwm/pwmchip0/pwm0/polarity
echo "1" > /sys/class/pwm/pwmchip0/pwm0/enable
